<?php 

namespace App\Repositories;

use Illuminate\Http\Request;
use App\Models\Events as EventModel;


class EventRepository
{
	use Traits\Filterable;

	/**
	 * EventModel model
	 */
	protected $event;

	/**
     * EventRepository constructor.
     * @param EventModel $event
     */
	public function __construct(EventModel $event)
	{
		$this->event = $event;
	}

	/**
	 * 获取事件分页列表.
	 * 
	 * @author 28youth
	 * @param  \Illuminate\Http\Request $request
	 * @return mixed
	 */
	public function getPaginateList(Request $request)
	{	
		return $this->getFilteredPaginateList($request, $this->event);
	}

	/**
	 * 获取全部事件列表.
	 * 
	 * @author 28youth
	 * @param  \Illuminate\Http\Request $request
	 * @return mixed
	 */
	public function getList(Request $request)
	{
		return $this->getFilteredList($request, $this->event);
	}


}