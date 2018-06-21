<?php

namespace App\Services\Admin;

use App\Repositories\PointRepository;
use Illuminate\Http\Request;
use Excel;

class PointService
{
    protected $point;

    public function __construct(PointRepository $pointRepository)
    {
        $this->point = $pointRepository;
    }

    /**
     * @param Request $request
     * @return array
     * 积分变动list页面
     */
    public function index(Request $request)
    {
        return $this->point->getPointList($request);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 积分变动导出
     */
    public function export(Request $request)
    {
        $point = $this->point->getPointData($request);
        if (false == (bool)$point->all()) {
            return response()->json(['message' => '没有找到符号条件的数据'], 404);
        }
        $pointTop[] = ['标题', '员工姓名', '品牌名称', '部门名称', '店铺名称',
            'A分变化', 'B分变化', '变化时间', '积分来源', '初审人姓名', '终审人姓名'];
        foreach ($point as $k => $v) {
            $pointTop[] = [$v['title'], $v['staff_name'], $v['brand_name'], $v['department_name'],
                $v['shop_name'], $v['point_a'], $v['point_b'], $v['changed_at'],
                $v['source_id'], $v['first_approver_name'], $v['final_approver_name'],];
        }
        Excel::create('积分变动日志', function ($excel) use ($pointTop) {
            $excel->sheet('score', function ($query) use ($pointTop) {
                $query->rows($pointTop);
            });
        })->export('xlsx');
    }
}