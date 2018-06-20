<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use function App\monthBetween;
use App\Models\PointLogSource;
use App\Models\ArtisanCommandLog;
use App\Models\PointLog as PointLogModel;
use App\Models\PersonalPointStatistic as StatisticModel;
use App\Models\PersonalPointStatisticLog as StatisticLogModel;

class CalculateUserPoint extends Command
{

    protected $signature = 'pms:calculate-user-point';
    protected $description = 'Calculate user point';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
    	$this->calculateMonthPoint();
    }

    /**
     *  日结积分.
     *  
     * @author 28youth
     */
    public function calculateDailyPoint()
    {
        $statistic = [];
        $dailyTime = Carbon::now();
        $lastTime = ArtisanCommandLog::query()
            ->where('command_sn', 'pms:calculate-user-point')
            ->latest('id')
            ->first();

        $users = PointLogModel::query()
            ->when($lastTime, function ($query) use ($lastTime, $dailyTime) {
                $query->whereBetween('created_at', [$lastTime->created_at, $dailyTime]);
            })
            ->groupBy('staff_sn')
            ->pluck('staff_sn')
            ->toArray();

        $logs = PointLogModel::query()
            ->when($lastTime, function ($query) use ($lastTime, $dailyTime) {
                $query->whereBetween('created_at', [$lastTime->created_at, $dailyTime]);
            })
            ->get();

        foreach ($logs as $key => $log) {
            if (in_array($log->staff_sn, $users)) {
                // 当月各来源分类统计
                $statistic[$log->staff_sn]['source_b_monthly'] = $this->monthlySource($log, $statistic);

                // 当月 ab 总分
                $statistic[$log->staff_sn]['point_monthly'] = $this->monthlyPoint($log, $statistic);
            }
        }

        try {
            // 创建任务日志
            // todo
            
            foreach ($statistic as $staff => &$daily) {
                // 上次日结数据
                $lastDaily = StatisticModel::query()
                    ->where('staff_sn', $staff)
                    ->orderBy('calculated_at', 'desc')
                    ->first(); 

                // 合并上次统计
                if ($lastDaily !== null) {
                    $daily['point_monthly']['point_a_total'] +=  $lastDaily->point_a;
                    $daily['point_monthly']['point_b_total'] +=  $lastDaily->point_b_monthly;
                    $daily['source_b_monthly'] = $this->mergeSource(
                        $daily['source_b_monthly'], 
                        $lastDaily->source_b_monthly
                    );
                    $daily['source_b_total'] = $this->mergeSource(
                        $daily['source_b_total'], 
                        $lastDaily->source_b_total
                    );
                }

                // 结算跨月 清空a分和当月b分 
                if ($lastTime && !Carbon::parse($lastTime->created_at)->isCurrentMonth()) {
                    $daily['point_monthly']['point_a_total'] = 0;
                    $daily['point_monthly']['point_b_total'] = 0;
                }

                $user = $this->checkStaff($staff);
                $dailyModel = new StatisticModel();
                $dailyModel->fill($user);
                $dailyModel->calculated_at = $dailyTime;
                $dailyModel->point_a = $daily['point_monthly']['point_a_total'];
                $dailyModel->point_b_monthly = $daily['point_monthly']['point_b_total'];
                $dailyModel->source_b_monthly = $daily['source_b_monthly'];
                $dailyModel->source_b_total = $daily['source_b_total'] ?? $daily['source_b_monthly'];
                $dailyModel->save();
            }

        } catch (Exception $e) {
            
        }
    }
    
    /**
     * 月结来源积分统计.
     * 
     * @author 28youth
     * @param  [type] $log
     * @param  [type] $statistic
     * @return array
     */
    public function monthlySource($log, $statistic)
    {
        $source_b_monthly = ($statistic[$log->staff_sn]['source_b_monthly']) ?? PointLogSource::get()->toArray();

        foreach ($source_b_monthly as $key => &$value) {
            // 初始化值
            $value['add_point'] = $value['add_point'] ?? 0;
            $value['sub_point'] = $value['sub_point'] ?? 0;
            $value['point_b_total'] = $value['point_b_total'] ?? 0;

            if ($value['id'] === $log->source_id) {

                $value['point_b_total'] += $log->point_b;
                if ($log->point_b >= 0) {
                    $value['add_point'] = $value['add_point'] + $log->point_b;
                } else {
                    $value['sub_point'] = $value['sub_point'] + $log->point_b;
                }
            }
        }

        return $source_b_monthly;
    }

    /**
     * 获取结算所需用户信息.
     * 
     * @author 28youth
     * @return array
     */
    public function checkStaff(int $staff_sn): array
    {
        $user = app('api')->getStaff($staff_sn);
        return [
            'staff_sn' => $user['staff_sn'] ?? 0,
            'staff_name' => $user['realname'] ?? '',
            'brand_id' => $user['brand']['id'] ?? 0,
            'brand_name' => $user['brand']['name'] ?? '',
            'department_id' => $user['department']['id'] ?? 0,
            'department_name' => $user['department']['full_name'] ?? '',
            'shop_sn' => $user['shop']['shop_sn'] ?? '',
            'shop_name' => $user['shop']['shop_name'] ?? '',
        ];
    }

    /**
     * 当月 ad 总分统计.
     * 
     * @author 28youth
     * @param  [type] $log
     * @param  [type] $statistic
     * @return array
     */
    public function monthlyPoint($log, $statistic)
    {
        $point_monthly = ($statistic[$log->staff_sn]['point_monthly']) ?? [
            'point_a_total' => 0,
            'point_b_total' => 0
        ];

        $point_monthly['point_a_total'] += $log->point_a;
        $point_monthly['point_b_total'] += $log->point_b;

        return $point_monthly;
    }

    /**
     * 合并各分类来源.
     * 
     * @author 28youth
     * @param  $source_b_lastmonthly  上次结算数据
     * @param  $source_b_monthly 本次结算数据
     */
    public function mergeSource($source_b_lastmonthly, $source_b_monthly)
    {
        $source_b_total = $source_b_lastmonthly;
        foreach ($source_b_total as $key => &$source) {
            if ($source['id']  === $source_b_monthly[$key]['id']) {
                $source['add_point'] += $source_b_monthly[$key]['add_point'];
                $source['sub_point'] += $source_b_monthly[$key]['sub_point'];
                $source['point_b_total'] += $source_b_monthly[$key]['point_b_total'];
            }
        }

        return $source_b_total;
    }

    // 月结积分
    /*public function calculateMonthPoint()
    {
        $statistic = [];
        $users = PointLogModel::query()
            ->whereBetween('created_at', monthBetween())
            ->groupBy('staff_sn')
            ->pluck('staff_sn')
            ->toArray();

        $logs = PointLogModel::whereBetween('date', monthBetween())->get();

        foreach ($logs as $key => $log) {
            if (in_array($log->staff_sn, $users)) {
                // 当月各来源分类统计
                $statistic[$log->staff_sn]['source_b_monthly'] = $this->monthlySource($log, $statistic);

                // 当月 ab 总分
                $statistic[$log->staff_sn]['point_monthly'] = $this->monthlyPoint($log, $statistic);
            }
        }

        foreach ($statistic as $staff => &$monthly) {

            // 上次月结数据
            $lastMonth = StatisticLogModel::query()
                ->select('source_b_total', 'point_b_total')
                ->where('staff_sn', $staff)
                ->orderBy('created_at', 'desc')
                ->first();

            // 无上次月结记录
            if ($lastMonth === null) {
                # code...
            }
            
            // $monthly['point_b_total'] = 
            // $monthly['source_b_total'] = 
        }
    }*/

}
