<?php

namespace App\Http\Controllers\APIs;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\EventApprove;
use App\Models\Event as EventModel;
use App\Repositories\EventLogRepository;
use App\Models\EventLog as EventLogModel;
use App\Http\Requests\API\StoreEventLogRequest;

class EventLogController extends Controller
{
    protected $eventLogRepository;

    public function __construct(EventLogRepository $eventlog)
    {
        $this->eventLogRepository = $eventlog;
    }

    /**
     * 获取事件日志分类列表.
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $type = $request->query('type', 'all');

        $items = app()->call([
            $this->eventLogRepository,
            camel_case('get_' . $type . '_list')
        ]);

        return response()->json($items, 200);
    }

    /**
     * 创建事件日志.
     *
     * @author 28youth
     * @param  \App\Http\Requests\API\StoreEventLogRequest $request
     * @param  \App\Models\EventLog $eventlog
     * @param  \App\Models\Event $event
     * @return mixed
     */
    public function store(StoreEventLogRequest $request, EventLogModel $eventlog)
    {
        $user = $request->user();
        $data = $request->all();
        $event = EventModel::find($request->event_id);

        $eventlog->fill($data);
        $eventlog->event_name = $event->name;
        $eventlog->event_type_id = $event->type_id;
        $eventlog->recorder_sn = $user->staff_sn;
        $eventlog->recorder_name = $user->realname;
        $addressees = $this->mergeAddressees($event->default_cc_addressees, $data['addressees']);

        $eventlog->getConnection()->transaction(function () use ($eventlog, $data, $user, $addressees) {
            $eventlog->save();
            $eventlog->addressee()->createMany($addressees);
            $eventlog->participant()->createMany($data['participants']);
            $this->makeApprove($user, $eventlog);
        });

        return response()->json(['message' => '添加成功'], 201);
    }

    /**
     * 自动审核判定.
     *
     * @return void
     */
    protected function makeApprove($user, $logModel)
    {
        $firstSn = $logModel->first_approver_sn;
        $finalSn = $logModel->final_approver_sn;
        $approveService = new EventApprove($logModel);

        // 记录人、初审人相同时 初审通过
        if ($user->staff_sn === $firstSn) {
            $approveService->firstApprove(['remark' => '初审人与记录人相同，系统自动通过。']);
        }
        
        // 记录人等于终审人且等于初审人,终审通过
        if ($user->staff_sn === $finalSn && $user->staff_sn === $firstSn) {
            $approveService->finalApprove(['remark' => '终审人与记录人相同，系统自动通过。']);
        }
    }

    /**
     * 合并抄送人.
     * 
     * @author 28youth
     */
    public function mergeAddressees(...$params)
    {
        $addressees = array_merge(
            array_filter((array)$params[0]),
            array_filter((array)$params[1])
        );
        $tmpArr = [];
        foreach ($addressees as $key => $value) {
            if (in_array($value['staff_sn'], $tmpArr)) {
                unset($addressees[$key]);
            } else {
                $tmpArr[] = $value['staff_sn'];
            }
        }

        return $addressees;
    }

    /**
     * 获取事件详情.
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\EventLog $eventlog
     * @return mixed
     */
    public function show(Request $request, EventLogModel $eventlog)
    {
        $eventlog->load('participant', 'addressee', 'event');
        $eventlog->executed_at = Carbon::parse($eventlog->executed_at)->toDateString();

        return response()->json($eventlog);
    }

    /**
     * 初审事件.
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\EventLog $eventlog
     * @return mixed
     */
    public function firstApprove(Request $request, EventLogModel $eventlog)
    {
        $approveService = new EventApprove($eventlog);

        $response = $approveService->firstApprove([
            'remark' => $request->remark
        ]);

        return response()->json($response, 201);
    }

    /**
     * 终审事件.
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\EventLog $eventlog
     * @return mixed
     */
    public function finalApprove(Request $request, EventLogModel $eventlog)
    {
        $eventlog->getConnection()->transaction(function () use ($eventlog, $request) {
            $approveService = new EventApprove($eventlog);
            $approveService->finalApprove([
                'first_approver_point' => $request->first_approver_point,
                'recorder_point' => $request->recorder_point,
                'remark' => $request->remark
            ]);
        });

        return response()->json(['message' => '操作成功'], 201);
    }

    /**
     *  驳回事件.
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\EventLog $eventlog
     * @return mixed
     */
    public function reject(Request $request, EventLogModel $eventlog)
    {
        $user = $request->user();

        if ($eventlog->status_id === 0 && $user->staff_sn !== $eventlog->first_approver_sn) {
            return response()->json([
                'message' => '非初审人无权驳回'
            ], 401);
        }

        if ($eventlog->status_id === 1 && $user->staff_sn !== $eventlog->final_approver_sn) {
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
     * @param  \Illuminate\Http\Request $request
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