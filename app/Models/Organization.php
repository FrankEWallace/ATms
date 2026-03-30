<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    use HasUuid;

    protected $table = 'organizations';

    public $timestamps = false;

    protected $fillable = [
        'name',
        'slug',
        'logo_url',
        'weekly_report_enabled',
        'weekly_report_email',
    ];

    protected $casts = [
        'weekly_report_enabled' => 'boolean',
        'created_at'            => 'datetime',
    ];

    public function sites()
    {
        return $this->hasMany(Site::class, 'org_id');
    }

    public function suppliers()
    {
        return $this->hasMany(Supplier::class, 'org_id');
    }

    public function channels()
    {
        return $this->hasMany(Channel::class, 'org_id');
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class, 'org_id');
    }

    public function alertRules()
    {
        return $this->hasMany(AlertRule::class, 'org_id');
    }

    public function integrationConfigs()
    {
        return $this->hasMany(IntegrationConfig::class, 'org_id');
    }
}
