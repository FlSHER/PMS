<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class EventLog extends Model
{

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
}
