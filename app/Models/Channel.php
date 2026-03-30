<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    use HasUuid;

    protected $table = 'channels';

    public $timestamps = false;

    protected $fillable = [
        'org_id',
        'name',
        'type',
        'description',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'channel_id');
    }
}
