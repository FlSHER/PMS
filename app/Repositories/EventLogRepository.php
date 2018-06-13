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
		return $this->eventlog->formatFilter($filters)->pagination();
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
		return $this->eventlog->formatFilter($filters)->get();
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

		return $this->eventlog->formatFilter($filters)
			->whereHas('participant', function($query) use ($user) {
				return $query->where('participant_sn', $user->staff_sn);
			})
			->latest('id')
			->pagination();
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

		return $this->eventlog->formatFilter($filters)
			->where('recorder_sn', $user->staff_sn)
			->latest('id')
			->pagination();
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

		return $this->eventlog->formatFilter($filters)
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
			->pagination();
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

		return $this->eventlog->formatFilter($filters)
			->whereHas('addressee', function($query) use ($user) {
				$query->where('addressee_sn', $user->staff_sn);
			})
			->latest('id')
			->pagination();
	}

	/**
	 * 待审核的事件日志列表. 初审当前 状态0 终审当前 状态1
	 * 
	 * @author 28youth
	 * @param  \Illuminate\Http\Request $request
	 * @return mixed
	 */
	public function getProcessingList(Request $request)
	{
		$user = $request->user();
		$filters = $request->query('filters');

		return $this->eventlog->formatFilter($filters)
			->where(function ($query) use ($user) {
				$query->where('first_approver_sn', $user->staff_sn)->byAudit(0);
			})
			->orWhere(function ($query) use ($user) {
				$query->where('final_approver_sn', $user->staff_sn)->byAudit(1);
			})	
			->latest('id')
			->pagination();
	}

	/**
	 * 获取事件参与人.
	 * 
	 * @author 28youth
	 * @param  EventLogModel $eventlog
	 * @return mixed
	 */
	public function getParticipant(EventLogModel $eventlog)
	{
		return $eventlog->participant()->pluck('participant_sn');
	}

}