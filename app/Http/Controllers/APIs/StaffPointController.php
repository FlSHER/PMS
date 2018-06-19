<?php

namespace App\Http\Controllers\APIs;

use Carbon\Carbon;
use Illuminate\Http\Request;
use function App\monthBetween;
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
        $totalSource = $this->totalSource($user);
        $datetime = $request->query('datetime');
        // 当前月统计
        if (Carbon::parse($datetime)->isCurrentMonth()) {

            $items = $this->currentMonthCredit($user);
        } else {
            $monthly = StatisticLogModel::query()
                ->select('source_b_monthly')
                ->where('staff_sn', $user->staff_sn)
                ->whereBetween('date', monthBetween($datetime))
                ->first();

            $source_b_total = collect($monthly['source_b_monthly'])
                ->reduce(function ($carry, $item) {
                    return $carry + $item['total_b'];
                });

            $items = [
                'point_b_total' => $source_b_total,
                'source_b_monthly' => $monthly['source_b_monthly']
            ];
        }
        $items['source_b_total'] = $totalSource;

        $datas = array_merge($items, [
            'department' => $user->department,
            'realname' => $user->realname,
            'staff_sn' => $user->staff_sn,
            'brand' => $user->brand
        ]);

        return response()->json($datas);
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

        $items = PointLogModel::query()
            ->where('staff_sn', $user->staff_sn)
            ->filterByQueryString()
            ->withPagination();

        return response()->json($items);
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
     * 统计员工分类积分数.
     *
     * @author 28youth
     * @param  $user
     * @return array
     */
    public function totalSource($user)
    {
        // 获取上次月结积分统计
        $prevMonth = StatisticLogModel::query()
            ->select('source_b_monthly')
            ->where('staff_sn', $user->staff_sn)
            ->orderBy('created_at', 'desc')
            ->first();

        // 获取本月积分分类统计
        $curMonth = PointLogModel::where('staff_sn', $user->staff_sn)
            ->select('source_id', \DB::raw('SUM(point_b) as total_b'))
            ->whereBetween('created_at', monthBetween())
            ->groupBy('source_id')
            ->get();

        // 合并分类积分统计
        $total = $curMonth->map(function ($item, $key) use ($prevMonth) {
            $source = $prevMonth['source_b_monthly'];
            if ($source[$key]['source_id'] === $item['source_id']) {
                $item['total_b'] += $source[$key]['total_b'];
            }
            return $item;
        });

        return $total;
    }

    /**
     * 统计员工当月积分情况.
     *
     * @author 28youth
     * @param  Request $request
     * @return mixed
     */
    protected function currentMonthCredit($user)
    {
        $curMonth = PointLogModel::where('staff_sn', $user->staff_sn)
            ->select('source_id', \DB::raw('SUM(point_b) as total_b'))
            ->whereBetween('created_at', monthBetween())
            ->groupBy('source_id')
            ->get();

        $totalB = $curMonth->reduce(function ($carry, $item) {
            return $carry + $item['total_b'];
        });

        return [
            'source_b_monthly' => $curMonth->toArray(),
            'point_b_total' => $totalB
        ];
    }
}