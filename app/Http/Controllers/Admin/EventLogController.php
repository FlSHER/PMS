<?php

namespace App\Http\Controllers\Admin;

use App\Services\Admin\EventLogService;
use App\Services\EventApprove;
use Illuminate\Http\Request;
use App\Models\EventLog as EventLogModel;

class EventLogController extends Controller
{
    protected $eventLogService;

    public function __construct(EventLogService $eventLog)
    {
        $this->eventLogService = $eventLog;
    }

    public function index(Request $request)
    {
        return $this->eventLogService->getEventLogList($request);
    }

    public function details(Request $request)
    {
        return $this->eventLogService->getEventLogDetails($request);
    }

    /**
     * 作废操作.
     *
     * @return void
     */
    public function revoke(Request $request, EventLogModel $eventlog)
    {
        $rules = [
            'first_approver_point' => 'required|integer|min:0',
            'final_approver_point' => 'required|integer|min:0',
            'recorder_point' => 'required|integer|min:0',
        ];
        $messages = [
            'recorder_point.min' => '记录人扣分值不能小于:min',
            'first_approver_point.min' => '初审人扣分值不能小于:min',
            'final_approver_point.min' => '终审人扣分值不能小于:min',
        ];
        $this->validate($request, $rules, $messages);

        $custom = $request->only(['recorder_point', 'first_approver_point', 'final_approver_point']);

        $params = [
            'recorder_point' => $custom['recorder_point'] ?: $eventlog->recorder_point,
            'first_approver_point' => $custom['first_approver_point'] ?: $eventlog->first_approver_point,
            'final_approver_point' => $custom['final_approver_point'] ?: $eventlog->final_approver_point
        ];

        $eventlog->getConnection()->transaction(function () use ($eventlog, $params) {
            $approveService = new EventApprove($eventlog);
            $approveService->revokeApprove($params);
        });

        return response()->json($eventlog, 201);
    }
}
