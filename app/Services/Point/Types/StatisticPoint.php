<?php 

namespace App\Services\Point\Types;

use Carbon\Carbon;
use App\Services\Point\Log;
use App\Models\PointLogSource;
use Illuminate\Support\Facades\Cache;
use App\Models\PersonalPointStatistic as StatisticModel;
use App\Models\PersonalPointStatisticLog as StatisticLogModel;

class StatisticPoint extends Log
{

    /**
     * 统计日结积分信息.
     * 
     * @author 28youth
     * @param  array
     * @return void
     */
    public function statisticDaily($daily, $staffsn)
    {
        $staff = $this->checkClientStaff($staffsn);

        $logModel = StatisticModel::where('staff_sn', $staffsn)->first();
        if ($logModel === null) {
            $logModel = new StatisticModel();
        }
        $logModel->fill($staff + $daily);

        // 兼容老数据缺失字段
        $logModel->point_a_total = $logModel->point_a_total ? : $logModel->point_a;
        $logModel->source_a_total = $logModel->source_a_total ? : $this->makeSourceData();
        $logModel->source_a_monthly = $logModel->source_a_monthly ? : $this->makeSourceData();

        $logModel->save();
    }

    /**
     * 统计月结积分信息.
     * 
     * @author 28youth
     * @param  array
     * @return void
     */
    public function statisticMonthly($monthly, $staffsn)
    {
        $staff = $this->checkClientStaff($staffsn);

        $date = Carbon::create(null, null, 02)->subMonth();
        $logModel = StatisticLogModel::where('staff_sn', $staffsn)
            ->whereBetween('date', monthBetween($date))
            ->first();
        if ($logModel === null) {
            $logModel = new StatisticLogModel();
            $logModel->date = $date;
        }
        $logModel->fill($staff + $monthly);

        // 兼容老数据缺失字段
        $logModel->point_a_total = $logModel->point_a_total ? : $logModel->point_a;
        $logModel->source_a_total = $logModel->source_a_total ? : $this->makeSourceData();
        $logModel->source_a_monthly = $logModel->source_a_monthly ? : $this->makeSourceData();

        $logModel->save();
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
}