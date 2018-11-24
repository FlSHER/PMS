<?php

namespace App\Services\Point\Types;

use Carbon\Carbon;
use App\Services\Point\Log;
use App\Models\EventLog as EventLogModel;
use App\Models\PointLog as PointLogModel;
use App\Models\EventType as EventTypeModel;

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
        $logs = $eventlog->participants->map(function ($item) use ($baseData, $eventlog) {
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
            'title' => '奖扣-初审人: ' . $eventlog->event_name
        ]);
        // 记录人得分
        $logs[] = array_merge($baseData, [
            'point_a' => 0,
            'point_b' => $eventlog->recorder_point,
            'staff_sn' => $eventlog->recorder_sn,
            'title' => '奖扣-记录人: ' . $eventlog->event_name
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
        $curDay = Carbon::now()->day;
        $changedAt = Carbon::parse($log['changed_at']);
        $curMonth = Carbon::now()->startOfMonth();
        $preMonth = Carbon::now()->subMonth()->startOfMonth();

        if ($curDay >= $setDay && $changedAt->lt($curMonth)) {
            $log['changed_at'] = $curMonth;
        } elseif ($changedAt->lt($preMonth)) {
            $log['changed_at'] = $preMonth;
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
            'recorder_sn' => $eventlog->recorder_sn,
            'recorder_name' => $eventlog->recorder_name,
            'type_id' => $this->hasType($eventlog->event_type_id),
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

    /**
     * 根据事件分类返回积分分类ID.
     *
     * @author 28youth
     * @param  int $type_id
     */
    public function hasType(int $type_id)
    {
        $topType = $this->getTopType($type_id);

        switch ($topType->id) {
            case 1:
                // 工作类事件
                return 1;
                break;
            case 13:
                // 行政类事件
                return 2;
                break;
            case 19:
                // 创新类事件
                return 3;
                break;
            case 20:
                // 其他类事件
                return 4;
                break;
        }
    }

    /**
     * 获取某分类的顶级分类.
     *
     * @author 28youth
     * @param  int $id
     * @return EventTypeModel
     */
    public function getTopType(int $id): EventTypeModel
    {
        $type = EventTypeModel::where('id', $id)->first();

        if ($type->parent_id && !empty($type->parent_id)) {
            return $this->getTopType($type->parent_id);
        }

        return $type;
    }
}