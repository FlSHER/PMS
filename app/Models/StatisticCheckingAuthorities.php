<?php
namespace App\Models;

use App\Models\Traits\ListScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class StatisticCheckingAuthorities extends Model
{
    use ListScopes;

    protected $table = 'statistic_checking_authorities';
    protected $fillable = ['group_id', 'admin_sn', 'admin_name'];

    public function groups()
    {
        return $this->belongsTo(AuthorityGroup::class, 'group_id', 'id');
    }
}