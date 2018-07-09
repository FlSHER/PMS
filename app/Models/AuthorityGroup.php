<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuthorityGroup extends Model
{
    use Traits\ListScopes;
    use Relations\AuthGroupHasStaff;
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    protected $table = 'authority_groups';

    protected $fillable = ['name'];

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

    public function Administrator()
    {
        return $this->hasMany(TaskPublishingAuthorities::class, 'group_id', 'id');
    }
}
