<?php

namespace App\Http\Controllers\Admin;

use App\Services\Admin\EventLogService;
use Illuminate\Http\Request;

class EventLogController extends Controller
{
    protected $eventLogService;
    public function __construct(EventLogService $eventLog)
    {
        $this->eventLogService=$eventLog;
    }
    public function index(Request $request)
    {
        return $this->eventLogService->getEventLogList($request);
    }

    public function details(Request $request)
    {
        return $this->eventLogService->getEventLogDetails($request);
    }
}
