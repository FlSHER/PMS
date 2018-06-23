<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
	use Traits\ListScopes;
    
    protected $fillable = ['name', 'description', 'point'];

    /**
     * 拥有证书的员工.
     * 
     * @author 28youth
     * @return \Illuminate\Database\Eloquent\hasMany
     */
    public function staff()
    {
    	return $this->hasMany(CertificateStaff::class, 'certificate_id', 'id');
    }
}
