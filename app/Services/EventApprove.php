<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\ArtisanCommandLog;
use App\Services\Point\Types\Event;
use App\Models\EventLog as EventLogModel;
use App\Models\PointLog as PointLogModel;
use App\Models\PointType as PointTypeModel;
use App\Models\EventLogGroup as EventLogGroupModel;
use App\Models\PersonalPointStatistic as StatisticModel;
use App\Models\PersonalPointStatisticLog as StatisticLogModel;

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
            'first_approve_remark' => !empty($params['remark']) ? $params['remark'] : '',
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
            'recorder_point' => !empty($params['recorder_point']) ? $params['recorder_point'] : 0,
            'first_approver_point' => !empty($params['first_approver_point']) ? $params['first_approver_point'] : 0,
            'final_approve_remark' => !empty($params['remark']) ? $params['remark'] : '',
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

        // 修改事件分组状态.
        $this->group->revoke_remark = request()->remark;
        $this->group->status_id = -3;
        $this->group->save();

        // 同步状态到事件.
        EventLogModel::where('event_log_group_id', $this->group->id)->update([
            'revoke_remark' => request()->remark,
            'status_id' => -3
        ]);

        $log_ids = $this->group->logs->pluck('id');
        // 修改积分状态为已撤销
        PointLogModel::whereIn('source_foreign_key', $log_ids)
            ->where('source_id', 2)
            ->update(['is_revoke' => 1]);

        $logs = PointLogModel::query()
            ->whereIn('source_foreign_key', $log_ids)
            ->where('source_id', 2)
            ->get();

        // 判断事件是否统计(已统计进入更新统计)
        $command = $this->preNode();
        $createAt = Carbon::parse($logs->last()->created_at);
        if ($command && Carbon::parse($command->created_at)->gt($createAt)) {
            $logs->map(function ($item) {
                // 非本月生效的积分日志
                if (!Carbon::parse($item->changed_at)->isCurrentMonth()) {

                    $this->updateMonthlyStatistic($item);
                } else {
                    // 统计当月分
                    $this->updateDailyStatistic($item);
                }
            });
        }
    }

    /**
     * 更新月结数据.
     *
     * @author 28youth
     * @param  [type] $log [description]
     * @return [type]      [description]
     */
    public function updateMonthlyStatistic($log)
    {
        $logModel = StatisticLogModel::query()
            ->where('date', $log->change_at)
            ->where('staff_sn', $log->staff_sn)
            ->first();
        $logModel->point_a -= $log->point_a;
        $logModel->point_a_total -= $log->point_a;
        $logModel->source_a_monthly = $this->makeSource($logModel->source_a_monthly, $log, 'source_a_monthly');
        $logModel->source_a_total = $this->makeSource($logModel->source_a_total, $log, 'source_a_total');

        $logModel->point_b_monthly -= $log->point_b;
        $logModel->point_b_total -= $log->point_b;
        $logModel->source_b_monthly = $this->makeSource($logModel->source_b_monthly, $log, 'source_b_monthly');
        $logModel->source_b_total = $this->makeSource($logModel->source_b_total, $log, 'source_b_total');
        $logModel->save();
    }

    /**
     * 更新日结数据.
     *
     * @author 28youth
     * @param  [type] $log [description]
     * @return [type]      [description]
     */
    public function updateDailyStatistic($log)
    {
        $logModel = StatisticModel::where('staff_sn', $log->staff_sn)->first();
        $logModel->point_a -= $log->point_a;
        $logModel->point_a_total -= $log->point_a;
        $logModel->source_a_monthly = $this->makeSource($logModel->source_a_monthly, $log, 'source_a_monthly');
        $logModel->source_a_total = $this->makeSource($logModel->source_a_total, $log, 'source_a_total');

        $logModel->point_b_monthly -= $log->point_b;
        $logModel->point_b_total -= $log->point_b;
        $logModel->source_b_monthly = $this->makeSource($logModel->source_b_monthly, $log, 'source_b_monthly');
        $logModel->source_b_total = $this->makeSource($logModel->source_b_total, $log, 'source_b_total');
        $logModel->save();
    }

    /**
     * 撤销更新分类统计分.
     *
     * @author 28youth
     * @param  [type] $source 来源统计
     * @param  [type] $log    积分记录
     * @return array
     */
    public function makeSource($source, $log, $type)
    {
        foreach ($source as $k => &$v) {
            if (in_array($type, ['source_a_monthly', 'source_a_total'])) {
                $v['point'] -= $log->point_a;
                if ($log->point_a >= 0) {
                    $v['add_point'] -= $log->point_a;
                } else {
                    $v['sub_point'] -= $log->point_a;
                }
            } elseif (in_array($type, ['source_b_monthly', 'source_b_total'])) {
                $v['point'] -= $log->point_b;
                if ($log->point_b >= 0) {
                    $v['add_point'] -= $log->point_b;
                } else {
                    $v['sub_point'] -= $log->point_b;
                }
            }
        }
        return $source;
    }

     /**
     * 上次结算节点信息.
     *
     * @author 28youth
     * @return \App\Models\ArtisanCommandLog|null
     */
    protected function preNode()
    {
        return ArtisanCommandLog::query()
            ->bySn('pms:calculate-staff-point')
            ->where('status', 1)
            ->latest('id')
            ->first();
    }

    /**
     * 撤销事件(旧).
     *
     * @author 28youth
     * @param  array $params
     * @return mixed
     */
    /*public function revokeApprove(array $params)
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
    }*/
}
