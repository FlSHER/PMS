<?php

namespace App\Http\Controllers\Admin;

use App\Models\EventLogGroup;
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
        return EventLogGroup::with(['logs'])
            ->filterByQueryString()
            ->sortByQueryString()
            ->withPagination();
    }

    public function details(Request $request)
    {
        return $this->eventLogService->getEventLogDetails($request);
    }

    /**
     * 撤销操作
     *
     * @return void
     */
    public function revoke(Request $request, EventLogGroup $eventLogGroup)
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
            'recorder_point' => $custom['recorder_point'] ?: $eventLogGroup->recorder_point,
            'first_approver_point' => $custom['first_approver_point'] ?: $eventLogGroup->first_approver_point,
            'final_approver_point' => $custom['final_approver_point'] ?: $eventLogGroup->final_approver_point
        ];

        $eventLogGroup->getConnection()->transaction(function () use ($eventLogGroup, $params) {
            $approveService = new EventApprove($eventLogGroup);
            $approveService->revokeApprove($params);
        });

        return response()->json($eventLogGroup, 201);
    }
}
