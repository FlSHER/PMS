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
    protected $table = 'authority_groups';

    protected $fillable = ['name'];

    /**
     * has department.
     *
     * @author 28youth
     * @return \Illuminate\Database\Eloquent\Relations\hasmany
     */
    public function departments()
    {
        return $this->hasMany(AuthorityGroupHasDepartment::class, 'authority_group_id', 'id');
    }

    /**
     * 统计审查权限分组人员.
     *
     * @author 28youth
     * @return \Illuminate\Database\Eloquent\Relations\hasmany
     */
    public function checking()
    {
        return $this->hasMany(StatisticCheckingAuthorities::class, 'group_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function staff()
    {
        return $this->hasMany(AuthorityGroupHasStaff::class, 'authority_group_id', 'id');
    }

    public function Administrator()
    {
        return $this->hasMany(TaskPublishingAuthorities::class, 'group_id', 'id');
    }
}
