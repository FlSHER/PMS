<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalPointStatistic extends Model
{
	protected $casts = [
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
        'department_name'
    ];
}
