<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
	use Traits\ListScopes;
    
    
    protected $fillable = ['name', 'description', 'point'];
}
