<?php

namespace App\Http\Controllers\Admin;

use App\Models\AuthorityGroup;
use App\Models\TaskPublishingAuthorities;
use Illuminate\Validation\Rule;
use App\Services\Admin\TaskAuthorityService;
use Illuminate\Http\Request;

class TaskAuthorityController extends Controller
{
    protected $taskService;

    public function __construct(TaskAuthorityService $taskService)
    {
        $this->taskService = $taskService;
    }

    /**
     * 任务分配权限list页面
     * @param Request $request
     * @return TaskAuthorityService[]|\Illuminate\Database\Eloquent\Collection
     */
    public function index(Request $request)
    {
        return $this->taskService->getTaskAuthorityList($request);
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
        return $this->taskService->addTask($request);
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
        return $this->taskService->addTask($request);
    }

    /**
     * 删除管理人员
     *
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function delete(Request $request)
    {
        return $this->taskService->delTask($request->route('admin_sn'));
    }

    protected function addTaskFormVerify($request)
    {
        $this->validate($request, [
            'admin_sn' => 'required|numeric|digits:6|unique:task_publishing_authorities,admin_sn',
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
     * 任务分配验证
     *
     * @param $request
     */
    protected function editTaskFormVerify($request)
    {
        $this->validate($request, [
            'admin_sn' => ['required','numeric','digits:6',
//                function($attribute, $value, $fail) use($request){
//                    if((bool)TaskPublishingAuthorities::where('admin_sn',$request->user()->staff_sn)->first()==false){
//                        return $fail('未找到当前管理编号');
//                    }
//                }
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
