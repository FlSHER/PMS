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
use DB;

class AuthorityService
{
    protected $authRepository;

    public function __construct(AuthorityRepository $authorityRepository)
    {
        $this->authRepository = $authorityRepository;
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
        $arrayId = (array)$this->authRepository->addAuthority($request);
        $authorityId = implode($arrayId);
        if ($request->staff != null) {
            try{
                DB::beginTransaction();
                foreach ($request->staff as $k => $v) {
                    $bool = $this->authRepository->staffOnly($authorityId, $v['staff_sn']);
                    if (true == (bool)$bool) {
                        DB::rollback();
                        abort(400, $v['staff_sn'] . '员工编号已存在');
                    }
                    $this->authRepository->editStaffGroup($authorityId, $v);
                }
                DB::commit();
            }catch (\Exception $e){
                DB::rollback();
                abort(400, $v['staff_sn'] . '添加失败');
            }
        }
        if ($request->departments != null) {
            try{
                DB::beginTransaction();
                foreach ($request->departments as $key => $val) {
                    $departmentBool = $this->authRepository->departmentOnly($authorityId, $val['department_id']);
                    if (true == (bool)$departmentBool) {
                        DB::rollback();
                        abort(400, $val['department_id'] . '部门已存在');
                    }
                    $this->authRepository->editDepartmentGroup($authorityId, $val);
                }
                DB::commit();
            }catch (\Exception $e){
                DB::rollback();
                abort(400, $val['department_id'] . '添加失败');
            }
        }
        $authId = ['id' => $authorityId];
        return response($this->authRepository->getIdAuthGroup($authId), 201);
    }

    /**
     * @param $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * 编辑权限分组
     */
    public function editAuthGroup($request)
    {
        $id = $request->route('id');
        if ($request->name != null) {
            $authGroup = $this->authRepository->editAuthGroup($request);
            if (false == (bool)$authGroup) {
                abort(400, '分组操作失败');
            }
        }
        if ($request->staff != null) {
            try {
                DB::beginTransaction();//开始
                $this->authRepository->deleteStaffGroup($id);
                foreach ($request->staff as $k => $v) {
                    $this->authRepository->editStaffGroup($id, $v);
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                abort(400, '分组员工操作失败');
            }
        }
        if ($request->departments != null) {
            try {
                DB::beginTransaction();//开始
                $this->authRepository->deleteDepartmentGroup($id);
                foreach ($request->departments as $key => $val) {
                    $this->authRepository->editDepartmentGroup($id, $val);
                }
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                abort(400, '分组部门操作失败');
            }
        }
        return response($this->authRepository->getIdAuthGroup($request->route('id')), 201);
    }

    /**
     * @param $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * 删除权限分组
     */
    public function deleteAuthGroup($request)
    {
        $status = $this->authRepository->deleteAuthGroup($request);
        if ((bool)$status == true) {
            return response('', 204);
        }
        abort(400, '删除失败');
    }
}