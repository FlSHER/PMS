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
		return $this->getFilteredPaginateList($request, $this->eventlog);
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
		return $this->getFilteredList($request, $this->eventlog);
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
		$limit = $request->query('limit', 10);

		$items = $this->eventlog
			->whereHas('participant', function($query) use ($user) {
				return $query->where('participant_sn', $user->staff_sn);
			})
			->latest('id')
			->paginate($limit);

		return $this->response($items);
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
		$limit = $request->query('limit', 10);

		$items = $this->eventlog
			->where('recorder_sn', $user->staff_sn)
			->latest('id')
			->paginate($limit);

		return $this->response($items);
	}

	/**
	 * 获取我审核的事件记录列表.
	 * 
	 * @author 28youth
	 * @param  \Illuminate\Http\Request $request
	 * @return mixed
	 */
	public function getApprovedList(Request $request)
	{
		$user = $request->user();
		$limit = $request->query('limit', 10);

		$items = $this->eventlog
			->where('first_approver_sn', $user->staff_sn)
			->orWhere('final_approver_sn', $user->staff_sn)
			->latest('id')
			->paginate($limit);

		return $this->response($items);
	}

	/**
	 * 获取抄送我的事件记录列表.
	 * 
	 * @author 28youth
	 * @param  \Illuminate\Http\Request $request
	 * @return mixed
	 */
	public function getCopyList(Request $request)
	{
		$user = $request->user();
		$limit = $request->query('limit', 10);

		$items = $this->eventlog
			->whereHas('copy', function($query) use ($user) {
				return $query->where('addressee_sn', $user->staff_sn);
			})
			->latest('id')
			->paginate($limit);

		return $this->response($items);
	}

	/**
	 * 待审核的事件日志列表.
	 * 
	 * @author 28youth
	 * @param  \Illuminate\Http\Request $request
	 * @return mixed
	 */
	public function getProcessingList(Request $request)
	{
		$limit = $request->query('limit', 10);

		$items = $this->eventlog
			->byAudit(0)
			->latest('id')
			->paginate($limit);

		return $this->response($items);
	}

	protected function response($items)
	{
		return [
			'data' => $items->items(),
			'total' => $items->count(),
			'page' => $items->currentPage(),
			'pagesize' => $items->perPage(),
			'totalpage' => $items->total(),
		];
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