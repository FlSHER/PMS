<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PointTargetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:pointTarget';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *写功能代码
     * @return mixed
     */
    public function handle()
    {
        $target=\App\Models\PointManagementTargets::get();
        if($target->all() != false){
            foreach ($target as $k=>$v){
                $this->addTargetLogs($v);
            }
        }
        $hasStaff=\App\Models\PointManagementTargetHasStaff::with('targets.nextMonth')->get();
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
        $logsStaff->target_log_id=$all->targets->nextMonth['id'];
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
