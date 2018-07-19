<?php

namespace App\Http\Controllers\APIs;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\PointLogSource;
use function App\monthBetween;
use function App\stageBetween;
use App\Models\PointLog as PointLogModel;
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
                ->whereBetween('date', monthBetween($datetime))
                ->orderBy('date', 'desc')
                ->first();
        } else {
            $monthly = StatisticLogModel::query()
                ->where('staff_sn', $user->staff_sn)
                ->whereBetween('date', monthBetween($datetime))
                ->first();
        }

        $monthly['add_point_total'] = array_reduce($monthly->source_b_monthly, function ($carry, $item) {
            return $carry + $item['add_point'];
        });

        $monthly['sub_point_total'] = array_reduce($monthly->source_b_monthly, function ($carry, $item) {
            return $carry + $item['sub_point'];
        });

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
        if ($etime->isCurrentMonth()) {
            $etime->subMonth();
        }
        $stime = clone $etime;
        $monthly = [];
        for ($i=0; $i <= 3; $i++) { 
            $monthly[]['month'] = $i ? $stime->subMonth()->month : $stime->month;
        }

        $items = StatisticLogModel::query()
            ->select('point_a', 'point_b_monthly as total', 'date')
            ->where('staff_sn', $user->staff_sn)
            ->whereBetween('date', stageBetween($stime->startOfMonth(), $etime->endOfMonth()))
            ->get();

        $items->map(function ($item) use (&$monthly) {
            $current = Carbon::parse($item->date)->month;
            foreach ($monthly as $key => &$month) {
                $month['point_a'] = 0;
                $month['point_b'] = 0;
                if ($current == $month['month']) {
                    $month['point_a'] = $item->point_a;
                    $month['point_b'] = $item->total;
                }
            }
        });

        return $monthly;
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
            ->where('staff_sn', ($staffSn ?: $user->staff_sn))
            ->filterByQueryString()
            ->sortByQueryString()
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