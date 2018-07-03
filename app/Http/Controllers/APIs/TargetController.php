<?php 

namespace App\Http\Controllers\APIs;

use Carbon\Carbon;
use Illuminate\Http\Request;
use function App\monthBetween;
use App\Models\PointManagementTargets as TargetModel;
use App\Models\PointManagementTargetLogs as TargetLogModel;
use App\Models\PointManagementTargetHasStaff as TargetStaffModel;
use App\Models\PointManagementTargetLogHasStaff as TargetLogStaffModel;

class TargetController extends Controller
{
    
    /**
     * 获取奖扣指标.
     * 
     * @author 28youth
     * @param  \Illuminate\Http\Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $datetime = Carbon::parse($request->query('datetime'));

        $target = TargetStaffModel::query()
            ->where('staff_sn', $user->staff_sn)
            ->leftJoin(
                'point_management_targets', 
                'point_management_target_has_staff.target_id', '=', 'point_management_targets.id'
            )
            ->first();

        if ($target === null) {
            return response()->json(['messsage' => '还未被分配指标'], 404);
        }

        $data = TargetLogStaffModel::query()
            ->with('targetlog')
            ->where('staff_sn', $target->staff_sn)
            ->whereBetween('date', monthBetween($datetime))
            ->first();

        return response()->json($data, 200);
    }
}