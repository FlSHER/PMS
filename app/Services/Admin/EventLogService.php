<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/16/012
 * Time: 11:12
 */

namespace App\Services\Admin;

use App\Repositories\EventLogRepository;

class EventLogService
{
    protected $eventLogRepository;
    public function __construct(EventLogRepository $eventLogRepository)
    {
        $this->eventLogRepository=$eventLogRepository;
    }
    public function getEventLogList($request)
    {
        return $this->eventLogRepository->getEventLogList($request);
    }

    public function getEventLogDetails($request)
    {
        $id=$request->route('id');
        return $this->eventLogRepository->getEventLogSingleness($id);
    }
}