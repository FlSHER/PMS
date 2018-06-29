<?php

namespace App\Services\Admin;

use App\Repositories\PointTargetRepository;
use Illuminate\Http\Request;

class PointTargetService
{
    protected $targetRepository;

    public function __construct(PointTargetRepository $targetRepository)
    {
        $this->targetRepository = $targetRepository;
    }

    /**
     * @return mixed
     * 获取列表
     */
    public function getPointList()
    {
        return $this->targetRepository->pointList();
    }

    /**
     * @param $request
     * @return \App\Models\PointManagementTargets|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|null|object
     * 获取单条详细信息
     */
    public function getSingle($request)
    {
        return $this->targetRepository->targetDetails($request);
    }

    /**
     * @param $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * 删除任务
     */
    public function deleteMission($request)
    {
        $deleteId = $request->route('id');
        $this->targetRepository->deleteStaff($deleteId);
        $this->targetRepository->deleteTarget($deleteId);
        return response('', 204);
    }

    /**
     * @param $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * 添加数据
     */
    public function addTarget($request)
    {
        return $this->targetRepository->addTargetData($request);
    }

    /**
     * @param $request
     * @return mixed
     * 修改指标
     */
    public function editTarget($request)
    {
        return $this->targetRepository->updateTarget($request);
    }

    /**
     * @param $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * 批量同步人员
     */
    public function editStaff($request)
    {
        $staff = $request->all();
        if ($staff == true) {
            $id = $request->route('id');
            $this->targetRepository->deleteStaff($id);
            foreach ($staff as $k => $v) {
                $this->targetRepository->updateStaff($id, $v);
            }
            return response('修改成功', 201);
        }
    }
}