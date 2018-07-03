<?php

namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FinalApprover extends Model
{
    use ListScopes;
    use SoftDeletes;
    protected $table = 'final_approvers';
    protected $fillable = [
        'staff_sn', 'staff_name', 'point_a_awarding_limit', 'point_a_deducting_limit', 'point_b_awarding_limit', 'point_b_deducting_limit'
    ];
}
