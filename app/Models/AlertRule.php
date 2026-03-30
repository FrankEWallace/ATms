<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class AlertRule extends Model
{
    use HasUuid;

    protected $table = 'alert_rules';

    public $timestamps = false;

    protected $fillable = [
        'org_id',
        'name',
        'metric',
        'condition',
        'threshold',
        'notify_email',
        'enabled',
    ];

    protected $casts = [
        'threshold'  => 'decimal:4',
        'enabled'    => 'boolean',
        'created_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }
}
