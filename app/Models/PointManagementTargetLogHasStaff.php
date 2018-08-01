<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointManagementTargetLogHasStaff extends Model
{
    protected $table='point_management_target_log_has_staff';

    protected $fillable = [
        'target_id', 'target_log_id', 'date','staff_sn','staff_name',
        'brand_id','brand_name','department_id','department_name','shop_sn',
        'shop_name','point_b_awarding_result','point_b_deducting_result',
        'event_count_result','deducting_percentage_result'
    ];

    public function target()
    {
    	return $this->belongsTo(PointManagementTargetLogs::class, 'target_log_id');
    }
}