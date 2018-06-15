<?php 

namespace App\Http\Controllers\APIs;

use Illuminate\Http\Request;
use App\Models\AuthorityGroup;

class AuthorityController extends Controller
{
	/**
	 * 获取员工权限分组.
	 * 
	 * @author 28youth
	 * @param  Illuminate\Http\Request $request
	 * @param  App\Models\AuthorityGroup $group
	 * @return mixed
	 */
	public function index(Request $request, AuthorityGroup $group)
	{
		$user = $request->user();

		$items = $group->query()
			->whereHas('hasStaff', function ($query) use ($user) {
			    $query->where('staff_sn', $user->staff_sn);
			})
			->get();

		return response()->json($items, 200);
	}
	
}