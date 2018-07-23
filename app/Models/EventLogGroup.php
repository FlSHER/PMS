<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class EventLogGroup extends Model
{
    use Traits\ListScopes;

    /**
     * 批量赋值的属性
     *
     * @var array
     */
    protected $fillable = [
        'title',
        'remark',
        'first_approver_sn',
        'first_approver_name',
        'final_approver_sn',
        'final_approver_name',
        'executed_at',
        'event_count',
        'participant_count'
    ];
    
    /**
     * has event log.
     * 
     * @author 28youth
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function logs()
    {
        return $this->hasMany(EventLog::class, 'event_log_group_id', 'id');
    }

    /**
     * 事件抄送者.
     *
     * @author 28youth
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function addressees()
    {
        return $this->hasMany(EventLogAddressee::class, 'event_log_id', 'id');
    }

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

}
