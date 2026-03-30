<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class UserSiteRole extends Model
{
    use HasUuid;

    protected $table = 'user_site_roles';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'site_id',
        'role',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }
}
