<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventLogConcern extends Model
{

    /**
     * has event log.
     * 
     * @author 28youth
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function logs()
    {
        return $this->hasMany(EventLog::class, 'concern_id', 'id');
    }

}
