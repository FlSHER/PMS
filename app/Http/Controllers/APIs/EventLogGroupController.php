<?php

namespace App\Http\Controllers\APIs;

use Illuminate\Http\Request;
use App\Models\EventLogGroup as EventLogGroupModel;
use App\Repositories\EventLogGroup as EventLogGroupRepository;

class EventLogGroupController extends Controller
{
    protected $group;

    public function __construct(EventLogGroupRepository $group)
    {
        $this->group = $group;
    }

    /**
     * 获取事件日志分组列表.
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $type = $request->query('type', 'all');

        $items = app()->call([
            $this->group,
            camel_case('get_' . $type . '_list')
        ]);

        return response()->json($items, 200);
    }

    /**
     * 获取事件分组详情.
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @param  \App\Models\EventLogGroupModel $group
     * @return mixed
     */
    public function show(Request $request, EventLogGroupModel $group)
    {
        $group->load('addressees', 'logs.participants', 'logs.event');
        $group->executed_at = Carbon::parse($group->executed_at)->toDateString();

        return response()->json($group);
    }

}