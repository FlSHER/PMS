<?php

namespace App\Http\Controllers\APIs;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\EventApprove;
use App\Models\Event as EventModel;
use App\Models\EventLog as EventLogModel;
use App\Http\Requests\API\StoreEventLogRequest;
use App\Models\EventLogConcern as EventLogConcernModel;
use App\Repositories\EventLogConcern as EventLogConcernRepository;

class EventLogController extends Controller
{
    protected $concernRepository;

    public function __construct(EventLogConcernRepository $concernRepository)
    {
        $this->concernRepository = $concernRepository;
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
            $this->concernRepository,
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
        $eventlog->fill($data);

        try {
            \DB::beginTransaction();

            $concern = $this->fillEventLogConcernData($request);
            $concern->save();

            foreach ($data['events'] as $key => $val) {
                $event = EventModel::find($val['event_id']);

                $eventlog->concern_id = $concern->id;
                $eventlog->event_id = $event->id;
                $eventlog->event_name = $event->name;
                $eventlog->event_type_id = $event->type_id;
                $eventlog->recorder_sn = $user->staff_sn;
                $eventlog->recorder_name = $user->realname;
                $eventlog->description = $val['description'];
                $eventlog->save();

                // 添加事件参与人
                $eventlog->participant()->createMany(
                    $val['participants']
                );
            }
            
            // 添加事件抄送人
            $concern->addressees()->createMany($data['addressees']);

            // 自动审核
            $this->makeApprove($user, $concern);

            \DB::commit();

        } catch (Exception $e) {

            \DB::rollBack();

            return response()->json(['message' => '服务器错误'], 500);
        }

        return response()->json(['message' => '添加成功'], 201);
    }

    /**
     * 填充奖扣关联表数据.
     * 
     * @author 28youth
     * @param  array $data
     */
    public function fillEventLogConcernData($request) : EventLogConcernModel
    {
        $user = $request->user();

        $concern = new EventLogConcernModel();
        $concern = $concern->fill($request->all());
        $concern->recorder_sn = $user->staff_sn;
        $concern->recorder_name = $user->realname;

        return $concern;
    }

    /**
     * 自动审核判定.
     *
     * @return void
     */
    protected function makeApprove($user, $concern)
    {
        $firstSn = $concern->first_approver_sn;
        $finalSn = $concern->final_approver_sn;
        $approveService = new EventApprove($concern);

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
     * @param  \App\Models\EventLogConcernModel $concern
     * @return mixed
     */
    public function show(Request $request, EventLogConcernModel $concern)
    {
        $concern->load('addressees', 'logs.participant', 'logs.event');
        $concern->executed_at = Carbon::parse($concern->executed_at)->toDateString();

        return response()->json($concern);
    }

    /**
     * 初审事件.
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\EventLogConcernModel $concern
     * @return mixed
     */
    public function firstApprove(Request $request, EventLogConcernModel $concern)
    {
        $approveService = new EventApprove($concern);
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
     * @param  \App\Models\EventLogConcernModel $concern
     * @return mixed
     */
    public function finalApprove(Request $request, EventLogConcernModel $concern)
    {
        $concern->getConnection()->transaction(function () use ($concern, $request) {
            $approveService = new EventApprove($concern);
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
     * @param  \App\Models\EventLogConcernModel $concern
     * @return mixed
     */
    public function reject(Request $request, EventLogConcernModel $concern)
    {
        $user = $request->user();

        if ($concern->status_id === 0 && $user->staff_sn !== $concern->first_approver_sn) {
            return response()->json([
                'message' => '非初审人无权驳回'
            ], 401);
        }

        if ($concern->status_id === 1 && $user->staff_sn !== $concern->final_approver_sn) {
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
        $concern->rejecter_sn = $makeData['rejecter_sn'];
        $concern->rejecter_name = $makeData['rejecter_name'];
        $concern->reject_remark = $makeData['reject_remark'];
        $concern->rejected_at = now();
        $concern->status_id = -1;
        $concern->save();

        EventModel::where('concern_id', $concern->id)->update($makeData);

        return response()->json($concern, 201);
    }

    /**
     * 撤回事件.
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\EventLogConcernModel $eventlog
     * @return mixed
     */
    public function withdraw(Request $request, EventLogConcernModel $concern)
    {
        $user = $request->user();

        if ($concern->status_id === 2 || $concern->status_id === -2) {
            return response()->json([
                'message' => '已终审或已撤回'
            ], 422);
        }

        if ($concern->recorder_sn !== $user->staff_sn) {
            return response()->json([
                'message' => '非记录人无权撤回'
            ], 401);
        }

        $concern->status_id = -2;
        $concern->save();

        EventModel::where('concern_id', $concern->id)->update(['status_id' => -2]);

        return response()->json($concern, 201);
    }

}