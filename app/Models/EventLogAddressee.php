<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventLogAddressee extends Model
{
	
    /**
     * 批量赋值的属性
     * 
     * @var array
     */
    protected $fillable = [
        'event_log_group_id',
        'staff_sn',
        'staff_name',
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
}
