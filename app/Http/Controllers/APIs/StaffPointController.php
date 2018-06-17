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
            $items = StatisticLogModel::query()
                ->select('source_b_monthly')
                ->where('staff_sn', $user->staff_sn)
                ->whereBetween('date', monthBetween($datetime))
                ->first();

            $items['source_b_total'] = collect($items['source_b_monthly'])
                ->reduce(function ($carry, $item) {
                    return $carry + $item['total_b'];
                });
        }

        return response()->json([
            'source_b_total' => $totalSource,
            'source_b_monthly' => $items
        ]);
    }

    /**
     * 获取积分明细.
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function show(Request $request, PointLogModel $pointLogModel)
    {
        $user = $request->user();
        $brand_id = $request->query('brand_id');
        $point_type = $request->query('point_type');
        $section = array_filter(explode('-', $request->query('section')));
        $datetime = array_filter(explode('~', $request->query('datetime')));

        $map = [
            'all' => function ($query) {
                $query->orderBy('created_at', 'desc');
            },
            'static' => function ($query) {
                $query->where('source_id', 1)
                    ->orderBy('created_at', 'desc');
            },
            'event' => function ($query) {
                $query->where('source_id', 2)
                    ->orderBy('created_at', 'desc');
            },
            'task' => function ($query) {
                $query->where('source_id', 3)
                    ->orderBy('created_at', 'desc');
            },
            'system' => function ($query) {
                $query->where('source_id', 4)
                    ->orderBy('created_at', 'desc');
            }
        ];
        $type = in_array($type = $request->query('type', 'all'), array_keys($map)) ? $type : 'all';

        call_user_func($map[$type], $query = $pointLogModel
            ->when(($point_type && $section), function ($query) use ($point_type, $section) {
                $query->whereBetween($point_type, [$section]);
            })
            ->when($datetime, function ($query) use ($datetime) {
                $query->whereBetween('created_at', [$datetime]);
            })
            ->when($brand_id, function ($query) use ($brand_id) {
                $query->where('brand_id', $brand_id);
            })
            ->sortByQueryString());
        $items = $query->pagination();

        return response()->json($items);
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
            'source_b_total' => $totalB
        ];
    }
}