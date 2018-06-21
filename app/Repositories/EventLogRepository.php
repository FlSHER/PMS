<?php

namespace App\Repositories;

use Illuminate\Http\Request;
use App\Models\EventLog as EventLogModel;


class EventLogRepository
{
    use Traits\Filterable;

    /**
     * EventLog model
     */
    protected $eventlog;

    /**
     * EventLogRepository constructor.
     * @param EventLog $eventlog
     */
    public function __construct(EventLogModel $eventlog)
    {
        $this->eventlog = $eventlog;
    }

    /**
     * 获取事件日志分页列表.
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function getPaginateList(Request $request)
    {
        $filters = $request->query('filters');
        return $this->eventlog->filterByQueryString()->withPagination();
    }

    /**
     * 获取全部事件日志列表.
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function getList(Request $request)
    {
        $filters = $request->query('filters');
        return $this->eventlog->filterByQueryString()->get();
    }

    /**
     * 获取我参与的事件日志列表.
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function getParticipantList(Request $request)
    {
        $user = $request->user();
        $filters = $request->query('filters');

        return $this->eventlog->filterByQueryString()
            ->whereHas('participant', function ($query) use ($user) {
                return $query->where('staff_sn', $user->staff_sn);
            })
            ->latest('id')
            ->withPagination();
    }

    /**
     * 获取我记录的事件日志列表.
     *
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function getRecordedList(Request $request)
    {
        $user = $request->user();
        $filters = $request->query('filters');

        return $this->eventlog->filterByQueryString()
            ->where('recorder_sn', $user->staff_sn)
            ->latest('id')
            ->withPagination();
    }

    /**
     * 获取我审核的事件记录列表. 初审 终审 驳回 并且对应时间不为空
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function getApprovedList(Request $request)
    {
        $user = $request->user();
        $filters = $request->query('filters');

        return $this->eventlog->filterByQueryString()
            ->where(function ($query) use ($user) {
                $query->where('first_approver_sn', $user->staff_sn)
                    ->whereNotNull('first_approved_at');
            })
            ->orWhere(function ($query) use ($user) {
                $query->where('final_approver_sn', $user->staff_sn)
                    ->whereNotNull('final_approved_at');
            })
            ->orWhere(function ($query) use ($user) {
                $query->where('rejecter_sn', $user->staff_sn)
                    ->whereNotNull('rejected_at');
            })
            ->latest('id')
            ->withPagination();
    }

    /**
     * 获取抄送我的事件记录列表.
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function getAddresseeList(Request $request)
    {
        $user = $request->user();
        $filters = $request->query('filters');

        return $this->eventlog->filterByQueryString()
            ->whereHas('addressee', function ($query) use ($user) {
                $query->where('staff_sn', $user->staff_sn);
            })
            ->latest('id')
            ->withPagination();
    }

    /**
     * 审核的事件日志列表.
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function getProcessingList(Request $request)
    {
        $user = $request->user();
        $type = $request->query('type', 0);

        return $this->eventlog->filterByQueryString()
            ->when(($type == 0), function ($query) use ($user) {
                $query->where(function ($query) use ($user) {
                    $query->where('first_approver_sn', $user->staff_sn)->byAudit(0);

                })->orWhere(function ($query) use ($user) {
                    $query->where('final_approver_sn', $user->staff_sn)->byAudit(1);
                });
            })
            ->when(($type == 1), function ($query) use ($user) {
                $query->where(function ($query) use ($user) {
                    $query->where('final_approver_sn', $user->staff_sn)->byAudit(2);

                })->orWhere(function ($query) use ($user) {
                    $query->where('rejecter_sn', $user->staff_sn)->byAudit(-1);
                });
            })
            ->sortByQueryString()
            ->withPagination();
    }
}