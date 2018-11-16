<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Services\Admin\PointService;
use Illuminate\Support\Facades\Validator;

class PointController extends Controller
{
    protected $point;
    protected $error;

    public function __construct(PointService $point)
    {
        $this->point = $point;
    }

    /**
     * @param Request $request
     * 积分变动日志
     */
    public function index(Request $request)
    {
        return $this->point->index($request);
    }

    /**
     * @param Request $request
     * @return mixed
     *积分变动详情
     */
    public function details(Request $request)
    {
        return $this->point->getDetails($request);
    }

    /**
     * @param Request $request .
     * 积分变动导出
     */
    public function export(Request $request)
    {
        return $this->point->export($request);
    }

    public function store(Request $request)
    {
        $all = $request->all();
        if (count($all) == count($all, 1)) {
            $this->storeVerify($request);
        } else {
            $this->error = [];
            for ($i = 0; $i < count($all); $i++) {
                $obj = new \App\Http\Requests\Admin\EventRequest($all[$i]);
                $this->storeVerify($obj);
            }
            if ($this->error != []) {
                return $this->error;
            }
        }
        return $this->point->storePoint($all);
    }

    public function delete(Request $request)
    {
        return $this->point->deletePoint($request->route('id'));
    }

    protected function storeVerify($request)
    {
        try {
            $this->validate($request,
                [
                    'title' => 'required|max:50',
                    'staff_sn' => 'numeric|required|digits:6',
                    'staff_name' => 'required|max:10',
                    'brand_id' => 'required|numeric|max:255',
                    'brand_name' => 'required|max:10',
                    'department_id' => 'required|numeric|max:32767',
                    'department_name' => 'required|max:100',
                    'shop_sn' => 'max:10|nullable',
                    'shop_name' => 'max:50|nullable',
                    'point_a' => 'required|numeric|max:8388607|min:-8388608',
                    'point_b' => 'required|numeric|max:8388607|min:-8388608',
                    'changed_at' => 'date|nullable',
                    'source_id' => 'required|exists:point_log_sources,id',
                    'source_foreign_key' => 'nullable|numeric|max:2147483647',//调用方 数据id
                    'first_approver_sn' => 'nullable|digits:6|numeric',
                    'first_approver_name' => 'required|max:10',
                    'final_approver_sn' => 'nullable|digits:6|numeric',
                    'final_approver_name' => 'required|max:10',
                    'type_id' => 'required|numeric|exists:point_types,id',
                    'is_revoke' => 'between:0,1',
                ], [], [
                    'title' => '标题',
                    'staff_sn' => '员工编号',
                    'staff_name' => '员工姓名',
                    'brand_id' => '品牌ID',
                    'brand_name' => '品牌名称',
                    'department_id' => '部门ID',
                    'department_name' => '部门名称',
                    'shop_sn' => '店铺编号',
                    'shop_name' => '店铺名称',
                    'point_a' => 'A分',
                    'point_b' => 'B分',
                    'changed_at' => '变化时间',
                    'source_id' => '积分来源',
                    'source_foreign_key' => '来源关联ID',
                    'first_approver_sn' => '初审人编号',
                    'first_approver_name' => '初审人姓名',
                    'final_approver_sn' => '终审人编号',
                    'final_approver_name' => '终审人姓名',
                    'type_id' => '分类ID',
                    'is_revoke' => '是否撤回记录',
                ]
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            $this->error[] = $e->validator->errors()->getMessages();
        } catch (\Exception $e) {
            $this->error['message'] = '系统异常：' . $e->getMessage();
        }
    }
}