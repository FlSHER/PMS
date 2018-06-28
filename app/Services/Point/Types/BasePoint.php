<?php 

namespace App\Services\Point\Types;

use Carbon\Carbon;
use App\Services\Point\Log;
use App\Models\PointLog as PointLogModel;


class BasePoint extends Log
{
	
	/**
	 * 基础分记录.
	 * 
	 * @author 28youth
	 * @param  array
	 * @return mixed
	 */
	public function record($staff)
	{
		$model = new PointLogModel();

        $model->title = '基础分统计结果';
        $model->staff_sn = $staff['staff_sn'];
        $model->staff_name = $staff['realname'];
        $model->brand_id = $staff['brand']['id'];
        $model->brand_name = $staff['brand']['name'];
        $model->department_id = $staff['department_id'];
        $model->department_name = $staff['department']['full_name'];
        $model->shop_sn = $staff['shop_sn'];
        $model->shop_name = $staff['shop']['name'];
        $model->point_b = $staff['base_point'];
        $model->source_id = self::FIXED_POINT;
        $model->changed_at = Carbon::now();
        $model->save();
	}
}