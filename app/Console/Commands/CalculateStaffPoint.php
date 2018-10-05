<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\PointLogSource;
use Illuminate\Console\Command;
use App\Models\ArtisanCommandLog;
use Illuminate\Support\Facades\Cache;
use App\Models\PointLog as PointLogModel;
use App\Models\PointType as PointTypeModel;
use App\Jobs\StatisticPoint as StatisticPointJob;
use App\Jobs\StatisticLogPoint as StatisticLogPointJob;
use App\Models\PersonalPointStatistic as StatisticModel;

class CalculateStaffPoint extends Command
{
    /**
     * 日结统计数据.
     *
     * @var array
     */
    protected $daily;

    /**
     * 月结统计数据.
     *
     * @var array
     */
    protected $monthly;

    /**
     * 当前时间.
     *
     * @var datetime
     */
    protected $curtime;

    /**
     * 上次结算时间.
     *
     * @var datetime
     */
    protected $pretime;

    protected $signature = 'pms:calculate-staff-point';
    protected $description = 'Calculate staff point';

    public function __construct()
    {
        $this->curtime = now();
        $this->pretime = $this->preNode()->created_at ?? null;

        parent::__construct();
    }

    public function handle()
    {
        if ($this->preNode() !== null) {
            // 获取所有日结数据进行结算
            StatisticModel::get()->map(function ($item) {
                // 结算跨月
                if (!Carbon::parse($this->pretime)->isCurrentMonth()) {
                    // 放入月结数据
                    $key = $item->staff_sn . '|' . now()->startOfMonth();
                    $this->monthly[$key] = $item->toArray();
                    // 初始化月结时间
                    $this->monthly[$key]['date'] = now()->startOfMonth();

                    // 跨月清空数据
                    $item->point_a = 0;
                    $item->source_a_monthly = $this->makePointTypeData();
                    $item->point_b_monthly = 0;
                    $item->source_b_monthly = $this->makePointTypeData();
                }
                $this->daily[$item->staff_sn] = $item->toArray();
                // 初始化日结时间
                $this->daily[$item->staff_sn]['calculated_at'] = $this->curtime;
            });
        }
        // 拿上次统计到现在的积分日志
        $logs = PointLogModel::query()
            ->select('point_a', 'point_b', 'staff_sn', 'type_id', 'changed_at')
            ->when($this->preNode(), function ($query) {
                $query->whereBetween('created_at', [$this->preNode()->created_at, now()]);
            })
            ->where('is_revoke', 0)
            ->get();

        $logs->map(function ($item) {
            if (!isset($this->daily[$item->staff_sn])) {
                $this->initDailyStatisticData($item);
            }
            // 非本月生效的积分日志
            if (empty($item->changed_at)) {
                //
            } elseif (!Carbon::parse($item->changed_at)->isCurrentMonth()) {
                $this->handleLastMonthlyStatisticData($item);
            } else {
                // 统计当月分
                $this->monthStatisticData($item);
            }
            // 统计累计分
            $this->totalStatisticData($item);
        });
        $commandModel = $this->createLog();
        try {
            \DB::beginTransaction();

            if ($this->daily) {
                foreach ($this->daily as $key => $day) {
                    StatisticPointJob::dispatch($day, $key);
                }
            }

            if ($this->monthly) {
                foreach ($this->monthly as $key => $month) {
                    StatisticLogPointJob::dispatch($month, $month['staff_sn']);
                }
            }

            $commandModel->save();

            \DB::commit();
        } catch (Exception $e) {
            \DB::rollBack();

            $commandModel->status = 2;
            $commandModel->save();
        }
    }

    /**
     * 上次结算节点信息.
     *
     * @author 28youth
     * @return \App\Models\ArtisanCommandLog|null
     */
    public function preNode()
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
    public function createLog(): ArtisanCommandLog
    {
        $artisan = new ArtisanCommandLog();
        $artisan->command_sn = 'pms:calculate-staff-point';
        $artisan->created_at = now();
        $artisan->title = '每月积分结算';
        $artisan->status = 1;

        return $artisan;
    }

    public function initDailyStatisticData($log)
    {
        $this->daily[$log->staff_sn] = [
            'point_a' => 0,
            'source_a_monthly' => $this->makePointTypeData(),
            'point_b_monthly' => 0,
            'source_b_monthly' => $this->makePointTypeData(),
            'point_a_total' => 0,
            'source_a_total' => $this->makePointTypeData(),
            'point_b_total' => 0,
            'source_b_total' => $this->makePointTypeData(),
            'calculated_at' => $this->curtime,
        ];
    }

    /**
     * 处理上月统计数据.
     *
     * @author 28youth
     * @param  $log
     */
    public function handleLastMonthlyStatisticData($log)
    {
        // 是否存在结算月份的结算员工
        $key = $log->staff_sn . '|' . Carbon::parse($log->changed_at)->startOfMonth();

        if (isset($this->monthly[$key])) {
            if (!empty($log->changed_at)) {
                $this->monthly[$key]['point_a'] += $log->point_a;
                $this->monthly[$key]['source_a_monthly'] = $this->monthlySource($log, 'source_a_monthly');

                $this->monthly[$key]['point_b_monthly'] += $log->point_b;
                $this->monthly[$key]['source_b_monthly'] = $this->monthlySource($log, 'source_b_monthly');
            }
        } else {
            if (!empty($log->changed_at)) {
                $this->monthly[$key]['point_a'] = $log->point_a;
                $this->monthly[$key]['source_a_monthly'] = $this->monthlySource($log, 'source_a_monthly');

                $this->monthly[$key]['point_b_monthly'] = $log->point_b;
                $this->monthly[$key]['source_b_monthly'] = $this->monthlySource($log, 'source_b_monthly');
            }
            // 新增的结算时间等于积分的生效时间
            $this->monthly[$key]['date'] = Carbon::parse($log->changed_at)->startOfMonth();
        }
        $this->monthly[$key]['staff_sn'] = $log->staff_sn;
    }

    /**
     * 处理上次统计数据.
     *
     * @author 28youth
     * @param  $log
     */
    public function monthStatisticData($log)
    {
        $this->daily[$log->staff_sn]['point_a'] += $log->point_a;
        $this->daily[$log->staff_sn]['source_a_monthly'] = $this->monthlySource($log, 'source_a_monthly', 'daily');

        $this->daily[$log->staff_sn]['point_b_monthly'] += $log->point_b;
        $this->daily[$log->staff_sn]['source_b_monthly'] = $this->monthlySource($log, 'source_b_monthly', 'daily');

        $this->daily[$log->staff_sn]['calculated_at'] = $this->curtime;
    }

    /**
     * 处理总分结算.
     *
     * @return void
     */
    public function totalStatisticData($log)
    {
        $changedAt = $log->changed_at ?: '2018-07-01 00:00:00';
        $i = Carbon::parse($changedAt)->startOfMonth();
        for ($i; $i->timestamp() < Carbon::startOfMonth()->timestamp(); $i->addMonth()) {
            $key = $log->staff_sn . '|' . $i;
            if ($this->monthly[$key]) {
                $this->monthly[$key]['point_a_total'] += $log->point_a;
                $this->monthly[$key]['source_a_total'] = $this->monthlySource($log, 'source_a_total');
                $this->monthly[$key]['point_b_total'] += $log->point_b;
                $this->monthly[$key]['source_b_total'] = $this->monthlySource($log, 'source_b_total');
            }
        }

        $this->daily[$log->staff_sn]['point_a_total'] += $log->point_a;
        $this->daily[$log->staff_sn]['source_a_total'] = $this->monthlySource($log, 'source_a_total', 'daily');

        $this->daily[$log->staff_sn]['point_b_total'] += $log->point_b;
        $this->daily[$log->staff_sn]['source_b_total'] = $this->monthlySource($log, 'source_b_total', 'daily');

        $this->daily[$log->staff_sn]['staff_sn'] = $log->staff_sn;
    }

    /**
     * 初始化默认积分来源信息.
     *
     * @author 28youth
     * @return array
     */
    public function makeSourceData()
    {
        $cacheKey = 'default_point_log_source';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        $source = PointLogSource::get()->map(function ($item) {
            $item->add_point = 0;
            $item->sub_point = 0;
            $item->add_a_point = 0;
            $item->sub_a_point = 0;
            $item->point_a_total = 0;
            $item->point_b_total = 0;

            return $item;
        })->toArray();

        $expiresAt = now()->addDay();
        Cache::put($cacheKey, $source, $expiresAt);

        return $source;
    }

    /**
     * 初始化统计分类数据.
     *
     * @author 28youth
     * @return array
     */
    public function makePointTypeData()
    {
        $cacheKey = 'default_point_type_source';

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }
        $source = PointTypeModel::get()->map(function ($item) {
            $item->add_point = 0;
            $item->sub_point = 0;
            $item->point = 0;

            return $item;
        })->toArray();

        $expiresAt = now()->addDay();
        Cache::put($cacheKey, $source, $expiresAt);

        return $source;
    }

    /**
     * 来源积分统计.
     *
     * @author 28youth
     * @return array
     */
    public function monthlySource($log, $type, $cate = 'monthly')
    {
        $key = $log->staff_sn;
        if ($cate == 'monthly') $key .= '|' . Carbon::parse($log->changed_at)->startOfMonth();
        $current = $this->{$cate}[$key][$type] ?? $this->makePointTypeData();

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
}
