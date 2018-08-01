<?php

namespace App\Http\Controllers\APIs;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\EventApprove;
use App\Models\Event as EventModel;
use App\Models\EventLog as EventLogModel;
use App\Http\Requests\API\StoreEventLogRequest;
use App\Models\EventLogGroup as EventLogGroupModel;
use App\Repositories\EventLogRepository as EventLogRepository;

class EventLogController extends Controller
{
    protected $eventlog;

    public function __construct(EventLogRepository $eventlogRepository)
    {
        $this->eventlog = $eventlogRepository;
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
            $this->eventlog,
            camel_case('get_' . $type . '_list')
        ]);

        return response()->json($items, 200);
    }

    /**
     * 创建事件日志.
     *
     * @author 28youth
     * @param  \App\Http\Requests\API\StoreEventLogRequest $request
     * @return mixed
     */
    public function store(StoreEventLogRequest $request)
    {
        $user = $request->user();
        $data = $request->all();

        try {
            \DB::beginTransaction();

            $group = $this->fillEventLogGroupData($request);
            $group->save();

            foreach ($data['events'] as $key => $val) {
                $event = EventModel::find($val['event_id']);
                $eventlog = new EventLogModel();

                $eventlog->fill($data);
                $eventlog->event_log_group_id = $group->id;
                $eventlog->event_id = $event->id;
                $eventlog->event_name = $event->name;
                $eventlog->event_type_id = $event->type_id;
                $eventlog->recorder_sn = $user->staff_sn;
                $eventlog->recorder_name = $user->realname;
                $eventlog->description = $val['description'];
                $eventlog->save();

                // 添加事件参与人
                $eventlog->participants()->createMany(
                    $val['participants']
                );
            }
            
            // 添加事件抄送人
            $group->addressees()->createMany($data['addressees']);

            // 自动审核
            $this->makeApprove($user, $group);

            \DB::commit();

        } catch (Exception $e) {

            \DB::rollBack();

            return response()->json(['message' => '服务器错误'], 500);
        }

        return response()->json($group->load('logs', 'addressees'), 201);
    }

    /**
     * 填充奖扣关联表数据.
     * 
     * @author 28youth
     * @param  array $data
     */
    public function fillEventLogGroupData($request) : EventLogGroupModel
    {
        $user = $request->user();

        $group = new EventLogGroupModel();
        $group = $group->fill($request->all());
        $group->recorder_sn = $user->staff_sn;
        $group->recorder_name = $user->realname;

        return $group;
    }

    /**
     * 自动审核判定.
     *
     * @return void
     */
    protected function makeApprove($user, $group)
    {
        $firstSn = $group->first_approver_sn;
        $finalSn = $group->final_approver_sn;
        $approveService = new EventApprove($group);

        // 记录人、初审人相同时 初审通过
        if ($user->staff_sn === $firstSn) {
            $approveService->firstApprove(['remark' => '初审人与记录人相同，系统自动通过。']);
        } elseif ($finalSn === $firstSn) {
            $approveService->firstApprove(['remark' => '初审人与终审人相同，系统自动通过。']);
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
     * @param  \App\Models\EventLogModel $eventlog
     * @return mixed
     */
    public function show(Request $request, EventLogModel $eventlog)
    {
        $eventlog->load('group.addressees', 'participants');
        $eventlog->executed_at = Carbon::parse($eventlog->executed_at)->toDateString();

        return response()->json($eventlog);
    }

    /**
     * 初审事件.
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\EventLogGroupModel $group
     * @return mixed
     */
    public function firstApprove(Request $request, EventLogGroupModel $group)
    {
        $approveService = new EventApprove($group);
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
     * @param  \App\Models\EventLogGroupModel $group
     * @return mixed
     */
    public function finalApprove(Request $request, EventLogGroupModel $group)
    {
        $group->getConnection()->transaction(function () use ($group, $request) {
            $approveService = new EventApprove($group);
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
     * @param  \App\Models\EventLogGroupModel $group
     * @return mixed
     */
    public function reject(Request $request, EventLogGroupModel $group)
    {
        $user = $request->user();

        if ($group->status_id === 0 && $user->staff_sn !== $group->first_approver_sn) {
            return response()->json([
                'message' => '非初审人无权驳回'
            ], 401);
        }

        if ($group->status_id === 1 && $user->staff_sn !== $group->final_approver_sn) {
            return response()->json([
                'message' => '非终审人无权驳回'
            ], 401);
        }

        $makeData = [
            'rejecter_sn' => $user->staff_sn,
            'rejecter_name' => $user->realname,
            'reject_remark' => $request->remark,
            'rejected_at' => now(),
            'status_id' => -1,
        ];
        $group->rejecter_sn = $makeData['rejecter_sn'];
        $group->rejecter_name = $makeData['rejecter_name'];
        $group->reject_remark = $makeData['reject_remark'];
        $group->rejected_at = now();
        $group->status_id = -1;
        $group->getConnection()->transaction(function () use ($group, $makeData) {
            $group->save();

            EventLogModel::where('event_log_group_id', $group->id)->update($makeData);
        });

        return response()->json($group, 201);
    }

    /**
     * 撤回事件.
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\EventLogGroupModel $eventlog
     * @return mixed
     */
    public function withdraw(Request $request, EventLogGroupModel $group)
    {
        $user = $request->user();

        if ($group->status_id === 2 || $group->status_id === -2) {
            return response()->json([
                'message' => '已终审或已撤回'
            ], 422);
        }

        if ($group->recorder_sn !== $user->staff_sn) {
            return response()->json([
                'message' => '非记录人无权撤回'
            ], 401);
        }
        $group->status_id = -2;
        $group->first_approved_at = null;
        $group->getConnection()->transaction(function () use ($group) {
            $group->save();

            EventLogModel::where('event_log_group_id', $group->id)->update(['status_id' => -2]);
        });

        return response()->json($group, 201);
    }

}