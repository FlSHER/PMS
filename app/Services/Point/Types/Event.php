<?php

namespace App\Services\Point\Types;

use Carbon\Carbon;
use App\Services\Point\Log;
use App\Models\EventLog as EventLogModel;
use App\Models\PointLog as PointLogModel;

class Event extends Log
{

    /**
     * 记录事件参与人、初审人、记录人积分变更.
     *
     * @author 28youth
     * @param  \App\Models\EventLog $eventlog
     */
    public function record(EventLogModel $eventlog)
    {
        $baseData = $this->fillBaseData($eventlog);

        // 事件参与人得分
        $logs = $eventlog->participant->map(function ($item) use ($baseData, $eventlog) {
            $eventData = $item->toArray();
            $eventData['title'] = '参与奖扣: ' . $eventlog->event_name;
            $eventData['point_a'] = round($eventData['point_a'] * $eventData['count']);
            $eventData['point_b'] = round($eventData['point_b'] * $eventData['count']);
            $item = array_merge($eventData, $baseData);

            return $item;
        })->toArray();

        // 初审人得分
        $logs[] = array_merge($baseData, [
            'point_a' => 0,
            'point_b' => $eventlog->first_approver_point,
            'staff_sn' => $eventlog->first_approver_sn,
            'source_id' => self::SYSTEM_POINT,
            'title' => '奖扣-初审人: ' . $eventlog->event_name
        ]);
        // 记录人得分
        $logs[] = array_merge($baseData, [
            'point_a' => 0,
            'point_b' => $eventlog->recorder_point,
            'staff_sn' => $eventlog->recorder_sn,
            'source_id' => self::SYSTEM_POINT,
            'title' => '奖扣-记录人: ' . $eventlog->event_name
        ]);

        array_walk($logs, [$this, 'createLog']);
    }

    /**
     * 记录撤销奖扣操作
     *
     * @param EventLogModel $eventlog
     * @return void
     */
    public function revoke(EventLogModel $eventlog, $params)
    {
        $baseData = $this->fillBaseData($eventlog);

        $logs = $eventlog->participant->map(function ($item) use ($baseData, $eventlog) {
            $eventData = $item->toArray();
            $eventData['title'] = '撤销奖扣: ' . $eventlog->event_name;
            $eventData['point_a'] = -round($eventData['point_a'] * $eventData['count']);
            $eventData['point_b'] = -round($eventData['point_b'] * $eventData['count']);
            return array_merge($eventData, $baseData);
        })->filter()->toArray();

        // 初审人扣分
        $logs[] = array_merge($baseData, [
            'point_a' => 0,
            'point_b' => -$params['first_approver_point'],
            'staff_sn' => $eventlog->first_approver_sn,
            'source_id' => self::SYSTEM_POINT,
            'title' => '撤销奖扣-初审人: ' . $eventlog->event_name
        ]);

        // 终审人扣分
        $logs[] = array_merge($baseData, [
            'point_a' => 0,
            'point_b' => -$params['final_approver_point'],
            'staff_sn' => $eventlog->final_approver_sn,
            'source_id' => self::SYSTEM_POINT,
            'title' => '撤销奖扣-终审人: ' . $eventlog->event_name
        ]);

        // 记录人扣分
        $logs[] = array_merge($baseData, [
            'point_a' => 0,
            'point_b' => -$params['recorder_point'],
            'staff_sn' => $eventlog->recorder_sn,
            'source_id' => self::SYSTEM_POINT,
            'title' => '撤销奖扣-记录人: ' . $eventlog->event_name
        ]);
        
        array_walk($logs, [$this, 'createLog']);
    }

    /**
     * 创建积分变更日志.
     *
     * @author 28youth
     * @param  array $params
     */
    protected function createLog($log)
    {
        // a b 分均等于零不录入记录
        if ($log['point_a'] == 0 && $log['point_b'] == 0) {
            return false;
        }

        // 根据事件执行时间 月结后自动顺延到本月一号
        $setDay = config('command.monthly_date');
        $curDay = Carbon::now()->daysInMonth;
        $changedAt = Carbon::parse($log['changed_at']);
        $curMonth = Carbon::now()->startOfMonth();

        if ($curDay >= $setDay && $changedAt->lt($curMonth)) {
            $log['changed_at'] = $curMonth;
        }
        // 最新用户信息
        $user = $this->checkStaff($log['staff_sn']);

        $model = new PointLogModel();
        $model->fill($user + $log);
        $model->save();
    }

    /**
     * 填充事件基础数据.
     *
     * @author 28youth
     * @param  \App\Models\EventLog $eventlog
     * @return array
     */
    protected function fillBaseData(EventLogModel $eventlog): array
    {
        return [
            'source_id' => self::EVENT_POINT,
            'source_foreign_key' => $eventlog->id,
            'first_approver_sn' => $eventlog->first_approver_sn,
            'first_approver_name' => $eventlog->first_approver_name,
            'final_approver_sn' => $eventlog->final_approver_sn,
            'final_approver_name' => $eventlog->final_approver_name,
            'changed_at' => $eventlog->executed_at,
            'created_at' => Carbon::now(),
        ];
    }
}