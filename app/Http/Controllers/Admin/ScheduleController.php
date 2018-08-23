<?php

namespace App\Http\Controllers\Admin;

use Cache;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\PointLog;
use App\Models\CommonConfig;
use App\Models\AttendanceRecord;
use App\Models\AuthorityGroupHasStaff;
use App\Models\PersonalPointStatistic;
use App\Models\PersonalPointStatisticLog;
use App\Http\Requests\Admin\AttendanceRequest;

class ScheduleController extends Controller
{

    /**
     * 获取考勤记录列表.
     * 
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $data = AttendanceRecord::query()
            ->filterByQueryString()
            ->sortByQueryString()
            ->withPagination();

        return response()->json($data, 200);
    }

    /**
     * 更新考勤统计.
     * 
     * @author 28youth
     * @param  App\Http\Requests\Admin\AttendanceRequest $request
     * @param  App\Models\AttendanceRecord  $record
     * @return mixed
     */
    public function update(AttendanceRequest $request, AttendanceRecord $record)
    {
        $data = $request->all();
        $time = $data['worktime'] - $record->worktime;

        $record->fill($data);

        return $record->getConnection()->transaction(function () use ($record, $time) {
            $record->save();
            $this->upateAttendanceRecord($record, $time);

            return response()->json($record);
        });

    }

    /**
     * 同步修改积分记录统计记录.
     * 
     * @author 28youth
     * @param  [type] $record
     * @param  [type] $time
     */
    public function upateAttendanceRecord($record, $time)
    {
        $config = $this->getConfig();

        // 不足以转化最低加减分则停止
        if ($config->time > abs($time)) {
            return false;
        }
        $date = Carbon::parse($record->workDate);
        $point = ($time / $config->time) * $config->point;

        // 修改积分记录
        $log = PointLog::byForeignKey($record->id)->where('source_id', 3)->first();
        if (empty($log)) {
            return false;
        }
        $log->point_b += $point;
        $log->save();

        // 修改统计记录
        if ($date->isCurrentMonth()) {
            $statistic = PersonalPointStatistic::where('staff_sn', $log->staff_sn)->first();
            $statistic->point_b_monthly += $point;
            $statistic->point_b_total += $point;
            $statistic->source_b_monthly = $this->mergeSource($statistic->source_b_monthly, $point);
            $statistic->source_b_total = $this->mergeSource($statistic->source_b_total, $point);
            $statistic->save();
        } else {
            $statistic = PersonalPointStatisticLog::query()
                ->where('date', $date->startOfMonth())
                ->where('staff_sn', $log->staff_sn)
                ->first();
            $statistic->point_b_monthly += $point;
            $statistic->point_b_total += $point;
            $statistic->source_b_monthly = $this->mergeSource($statistic->source_b_monthly, $point);
            $statistic->source_b_total = $this->mergeSource($statistic->source_b_total, $point);
            $statistic->save();
        }
    }

    /**
     * 修改分类统计.
     * 
     * @author 28youth
     * @param  [type] $source
     * @param  [type] $point 
     */
    public function mergeSource($source, $point)
    {
        foreach ($source as $k => &$v) {
            $v['point'] += $point;
            if ($point >= 0) {
                $v['add_point'] += $point;
            } else {
                $v['sub_point'] += $point;
            }
        }

        return $source;
    }

    /**
     * 获取考勤转积分配置.
     * 
     * @author 28youth
     * @return array
     */
    protected function getConfig()
    {
        $key = "attendance_radio_config";

        if (Cache::has($key)) {
            return Cache::get($key);
        }
        $config = CommonConfig::byNamespace('basepoint')->byName('attendance_radio')->first();
        $config = json_decode($config->value);

        Cache::put($key, $config);

        return $config;   
    }

    
}