<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
class Event extends Model
{
    use SoftDeletes;
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */

    protected $table = 'event';
    protected $fillable = [
        'name', 'type_id', 'point_a_min', 'point_a_max', 'point_b_min', 'point_b_max', 'point_a_default', 'point_b_default', 'first_approver_sn', 'first_approver_name', 'final_approver_sn', 'final_approver_name', 'first_approver_locked', 'final_approver_locked', 'default_cc_addressees', 'is_active', 'created_at', 'updated_at', 'deleted_at',
    ];
    protected $casts = [
        'default_cc_addressees' => 'array',
    ];
    /**
     * has event log.
     * 
     * @author 28youth
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function logs()
    {
    	return $this->hasMany(EventLog::class, 'event_id', 'id');
    }
}
