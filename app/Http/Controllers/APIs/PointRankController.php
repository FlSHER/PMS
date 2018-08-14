<?php

namespace App\Http\Controllers\APIs;

use Carbon\Carbon;
use Illuminate\Http\Request;
use function App\monthBetween;
use function App\stageBetween;
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
            ->orderBy('date', 'desc')
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
        if (!in_array($type, ['month', 'stage', 'total'])) {
            $type = 'month';
        }
        $group = GroupModel::find($request->query('group_id', 1));

        return app()->call(
            [
                $this, 
                camel_case($type.'_rank')
            ], 
            [
                $group, 
                $request->user(),
                $group->staff()->pluck('staff_sn'),  
                $group->departments()->pluck('department_id')
            ]
        );
    }

    /**
     * 获取月度排行.
     *
     * @author 28youth
     * @return mixed
     */
    public function monthRank(...$params)
    {
        $datetime = Carbon::parse(request()->query('datetime'));
        [$group, $user, $staffSns, $departmentIds] = $params;

        if ($datetime->isCurrentMonth()) {
            $calculatedAt = \DB::table('artisan_command_logs')
                ->where('command_sn', 'pms:calculate-staff-point')
                ->orderBy('id', 'desc')->value('created_at');
            $items = StatisticModel::query()
                ->select('staff_sn', 'staff_name', 'point_b_monthly as total')
                ->where(function ($query) use ($staffSns, $departmentIds) {
                    $query->whereIn('staff_sn', $staffSns)->orWhereIn('department_id', $departmentIds);
                })
                ->orderBy('total', 'desc')
                ->get();
        } else {
            $items = StatisticLogModel::query()
                ->select('staff_sn', 'staff_name', 'point_b_monthly as total')
                ->where(function ($query) use ($staffSns, $departmentIds) {
                    $query->whereIn('staff_sn', $staffSns) ->orWhereIn('department_id', $departmentIds);
                })
                ->where('date', $datetime)
                ->orderBy('total', 'desc')
                ->get();
        }

        $this->calculatedRank($items, $user, $group);

        $response = [
            'list' => $items,
            'group_id' => $group->id,
            'user' => [
                'rank' => $user->rank ?? 1,
                'name' => $user->realname,
                'total' => $user->total,
                'prev_rank' => $this->prevMonthRank($group)
            ],
        ];
        if (Carbon::parse($datetime)->isCurrentMonth()) {
            $response['calculated_at'] = $calculatedAt;
        }

        return response()->json($response, 200);
    }

    /**
     * 获取阶段排行.
     *
     * @author 28youth
     * @return mixed
     */
    public function stageRank(...$params)
    {
        $stime = request()->query('start_at');
        $etime = request()->query('end_at');
        [$group, $user, $staffSns, $departmentIds] = $params;

        $items = StatisticLogModel::query()
            ->select(\DB::raw('staff_sn, staff_name, SUM(point_b_monthly) as total'))
            ->whereBetween('date', stageBetween($stime, $etime))
            ->where(function ($query) use ($staffSns, $departmentIds) {
                $query->whereIn('staff_sn', $staffSns)->orWhereIn('department_id', $departmentIds);
            })
            ->groupBy(['staff_sn', 'staff_name'])
            ->orderBy('total', 'desc')
            ->get();

        $this->calculatedRank($items, $user, $group);

        return response()->json([
            'list' => $items,
            'group_id' => $group->id,
            'user' => [
                'rank' => $user->rank ?? 1,
                'name' => $user->realname,
                'total' => $user->total,
            ]
        ], 200);
    }

    /**
     * 获取累计排行.
     *
     * @author 28youth
     * @return mixed
     */
    public function totalRank(...$params)
    {
        [$group, $user, $staffSns, $departmentIds] = $params;

        $calculatedAt = \DB::table('artisan_command_logs')
            ->where('command_sn', 'pms:calculate-staff-point')
            ->orderBy('id', 'desc')->value('created_at');

        $items = StatisticModel::query()
            ->select('staff_sn', 'staff_name', 'point_b_total as total')
            ->where(function ($query) use ($staffSns, $departmentIds) {
                $query->whereIn('staff_sn', $staffSns)->orWhereIn('department_id', $departmentIds);
            })
            ->orderBy('total', 'desc')
            ->get();

        $this->calculatedRank($items, $user, $group);

        return response()->json([
            'list' => $items,
            'group_id' => $group->id,
            'user' => [
                'rank' => $user->rank ?? 1,
                'name' => $user->realname,
                'total' => $user->total,
            ],
            'calculated_at' => $calculatedAt
        ], 200);
    }

    /**
     * 获取上月排行.
     *
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return int
     */
    public function prevMonthRank(GroupModel $group)
    {
        $user = request()->user();
        $user->rank = 1;
        $datetime = Carbon::parse(request()->query('datetime'))->subMonth();

        $items = StatisticLogModel::query()
            ->select('staff_sn', 'staff_name', 'point_b_total as total')
            ->where(function ($query) use ($group) {
                $query->whereIn('staff_sn', $group->staff()->pluck('staff_sn'))
                    ->orWhereIn('department_id', $group->departments()->pluck('department_id'));
            })
            ->where('date', $datetime)
            ->orderBy('total', 'desc')
            ->get();

        $this->calculatedRank($items, $user, $group);

        return $user->rank;
    }

    /**
     * 统计分组排名.
     * 
     * @author 28youth
     * @param  [type] $items 积分统计信息
     * @param  [type] &$user 当前认证员工信息
     * @param  [type] $group 员工分组信息
     */
    public function calculatedRank(...$params)
    {
        [$items, $user, $group] = $params;

        $user->total = 0;
        $prevItem = (object)['total' => 0, 'rank' => 1];
        $curkey = 0;

        $items->map(function ($item, $key) use (&$user, &$prevItem, &$curkey) {
            $curkey = ($key + 1);
            $rank = ($prevItem->total == $item->total) ? $prevItem->rank : ($key + 1);
            $item->rank = $rank;
            if ($item->staff_sn === $user->staff_sn) {
                $user->rank = $rank;
                $user->total = $item->total;
            }
            $prevItem = $item;
            return $item;
        });

        $lastRank = ($prevItem->total == 0) ? $curkey : ($curkey + 1);

        $group->staff->map(function ($staff) use ($items, &$user, $lastRank) {
            if (!in_array($staff->staff_sn, $items->pluck('staff_sn')->toArray())) {
                $items->push([
                    'staff_sn' => $staff->staff_sn,
                    'staff_name' => $staff->staff_name,
                    'rank' => $lastRank,
                    'total' => 0,
                ]);
                if ($staff->staff_sn === $user->staff_sn) {
                    $user->rank = $lastRank;
                }
            }
        });

        $staffResponse = collect(app('api')->getStaff([
            'filters' => 'department_id='.json_encode($group->departments()->pluck('department_id')).';status_id>=0'
        ]));

        $staffResponse->map(function ($staff) use ($items, &$user, $lastRank) {
            if (!in_array($staff['staff_sn'], $items->pluck('staff_sn')->toArray())) {
                $items->push([
                    'staff_sn' => $staff['staff_sn'],
                    'staff_name' => $staff['realname'],
                    'rank' => $lastRank,
                    'total' => 0,
                ]);
                if ($staff['staff_sn'] === $user->staff_sn) {
                    $user->rank = $lastRank;
                }
            }
        });
    }
}