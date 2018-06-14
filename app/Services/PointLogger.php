<?php 

namespace App\Services;

use App\Jobs\PointLogger AS PointLoggerJob;
use App\Models\PointLog as PointLogModel;
use App\Models\EventLog as EventLogModel;

class PointLogger
{

    public function logEventPoint(EventLogModel $eventlog)
    {
        $title = '事件积分变化';  
        $participants = $eventlog->participant;
        $participants->map(function ($item) use ($title, $eventlog) {

            $this->createPointLog($item->staff_sn, [
                'title'  => $title,
                'point_a' => $item->point_a,
                'point_b' => $item->point_b,
                'source_foreign_key' => $eventlog->id,
                'first_approver_sn' => $eventlog->first_approver_sn,
                'first_approver_name' => $eventlog->first_approver_name,
                'final_approver_sn' => $eventlog->final_approver_sn,
                'final_approver_name' => $eventlog->final_approver_name,
            ]);
        });
    }

    public function logSystemPoint($target_sn, EventLogModel $eventlog)
    {
        $title = '系统积分变化';

        $this->createPointLog($target_sn, $title, $eventlog);
    }

    /**
     * 创建积分变更记录.
     * 
     * @author 28youth
     * @return mixed
     */
    protected function createPointLog(int $staff_sn, array $params = []): PointLogModel
    {
        $user = $this->checkStaff($staff_sn);
        $model = new PointLogModel();
        $model->fill($params + $user);
        $model->save();

        return $model; 
    }

    /**
     * 获取积分记录所需用户信息.
     * 
     * @author 28youth
     * @return array
     */
    public function checkStaff(int $staff_sn): array
    {
        $user = app('api')->getStaff($staff_sn);

        return [
            'staff_sn' => $user->staff_sn ?? 0,
            'staff_name' => $user->realname ?? '',
            'brand_id' => $user->brand->id ?? 0,
            'brand_name' => $user->brand->name ?? '',
            'department_id' => $user->department->id ?? 0,
            'department_name' => $user->department->name ?? '',
            'shop_sn' => $user->shop->shop_sn ?? '',
            'shop_name' => $user->shop->shop_name ?? '',
        ];
    }
}