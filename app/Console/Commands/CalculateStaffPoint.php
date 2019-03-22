<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Console\Command;
use App\Models\ArtisanCommandLog;
use Illuminate\Support\Facades\Cache;
use App\Models\PointLog as PointLogModel;
use App\Models\PointType as PointTypeModel;
use App\Models\PersonalPointStatistic as StatisticModel;
use App\Models\PersonalPointStatisticLog as StatisticLogModel;

class CalculateStaffPoint extends Command
{
    // 日结数据
    protected $daily;

    // 月结数据
    protected $monthly;

    // 当前时间
    protected $curtime;

    // 上次结算时间
    protected $pretime;

    // 上次结算日志
    protected $prelog;

    // 初始化分类统计
    protected $initType;

    protected $signature = 'pms:calculate-staff-point';
    protected $description = '统计员工积分信息';

    public function __construct()
    {
        parent::__construct();

        $this->prelog = $this->preCommandLog();
        $this->pretime = $this->prelog->created_at ?? null;
        $this->curtime = now()->toDateTimeString();
        $this->initType = $this->makePointType();
    }

    public function handle()
    {
        // 查询新的积分记录进行结算
        $logs = PointLogModel::when($this->prelog, function ($query) {
                $query->whereBetween('created_at', [$this->pretime, $this->curtime]);
            })
            ->select('point_a', 'point_b', 'staff_sn', 'type_id', 'changed_at')
            ->where('is_revoke', 0)
            ->get();

        // 日结数据处理逻辑
        if (!empty($this->prelog)) $this->dataFormat($logs);

        $logs->map(function ($item) {
            // 初始化日结数据
            if (empty($this->daily[$item->staff_sn])) $this->initDailyData($item->staff_sn);

            if (empty($item->changed_at)) {
                // 一次性积分只算到累计分
            } elseif (!Carbon::parse($item->changed_at)->isCurrentMonth()) {
                // 历史月 月累计分处理
                $this->handleHistoryData($item);
            } else {
                // 当月 月累计分处理
                $this->handleCurrentData($item);
            }
            // 当月/历史月 总累计分处理
            $this->totalStatisticData($item);
        });
        $logModel = $this->createLog();
        try {
            \DB::beginTransaction();

            if (!empty($this->daily)) array_walk($this->daily, [$this, 'updateDaily']);
            if (!empty($this->monthly)) array_walk($this->monthly, [$this, 'updateMonthly']);
            $logModel->save();

            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollBack();
            $logModel->status = 2;
            $logModel->save();
            \Log::error('pms:calculate-staff-point'.$e->getMessage());
        }
    }

    /**
     * 所有日结数据计算跨月.
     * 
     * @param  string $value
     * @return void
     */
    protected function dataFormat($logs)
    {
        $pretime = Carbon::parse($this->pretime)->startOfMonth();
        $isCurMonth = $pretime->isCurrentMonth();

        // 获取所有日结数据
        StatisticModel::get()->map(function ($item) use ($isCurMonth, $pretime, $logs) {
            $staffSn = $item->staff_sn;

            // 跨月结算（日结数据转月结）
            if ($isCurMonth === false) {
                $key = sprintf('%s|%s', $staffSn, $pretime);
                $this->monthly[$key] = $item->toArray();
                $this->monthly[$key]['date'] = $pretime->toDateTimeString();

                // 清空日结中的当月累计分数据
                $item->point_a = 0;
                $item->point_b_monthly = 0;
                $item->source_a_monthly = $this->initType;
                $item->source_b_monthly = $this->initType;
                $item->calculated_at = $this->curtime;

                // 跨月更新所有人的日结记录
                $this->daily[$staffSn] = $item->toArray();
            }

            // 没跨月只更新有积分变更记录的日结记录
            if ($isCurMonth && $logs->where('staff_sn', $staffSn)->isNotEmpty()) {
                $this->daily[$staffSn] = $item->toArray();
            }
        });
    }


    // 初始化新的日结记录
    protected function initDailyData($staffSn)
    {
        $this->daily[$staffSn] = [
            'point_a' => 0,
            'point_a_total' => 0,
            'point_b_monthly' => 0,
            'point_b_total' => 0,
            'staff_sn' => $staffSn,
            'source_a_monthly' => $this->initType,
            'source_b_monthly' => $this->initType,
            'source_a_total' => $this->initType,
            'source_b_total' => $this->initType,
            'calculated_at' => $this->curtime,
        ];
    }

    // 历史月 月累计分处理
    protected function handleHistoryData($log)
    {
        $startAt = Carbon::parse($log->changed_at)->startOfMonth();
        $monthly = StatisticLogModel::query()
            ->where('date', $startAt)
            ->where('staff_sn', $log->staff_sn)
            ->first();

        if (empty($monthly)) {
            // 新添加到积分系统的员工更新历史月结记录时：
            // 创建历史月份到上次结算月份的月结记录
            $this->initBetweenMonth($log);
        } else {
            // 创建历史月份更新数据结构
            $this->createMonthData($startAt, $log->staff_sn);
        }

        //changed_at非空时加分
        if (!empty($log->changed_at)) {
            $key = sprintf('%s|%s', $log->staff_sn, $startAt);

            $this->monthly[$key]['point_a'] += $log->point_a;
            $this->monthly[$key]['source_a_monthly'] = $this->monthlySource($this->monthly[$key], $log, 'source_a_monthly');

            $this->monthly[$key]['point_b_monthly'] += $log->point_b;
            $this->monthly[$key]['source_b_monthly'] = $this->monthlySource($this->monthly[$key], $log, 'source_b_monthly');
        }
    }

    // 创建历史月份到上次结算月份的月结记录
    public function initBetweenMonth(PointLogModel $log)
    {
        $startAt = Carbon::parse($log->changed_at)->startOfMonth();
        $endAt = Carbon::parse($this->pretime)->startOfMonth();

        for ($startAt; $startAt->timestamp < $endAt->timestamp; $startAt->addMonth()) {
            $this->createMonthData($startAt, $log->staff_sn);
        }
    }

    // 创建历史月份更新数据结构
    protected function createMonthData(Carbon $date, $staffSn)
    {
        $key = sprintf('%s|%s', $staffSn, $date);
        $this->monthly[$key] = [
            'date' => $date->toDateTimeString(),
            'point_a' => 0,
            'staff_sn' => $staffSn,
            'point_a_total' => 0,
            'point_b_total' => 0,
            'point_b_monthly' => 0,
            'source_a_monthly' => $this->initType,
            'source_b_monthly' => $this->initType,
            'source_a_total' => $this->initType,
            'source_b_total' => $this->initType,
        ];
    }

    // 当月 月累计分处理
    public function handleCurrentData($log)
    {
        $staffSn = $log->staff_sn;
        $this->daily[$staffSn]['point_a'] += $log->point_a;
        $this->daily[$staffSn]['source_a_monthly'] = $this->monthlySource($this->daily[$staffSn], $log, 'source_a_monthly');

        $this->daily[$staffSn]['point_b_monthly'] += $log->point_b;
        $this->daily[$staffSn]['source_b_monthly'] = $this->monthlySource($this->daily[$staffSn], $log, 'source_b_monthly');
    }

    // 当月/历史月 总累计分处理
    public function totalStatisticData($log)
    {
        $changedAt = $log->changed_at ?: '2018-07-01 00:00:00';
        $startAt = Carbon::parse($changedAt)->startOfMonth();
        for ($startAt; $startAt->timestamp < now()->startOfMonth()->timestamp; $startAt->addMonth()) {
            $key = sprintf('%s|%s', $log->staff_sn, $startAt);
            if (!empty($this->monthly[$key])) {
                $this->monthly[$key]['point_a_total'] += $log->point_a;
                $this->monthly[$key]['source_a_total'] = $this->monthlySource($this->monthly[$key], $log, 'source_a_total');
                $this->monthly[$key]['point_b_total'] += $log->point_b;
                $this->monthly[$key]['source_b_total'] = $this->monthlySource($this->monthly[$key], $log, 'source_b_total');
            }
        }
        $this->daily[$log->staff_sn]['point_a_total'] += $log->point_a;
        $this->daily[$log->staff_sn]['source_a_total'] = $this->monthlySource($this->daily[$log->staff_sn], $log, 'source_a_total');
        $this->daily[$log->staff_sn]['point_b_total'] += $log->point_b;
        $this->daily[$log->staff_sn]['source_b_total'] = $this->monthlySource($this->daily[$log->staff_sn], $log, 'source_b_total');
    }

    /**
     * 初始化统计分类数据.
     *
     * @author 28youth
     * @return array
     */
    public function makePointType(): array
    {
        $minutes = now()->addDay();
        $source = Cache::remember('default_point_type', $minutes, function() {
            return PointTypeModel::get()->map(function ($item) {
                $item->add_point = 0;
                $item->sub_point = 0;
                $item->point = 0;

                return $item;
            })->toArray();
        });

        return $source;
    }

    /**
     * 上次结算节点信息.
     *
     * @author 28youth
     * @return \App\Models\ArtisanCommandLog|null
     */
    protected function preCommandLog()
    {
        return ArtisanCommandLog::query()
            ->bySn('pms:calculate-staff-point')
            ->where('status', 1)
            ->latest('id')
            ->first();
    }

    /**
     * 创建积分日志.
     *
     * @author 28youth
     * @return ArtisanCommandLog
     */
    protected function createLog(): ArtisanCommandLog
    {
        $artisan = new ArtisanCommandLog();
        $artisan->command_sn = 'pms:calculate-staff-point';
        $artisan->created_at = now();
        $artisan->title = '每月积分结算';
        $artisan->status = 1;

        return $artisan;
    }

    /**
     * 来源积分计算.
     *
     * @author 28youth
     * @return array
     */
    public function monthlySource($origin, $log, $type)
    {
        $current = $origin[$type] ?? $this->initType;
        foreach ($current as $k => &$v) {
            if ($v['id'] === $log->type_id) {
                if (in_array($type, ['source_a_monthly', 'source_a_total'])) {
                    $v['point'] += $log->point_a;
                    if ($log->point_a >= 0) {
                        $v['add_point'] += $log->point_a;
                    } else {
                        $v['sub_point'] += $log->point_a;
                    }
                } elseif (in_array($type, ['source_b_monthly', 'source_b_total'])) {
                    $v['point'] += $log->point_b;
                    if ($log->point_b >= 0) {
                        $v['add_point'] += $log->point_b;
                    } else {
                        $v['sub_point'] += $log->point_b;
                    }
                }
            }
        }
        return $current;
    }

    /**
     * 统计日结积分信息.
     * 
     * @return void
     */
    protected function updateDaily($daily)
    {
        $staffsn = $daily['staff_sn'];
        $staff = $this->checkClientStaff($staffsn);

        $logModel = StatisticModel::where('staff_sn', $staffsn)->first();
        if (empty($logModel)) {
            $logModel = new StatisticModel();
        }
        $logModel->fill($staff + $daily);

        $logModel->save();
    }

    /**
     * 统计月结积分信息.
     * 
     * @return void
     */
    protected function updateMonthly($monthly)
    {
        $staffsn = $monthly['staff_sn'];
        $staff = $this->checkClientStaff($staffsn);
        $logModel = StatisticLogModel::query()
            ->where('date', $monthly['date'])
            ->where('staff_sn', $staffsn)
            ->first();
        if (empty($logModel)) {
            $logModel = new StatisticLogModel();
            $logModel->fill($staff + $monthly);
            $logModel->save();
        } else {
            $logModel->fill($staff);
            $logModel->date = $monthly['date'];
            $logModel->point_a += $monthly['point_a'];
            $logModel->point_a_total += $monthly['point_a_total'];
            $logModel->point_b_monthly += $monthly['point_b_monthly'];
            $logModel->point_b_total += $monthly['point_b_total'];
            $logModel->source_a_monthly = $this->mergeSource($logModel->source_a_monthly, $monthly['source_a_monthly']);
            $logModel->source_a_total = $this->mergeSource($logModel->source_a_total, $monthly['source_a_total']);
            $logModel->source_b_monthly = $this->mergeSource($logModel->source_b_monthly, $monthly['source_b_monthly']);
            $logModel->source_b_total = $this->mergeSource($logModel->source_b_total, $monthly['source_b_total']);
            $logModel->save();
        }
    }

    /**
     * 获取结算所需用户信息.
     *
     * @author 28youth
     * @return array
     */
    protected function checkClientStaff(int $staff_sn): array
    {
        $user = app('api')->client()->getStaff($staff_sn);
        return [
            'staff_sn' => $user['staff_sn'],
            'staff_name' => $user['realname'],
            'brand_id' => $user['brand']['id'] ?? 0,
            'brand_name' => $user['brand']['name'] ?? '',
            'department_id' => $user['department']['id'] ?? 0,
            'department_name' => $user['department']['full_name'] ?? '',
            'shop_sn' => $user['shop']['shop_sn'] ?? '',
            'shop_name' => $user['shop']['shop_name'] ?? '',
        ];
    }

    /**
     * 更新各来源分统计.
     *
     * @author 28youth
     * @return array
     */
    protected function mergeSource($source, $data): array
    {
        foreach ($data as $key => $value) {
            if (isset($source[$key])) {
                $source[$key]['add_point'] += $value['add_point'];
                $source[$key]['sub_point'] += $value['sub_point'];
                $source[$key]['point'] += $value['point'];
            }
        }
        return $source;
    }
}
