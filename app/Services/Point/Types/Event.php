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
            $item = [
                'title' => '事件积分变化',
                'point_a' => $item->point_a,
                'point_b' => $item->point_b,
                'staff_sn' => $item->staff_sn,
                'source_id' => self::EVENT_POINT,
                'source_foreign_key' => $eventlog->id,
                'first_approver_sn' => $eventlog->first_approver_sn,
                'first_approver_name' => $eventlog->first_approver_name,
                'final_approver_sn' => $eventlog->final_approver_sn,
                'final_approver_name' => $eventlog->final_approver_name,
                'changed_at' => $item->executed_at,
                'created_at' => Carbon::now(),
            ];

            // 根据事件执行时间 月结后自动顺延到本月一号
            if (!Carbon::parse($item->executed_at)->isCurrentMonth()) {
                $item['changed_at'] = Carbon::create(null, null, 01);
            }

            return $item;

        })->toArray();

        $this->createLogs($logs);
    }

}