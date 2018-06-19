<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CommonConfig extends Model
{
    use Traits\HasCompositePrimaryKey;

    protected $primaryKey = ['name', 'namespace'];

    public $incrementing = false;

    protected $fillable = ['name', 'namespace', 'value'];

    /**
     * 复用 namespace.
     *
     * @author 28youth
     * @param Illuminate\Database\Eloquent\Builder $query
     * @param string                               $namespace
     * @return Illuminate\Database\Eloquent\Builder
     *
     */
    public function scopeByNamespace(Builder $query, string $namespace): Builder
    {
        return $query->where('namespace', $namespace);
    }

    /**
     * 复用 name.
     *
     * @author 28youth
     * @param Illuminate\Database\Eloquent\Builder $query
     * @param string                               $name
     * @return Illuminate\Database\Eloquent\Builder
     *
     */
    public function scopeByName(Builder $query, string $name): Builder
    {
        return $query->where('name', $name);
    }

}
