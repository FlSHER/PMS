<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class EventLog extends Model
{
    use Traits\ListScopes;

    /**
     * 批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'event_id',
        'description',
        'first_approver_sn',
        'first_approver_name',
        'final_approver_sn',
        'final_approver_name',
        'executed_at'
    ];

    /**
     * 复用状态筛选.
     *
     * @author 28youth
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByAudit(Builder $query, int $status): Builder
    {
        return $query->where('status_id', $status);
    }

    /**
     * 事件参与者.
     *
     * @author 28youth
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function participant()
    {
        return $this->hasMany(EventLogParticipant::class, 'event_log_id', 'id');
    }

    /**
     * 事件抄送者.
     *
     * @author 28youth
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function addressee()
    {
        return $this->hasMany(EventLogAddressee::class, 'event_log_id', 'id');
    }

    /**
     * 事件类型.
     *
     * @author 28youth
     * @return \Illuminate\Database\Eloquent\Relations\hasOne
     */
    public function event()
    {
        return $this->belongsTo(Event::class, 'event_id');
    }

    public function eventType()
    {
        return $this->belongsTo(EventType::class,'event_type_id');
    }
}
