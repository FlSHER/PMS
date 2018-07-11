<?php

namespace App\Services\Admin;

use App\Repositories\StatisticRepository;
use DB;
use App\Repositories\TaskAuthorityRepositories;
use Illuminate\Http\Request;
use Prophecy\Exception\Exception;


class StatisticService
{
    protected $statistic;

    public function __construct(StatisticRepository $statisticRepository)
    {
        $this->statistic=$statisticRepository;
    }
    /**
     * 获取管理列表
     *
     * @param Request $request
     * @return TaskAuthorityRepositories[]|\Illuminate\Database\Eloquent\Collection
     */
    public function getTaskAuthorityList(Request $request)
    {
        $array = $this->statistic->getTaskList($request);
        return $array;
    }

    /**
     * 添加管理人员
     *
     * @param $request
     * @return mixed
     */
    public function addTask($request)
    {
        $all = $request->all();
        \DB::beginTransaction();
        try {
            $this->statistic->deleteStaff($all['admin_sn']);
            foreach ($all['groups'] as $key => $value) {
                $bool = $this->statistic->addTaskData($value['id'], $all['admin_sn'], $all['admin_name']);
                if (false == (bool)$bool) {
                    \DB::rollback();
                    abort(400, '操作失败');
                }
            }
            \DB::commit();
        } catch (\Exception $e) {
            \DB::rollback();
            abort(400, '操作失败');
        }
        return response($this->statistic->getTaskFirst($all['admin_sn']),201);
    }

    /**
     * 删除权限人员
     *
     * @param $id
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function delTask($adminSn)
    {
        if ($this->statistic->getTask($adminSn) == null) {
            abort(404, '提供无效的参数');
        }
        if ($this->statistic->deleteTaskData($adminSn)) {
            return response('', 204);
        } else {
            return response('', 400);
        }
    }
}