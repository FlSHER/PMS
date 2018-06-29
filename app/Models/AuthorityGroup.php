<?php

namespace App\Models;
use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;

class AuthorityGroup extends Model
{
    use ListScopes;
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
     * @return \Illuminate\Database\Eloquent\Relations\hasmany
     */

    public function department()
    {
        return $this->hasMany(AuthorityGroupHasDepartment::class,'authority_group_id','id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */


    public function staff()
    {
        return $this->hasMany(AuthorityGroupHasStaff::class,'authority_group_id','id');
    }
}
