<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthorityGroupHasStaff extends Model
{
	
	protected $table = 'authority_group_has_staff';
	
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;
    protected $fillable = [
        'authority_group_id', 'staff_sn', 'staff_name',
    ];

    protected $primaryKey  = 'authority_group_id';
}
