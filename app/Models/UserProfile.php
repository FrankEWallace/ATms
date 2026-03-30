<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasUuid;

    protected $table = 'user_profiles';

    public $timestamps = false;

    protected $fillable = [
        'id',
        'org_id',
        'full_name',
        'avatar_url',
        'phone',
        'onboarding_completed',
    ];

    protected $casts = [
        'onboarding_completed' => 'boolean',
        'created_at'           => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }
}
