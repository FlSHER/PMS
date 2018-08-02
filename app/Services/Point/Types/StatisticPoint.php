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

        $date = Carbon::create(null, null, 02)->subMonth();
        $logModel = StatisticLogModel::where('staff_sn', $staffsn)
            ->whereBetween('date', monthBetween($date))
            ->first();
        if ($logModel === null) {
            $logModel = new StatisticLogModel();
            $logModel->date = $date;
        }
        $logModel->fill($staff + $monthly);

        $logModel->save();
    }

}