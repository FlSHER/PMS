<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ArtisanCommandLog extends Model
{
	/**
	 * 复用任务编号筛选.
	 * 
	 * @author 28youth
	 * @param  \Illuminate\Database\Eloquent\Builder $query
	 * @param  string  $sn
	 * @return \Illuminate\Database\Eloquent\Builder
	 */
    public function scopeBySn(Builder $query, string $sn): Builder
    {
    	return $query->where('command_sn', $sn);
    }
}
