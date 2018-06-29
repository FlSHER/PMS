<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PointManagementTargetHasStaff extends Model
{
    protected $table='point_management_target_has_staff';
    protected $fillable = [
        'target_id', 'staff_sn', 'staff_name'
    ];

    public function targets()
    {
        return $this->belongsTo(PointManagementTargets::class,'target_id','id');
    }
}