<?php

namespace App\Http\Controllers\Admin;

use App\Models\AuthorityGroup;
use App\Models\StatisticCheckingAuthorities;
use App\Services\Admin\StatisticService;
use Illuminate\Http\Request;

class StatisticController extends Controller
{
    protected $statistic;

    public function __construct(StatisticService $statisticService)
    {
        $this->statistic=$statisticService;
    }

    public function index(Request $request)
    {
        return $this->statistic->getTaskAuthorityList($request);
    }

    /**
     * 添加管理人员
     *
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {
        $this->addTaskFormVerify($request);
        return $this->statistic->addTask($request);
    }

    /**
     * 编辑管理人员
     *
     * @param Request $request
     * @return mixed
     */
    public function edit(Request $request)
    {
        $this->editTaskFormVerify($request);
        return $this->statistic->addTask($request);
    }

    /**
     * 删除管理人员
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function delete(Request $request)
    {
        return $this->statistic->delTask($request->route('admin_sn'));
    }

    /**
     * 添加验证
     *
     * @param $request
     */
    protected function addTaskFormVerify($request)
    {
        $this->validate($request, [
            'admin_sn' => 'required|numeric|digits:6|unique:statistic_checking_authorities,admin_sn',
            'admin_name' => 'required',
            'groups' => 'required',
            'groups.*.id' => ['required', 'numeric',
                function ($attribute, $value, $fail) {
                    $auth = AuthorityGroup::where('id', $value)->first();
                    if ((bool)$auth == false) {
                        return $fail('分组未找到');
                    }
                }
            ],
            'groups.*.name' => 'required'
        ], [], [
            'admin_sn' => '管理员编号',
            'admin_name' => '管理员名称',
            'groups' => '分组',
            'groups.*.name' => '分组名称',
            'groups.*.id' => '分组id',
        ]);
    }

    /**
     * 编辑验证
     *
     * @param $request
     */
    protected function editTaskFormVerify($request)
    {
        $this->validate($request, [
            'admin_sn' => ['required','numeric','digits:6',
                function($attribute, $value, $fail){
                    if((bool)StatisticCheckingAuthorities::where('admin_sn',$value)->first()==false){
                        return $fail('未找到当前管理员编号');
                    };
                }
                ],
            'admin_name' => 'required',
            'groups' => 'required',
            'groups.*.id' => ['required', 'numeric',
                function ($attribute, $value, $fail) {
                    $auth = AuthorityGroup::where('id', $value)->first();
                    if ((bool)$auth == false) {
                        return $fail('分组未找到');
                    }
                }
            ],
            'groups.*.name' => 'required'
        ], [], [
            'admin_sn' => '管理员编号',
            'admin_name' => '管理员名称',
            'groups' => '分组',
            'groups.*.name' => '分组名称',
            'groups.*.id' => '分组id',
        ]);
    }
}
