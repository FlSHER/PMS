<?php

namespace App\Http\Controllers\APIs;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\AttendanceRecord;


class ScheduleController extends Controller
{

    /**
     * 获取考勤统计信息.
     *
     * @author 28youth
     * @param  Illuminate\Http\Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $data = AttendanceRecord::query()
            ->filterByQueryString()
            ->sortByQueryString()
            ->orderBy('id', 'desc')
            ->withPagination();

        return response()->json($data, 200);
    }

    /**
     * 获取单条考勤信息.
     *
     * @author 28youth
     * @param  App\Models\AttendanceRecord $record
     * @return mixed
     */
    public function show(AttendanceRecord $record)
    {
        return response()->json($record, 200);
    }
}
