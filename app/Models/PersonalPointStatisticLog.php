<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalPointStatisticLog extends Model
{
    protected $casts = [
        'source_b_total' => 'array',
        'source_b_monthly' => 'array'
    ];
}
