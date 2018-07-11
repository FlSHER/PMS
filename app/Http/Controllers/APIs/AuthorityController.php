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
     * @return mixed
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        // 员工权限分组
        $authGroup = AuthorityGroup::query()
            ->whereHas('staff', function ($query) use ($user) {
                $query->where('staff_sn', $user->staff_sn);
            })
            ->orWhereHas('departments', function ($query) use ($user) {
                $query->where('department_id', $user->department['id']);
            })->get();
            
        // 所有分组
        $statisGroup = AuthorityGroup::get();

        return response()->json([
            'auth_group' => $authGroup,
            'statis_group' => $statisGroup
        ], 200);
    }

}