<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointManagementTargets extends Model
{
    protected $table = 'point_management_targets';

    protected $fillable = [
        'name', 'point_b_awarding_target', 'point_b_deducting_target', 'event_count_target',
        'deducting_percentage_target','point_b_awarding_coefficient','point_b_deducting_coefficient',
        'event_count_mission','deducting_percentage_ratio'
    ];
//    protected $appends=['point_b_awarding_coefficient','point_b_deducting_coefficient','event_count_mission','deducting_percentage_ratio'];
//
//    public function getPointBAwardingCoefficientAttribute()
//    {
//        return '0.9';
//    }
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

    public function nextMonth()
    {
        return $this->belongsTo(PointManagementTargetLogs::class, 'id', 'target_id');
    }

    public function nextMonthStaff()
    {
        return $this->hasMany(PointManagementTargetHasStaff::class, 'target_id', 'id');
    }

    public function thisMonthStaff()
    {
        return $this->hasMany(PointManagementTargetLogHasStaff::class, 'target_id', 'id');
    }
}