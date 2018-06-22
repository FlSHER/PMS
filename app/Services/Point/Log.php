<?php 

namespace App\Services\Point;

use App\Jobs\PointLogger AS PointLoggerJob;
use App\Models\PointLog as PointLogModel;
use App\Models\EventLog as EventLogModel;

class Log
{
	// 系统分
	const SYSTEM_POINT = 0;

	// 固定分
	const FIXED_POINT = 1;

	// 奖扣分
	const EVENT_POINT = 2;

	// 任务分
	const TASK_POINT = 3;

	// 考勤分
	const WORK_POINT = 4;

	// 日志分
	const LOG_POINT = 5;

	/**
     * 获取积分记录所需用户信息.
     * 
     * @author 28youth
     * @return array
     */
    protected function checkStaff(int $staff_sn): array
    {
        $user = app('api')->getStaff($staff_sn);

        return [
            'staff_sn' => $user['staff_sn'] ?? 0,
            'staff_name' => $user['realname'] ?? '',
            'brand_id' => $user['brand']['id'] ?? 0,
            'brand_name' => $user['brand']['name'] ?? '',
            'department_id' => $user['department_id'] ?? 0,
            'department_name' => $user['department']['full_name'] ?? '',
            'shop_sn' => $user['shop_sn'] ?? '',
            'shop_name' => $user['shop']['name'] ?? '',
        ];
    }

}