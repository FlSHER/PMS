<?php

namespace App\Http\Controllers\APIs;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\PointLogSource;
use function App\monthBetween;
use function App\stageBetween;
use App\Models\PointLog as PointLogModel;
use App\Models\AuthorityGroup as GroupModel;
use App\Models\PersonalPointStatistic as StatisticModel;
use App\Models\PersonalPointStatisticLog as StatisticLogModel;

class StaffPointController extends Controller
{
    /**
     * 积分分类统计列表.
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $datetime = Carbon::parse($request->query('datetime'));

        if (Carbon::parse($datetime)->isCurrentMonth()) {
            $monthly = StatisticModel::query()
                ->where('staff_sn', $user->staff_sn)
                ->whereBetween('calculated_at', monthBetween($datetime))
                ->orderBy('calculated_at', 'desc')
                ->first();
        } else {
            $monthly = StatisticLogModel::query()
                ->where('staff_sn', $user->staff_sn)
                ->whereBetween('date', monthBetween($datetime))
                ->first();
        }

        return response()->json([
            'monthly' => $monthly,
            'trend' => $this->statistics()
        ], 200);
    }

    /**
     * 获取某一段时间统计结果.
     * 
     * @author 28youth
     * @return mixed
     */
    public function statistics()
    {
        $user = request()->user();
        $etime = Carbon::parse(request()->query('datetime'));
        $stime = clone $etime;

        $items = StatisticLogModel::query()
            ->select('point_a', 'point_b_monthly as total', 'date')
            ->where('staff_sn', $user->staff_sn)
            ->whereBetween('date', stageBetween($stime->subMonth(4), $etime))
            ->get();

        return $items->map(function ($item) {
            $item->month = Carbon::parse($item->date)->month;

            return $item;
        })->toArray();
    }

    /**
     * 获取积分列表.
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $staffSn = $request->query('staff_sn');
        $groupId = $request->query('group_id');
        /* if ($staffSn && $groupId) {
            $group = GroupModel::where('id', $groupId)
                ->whereHas('checking', function ($query) use ($user) {
                    $query->where('admin_sn', $user->staff_sn);
                })->first();
            if ($group === null || ($group && !in_array($staffSn, $group->stafflist()))) {
                return response()->json(['message' => '无权查看当前员工'], 403);
            }
        } */

        $items = PointLogModel::query()
            ->where('staff_sn', ($staffSn ? : $user->staff_sn))
            ->filterByQueryString()
            ->withPagination();

        return response()->json($items, 200);
    }

    /**
     * 积分详情.
     * 
     * @author 28youth
     * @param  \App\Models\PointLog $pointlog
     * @return mixed
     */
    public function detail(PointLogModel $pointlog)
    {
        $pointlog->load('source');

        return response()->json($pointlog);
    }

    /**
     * 获取积分来源分类.
     *
     * @param \App\Models\PointLogSource $sourceModel
     * @return mixed
     */
    public function source(PointLogSource $sourceModel)
    {
        $items = $sourceModel->get();

        return response()->json($items, 200);
    }
}