<?php 

namespace App\Services;

use App\Jobs\PointLogger AS PointLoggerJob;
use App\Models\PointLog as PointLogModel;
use App\Models\EventLog as EventLogModel;

class PointLogger
{

    public function logEventPoint($target_sn, EventLogModel $eventlog)
    {
        $title = '事件积分变化';

        if (is_array($target_sn)) {
            foreach ($target_sn as $key => $value) {
                dispatch(new PointLoggerJob($value, $title, $eventlog));
            }
        }
        $this->createPointLog($target_sn, $title, $eventlog);
    }

    public function logSystemPoint($target_sn, EventLogModel $eventlog)
    {
        $title = '系统积分变化';

        $this->createPointLog($target_sn, $title, $eventlog);
    }

    /**
     * 创建积分变更记录.
     * 
     * @author 28youth
     * @param  \App\Models\EventLog $eventlog
     * @return mixed
     */
    protected function createPointLog($staff_sn, string $title, EventLogModel $eventlog): PointLogModel
    {
        $user = $this->checkUser($staff_sn);

        $logModel = new PointLogModel();
        $logModel->title = $title;
        $logModel->staff_sn = $user->staff_sn;
        $logModel->staff_name = $user->realname;
        $logModel->brand_id = $user->brand->id;
        $logModel->brand_name = $user->brand->name;
        $logModel->department_id = $user->department->id;
        $logModel->department_name = $user->department->name;
        $logModel->point_a = $eventlog->point_a;
        $logModel->point_b = $eventlog->point_b;
        $logModel->source_foreign_key = $eventlog->id;
        $logModel->first_approver_sn = $eventlog->first_approver_sn;
        $logModel->first_approver_name = $eventlog->first_approver_name;
        $logModel->final_approver_sn = $eventlog->final_approver_sn;
        $logModel->final_approver_name = $eventlog->final_approver_name;
        $logModel->save();

        return $logModel; 
    }
}