<?php

namespace App\Http\Controllers\Admin;

use App\Services\Admin\AuthorityService;
use Illuminate\Http\Request;

class AuthorityController extends Controller
{

    protected  $auth;

    public function __construct(AuthorityService $auth)
    {
        $this->auth=$auth;
    }

    /**
     * @param Request $request
     * @return \App\Repositories\AuthorityRepository[]|\Illuminate\Database\Eloquent\Collection
     * 权限分组列表
     */
    public function indexGroup(Request $request)
    {
        return $this->auth->indexAuthGroup($request);
    }

    /**
     * @param Request $request
     * 添加权限分组
     */
    public function storeGroup(Request $request)//添加分组
    {
        $this->addAuthGroupVerify($request);
        return $this->auth->addAuthGroup($request);
    }

    /**
     * @param Request $request
     * 编辑权限分组
     */
    public function editGroup(Request $request)//编辑分组
    {
        $this->addAuthGroupVerify($request);
        return $this->auth->editAuthGroup($request);
    }

    /**
     * 删除权限分组
     */
    public function deleteGroup(Request $request)//删除分组
    {
        return $this->auth->deleteAuthGroup($request);
    }

    public function addAuthGroupVerify($request)
    {
        $this->validate($request, [
            'name' => 'required',
            'staff_sn' => 'numeric',
            'department_id' => 'numeric',
        ], [], [
            'name' => '分组名称',
            'staff_sn' => '员工编号',
            'department_id' => '部门id',
        ]);
    }
}
