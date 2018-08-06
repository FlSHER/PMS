<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\ArtisanCommandLog;
use App\Services\Point\Types\Event;
use App\Models\EventLog as EventLogModel;
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
        $this->group->status_id = -3;
        $this->group->save();

        // 同步状态到事件.
        EventLogModel::where('event_log_group_id', $this->group->id)->update([
            'status_id' => -3
        ]);

        // 判断事件是否统计(已统计进入更新统计)
        $command = $this->preNode();
        $changeAt = Carbon::parse($this->group->executed_at);
        if ($command && Carbon::parse($command->created_at)->gt($changeAt)) {
            $params = [
                [
                    'staff_sn' => $this->group->recorder_sn, 
                    'point_b' => $params['recorder_point'], 
                    'change_at' => $changeAt, 
                    'point_a' => 0
                ],
                [
                    'staff_sn' => $this->group->first_approver_sn, 
                    'point_b' => $params['first_approver_point'], 
                    'change_at' => $changeAt, 
                    'point_a' => 0
                ],
                [
                    'staff_sn' => $this->group->final_approver_sn, 
                    'point_b' => $params['final_approver_point'], 
                    'change_at' => $changeAt, 
                    'point_a' => 0
                ]
            ];
            $this->group->logs->map(function ($item) use (&$params, $changeAt) {
                $item->participants->map(function ($val) use (&$params, $changeAt) {
                    array_push($params, [
                        'change_at' => $changeAt,
                        'staff_sn' => $val->staff_sn,
                        'point_a' => round($val->point_a * $val->count),
                        'point_b' => round($val->point_b * $val->count)
                    ]);
                });
            });
            $merge = $this->mergeData($params);

            array_walk($merge, [$this, 'handleStatistic']);
        }
    }

    /**
     * 撤销更新统计数据.
     * 
     * @author 28youth
     * @param  array $log 
     * @param  int $staffsn
     * @return void
     */
    public function handleStatistic($log, $staffsn)
    {
        $isCurrent = $log['change_at']->isCurrentMonth();

        // 更新当月统计
        if ($isCurrent) {
            $logModel = StatisticModel::where('staff_sn', $staffsn)->first();
            $logModel->point_a -=  $log['point_a'];
            $logModel->point_a_total -=  $log['point_a'];
            $logModel->point_b_monthly -=  $log['point_b'];
            $logModel->point_b_total -=  $log['point_b'];
            $logModel->save();

        // 更新其他月份统计
        } else {
            $logModel = StatisticLogModel::query()
                ->where('date', $log['change_at'])
                ->where('staff_sn', $staffsn)
                ->first();
            $logModel->point_a -=  $log['point_a'];
            $logModel->point_a_total -=  $log['point_a'];
            $logModel->point_b_monthly -=  $log['point_b'];
            $logModel->point_b_total -=  $log['point_b'];
            $logModel->save();
        }
    }

    /**
     * 合并重复的事件参与人.
     * 
     * @author 28youth
     */
    public function mergeData($params)
    {
        $tmpSn = [];
        $tmpArr = [];
        foreach ($params as $key => $v) {
            if (in_array($v['staff_sn'], $tmpSn)) {
                $tmpArr[$v['staff_sn']]['change_at'] = $v['change_at'];
                if (isset($tmpArr[$v['staff_sn']])) {
                    $tmpArr[$v['staff_sn']]['point_b'] += (int)$v['point_b'];
                    $tmpArr[$v['staff_sn']]['point_a'] += (int)$v['point_a'];
                } else {
                    $tmpArr[$v['staff_sn']]['point_b'] = (int)$v['point_b'];
                    $tmpArr[$v['staff_sn']]['point_a'] = (int)$v['point_a'];
                }
            } else {
                if (!isset($tmpArr[$v['staff_sn']])) {
                    $tmpArr[$v['staff_sn']]['change_at'] = $v['change_at'];
                    $tmpArr[$v['staff_sn']]['point_b'] = (int)$v['point_b'];
                    $tmpArr[$v['staff_sn']]['point_a'] = (int)$v['point_a'];
                }
                $tmpSn[] = $v['staff_sn'];
            }
        }

        return $tmpArr;
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
