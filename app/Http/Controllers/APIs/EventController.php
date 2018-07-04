<?php 

namespace App\Http\Controllers\APIs;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Event as EventModel;
use App\Models\EventLog as EventLogModel;
use App\Models\EventType as EventTypeMdel;
use App\Models\FinalApprover as FinalApproverModel;

class EventController extends Controller
{

	/**
	 * 获取事件列表.
	 * 
	 * @author 28youth
	 * @return mixed
	 */
	public function index()
	{
		$items = EventModel::query()
			->filterByQueryString()
			->withPagination();

		return response()->json($items, 200);
	}

    /**
     * 获取事件分类列表.
     *
     * @author 28youth
     * @param  \App\Models\EventType $category
     * @return mixed　
     */
    public function cates(EventTypeMdel $category)
    {
        $cates = $category->orderBy('sort', 'asc')->get();

        return response()->json($cates, 200);
    }

    /**
     * 获取分类下的事件.
     *
     * @author 28youth
     * @param  \App\Models\EventType $category
     * @return mixed　
     */
    public function events(EventTypeMdel $category)
    {
        $events = $category->events()->byActive()->get();

        return response()->json($events, 200);
    }

    /**
     * 获取终审人列表.
     *
     * @author 28youth
     * @return mixed
     */
    public function finalStaff()
    {
        $items = FinalApproverModel::get();

        return response()->json($items, 200);
    }

}