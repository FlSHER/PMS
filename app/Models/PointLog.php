<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PointLog extends Model
{
    use Traits\ListScopes;
    use SoftDeletes;

    /**
     * 批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'point_a',
        'point_b',
        'shop_sn',
        'shop_name',
        'staff_sn',
        'staff_name',
        'source_id',
        'brand_id',
        'brand_name',
        'department_id',
        'department_name',
        'source_foreign_key',
        'changed_at',
        'first_approver_sn',
        'first_approver_name',
        'final_approver_sn',
        'final_approver_name',
        'type_id'
    ];

    /**
     * has credit source.
     *
     * @author 28youth
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function source()
    {
        return $this->hasOne(PointLogSource::class, 'id', 'source_id');
    }

    public function getChangedAtAttribute($value)
    {
        return date('Y-m-d', strtotime($value));
    }
}
