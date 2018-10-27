<?php 

namespace App\Services\Point\Types;

use Carbon\Carbon;
use App\Services\Point\Log;
use App\Models\PointLogSource;
use function App\monthBetween;
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
        $logModel = StatisticLogModel::query()
            ->where('date', $monthly['date'])
            ->where('staff_sn', $staffsn)
            ->first();
        if ($logModel === null) {
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
     * 更新各来源分统计.
     *
     * @author 28youth
     * @return array
     */
    public function mergeSource($source, $data)
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