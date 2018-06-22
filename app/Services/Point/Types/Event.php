<?php 

namespace App\Services\Point\Types;

use Carbon\Carbon;
use App\Services\Point\Log;
use App\Models\EventLog as EventLogModel;
use App\Models\PointLog as PointLogModel;

class Event extends Log
{

    public function record(EventLogModel $eventlog)
    {
        $participants = $eventlog->participant;
        
        $logs = $participants->map(function ($item) use ($eventlog) {
            $user = $this->checkStaff($item->staff_sn);
            $item = [
                'title'  => '事件积分变化',
                'point_a' => $item->point_a,
                'point_b' => $item->point_b,
                'source_id' => self::EVENT_POINT,
                'source_foreign_key' => $eventlog->id,
                'first_approver_sn' => $eventlog->first_approver_sn,
                'first_approver_name' => $eventlog->first_approver_name,
                'final_approver_sn' => $eventlog->final_approver_sn,
                'final_approver_name' => $eventlog->final_approver_name,
                'created_at' => Carbon::now(),
            ];

            return $user + $item;
        })->toArray();

        try {
            PointLogModel::insert($logs);
        } catch (Exception $e) {
            
        }
    }

}