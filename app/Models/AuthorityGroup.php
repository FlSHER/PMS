<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthorityGroup extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    protected $table='authority_groups';

    protected $fillable = ['name'];
    /**
     * has department.
     * 
     * @author 28youth
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     */
    public function department()
    {
    	return $this->hasOne(AuthorityGroupHasDepartment::class, 'authority_group_id', 'id');
    }

    /**
     * has staff.
     * 
     * @author 28youth
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     */
    public function staff()
    {
    	return $this->hasOne(AuthorityGroupHasStaff::class, 'authority_group_id', 'id');
    }

    public function departmentMany()
    {
        return $this->hasMany(AuthorityGroupHasDepartment::class,'authority_group_id','id');
    }

    public function staffMany()
    {
        return $this->hasMany(AuthorityGroupHasStaff::class,'authority_group_id','id');
    }
}
