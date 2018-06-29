<?php

namespace App\Services\Admin;

use App\Repositories\FinalsRepositories;
use Illuminate\Http\Request;

class FinalsService
{
    protected $finals;

    public function __construct(FinalsRepositories $finals)
    {
        $this->finals = $finals;
    }

    public function getFinalsList($request)
    {
        return $this->finals->getFinalsAll($request);
    }

    /**
     * @param $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * 添加终审人
     */
    public function addFinals($request)
    {
        if ($this->finals->repetition($request->staff_sn)) {
            abort(422, '当前员工编号已存在');
        };
        $id = $this->finals->delRepetition($request->staff_sn);
        if (true == (bool)$id) {
            $this->finals->restoreDeleteFinals($id);
            return $this->finals->deleteUpdateRestore($id, $request);
        }
        $bool = $this->finals->addFinals($request);
        if (false == (bool)$bool) {
            abort(404, '数据添加失败');
        }
        return response($this->finals->getFinals($bool), 201);
    }

    /**
     * @param $request
     * @return mixed
     * 编辑终审人
     */
    public function updateFinals($request)
    {
        if ($this->finals->editRepetition($request->route('id'), $request->staff_sn)) {
            abort(422, '当前员工编号已存在');
        }
        $id = $this->finals->delRepetition($request->staff_sn);
        if (true == (bool)$id) {
            $this->finals->restoreDeleteFinals($id);
            return $this->finals->deleteUpdateRestore($id, $request);
        }
        return $this->finals->updateFinals($request);
    }

    /**
     * @param $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     * 删除终审人
     */
    public function deleteFinals($request)
    {
        if (false == (bool)$this->finals->deleteFinalsData($request->route('id'))) {
            return response('删除失败', 404);
        }
        return response('', 204);
    }
}