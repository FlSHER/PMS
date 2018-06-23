<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalPointStatisticLog extends Model
{
    protected $fillable = [
        'staff_sn',
        'staff_name',
        'brand_id',
        'brand_name',
        'department_id',
        'department_name',
        'shop_sn',
        'shop_name',
        'date',
        'point_a',
        'point_b_monthly',
        'point_b_total',
        'source_b_monthly',
        'source_b_total'
    ];

    protected $casts = [
        'source_b_total' => 'array',
        'source_b_monthly' => 'array'
    ];
}
