<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class IntegrationConfig extends Model
{
    use HasUuid;

    protected $table = 'integration_configs';

    public $timestamps = false;

    protected $fillable = [
        'org_id',
        'integration_type',
        'config',
        'enabled',
    ];

    protected $casts = [
        'config'     => 'array',
        'enabled'    => 'boolean',
        'created_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }
}
