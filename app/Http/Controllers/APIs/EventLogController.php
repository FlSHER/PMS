<?php 

namespace App\Http\Controllers\APIs;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\EventLog as EventLogModel;

class EventLogController extends Controller
{

	/**
	 * EventLogController constructor.
	 * 
	 * @author 28youth
	 */
	public function __construct(Request $request)
	{
		$this->middleware('auth:api');
	}

	/**
	 * 初审通过.
	 * 
	 * @author 28youth
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \App\Models\EventLog $eventlog
	 * @return mixed
	 */
	public function firstApprove(Request $request, EventLogModel $eventlog)
	{
		$user = $request->user();

		if ($eventlog->first_approver_at !== null) {
			return response()->json([
				'message' => '初审已通过'
			], 422);
		}

		$eventlog->first_approver_sn = $user->staff_sn;
		$eventlog->first_approver_name = $user->realname;
		$eventlog->first_approver_remark = $request->remark;
		$eventlog->first_approver_at = Carbon::now();
		$eventlog->save();

		return response()->json([
			'message' => '初审成功'
		], 201);
	}

	/**
	 * 终审通过.
	 * 
	 * @author 28youth
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \App\Models\EventLog $eventlog
	 * @return mixed
	 */
	public function finalApprove(Request $request, EventLogModel $eventlog)
	{
		$user = $request->user();

		if ($eventlog->final_approver_at !== null) {
			return response()->json([
				'message' => '终审已通过'
			], 422);
		}

		$eventlog->final_approver_sn = $user->staff_sn;
		$eventlog->final_approver_name = $user->realname;
		$eventlog->final_approver_remark = $request->remark;
		$eventlog->final_approver_at = Carbon::now();
		$eventlog->save();

		return response()->json([
			'message' => '终审成功'
		], 201);
	}
}