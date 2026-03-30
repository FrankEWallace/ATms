<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasUuid;

    protected $table = 'audit_logs';

    public $timestamps = false;

    protected $fillable = [
        'site_id',
        'actor_id',
        'entity_type',
        'entity_id',
        'action',
        'old_data',
        'new_data',
    ];

    protected $casts = [
        'old_data'   => 'array',
        'new_data'   => 'array',
        'created_at' => 'datetime',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }
}
