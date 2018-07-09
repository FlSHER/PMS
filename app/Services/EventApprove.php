<?php

namespace App\Services;

use Carbon\Carbon;
use App\Services\Point\Types\Event;
use App\Models\EventLog as EventLogModel;

/**
 * 审核服务.
 */
class EventApprove
{	
	/**
	 * @var App\Models\EventLog
	 */
	protected $logModel;

	/**
	 * 注入事件日志模型.
	 * 
	 * @author 28youth
	 * @param  App\Models\EventLog $eventlog
	 */
	public function __construct(EventLogModel $eventlog)
	{
		$this->logModel = $eventlog;
	}

	/**
	 * 初审事件.
	 * 
	 * @author 28youth
	 * @param  array  $params
	 * @return EventLogModel
	 */
	public function firstApprove(array $params): EventLogModel
	{
		if ($this->logModel->first_approved_at !== null) {
            return response()->json([
                'message' => '初审已通过'
            ], 422);
        }
        $this->logModel->first_approve_remark = $params['remark'] ?? '准予通过';
        $this->logModel->first_approved_at = Carbon::now();
        $this->logModel->status_id = 1;
        $this->logModel->save();

        return $this->logModel;
	}


	/**
     * 终审事件.
     *
     * @author 28youth
     * @param  array  $params
     * @return mixed
     */
    public function finalApprove(array $params): EventLogModel
    {
        if ($this->logModel->final_approved_at !== null) {
            return response()->json([
                'message' => '终审已通过'
            ], 422);
        }
        $this->logModel->recorder_point = $params['recorder_point'] ?: 0;
        $this->logModel->first_approver_point = $params['first_approver_point'] ?: 0;
        $this->logModel->final_approve_remark = $params['remark'];
        $this->logModel->final_approved_at = Carbon::now();
        $this->logModel->status_id = 2;
        $this->logModel->save();
        
        // 事件参与者记录积分
    	app(Event::class)->record($this->logModel);
        
        return $this->logModel;
    }

}
