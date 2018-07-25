<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalPointStatistic extends Model
{
	protected $casts = [
        'source_a_total' => 'array',
        'source_a_monthly' => 'array',
        'source_b_total' => 'array',
        'source_b_monthly' => 'array'
    ];

    protected $fillable = [
        'point_a',
        'point_b',
        'shop_sn',
        'shop_name',
		'staff_sn',
        'staff_name',
        'brand_id',
        'brand_name',
        'department_id',
        'department_name',
        'point_b_monthly',
        'point_b_total',
        'source_b_monthly',
        'source_b_total',
        'point_a_total',
        'source_a_monthly',
        'source_a_total',
        'date'
    ];
}
