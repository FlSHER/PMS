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
     * @param  \App\Models\PointLog $pointlog
     * @return mixed
     */
    public function show(Request $request, PointLogModel $pointlog)
    {
        $user = $request->user();
        $counts = $this->currentMonthCredit($request, $pointlog);
        $totalPoint = StatisticModel::where('staff_sn', $user->staff_sn)->first(); 

        return response()->json([
            'staff_sn' => $user->staff_sn,
            'staff_name' => $user->realname,
            'point_statistic' => $counts
        ]);
    }

    /**
     * 获取部门排行信息.
     * 
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @param  string stage month:月度  stage:阶段 total:累计
     * @return mixed
     */
    public function departments(Request $request)
    {
        $type = $request->query('stage', 'month');
        if (! in_array($type, ['month', 'stage', 'total'])) {
            $type = 'month';
        }
        app()->call([$this, camel_case($type.'_rank')]);
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
        $groups = $this->getStaffGroup($request->query('group_id', 1));

        // 本月
        if (Carbon::parse($datetime)->isCurrentMonth()) {
            $items = StatisticModel::query()
                ->where(function ($query) use ($groups) {
                    $query->whereIn('staff_sn', $groups['staff_ids'])
                        ->orWhereIn('department_id', $groups['department_ids']);
                })
                ->whereBetween('calculated_at', monthBetween())
                ->orderBy('point_b_total', 'desc')
                ->get();
        } else {
            // 历史月份
            $items = StatisticLogModel::query()
                ->where(function ($query) use ($groups) {
                    $query->whereIn('staff_sn', $groups['staff_ids'])
                        ->orWhereIn('department_id', $groups['department_ids']);
                })
                ->whereBetween('calculated_at', monthBetween($datetime))
                ->orderBy('point_b_total', 'desc')
                ->get();
        }

        $items->map(function ($item, $key) use (&$user) {
            $item->rank = $key + 1;
            if ($item->staff_sn === $user->staff_sn) {
                $user->rank = $key+1;
            }
            return $item;
        });

        return response()->json([
            'list' => $items,
            'user' => [
                'rank' => $user->rank,
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
        $groups = $this->getStaffGroup($request->query('group_id', 1));

        $items = StatisticLogModel::query()
            ->select(\DB::raw('staff_sn, staff_name, SUM(point_b_monthly) as stage_b_total'))
            ->whereBetween('date', stageBetween($stime, $etime))
            ->where(function ($query) use ($groups) {
                $query->whereIn('staff_sn', $groups['staff_ids'])
                    ->orWhereIn('department_id', $groups['department_ids']);
            })
            ->groupBy(['staff_sn', 'staff_name'])
            ->orderBy('stage_b_total', 'desc')
            ->get();

        $items->map(function ($item, $key) use (&$user) {
            $item->rank = $key + 1;
            if ($item->staff_sn === $user->staff_sn) {
                $user->rank = $key+1;
            }
            return $item;
        });

        return response()->json([
            'list' => $items,
            'user' => [
                'rank' => $user->rank,
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
        $groups = $this->getStaffGroup($request->query('group_id', 1));

        $items = StatisticLogModel::query()
            ->select(\DB::raw('staff_sn, staff_name, SUM(point_b_monthly) as stage_b_total'))
            ->where(function ($query) use ($groups) {
                $query->whereIn('staff_sn', $groups['staff_ids'])
                    ->orWhereIn('department_id', $groups['department_ids']);
            })
            ->groupBy(['staff_sn', 'staff_name'])
            ->orderBy('stage_b_total', 'desc')
            ->get();

        $items->map(function ($item, $key) use (&$user) {
            $item->rank = $key + 1;
            if ($item->staff_sn === $user->staff_sn) {
                $user->rank = $key+1;
            }
            return $item;
        });

        return response()->json([
            'list' => $items,
            'user' => [
                'rank' => $user->rank,
                'name' => $user->realname
            ]
        ], 200);
    }

    /**
     * 获取分组员工和分组部门.
     * 
     * @author 28youth
     * @param  int    $group_id
     * @return array
     */
    protected function getStaffGroup(int $group_id): array
    {
        $group = GroupModel::find($group_id);

        return [
            'staff_ids' => $group->staff()->pluck('staff_sn'),
            'department_ids' => $group->department()->pluck('department_id')
        ];
    }

    /**
     * 统计员工当月积分情况.
     * 
     * @author 28youth
     * @param  Request $request
     * @return mixed
     */
    protected function currentMonthCredit(Request $request, PointLogModel $pointlog)
    {
        $user = $request->user();
        $between = monthBetween();

        $totalGroup = $pointlog->where('staff_sn', $user->staff_sn)
            ->select('source_id', \DB::raw('SUM(point_a) as total_a, SUM(point_b) as total_b'))
            ->whereBetween('created_at', $between)
            ->groupBy('source_id')
            ->get();

        $totalA = $totalGroup->reduce(function($carry, $item){
            return $carry + $item['total_a'];
        });
        $totalB = $totalGroup->reduce(function($carry, $item){
            return $carry + $item['total_b'];
        });

        return [
            'total_group' => $totalGroup->toArray(),
            'total_point_a' => $totalA,
            'total_point_b' => $totalB
        ];
    }
}