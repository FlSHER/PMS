<?php

/**
 * 权限，简单的表单验证
 */

namespace App\Http\Controllers\Admin;

use Validator;

use Illuminate\Http\Request;
use App\Services\EventApprove;
use App\Services\Admin\EventService;
use App\Services\Admin\EventTypeService;
use App\Http\Requests\Admin\EventRequest;

class EventController extends Controller
{
    protected $typeService;

    public function __construct(EventTypeService $typeService, EventService $eventService)
    {
        $this->typeService = $typeService;
        $this->eventService = $eventService;
    }

    /**
     * @param Request $request
     * 获取事件列表
     * 参数：type_id类型id,name事件名称,is_active激活状态,first_approver_sn初审人，first_approver_name初审人编号，final_approver_sn终审人,final_approver(2)_name终审人编号
     */
    public function index(Request $request)
    {
        return $this->eventService->index($request);
    }

    /**
     * @param EventRequest $request
     * 事件添加
     */
    public function store(EventRequest $request)
    {
        return $this->eventService->addEvent($request);
    }

    /**
     * @param EventRequest $request
     * @return mixed
     * 更新事件
     */
    public function update(EventRequest $request)
    {
        return $this->eventService->updateEvent($request);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response
     * 事件删除
     */
    public function delete(Request $request)
    {
        return $this->eventService->deleteEventFirstData($request);
    }

    /**
     * @param Request $request
     * excel导入
     */
    public function import(Request $request)
    {
        $this->validate($request, ['file' => 'required',], [], ['file' => '文件',]);
        return $this->eventService->excelImport();
    }

    /**
     * @param Request $request
     * excel 导出
     */
    public function export(Request $request)
    {
        return $this->eventService->excelExport($request);
    }

    /**
     * 导出范例文件
     */
    public function example()
    {
        return $this->eventService->excelExample();
    }

    /**
     * @param Request $request
     * @return mixed
     * 获取全部事件分类
     * 参数：无
     */
    public function indexType()
    {
        return $this->typeService->evenTypeList();
    }

    /**
     * @param Request $request
     * 事件分类排序/修改父级
     */
    public function refactorType(Request $request)
    {
        $old = $request->input('old_data');
        $new = $request->input('new_data');
        $this->eventTypeSortVerify($request);
        return $this->typeService->editEventType($old, $new);
    }

    /**
     * @param Request $request
     * 添加事件分类
     */
    public function storeType(EventRequest $request)
    {
        $all = $request->all();
        $this->eventTypeVerify($request);
        return $this->typeService->addEventType($all);
    }

    /**
     * @param Request $request
     * 编辑事件分类
     */
    public function updateType(Request $request)
    {
        $this->eventTypeVerify($request);
        return $this->typeService->updateEventType($request);
    }

    /**
     * @param Request $request
     * 删除事件分类
     */
    public function deleteType(Request $request)
    {
        return $this->typeService->deleteEventType($request->route('id'));
    }

    /**
     * @param Request $request
     *事件分类手动验证
     * store
     */
    protected function eventTypeVerify($request)
    {
        $this->validate($request, [
            'parent_id' => 'nullable|numeric',
            'name' => 'required',
            'sort' => 'required|numeric',
        ], [], [
            'parent_id' => '父类id',
            'name' => '名字',
            'sort' => '排序',
        ]);
    }

    /**
     * @param $request
     * 嵌套验证
     */
    protected function eventTypeSortVerify($request)
    {
        $this->validate($request, [
            'new_data.*.parent_id' => 'nullable|numeric',
            'new_data.*.name' => 'required',
            'new_data.*.sort' => 'required|numeric',
        ], [], [
            'new_data.*.parent_id' => '父类id',
            'new_data.*.name' => '名字',
            'new_data.*.sort' => '排序',
        ]);
    }
}