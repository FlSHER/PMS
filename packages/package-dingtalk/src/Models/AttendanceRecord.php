<?php

namespace Fisher\Schedule\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class AttendanceRecord extends Model
{
     /**
     * 批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'userId',
        'staff_sn',
        'staff_name',
        'groupId',
        'workDate',
        'baseOnTime',
        'baseOffTime',
        'userOnTime',
        'userOffTime',
        'restBeginTime',
        'restEndTime',
        'worktime',
        'latetime',
        'overtime',
        'leavetime',
        'earlytime'
    ];

    public function scopeByUserId(Builder $query, $userId)
    {
        return $query->where('userId', $userId);
    }
}
