<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EventType extends Model
{
    use SoftDeletes;
    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
    protected $table = 'event_types';
    protected $fillable = [
        'name', 'parent_id', 'sort'
    ];

    /**
     * 分类下的事件.
     * 
     * @author 28youth
     * @return \Illuminate\Database\Eloquent\hasMany
     */
    public function events()
    {
        return $this->hasMany(Events::class, 'type_id', 'id');
    }

}
