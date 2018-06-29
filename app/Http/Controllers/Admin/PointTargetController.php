<?php

namespace App\Http\Controllers\Admin;

use function App\monthBetween;
use App\Services\Admin\PointTargetService;
use Illuminate\Http\Request;

class PointTargetController extends Controller
{
    protected $target;

    public function __construct(PointTargetService $targets)
    {
        $this->target = $targets;
    }

    /**
     * @param Request $request
     * 获取列表
     */
    public function targets()
    {
        return $this->target->getPointList();
    }

    /**
     * @param Request $request
     * @return \App\Models\PointManagementTargets|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|null|object
     * 奖扣详细
     */
    public function targetsDetails(Request $request)
    {
        return $this->target->getSingle($request);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * 添加下月奖扣
     */
    public function storeTarget(Request $request)
    {
        $this->targetFormVerify($request);
        return $this->target->addTarget($request);
    }

    /**
     * @param Request $request
     * @return mixed
     * 修改奖扣指标
     */
    public function editTarget(Request $request)
    {
        $this->targetFormVerify($request);
        return $this->target->editTarget($request);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * 批量同步人员
     */
    public function editStaff(Request $request)

    {
        $this->staffVerify($request);
        return $this->target->editStaff($request);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * 删除下月人员
     */
    public function deleteTarget(Request $request)
    {
        return $this->target->deleteMission($request);
    }

    /**
     * @param $request
     * 指标字段验证
     */
    public function targetFormVerify($request)
    {
        $this->validate($request, [
            'name' => 'required',
            'point_b_awarding_target' => 'required|numeric',
            'point_b_deducting_target' => 'required|numeric',
            'event_count_target' => 'required|numeric',
            'deducting_percentage_target' => 'required|numeric',
        ], [], [
            'name' => '指标名称',
            'point_b_awarding_target' => '奖分指标',
            'point_b_deducting_target' => '扣分指标',
            'event_count_target' => '奖分次数指标',
            'deducting_percentage_target' => '奖扣比例指标',
        ]);
    }

    public function staffVerify($request)
    {
        $this->validate($request, [
            '.*.staff_sn' => 'required',
            '.*.staff_name' => 'required|numeric',
        ], [], [
            '.*.staff_sn' => '人员编号',
            '.*.staff_name' => '人员姓名',
        ]);
    }

    /**
     * @return array
     * 做测试用的
     */
    public function test()
    {
        $target=\App\Models\PointManagementTargets::get();
        if($target->all() != false){
            foreach ($target as $k=>$v){
                $this->addTargetLogs($v);
            }
        }
        $hasStaff=\App\Models\PointManagementTargetHasStaff::with(['targets.targetLogs' => function($query){
//            $query->whereBetween('date',monthBetween());  monthBetween()在1号的00:00:00  不会包含在当月
            $query->whereYear('date',date('Y'))->whereMonth('date',date('m'));
        }])->get();
        if($hasStaff->all() != false){
            foreach ($hasStaff as $k=>$v){
                $this->addStaffLogs($v);
            }
        }
    }

    public function addTargetLogs($v)
    {
        $logs=new \App\Models\PointManagementTargetLogs();
        $logs->target_id=$v['id'];
        $logs->date=date('Y-m-1');
        $logs->point_b_awarding_target=$v['point_b_awarding_target'];
        $logs->point_b_deducting_target=$v['point_b_deducting_target'];
        $logs->event_count_target=$v['event_count_target'];
        $logs->deducting_percentage_target=$v['deducting_percentage_target'];
        $logs->save();
    }

    public function addStaffLogs($all)
    {
        $logsStaff=new \App\Models\PointManagementTargetLogHasStaff();
        $oaStaff=app('api')->withRealException()->getStaff($all->staff_sn);
        $logsStaff->target_id=$all->targets['id'];
        $logsStaff->target_log_id=$all->targets->targetLogs[0]['id'];
        $logsStaff->date=date('Y-m-1');
        $logsStaff->staff_sn=$oaStaff['staff_sn'];
        $logsStaff->staff_name=$oaStaff['realname'];
        $logsStaff->brand_id=$oaStaff['brand_id'];
        $logsStaff->brand_name=$oaStaff['brand']['name'];
        $logsStaff->department_id=$oaStaff['department_id'];
        $logsStaff->department_name=$oaStaff['department']['full_name'];
        if(isset($oaStaff['shop']['name'])){
            $logsStaff->shop_sn=$oaStaff['shop']['shop_sn'];
            $logsStaff->shop_name=$oaStaff['shop']['name'];
        }
        $logsStaff->point_b_awarding_result=$all->targets['point_b_awarding_target'];
        $logsStaff->point_b_deducting_result=$all->targets['point_b_deducting_target'];
        $logsStaff->event_count_result=$all->targets['event_count_target'];
        $logsStaff->deducting_percentage_result=$all->targets['deducting_percentage_target'];
        $logsStaff->save();
    }
}