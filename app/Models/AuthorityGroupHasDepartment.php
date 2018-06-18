<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthorityGroupHasDepartment extends Model
{
	/**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
    protected $primaryKey  = 'authority_group_id';
    protected $fillable = [
        'authority_group_id', 'department_id', 'department_full_name',
    ];
}
