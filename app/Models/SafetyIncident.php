<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class SafetyIncident extends Model
{
    use HasUuid;

    protected $table = 'safety_incidents';

    public $timestamps = true;

    protected $fillable = [
        'site_id',
        'reported_by',
        'severity',
        'type',
        'title',
        'description',
        'actions_taken',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
        // 'type' is stored as string (not enum) because "near-miss" has a hyphen
    ];

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reported_by');
    }
}
