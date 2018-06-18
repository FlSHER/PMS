<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/6/16/012
 * Time: 11:12
 */
namespace App\Services\Admin;

//use App\Repositories\EventTypeRepository;
use App\Repositories\AuthorityRepository;
use Illuminate\Support\Facades\Auth;

class AuthorityService
{
    protected $authRepository;

    public function __construct(AuthorityRepository $authorityRepository)
    {
        $this->authRepository=$authorityRepository;
    }

    /**
     * @return AuthorityRepository[]|\Illuminate\Database\Eloquent\Collection
     * 权限分组列表
     */
    public function indexAuthGroup($request)
    {
        return $this->authRepository->getAuthGroupList($request);
    }

    /**
     * @param $request
     * 添加权限分组
     */
    public function addAuthGroup($request)
    {
        $auth = $this->authRepository->firstAuthGroup($request->name);
        $arrayId = false != $auth ? (array)$auth['id'] : (array)$this->authRepository->addAuthority($request);
        $authorityId = implode($arrayId);
        if ($request->staff_sn != null) {
            try {
                $staffInfo = app('api')->withRealException()->getStaff($request->staff_sn);
            } catch (\Exception $e) {
                abort(400, '当前员工编号错误');
            }
            if ($this->authRepository->staffOnly($authorityId, $request->staff_sn) != null) {
                abort(400, '当前员工编号已存在');
            };
            $staffData['authority_group_id'] = $authorityId;
            $staffData['staff_sn'] = $request->staff_sn;
            $staffData['staff_name'] = $staffInfo['realname'];
            $this->authRepository->addStaff($staffData);
        };
        if ($request->department_id != null) {
            try {
                $departmentInfo = app('api')->withRealException()->getDepartmenets($request->department_id);
            } catch (\Exception $e) {
                abort(400, '当前部门id错误');
            }
            if ($this->authRepository->departmentOnly($authorityId, $request->department_id) != null) {
                abort(400, '当前部门id已存在');
            };
            $departmentData['group_id'] = $authorityId;
            $departmentData['department_id'] = $request->department_id;
            $departmentData['department_full_name'] = $departmentInfo['full_name'];
            $this->authRepository->addDepartment($departmentData);
        }
        $authId = ['id' => $authorityId];
        return response($this->authRepository->getIdAuthGroup($authId), 201);
    }

    public function editAuthGroup($request)
    {//todo 名字不能用传上来的    自己oa查找
        if($request->name != null){
            if($this->authRepository->updateFirstAuthGroup($request)){
                abort(404,'分组名称重复');
            };
            $authGroup=$this->authRepository->editAuthGroup($request);
            if(false == (bool)$authGroup){
                abort(404,'分组操作失败');
            }
        }
        if($request->staff_sn != null){
            $staffGroup=$this->authRepository->editStaffGroup($request);
            if(false == (bool)$staffGroup){
                abort(404,'分组员工操作失败');
            }
        }
        if($request->department_id != null){
            $department=$this->authRepository->editDepartmentGroup($request);
            if(false == (bool)$department){
                abort(404,'分组员工操作失败');
            }
        }
        return response($this->authRepository->getIdAuthGroup($request->route('id')), 201);
    }

    public function deleteAuthGroup($request)
    {

    }
}