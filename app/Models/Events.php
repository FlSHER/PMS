<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Events extends Model
{
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

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
