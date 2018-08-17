<?php

namespace App\Http\Controllers\APIs;

use Carbon\Carbon;
use Illuminate\Http\Request;
use function App\monthBetween;
use function App\stageBetween;
use App\Models\AuthorityGroup as GroupModel;
use App\Models\PersonalPointStatistic as StatisticModel;
use App\Models\PersonalPointStatisticLog as StatisticLogModel;

class StatisticController extends Controller
{
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
        $group = GroupModel::find($request->query('group_id'));
        if ($group === null) {
            return response()->json(['message' => '分组不存在'], 404);
        }
        
        return app()->call(
            [$this, camel_case($type . '_rank')],
            [
                $group,
                $group->staff()->pluck('staff_sn'),
                $group->departments()->pluck('department_id')
            ]
        );
    }

    /**
     * 获取分组月度排行.
     *
     * @author 28youth
     * @return mixed
     */
    public function monthRank(...$params)
    {
        $datetime = Carbon::parse(request()->query('datetime'));
        [$group, $staffSns, $departmentIds] = $params;

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
                    $query->whereIn('staff_sn', $staffSns)->orWhereIn('department_id', $departmentIds);
                })
                ->where('date', $datetime)
                ->orderBy('total', 'desc')
                ->get();
        }

        $this->calculatedRank($items, $group);

        $response = [
            'list' => $items,
            'group_id' => $group->id
        ];
        if (Carbon::parse($datetime)->isCurrentMonth()) {
            $response['calculated_at'] = $calculatedAt;
        }

        return response()->json($response, 200);
    }

    /**
     * 获取分组阶段排行.
     *
     * @author 28youth
     * @return mixed
     */
    public function stageRank(...$params)
    {
        $stime = request()->query('start_at');
        $etime = request()->query('end_at');
        [$group, $staffSns, $departmentIds] = $params;

        $items = StatisticLogModel::query()
            ->select(\DB::raw('staff_sn, staff_name, SUM(point_b_monthly) as total'))
            ->whereBetween('date',stageBetween($stime, $etime))
            ->where(function ($query) use ($staffSns, $departmentIds) {
                $query->whereIn('staff_sn', $staffSns)->orWhereIn('department_id', $departmentIds);
            })
            ->groupBy(['staff_sn', 'staff_name'])
            ->orderBy('total', 'desc')
            ->get();

        $this->calculatedRank($items, $group);

        return response()->json([
            'list' => $items,
            'group_id' => $group->id
        ], 200);
    }

    /**
     * 获取分组累计排行.
     *
     * @author 28youth
     * @return mixed
     */
    public function totalRank(...$params)
    {
        [$group, $staffSns, $departmentIds] = $params;

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

        $this->calculatedRank($items, $group);

        return response()->json([
            'list' => $items,
            'group_id' => $group->id,
            'calculated_at' => $calculatedAt
        ], 200);
    }

    /**
     * 统计分组排名.
     * 
     * @author 28youth
     * @param  [type] $items 积分统计信息
     * @param  [type] $group 员工分组信息
     */
    public function calculatedRank(...$params)
    {
        [$items, $group] = $params;
        $prevItem = (object)['total' => 0, 'rank' => 1];
        $curkey = 1;

        $items->map(function ($item, $key) use (&$prevItem, &$curkey) {
            $curkey = $key;
            $rank = ($prevItem->total == $item->total) ? $prevItem->rank : ($key + 1);
            $item->rank = $rank;
            $prevItem = $item;
            return $item;
        });

        $lastRank = ($prevItem->total == 0) ? $curkey : ($curkey + 1);

        $group->staff->map(function ($staff) use ($items, $lastRank) {
            if (!in_array($staff->staff_sn, $items->pluck('staff_sn')->toArray())) {
                $items->push([
                    'staff_sn' => $staff->staff_sn,
                    'staff_name' => $staff->staff_name,
                    'rank' => $lastRank,
                    'total' => 0,
                ]);
            }
        });

        $staffResponse = collect(app('api')->getStaff([
            'filters' => 'department_id=' . json_encode($group->departments()->pluck('department_id')) . ';status_id>=0'
        ]));

        $staffResponse->map(function ($staff) use ($items, $lastRank) {
            if (!in_array($staff['staff_sn'], $items->pluck('staff_sn')->toArray())) {
                $items->push([
                    'staff_sn' => $staff['staff_sn'],
                    'staff_name' => $staff['realname'],
                    'rank' => $lastRank,
                    'total' => 0,
                ]);
            }
        });
    }
}

