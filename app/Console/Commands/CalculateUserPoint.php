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
        // 统计数据
        $statistics = $this->calculate();

        foreach ($statistics as $key => $value) {
            // 更新日结表
            
        }
    }

    /**
     * 积分统计.
     * 
     * @author 28youth
     * @return array
     */
    public function calculate()
    {
        $statistics = [];
        $calculatedAt = Carbon::now();
        $lastDaily = CommandLogModel::bySn('pms:calculate-user-point')->latest('id')->first();

        $builder = PointLogModel::query();
        if ($lastDaily !== null) {
            $builder->whereBetween('created_at', [$lastDaily->created_at, $calculatedAt]);
        }
        // 拿上次日结到现在的积分日志
        $logs = $builder->get();

        if ($logs === null) return false;

        // 拿上次日结到现在的积分日志员工
        $users = $builder->groupBy('staff_sn')->pluck('staff_sn')->toArray();

        // 统计每个员工的积分
        foreach ($logs as $key => $log) {
            if (in_array($log->staff_sn, $users)) {

                // 各来源 b 分类统计
                $statistics[$log->staff_sn]['point_source'] = $this->monthlySource($log, $statistics);

                // ab 总分, 累计 b 分
                $statistics[$log->staff_sn]['point_total'] = $this->monthlyPoint($log, $statistics);
            }
        }

        // 合并本次统计到上次结算
        foreach ($statistics as $staff => &$val) {

            // 累计来源统计,默认值
            $val['source_b_total'] = $val['point_source'];

            // 员工上次结算
            $last = StatisticModel::where('staff_sn', $staff)->latest('calculated_at')->first();

            // 叠加上次结算
            if ($last !== null) {
                $val['point_total']['point_a'] += $last->point_a;
                $val['point_total']['point_b_monthly'] += $last->point_b_monthly;
                $val['point_total']['point_b_total'] += $last->point_b_total;
                $val['point_source'] = $this->mergeSource($last->source_b_monthly, $val['point_source']);
                $val['source_b_total'] = $this->mergeSource($last->source_b_total, $val['point_source']);
            }

            // 结算跨月 清空对应积分 
            if ($lastDaily && !Carbon::parse($lastDaily->created_at)->isCurrentMonth()) {
                $val['point_total']['point_a'] = 0;
                $val['point_total']['point_b_monthly'] = 0;
            }
        }

        return $statistics;
    }

    /**
     * 来源积分统计.
     * 
     * @author 28youth
     * @return array
     */
    public function monthlySource($log, $statistic)
    {
        $point_source = ($statistic[$log->staff_sn]['point_source']) ?? PointLogSource::get();

        foreach ($point_source as $key => &$value) {
            // 初始化值
            $value['add_point'] = $value['add_point'] ?? 0;
            $value['sub_point'] = $value['sub_point'] ?? 0;
            $value['point_b_total'] = $value['point_b_total'] ?? 0;

            if ($value['id'] === $log->source_id) {
                $value['point_b_total'] += $log->point_b;
                if ($log->point_b >= 0) {
                    $value['add_point'] += $log->point_b;
                } else {
                    $value['sub_point'] += $log->point_b;
                }
            }
        }

        return $point_source;
    }

    /**
     * 累计总分统计.
     * 
     * @author 28youth
     * @return array
     */
    public function monthlyPoint($log, $statistic)
    {
        $point_monthly = ($statistic[$log->staff_sn]['point_total']) ?? [
            'point_a' => 0,
            'point_b_monthly' => 0,
            'point_b_total' => 0
        ];

        $point_monthly['point_a'] += $log->point_a;
        $point_monthly['point_b_monthly'] += $log->point_b;
        $point_monthly['point_b_total'] += $log->point_b;

        return $point_monthly;
    }

    /**
     * 合并各分类来源.
     * 
     * @author 28youth
     * @param  $last  上次结算数据
     * @param  $current 本次结算数据
     */
    public function mergeSource($last, $current)
    {
        foreach ($last as $key => &$val) {
            $current->map(function ($item) use (&$val) {
                if ($val['id'] === $item['id']) {
                    $val['add_point'] += $item['add_point'];
                    $val['sub_point'] += $item['sub_point'];
                    $val['point_b_total'] += $item['point_b_total'];
                }
            });
        }
        return $last;
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
    
    // 月结积分
    /*public function calculateMonthPoint()
    {
        $statistic = [];
        $users = PointLogModel::query()
            ->whereBetween('created_at', monthBetween())
            ->groupBy('staff_sn')
            ->pluck('staff_sn')
            ->toArray();

        $logs = PointLogModel::whereBetween('created_at', monthBetween())->get();

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
