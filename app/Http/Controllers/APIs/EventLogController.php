<?php 

namespace App\Http\Controllers\APIs;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\PointLogger;
use App\Repositories\EventLogRepository;
use App\Models\Events as EventsModel;
use App\Models\EventLog as EventLogModel;
use App\Http\Requests\API\StoreEventLogRequest;

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
     * 获取事件日志列表.
     * 
     * @author 28youth
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $type = $request->query('type');
        switch ($type) {
            case 'participant':
                $items = $this->eventLogRepository->getParticipantList($request);
                break;

            case 'recorded':
                $items = $this->eventLogRepository->getRecordedList($request);
                break;

            case 'approved':
                $items = $this->eventLogRepository->getApprovedList($request);
                break;

            case 'carbon_copy':
                $items = $this->eventLogRepository->getCopyList($request);
                break;
                  
            default:
                $items = $this->eventLogRepository->getPaginateList($request);
                break;
        }

        return response()->json($items, 200);
    }

    /**
     * 创建事件日志.
     * 
     * @author 28youth
     * @param  \App\Http\Requests\API\StoreEventLogRequest $request
     * @param  \App\Models\EventLog  $eventlog
     * @param  \App\Models\Events  $event
     * @return mixed
     */
    public function store(StoreEventLogRequest $request, EventLogModel $eventlog, EventsModel $event)
    {
        $user = $request->user();
        $datas = $this->getRequestOnly($request);

        foreach ($datas as $key => $data) {
            $eventlog->{$key} = $data;
        }

        $eventlog->event_type_id = $event->type_id;
        $eventlog->event_name = $event->name;
        $eventlog->recorder_sn = $user->staff_sn;
        $eventlog->recorder_name = $user->realname;
        $event->logs()->save($eventlog);

        return response()->json($eventlog, 201);
    }

    public function getRequestOnly(Request $request)
    {
        return $request->only(
            'point_a',
            'point_b',
            'description',
            'first_approver_sn',
            'first_approver_name',
            'first_approve_remark',
            'final_approver_sn',
            'final_approver_name',
            'final_approve_remark',
            'recorder_point'
        );
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
        $eventlog->first_approver_remark = $request->remark;
        $eventlog->first_approver_at = Carbon::now();
        $eventlog->status_id = 1;
        $eventlog->save();

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
        $eventlog->final_approver_remark = $request->remark;
        $eventlog->final_approver_at = Carbon::now();
        $eventlog->status_id = 2;

        $participant = $this->eventLogRepository->getParticipant($eventlog);

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

        if ($eventlog->status_id === 1 && $user->staff_sn !== $eventlog->first_approver_sn) {
            return response()->json([
                'message' => '非初审人无权驳回'
            ], 401);
        }

        if ($eventlog->status_id === 2 && $user->staff_sn !== $eventlog->final_approver_sn) {
            return response()->json([
                'message' => '非终审人无权驳回'
            ], 401);
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

        if ($eventlog->status_id === 2) {
            return response()->json([
                'message' => '已终审不能撤回'
            ], 422);
        }

        if ($eventlog->recorder_sn !== $user->staff_sn) {
            return response()->json([
                'message' => '非记录人无权撤回'
            ], 401);
        }

        $eventlog->status_id = -2;
        $eventlog->save();

        return response()->json([
            'message' => '撤回成功'
        ], 201);
    }

}