<?php 

namespace App\Http\Controllers\APIs;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\EventLog as EventLogModel;
use Illuminate\Support\Facades\Auth;
use App\Repositories\EventLogRepository;
use App\Services\PointLogger;

class EventLogController extends Controller
{
    protected $pointLoggerService;

    protected $eventLogRepository;

    public function __construct(PointLogger $pointLogger, EventLogRepository $eventlog)
    {
        $this->pointLoggerService = $pointLogger;
        $this->eventLogRepository = $eventlog;
    }

    /**
     * 初审事件.
     * 
     * @author 28youth
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EventLog $eventlog
     * @return mixed
     */
    public function firstApprove(Request $request, EventLogModel $eventlog)
    {
        $user = $request->user();

        if ($eventlog->first_approver_at !== null) {
            return response()->json([
                'message' => '初审已通过'
            ], 422);
        }

        $eventlog->first_approver_sn = $user->staff_sn;
        $eventlog->first_approver_name = $user->realname;
        $eventlog->first_approver_remark = $request->remark;
        $eventlog->first_approver_at = Carbon::now();
        $eventlog->status_id = 1;

        return response()->json([
            'message' => '初审成功'
        ], 201);
    }

    /**
     * 终审事件.
     * 
     * @author 28youth
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EventLog $eventlog
     * @return mixed
     */
    public function finalApprove(Request $request, EventLogModel $eventlog)
    {
        $user = $request->user();

        if ($eventlog->final_approver_at !== null) {
            return response()->json([
                'message' => '终审已通过'
            ], 422);
        }

        $eventlog->final_approver_sn = $user->staff_sn;
        $eventlog->final_approver_name = $user->realname;
        $eventlog->final_approver_remark = $request->remark;
        $eventlog->final_approver_at = Carbon::now();
        $eventlog->status_id = 2;

        $participant = $this->eventLogRepository->getParticipant();

        $eventlog->getConnection()->transaction(function () use ($eventlog, $participant) {
            $eventlog->save();
            // 事件参与者记录积分
            $this->pointLoggerService->logEventPoint($participant, $eventlog);
        });
        
        return response()->json([
            'message' => '终审成功'
        ], 201);
    }

    /**
     *  驳回事件.
     *  
     * @author 28youth
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EventLog $eventlog
     * @return mixed
     */
    public function reject(Request $request, EventLogModel $eventlog)
    {
        $user = $request->user();

        if ($eventlog->first_approver_at === null || $eventlog->final_approver_at === null) {
            return response()->json([
                'message' => '未审核不能驳回'
            ], 422);
        }

        $eventlog->rejecter_sn = $user->staff_sn;
        $eventlog->rejecter_name = $user->realname;
        $eventlog->rejecter_remark = $request->remark;
        $eventlog->rejecter_at = Carbon::now();
        $eventlog->status_id = -1;
        $eventlog->save();

        return response()->json([
            'message' => '驳回成功'
        ], 201);
    }

    /**
     * 撤回事件.
     * 
     * @author 28youth
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\EventLog $eventlog
     * @return mixed
     */
    public function withdraw(Request $request, EventLogModel $eventlog)
    {
        $user = $request->user();

        $eventlog->status_id = -2;
        $eventlog->save();

        return response()->json([
            'message' => '撤回成功'
        ], 201);
    }

}