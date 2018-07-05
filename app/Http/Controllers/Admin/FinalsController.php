<?php

namespace App\Http\Controllers\Admin;

use App\Services\Admin\FinalsService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinalsController extends Controller
{
    protected $finals;

    public function __construct(FinalsService $finals)
    {
        $this->finals = $finals;
    }

    /**
     * @param Request $request
     * list页面
     */
    public function index()
    {
        return $this->finals->getFinalsList();
    }

    /**
     * @param Request $request
     * @return mixed
     * 添加
     */
    public function store(Request $request)
    {
        $this->addFormVerify($request);
        return $this->finals->addFinals($request);
    }

    /**
     * @param Request $request
     * 编辑
     */
    public function edit(Request $request)
    {
        $this->editFormVerify($request);
        return $this->finals->updateFinals($request);
    }

    /**
     * @param Request $request
     * 删除
     */
    public function delete(Request $request)
    {
        return $this->finals->deleteFinals($request);
    }

    /**
     * @param Request $request
     * form验证
     */
    protected function addFormVerify(Request $request)
    {
        $this->validate($request, [
            'staff_sn' => 'required|numeric|unique:final_approvers,staff_sn',
            'staff_name' => 'required',
            'point_a_awarding_limit' => 'required|numeric',
            'point_a_deducting_limit' => 'required|numeric',
            'point_b_awarding_limit' => 'required|numeric',
            'point_b_deducting_limit' => 'required|numeric',
        ], [], [
            'staff_sn' => '审批编号',
            'staff_name' => '审批人姓名',
            'point_a_awarding_limit' => '加A分上限',
            'point_a_deducting_limit' => '减A分上限',
            'point_b_awarding_limit' => '加B分上限',
            'point_b_deducting_limit' => '减B分上限',
        ]);
    }

    /**
     * 终审人编辑form验证
     * @param Request $request
     */
    protected function editFormVerify(Request $request)
    {
        $this->validate($request, [
            'staff_sn' => ['required',
                Rule::unique('final_approvers','staff_sn')
                    ->whereNotIn('id',explode(' ',$request->route('id')))
                    ->ignore('id', $request->get('id', 0))
            ],
            'staff_name' => 'required',
            'point_a_awarding_limit' => 'required|numeric',
            'point_a_deducting_limit' => 'required|numeric',
            'point_b_awarding_limit' => 'required|numeric',
            'point_b_deducting_limit' => 'required|numeric',
        ], [], [
            'staff_sn' => '终审批编号',
            'staff_name' => '终审批人姓名',
            'point_a_awarding_limit' => '加A分上限',
            'point_a_deducting_limit' => '减A分上限',
            'point_b_awarding_limit' => '加B分上限',
            'point_b_deducting_limit' => '减B分上限',
        ]);
    }
}
