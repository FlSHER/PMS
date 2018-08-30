<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BasePointDetail extends Model
{
    protected $casts = [
        'data' => 'array',
    ];
}
