<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/4/12/012
 * Time: 16:57
 */

namespace App\Services\Admin;

use App\Models\Event;
use App\Models\EventType;
use Excel;
use App\Repositories\EventTypeRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use App\Repositories\EventRepository;
use SebastianBergmann\Exporter\Exporter;

class EventService
{
    public function __construct(EventRepository $eventRepository, EventTypeRepository $eventTypeRepository, \Maatwebsite\Excel\Excel $excel)
    {
        $this->eventRepository = $eventRepository;
        $this->eventTypeRepository = $eventTypeRepository;
        $this->excel = $excel;
    }

    /**
     * @param $request
     * @return mixed
     * 事件list页面
     */
    public function index($request)
    {
        return $this->eventRepository->getPaginateList($request);
    }

    /**
     * @param $request
     * @return mixed
     * 添加事件
     */
    public function addEvent($request)
    {
        $this->verifyEventType($request->type_id, $request->name);
        return $this->eventRepository->addEventData($request);
    }

    /**
     * @param $request
     * @return mixed
     * 事件修改
     */
    public function updateEvent($request)
    {
        $bool = $this->eventTypeRepository->getEventTypeData($request->type_id);
        if (false == (bool)$bool) {
            abort(404, '没有找到当前的事件分类');
        }
        $event = $this->eventRepository->updateGetOnly($request->route('id'), $request->name);
        if (true == (bool)$event) {
            abort(404, '当前名字已经被使用');
        }
        $request->first_approver_locked = $request->first_approver_sn > 0 ? $request->first_approver_sn : 0;//todo bug 审核人为空 锁定应当为0   数据仓库是接收all在这里改不了
        $request->final_approver_locked = $request->final_approver_sn > 0 ? $request->final_approver_locked : 0;
        return $this->eventRepository->updateEventData($request);
    }

    /**
     * @param $id
     * 检测有没有当前事件类型id
     */
    public function verifyEventType($id, $name)
    {
        $bool = $this->eventTypeRepository->getEventTypeData($id);
        if (false == (bool)$bool) {
            abort(404, '没有找到当前的事件分类');
        }
        $nameOnly = $this->eventRepository->nameWhereGetData($name);
        if (true == (bool)$nameOnly) {
            abort(404, '当前名字已经被占用');
        }
    }

    /**
     * @param $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\JsonResponse|\Symfony\Component\HttpFoundation\Response
     * 删除事件
     */
    public function deleteEventFirstData($request)
    {
        return $this->eventRepository->deleteEvents($request);
    }

    /**
     * 范例文件导出
     */
    public function excelExample()
    {
        $cellData[] = ['事件名字', '事件分类全称', 'A分最小值', 'A分最大值', 'B分最小值', 'B分最大值', 'A分默认值', 'B分默认值', '初审人编号（一个人）', '终审人编号（一个人）', '是否锁定初审人', '是否锁定终审人', '默认抄送人编号（可以选择多个人）', '是否激活'];
        $cellData[] = ['例：迟到', '例：工作类事件（此名称不能和以前重复）', '例：10', '例：20', '例：5', '例：10', '例：15', '8', '例：100000(可为空)', '例：100001(可为空)', '例：1（1：锁定，0：未锁定）', '例：1（1：锁定，0：未锁定）', '例：（编号=名字）100000=张三,100001=李四,1000002=王五(可为空)', '例：1（1：激活，0未激活）'];
        $fileName = '积分制事件导入范例文件';
        Excel::create($fileName, function ($excel) use ($cellData) {
            $excel->sheet('score', function ($sheet) use ($cellData) {
                $sheet->rows($cellData);
            });
        })->export('xlsx');
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     * Excel 导入
     */
    public function excelImport()
    {
        $excelPath = $_FILES['file']['tmp_name'];
        $res = [];
        Excel::load($excelPath, function ($matter) use (&$res) {
            $matter = $matter->getSheet();
            $res = $matter->toArray();
        });
        for ($i = 1; $i < count($res); $i++) {
            $x = $i + 1;
            $errorInfo = [];
            $dataInfo = [];
            if (count($res[$i]) != 14) {
                $errorInfo[] = ['序号：第' . $x . '条信息添加失败，错误：文件布局错误'];
            }
            if (!is_numeric($res[$i][2]) || !is_numeric($res[$i][3]) || !is_numeric($res[$i][4]) || !is_numeric($res[$i][5]) ||
                !is_numeric($res[$i][6]) || !is_numeric($res[$i][7]) || !is_numeric($res[$i][13])) {
                $errorInfo[] = ['序号：第' . $x . '条信息添加失败，错误：数字被文字代替'];
            }
            if (strlen($res[$i][0]) >= 147) {//数据库长度是50
                $errorInfo[] = ['序号：第' . $x . '条信息添加失败，错误：事件名称过长'];
                $dataInfo[] = $res[$i][0];
            }
            $onlyType = Event::where('name', $res[$i][0])->value('name');
            if ($onlyType == true) {
                $errorInfo[] = ['序号：第' . $x . '条信息添加失败，错误：事件名字重复 '];
                $dataInfo[] = $res[$i][0];
            }
            $eventTypeId = EventType::where('name', $res[$i][1])->value('id');
            if (false == (bool)$eventTypeId) {
                $errorInfo[] = ['序号：第' . $x . '条信息添加失败，错误：事件分类错误'];
                $dataInfo[] = $res[$i][1];
            }
            if ($res[$i][8] != '') {
                if (strlen($res[$i][8]) != 6 || !is_numeric($res[$i][8])) {
                    $errorInfo[] = ['序号：第' . $x . '条信息添加失败，错误：初审人编号长度错误'];
                    $dataInfo[] = $res[$i][8];
                }
                try {
                    $firstOa = app('api')->withRealException()->getStaff((int)$res[$i][8]);
                } catch (\Exception $e) {
                    $errorInfo[] = ['序号：第' . $x . '条信息添加失败，错误：初审人编号错误'];
                    $dataInfo[] = $res[$i][8];
                }
            }
            if ($res[$i][9] != '') {
                try {
                    $finalOa = app('api')->withRealException()->getStaff((int)$res[$i][9]);//todo 终审人里面找   待改
                } catch (\Exception $e) {
                    $errorInfo[] = ['序号：第' . $x . '条信息添加失败，错误：终审人编号错误'];
                    $dataInfo[] = $res[$i][9];
                }
            }
            if ($res[$i][2] >= $res[$i][3]) {
                $errorInfo[] = ['序号：第' . $x . '条信息添加失败，错误：A分最大值小于A分最小值'];
                $dataInfo[] = $res[$i][2] . '>' . $res[$i][3];;
            }
            if ($res[$i][4] >= $res[$i][5]) {
                $errorInfo[] = ['序号：第' . $x . '条信息添加失败，错误：B分最大值小于B分最小值'];
                $dataInfo[] = $res[$i][4] . '>' . $res[$i][5];
            }
            if ($res[$i][2] > $res[$i][6] || $res[$i][3] < $res[$i][6]) {
                $errorInfo[] = ['序号：第' . $x . '条信息添加失败，错误：默认值不在AB分之间'];
                $dataInfo[] = $res[$i][2] . '>' . $res[$i][6] . '>' . $res[$i][3];
            }
            if (isset($res[$i][12])) {
                $arr = explode(',', $res[$i][12]);
                $t = 0;
                $errorInfo1 = [];
                foreach ($arr as $k => $v) {
                    $t++;
                    $array = explode('=', $v);
                    try {
                        $oaData = app('api')->withRealException()->getStaff($array[0]);
                    } catch (\Exception $e) {
                        $errorInfo1[] = '抄送人第' . $t . '个编号错误';
                    }
                    if ($oaData['realname'] != $array[1]) {
                        $errorInfo1[] = '抄送人第' . $t . '个编号和名字不匹配';
                    }
                }
                if ($errorInfo1 != []) {
                    $errorInfo[] = ['序号：第' . $x . '条信息添加失败，错误：' . implode('、', $errorInfo1)];
                    $dataInfo[] = $res[$i][12];
                }
            }
            if ($errorInfo != []) {
                $err[] = [
                    'data' => (object)$dataInfo,
                    'message' => (object)$errorInfo
                ];
                continue;
            }
            $model = new Event();
            $model->name = $res[$i][0];//事件名称
            $model->type_id = $eventTypeId;//事件分类
            $model->point_a_min = $res[$i][2];//A分最小
            $model->point_a_max = $res[$i][3];//A分最大
            $model->point_b_min = $res[$i][4];//B分最小
            $model->point_b_max = $res[$i][5];//B分最大
            $model->point_a_default = $res[$i][6];//A分默认
            $model->point_b_default = $res[$i][7];//B分默认
            $model->first_approver_sn = $res[$i][8];//初审编号
            $model->first_approver_name = $firstOa->realname == true ? $firstOa->realname : "";//初审姓名
            $model->final_approver_sn = $res[$i][9];//终审编号
            $model->final_approver_name = $firstOa->realname == true ? $finalOa->realname : "";//终审姓名   //todo  终审人去列表查
            $model->first_approver_locked = $res[$i][8] == true ? $res[$i][10] : 0;//初审锁定
            $model->final_approver_locked = $res[$i][9] == true ? $res[$i][11] : 0;//终审锁定
            $model->default_cc_addressees = isset($arr) ? $arr : '';//默认抄送
            $model->is_active = $res[$i][13] == 1 ? 1 : 0;//激活
            $success = $model->save();
            if ($success == true) {
                $successInfo[] = '序号：第' . $x . '条导入成功';
            }
        }
        $data['data'] = isset($successInfo) ? (object)$successInfo : [];
        $data['errors'] = isset($err) ? $err : [];
        return $info[] = $data;
    }

    /**
     * @param $request
     * @return \Illuminate\Http\JsonResponse
     * 事件导出
     */
    public function excelExport($request)
    {
        $eventData = $this->eventRepository->getList($request);
        if (false == (bool)$eventData->all()) {
            return response()->json(['message' => '没有找到符号条件的数据'], 404);
        }
        $eventTop[] = ['事件', '分类', 'A分最小值', 'A分最大值', 'B分最小值', 'B分最大值', 'A分平均值', 'B分平均值', '默认初审人', '默认终审人', '初审人锁定', '终审人锁定', '默认抄送', '是否激活'];
//        $value   []=['name','type_id','point_a_min','point_a_max','point_b_min','point_b_max','point_a_default','point_b_default','first_approver_name','final_approver_name','first_approver_locked','final_approver_locked','default_cc_addressees','is_active'];
        foreach ($eventData as $k => $v) {
            $eventTop[] = [$v['name'], $v['type_id'], $v['point_a_min'], $v['point_a_max'],
                $v['point_b_min'], $v['point_b_max'], $v['point_a_default'], $v['point_b_default'],
                $v['first_approver_name'], $v['final_approver_name'] == 1 ? '锁定' : '未锁定',
                $v['first_approver_locked'] == 1 ? '锁定' : '未锁定', $v['final_approver_locked'],
                $v['default_cc_addressees'] != false ? implode(',', $v['default_cc_addressees'])
                    : $v['default_cc_addressees'], $v['is_active'] == 1 ? '激活' : '未激活'];
        }
        Excel::create('积分制事件', function ($excel) use ($eventTop) {
            $excel->sheet('score', function ($query) use ($eventTop) {
                $query->rows($eventTop);
            });
        })->export('xlsx');
    }
}