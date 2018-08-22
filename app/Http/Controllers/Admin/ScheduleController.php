<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\AuthorityGroupHasStaff;
use App\Models\AttendanceRecord;

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
     * 导出考勤记录.
     * 
     * @author 28youth
     * @return excel
     */
    public function exort()
    {
        # code...
    }

    
}