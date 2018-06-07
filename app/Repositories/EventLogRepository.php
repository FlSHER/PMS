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
	 * 获取事件奖扣纪录分页列表.
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
	 * 获取全部事件奖扣纪录列表.
	 * 
	 * @author 28youth
	 * @param  \Illuminate\Http\Request $request
	 * @return mixed
	 */
	public function getList(Request $request, $model)
	{
		return $this->getFilteredList($request, $this->eventlog);
	}

	/**
	 * 获取我纪录的事件列表.
	 * 
	 * @param  \Illuminate\Http\Request $request
	 * @return mixed
	 */
	public function getRecordedList(Request $request)
	{
		$user = $request->user();
		$limit = $request->query('limit', 20);

		$items = $this->eventlog
			->where('recorder_sn', $user->staff_sn)
			->paginate($limit);

		return [
			'data' => $items->items(),
			'total' => $items->count(),
			'page' => $items->currentPage(),
			'pagesize' => $limit,
			'totalpage' => $items->total(),
		];
	}

	/**
	 * 待审核的事件列表.
	 * 
	 * @author 28youth
	 * @param  \Illuminate\Http\Request $request
	 * @return mixed
	 */
	public function getProcessingList(Request $request)
	{
		$items = $this->eventlog
			->byAudit(0)
			->where('recorder_sn', $user->staff_sn)
			->paginate($limit);

		return [
			'data' => $items->items(),
			'total' => $items->count(),
			'page' => $items->currentPage(),
			'pagesize' => $limit,
			'totalpage' => $items->total(),
		];
	}

}