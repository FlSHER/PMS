<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/12/012
 * Time: 16:57
 */
namespace App\Services\Admin;

use App\Repositories\EventTypeRepository;
use Illuminate\Support\Facades\Auth;

class EventTypeService
{
    protected $typeRepository;

    public function __construct(EventTypeRepository $typeRepository)
    {
        $this->typeRepository=$typeRepository;
    }

    /**
     * @return array
     * 处理按父类id进行分类
     */
    public function evenTypeList()
    {
        return $this->typeRepository->evenTypeListGetData();
//        $eventTypeData=$this->typeRepository->EvenTypeListGetData();
//        return $this->treeStructureCycle($eventTypeData);
    }

    /**
     * @param $editData
     * 事件分类排序/修改父级
     */
    public function editEventType($old, $new)
    {
        foreach ($old as $key => $val) {
            $oldData = $this->typeRepository->getEventTypeDataToArray($val['id']);
            $differ = array_diff($oldData, $val);//todo 无法对比parent_id
            if ((bool)$differ == true) {
                $differError[] = $val['name'];
            }
        }
        if (isset($differError)) {
            $errorInfo = implode('、', $differError);
            return response()->json([
                'message' => $errorInfo . '数据已被更新'
            ], 400);
        }
        $accomplish = $this->typeRepository->updateEventTypeData($new);
        foreach ($accomplish as $k => $v) {
            if ($v == 0) {
                $error[] = $k;
            }
        }
        $errorData = isset($error) ? count($error) > 1 ? implode('、', $error) : false : false;
        if (false === $errorData) {
            return $this->typeRepository->evenTypeListGetData();
        } else {
            return response()->json(['message' => $errorData . '修改失败'], 400);
        }
    }

    /**
     * @param $arr
     * 添加事件类型
     */

    public function addEventType($arr)
    {
        $this->eventTypeNameOnly($arr['name']);
        $bool=$this->typeRepository->addEventType($arr);
        if(false==(bool)$bool){
            return response()->json([
                'message'=>'添加数据失败'
            ],400);
        }else{
            return $bool;
        }
    }

    /**
     * @param $arr
     * @return mixed|void
     * 修改事件分类
     */
    public function updateEventType($request)
    {
        $eventType = $this->typeRepository->getEventTypeData($request->route('id'));
        if(null == $eventType){
            abort(400,'id被非法变动');
        }
        $notData=$this->typeRepository->nameGetNotData($request->route('id'),$request->name);
        if(true == (bool)$notData){
            abort(400,'当前名字已经被占用');
        }
        $bool=$this->typeRepository->updateEventTypeRepository($request);
        return  false == (bool)$bool ? response()->json(['message'=>'信息修改失败'],400) : $bool ;
    }

    public function eventTypeNameOnly($name)
    {
        if(true == (bool)$this->typeRepository->nameGetData($name)){
            abort(404,'当前名字已经被占用');
        };
    }

    /**
     * 删除事件分类
     * 树结构下面所有分类删除及事件表也要删除
     * 110126
     */
    public function deleteEventType($id)
    {
        $list=$this->typeRepository->evenTypeListGetData();
        $arr=$this->treeCycle($list,$id);
        $arr[]=(int)$id;
        return $this->typeRepository->deleteEventType($arr);
    }

    /**
     * @param $list
     * @param $id
     * @param int $level
     * @return array
     * 获取所有该删除下面的子分类id
     */
    protected function treeCycle($list,$id,$level=0)
    {
        $subs=[];
        foreach($list as $item){
            if($item['parent_id']==$id){
                $item['level']=$level;
                $subs[]=$item['id'];
                $subs=array_merge($subs,$this->treeCycle($list,$item['id'],$level+1));
            }
        }
        return $subs;
    }
    /**
     * 循环树结构分类parent_id
     */
    protected function treeStructureCycle($list,$pk = 'id', $pid = 'parent_id', $child = 'son', $root = 0)
    {
        $tree     = [];
        $packData = [];
        foreach ($list as $data) {
            $packData[$data[$pk]] = $data;
        }
        foreach ($packData as $key => $val) {
            if ($val[$pid] == $root) {
                $tree[] = &$packData[$key];
            } else {
                $packData[$val[$pid]][$child][] = &$packData[$key];
            }
        }
        return $tree;
    }
}