<?php

namespace App\Services;

use Carbon\Carbon;
use App\Services\Point\Types\Event;
use App\Models\EventLog as EventLogModel;
use App\Models\EventLogGroup as EventLogGroupModel;

/**
 * 审核服务.
 */
class EventApprove
{
    /**
     * @var App\Models\EventLogGroup
     */
    protected $group;

    /**
     * 注入事件日志模型.
     *
     * @author 28youth
     * @param  App\Models\EventLogGroup $group
     */
    public function __construct(EventLogGroupModel $group)
    {
        $this->group = $group;
    }

    /**
     * 初审事件.
     *
     * @author 28youth
     * @param  array $params
     * @return EventLogGroupModel
     */
    public function firstApprove(array $params): EventLogGroupModel
    {
        abort_if($this->group->first_approved_at !== null, 422, '初审已通过');

        $makeData = [
            'first_approve_remark' => $params['remark'] ?: '准予通过',
            'first_approved_at' => now(),
            'status_id' => 1,
        ];
        $this->group->first_approve_remark = $makeData['first_approve_remark'];
        $this->group->first_approved_at = $makeData['first_approved_at'];
        $this->group->status_id = $makeData['status_id'];
        $this->group->save();

        EventLogModel::where('event_log_group_id', $this->group->id)->update($makeData);
        
        return $this->group;
    }


    /**
     * 终审事件.
     *
     * @author 28youth
     * @param  array $params
     * @return mixed
     */
    public function finalApprove(array $params)
    {
        abort_if($this->group->final_approved_at !== null, 422, '终审已通过');

        $makeData = [
            'recorder_point' => $params['recorder_point'] ? : 0,
            'first_approver_point' => $params['first_approver_point'] ? : 0,
            'final_approve_remark' => $params['remark'],
            'final_approved_at' => now(),
            'status_id' => 2,
        ];
        $this->group->recorder_point = $makeData['recorder_point'];
        $this->group->first_approver_point = $makeData['first_approver_point'];;
        $this->group->final_approve_remark = $makeData['final_approve_remark'];
        $this->group->final_approved_at = $makeData['final_approved_at'];
        $this->group->status_id = $makeData['status_id'];
        $this->group->save();

        EventLogModel::where('event_log_group_id', $this->group->id)->update($makeData);

        $this->group->logs->map(function ($item) {
            // 事件参与者记录积分
            app(Event::class)->record($item);
        });

        return $this->group;
    }

    /**
     * 撤销事件.
     *
     * @author 28youth
     * @param  array $params
     * @return mixed
     */
    public function revokeApprove(array $params)
    {
        abort_if($this->group->status_id !== 2, 400, '不可作废未完成的奖扣事件');

        $makeData = [
            'recorder_point' => $this->group->recorder_point + -(int)$params['recorder_point'],
            'first_approver_point' => $this->group->first_approver_point + -(int)$params['first_approver_point'],
            'final_approver_point' => $this->group->final_approver_point + -(int)$params['final_approver_point'],
            'status_id' => -3
        ];
        $this->group->recorder_point = $makeData['recorder_point'];
        $this->group->first_approver_point = $makeData['first_approver_point'];
        $this->group->final_approver_point = $makeData['final_approver_point'];
        $this->group->status_id = $makeData['status_id'];
        $this->group->save();
        
        EventLogModel::where('event_log_group_id', $this->group->id)->update($makeData);
        
        $this->group->logs->map(function ($item, $params) {
            // 撤销操作
            app(Event::class)->revoke($item, $params);
        });
    }
}
