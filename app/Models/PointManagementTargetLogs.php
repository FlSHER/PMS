<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointManagementTargetLogs extends Model
{
    protected $table = 'point_management_target_logs';

    protected $fillable = [
        'target_id', 'date', 'point_b_awarding_target', 'point_b_deducting_target',
        'event_count_target', 'deducting_percentage_target', 'point_b_awarding_coefficient',
        'point_b_deducting_coefficient', 'event_count_mission', 'deducting_percentage_ratio'
    ];

//    protected $appends = ['point_b_awarding_coefficient', 'point_b_deducting_coefficient', 'event_count_mission', 'deducting_percentage_ratio'];
//
//    public function getPointBAwardingCoefficientAttribute()
//    {
//        return '0.9';
//    }
//
//    public function getPointBDeductingCoefficientAttribute()
//    {
//        return '0.85';
//    }
//
//    public function getEventCountMissionAttribute()
//    {
//        return '5';
//    }
//
//    public function getDeductingPercentageRatioAttribute()
//    {
//        return '0.6';
//    }

    /**
     * 关联当月指标信息.
     *
     * @author 28youth
     * @return \Illuminate\Database\Eloquent\belongsTo|null
     */
    public function targetlog()
    {
        return $this->belongsTo(PointManagementTargetLogs::class, 'target_log_id', 'id');
    }
}