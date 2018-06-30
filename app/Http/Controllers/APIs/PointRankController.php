<?php 

namespace App\Http\Controllers\APIs;

use Carbon\Carbon;
use Illuminate\Http\Request;
use function App\monthBetween;
use function App\stageBetween;
use App\Models\PointLog as PointLogModel;
use App\Models\AuthorityGroup as GroupModel; 
use App\Models\PersonalPointStatistic as StatisticModel;
use App\Models\PersonalPointStatisticLog as StatisticLogModel;

class PointRankController extends Controller
{
    /**
     * 积分排名详情.
     * 
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function show(Request $request)
    {
        $user = $request->user();
        $monthly = StatisticModel::query()
                ->where('staff_sn', $user->staff_sn)
                ->orderBy('calculated_at', 'desc')
                ->first();

        return response()->json($monthly);
    }

    /**
     * 获取员工排行榜信息.
     * 
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @param  string stage month:月度  stage:阶段 total:累计
     * @return mixed
     */
    public function staff(Request $request)
    {
        $type = $request->query('stage', 'month');
        if (! in_array($type, ['month', 'stage', 'total'])) {
            $type = 'month';
        }

        return app()->call([$this, camel_case($type.'_rank')]);
    }

    /**
     * 获取月度排行.
     * 
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function monthRank(Request $request) 
    {
        $user = $request->user();
        $datetime = $request->query('datetime');
        $group = GroupModel::find($request->query('group_id', 1));

        // 本月
        if (Carbon::parse($datetime)->isCurrentMonth()) {
            $items = StatisticModel::query()
                ->select('staff_sn', 'staff_name', 'point_b_total as total')
                ->where(function ($query) use ($group) {
                    $query->whereIn('staff_sn', $group->staff()->pluck('staff_sn'))
                        ->orWhereIn('department_id', $group->department()->pluck('department_id'));
                })
                ->whereBetween('calculated_at', monthBetween())
                ->orderBy('point_b_total', 'desc')
                ->get();
        } else {
            // 历史月份
            $items = StatisticLogModel::query()
                ->select('staff_sn', 'staff_name', 'point_b_total as total')
                ->where(function ($query) use ($group) {
                    $query->whereIn('staff_sn', $group->staff()->pluck('staff_sn'))
                        ->orWhereIn('department_id', $group->department()->pluck('department_id'));
                })
                ->whereBetween('date', monthBetween($datetime))
                ->orderBy('total', 'desc')
                ->get();
        }

        $items->map(function ($item, $key) use (&$user) {
            $item->rank = $key + 1;
            if ($item->staff_sn === $user->staff_sn) {
                $user->rank = $key+1;
            }
            return $item;
        });

        $group->staff->map(function ($item, $key) use ($items, &$user) {
            if (!in_array($item->staff_sn, $items->pluck('staff_sn')->toArray())) {
                unset($item->authority_group_id);
                $item->total = 0;
                $item->rank = $items->count() + 1;
                $items->push($item);

                if ($item->staff_sn === $user->staff_sn) {
                    $user->rank = $item->rank;
                }
            }
        });

        return response()->json([
            'list' => $items,
            'user' => [
                'rank' => $user->rank ?? 1,
                'name' => $user->realname
            ]
        ], 200);
    }

    /**
     * 获取阶段排行.
     * 
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function stageRank(Request $request)
    {
        $user = $request->user();
        $stime = $request->query('start_at');
        $etime = $request->query('end_at');
        $group = GroupModel::find($request->query('group_id', 1));

        $items = StatisticLogModel::query()
            ->select(\DB::raw('staff_sn, staff_name, SUM(point_b_monthly) as total'))
            ->whereBetween('date', stageBetween($stime, $etime))
            ->where(function ($query) use ($group) {
                $query->whereIn('staff_sn', $group->staff()->pluck('staff_sn'))
                    ->orWhereIn('department_id', $group->department()->pluck('department_id'));
            })
            ->groupBy(['staff_sn', 'staff_name'])
            ->orderBy('total', 'desc')
            ->get();

        $items->map(function ($item, $key) use (&$user) {
            $item->rank = $key + 1;
            if ($item->staff_sn === $user->staff_sn) {
                $user->rank = $key+1;
            }
            return $item;
        });

        $group->staff->map(function ($item, $key) use ($items, &$user) {
            if (!in_array($item->staff_sn, $items->pluck('staff_sn')->toArray())) {
                unset($item->authority_group_id);
                $item->total = 0;
                $item->rank = $items->count() + 1;
                $items->push($item);

                if ($item->staff_sn === $user->staff_sn) {
                    $user->rank = $item->rank;
                }
            }
        });

        return response()->json([
            'list' => $items,
            'user' => [
                'rank' => $user->rank ?? 1,
                'name' => $user->realname
            ]
        ], 200);
    }

    /**
     * 获取累计排行.
     * 
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function totalRank(Request $request)
    {
        $user = $request->user();
        $group = GroupModel::find($request->query('group_id', 1));

        $items = StatisticLogModel::query()
            ->select(\DB::raw('staff_sn, staff_name, SUM(point_b_monthly) as total'))
            ->where(function ($query) use ($group) {
                $query->whereIn('staff_sn', $group->staff()->pluck('staff_sn'))
                    ->orWhereIn('department_id', $group->department()->pluck('department_id'));
            })
            ->groupBy(['staff_sn', 'staff_name'])
            ->orderBy('total', 'desc')
            ->get();

        $items->map(function ($item, $key) use (&$user) {
            $item->rank = $key + 1;
            if ($item->staff_sn === $user->staff_sn) {
                $user->rank = $key+1;
            }
            return $item;
        });

        $group->staff->map(function ($item, $key) use ($items, &$user) {
            if (!in_array($item->staff_sn, $items->pluck('staff_sn')->toArray())) {
                unset($item->authority_group_id);
                $item->total = 0;
                $item->rank = $items->count() + 1;
                $items->push($item);

                if ($item->staff_sn === $user->staff_sn) {
                    $user->rank = $item->rank;
                }
            }
        });

        return response()->json([
            'list' => $items,
            'user' => [
                'rank' => $user->rank ?? 1,
                'name' => $user->realname
            ]
        ], 200);
    }
}