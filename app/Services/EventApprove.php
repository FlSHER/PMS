<?php

namespace App\Services;

use Carbon\Carbon;
use App\Services\Point\Types\Event;
use App\Models\EventLog as EventModel;
use App\Models\EventLogConcern as EventLogConcernModel;

/**
 * 审核服务.
 */
class EventApprove
{
    /**
     * @var App\Models\EventLogConcern
     */
    protected $concern;

    /**
     * 注入事件日志模型.
     *
     * @author 28youth
     * @param  App\Models\EventLogConcern $concern
     */
    public function __construct(EventLogConcernModel $concern)
    {
        $this->concern = $concern;
    }

    /**
     * 初审事件.
     *
     * @author 28youth
     * @param  array $params
     * @return EventLogConcernModel
     */
    public function firstApprove(array $params): EventLogConcernModel
    {
        if ($this->concern->first_approved_at !== null) {
            return response()->json([
                'message' => '初审已通过'
            ], 422);
        }
        $makeData = [
            'first_approve_remark' => $params['remark'] ?: '准予通过',
            'first_approved_at' => now(),
            'status_id' => 1,
        ];
        $this->concern->first_approve_remark = $makeData['first_approve_remark'];
        $this->concern->first_approved_at = $makeData['first_approved_at'];
        $this->concern->status_id = $makeData['status_id'];
        $this->concern->save();

        EventModel::where('concern_id', $this->concern->id)->update($makeData);
        
        return $this->concern;
    }


    /**
     * 终审事件.
     *
     * @author 28youth
     * @param  array $params
     * @return mixed
     */
    public function finalApprove(array $params): EventLogConcernModel
    {
        if ($this->concern->final_approved_at !== null) {
            return response()->json([
                'message' => '终审已通过'
            ], 422);
        }
        $makeData = [
            'recorder_point' => empty($params['recorder_point']) ? 0 : $params['recorder_point'],
            'first_approver_point' => empty($params['first_approver_point']) ? 0 : $params['first_approver_point'],
            'final_approve_remark' => $params['remark'],
            'final_approved_at' => now(),
            'status_id' => 2,
        ];
        $this->concern->recorder_point = $makeData['recorder_point'];
        $this->concern->first_approver_point = $makeData['first_approver_point'];;
        $this->concern->final_approve_remark = $makeData['final_approve_remark'];
        $this->concern->final_approved_at = $makeData['final_approved_at'];
        $this->concern->status_id = $makeData['status_id'];
        $this->concern->save();

        EventModel::where('concern_id', $this->concern->id)->update($makeData);

        $this->concern->logs->map(function ($item) {
            // 事件参与者记录积分
            app(Event::class)->record($item);
        });

        return $this->concern;
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
        if ($this->concern->status_id !== 2) {
            abort(400, '不可作废未完成的奖扣事件');
        }
        $makeData = [
            'recorder_point' => $this->concern->recorder_point + -(int)$params['recorder_point'],
            'first_approver_point' => $this->concern->first_approver_point + -(int)$params['first_approver_point'],
            'final_approver_point' => $this->concern->final_approver_point + -(int)$params['final_approver_point'],
            'status_id' => -3
        ];
        $this->concern->recorder_point = $makeData['recorder_point'];
        $this->concern->first_approver_point = $makeData['first_approver_point'];
        $this->concern->final_approver_point = $makeData['final_approver_point'];
        $this->concern->status_id = $makeData['status_id'];
        $this->concern->save();
        
        EventModel::where('concern_id', $this->concern->id)->update($makeData);
        
        $this->concern->logs->map(function ($item, $params) {
            // 撤销操作
            app(Event::class)->revoke($item, $params);
        });
    }
}
