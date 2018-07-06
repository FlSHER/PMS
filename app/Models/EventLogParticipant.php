<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventLogParticipant extends Model
{

    /**
     * 批量赋值的属性
     * 
     * @var array
     */
    protected $fillable = [
        'event_log_id',
        'staff_sn',
        'staff_name',
        'point_a',
        'point_b',
        'count'
    ];

    /**
     * Indicates if the IDs are auto-incrementing.
     *
     * @var bool
     */
    public $incrementing = false;

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;


    /**
     * 设定事件完成次数。
     *
     * @param  string  $value
     * @return void
     */
    public function setCountAttribute($value)
    {
        $this->attributes['count'] = ((bool)$value !== true) ? 1 : $value;
    }
}
