<?php 

namespace App\Http\Controllers\APIs;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\PointLogger;
use App\Repositories\EventLogRepository;
use App\Models\EventType as EventTypeMdel;
use App\Models\Event as EventModel;
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
     * 获取事件日志分类列表.
     * 
     * @author 28youth
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $type = $request->query('type', 'all');

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

            case 'addressee':
                $items = $this->eventLogRepository->getAddresseeList($request);
                break;
                  
            default:
                $items = $this->eventLogRepository->getPaginateList($request);
                break;
        }

        return response()->json($items, 200);
    }

    /**
     * 获取奖扣审核列表.
     * 
     * @author 28youth
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    public function audit(Request $request)
    {
        $items = $this->eventLogRepository->getProcessingList($request);

        return response()->json($items);
    }

    /**
     * 获取事件分类列表.
     * 
     * @author 28youth
     * @param  \App\Models\EventType $category
     * @return mixed　
     */
    public function cates(EventTypeMdel $category)
    {
        $cates = $category->orderBy('sort', 'asc')->get();

        return response()->json($cates);
    }

    /**
     * 创建事件日志.
     * 
     * @author 28youth
     * @param  \App\Http\Requests\API\StoreEventLogRequest $request
     * @param  \App\Models\EventLog  $eventlog
     * @param  \App\Models\Event  $event
     * @return mixed
     */
    public function store(StoreEventLogRequest $request, EventLogModel $eventlog)
    {
        $user = $request->user();
        $datas = $request->all();
        $event = EventModel::find($request->event_id);

        $eventlog->fill($datas);
        $eventlog->event_name = $event->name;
        $eventlog->event_type_id = $event->type_id;
        $eventlog->recorder_sn = $user->staff_sn;
        $eventlog->recorder_name = $user->realname;

        $eventlog->getConnection()->transaction(function() use ($eventlog, $datas) {
            $eventlog->save();
            $eventlog->addressee()->createMany($datas['addressees']);
            $eventlog->participant()->createMany($datas['participants']);
        });

        return response()->json(['message' => '添加成功'], 201);
    }

    /**
     * 获取事件详情.
     * 
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\EventLog  $eventlog
     * @return mixed
     */
    public function show(Request $request, EventLogModel $eventlog)
    {
        $this->pointLoggerService->logEventPoint($eventlog);
        $eventlog->load('participant', 'addressee');

        return response()->json($eventlog);
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

        if ($eventlog->first_approved_at !== null) {
            return response()->json([
                'message' => '初审已通过'
            ], 422);
        }
        $eventlog->first_approve_remark = $request->remark;
        $eventlog->first_approved_at = Carbon::now();
        $eventlog->status_id = 1;
        $eventlog->save();

        return response()->json($eventlog, 201);
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

        if ($eventlog->final_approved_at !== null) {
            return response()->json([
                'message' => '终审已通过'
            ], 422);
        }
        $eventlog->recorder_point = $request->recorder_point;
        $eventlog->first_approver_point = $request->first_approver_point;
        $eventlog->final_approve_remark = $request->remark;
        $eventlog->final_approved_at = Carbon::now();
        $eventlog->status_id = 2;

        $eventlog->getConnection()->transaction(function () use ($eventlog, $participant) {
            $eventlog->save();
            // 事件参与者记录积分
            $this->pointLoggerService->logEventPoint($eventlog);
        });
        
        return response()->json($eventlog, 201);
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
        $eventlog->reject_remark = $request->remark;
        $eventlog->rejected_at = Carbon::now();
        $eventlog->status_id = -1;
        $eventlog->save();

        return response()->json($eventlog, 201);
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

        if ($eventlog->status_id === 2 || $eventlog->status_id === -2) {
            return response()->json([
                'message' => '已终审或已撤回'
            ], 422);
        }

        if ($eventlog->recorder_sn !== $user->staff_sn) {
            return response()->json([
                'message' => '非记录人无权撤回'
            ], 401);
        }

        $eventlog->status_id = -2;
        $eventlog->save();

        return response()->json($eventlog, 201);
    }

}