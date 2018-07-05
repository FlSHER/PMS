<?php
/**
 * 权限，简单的表单验证
 */

namespace App\Http\Controllers\Admin;

use App\Services\Admin\EventService;

use Illuminate\Validation\Rule;
use Validator;
use App\Providers\RepositoryServiceProvider;
use App\Http\Requests\Admin\EventRequest;
use App\Services\Admin\EventTypeService;
use Illuminate\Http\Request;

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
    public function update(Request $request)
    {
        $this->editEventVerify($request);
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
    public function storeType(Request $request)
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

    /**
     * 事件编辑验证
     * @param $request
     */
    public function editEventVerify($request)
    {
        $this->validate($request,[
            'name'=>['required','max:40',
                Rule::unique('events', 'name')
                    ->where('type_id',$request->all('type_id'))
                    ->whereNotIn('id',explode(' ',$request->route('id')))
                    ->ignore('id', $request->get('id', 0))
            ],
            'type_id'=>'required|numeric',
            'point_a_min'=>'required|numeric',
            'point_a_max'=>'required|numeric',
            'point_a_default'=>'required|numeric',
            'point_b_min'=>'required|numeric',
            'point_b_max'=>'required|numeric',
            'point_b_default'=>'required|numeric',
//            'first_approver_sn'=>'',
//            'first_approver_name'=>'',
//            'final_approver_sn'=>'',
//            'final_approver_name'=>'',
            'first_approver_locked'=>'required|min:0|max:1',//0未锁定1锁定
            'final_approver_locked'=>'required|min:0|max:1',//0未锁定1锁定
//            'default_cc_addressees'=>'nullable',
            'is_active'=>'required|min:0|max:1'//0未激活1激活
        ],[],[
            'name'=>'事件名称',
            'type_id'=>'事件类型',
            'point_a_min'=>'A分最小值',
            'point_a_max'=>'A分最大值',
            'point_a_default'=>'A分默认值',
            'point_b_min'=>'B分最小值',
            'point_b_max'=>'B分最大值',
            'point_b_default'=>'B分默认值',
//            'first_approver_sn'=>'初审人编号',
//            'first_approver_name'=>'初审人姓名',
//            'final_approver_sn'=>'终审人编号',
//            'final_approver_name'=>'终审人姓名',
            'first_approver_locked'=>'初审人锁定',//0未锁定1锁定
            'final_approver_locked'=>'终审人锁定',//0未锁定1锁定
//            'default_cc_addressees'=>'默认抄送人',
            'is_active'=>'是否激活'//0未激活1激活
        ]);
    }
}