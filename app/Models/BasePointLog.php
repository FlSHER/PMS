<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BasePointLog extends Model
{
    /**
     * 批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'point_b',
        'shop_sn',
        'shop_name',
        'staff_sn',
        'staff_name',
        'brand_id',
        'brand_name',
        'department_id',
        'department_name',
    ];

    /**
     * 基础分记录详情.
     *
     * @return \Illuminate\Database\Eloquent\Relations\hasMany
     */
    public function details()
    {
        return $this->hasMany(BasePointDetail::class, 'source_foreign_key', 'id');
    }
}
