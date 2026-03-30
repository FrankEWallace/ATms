<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Site extends Model
{
    use HasUuid;

    protected $table = 'sites';

    public $timestamps = false;

    protected $fillable = [
        'org_id',
        'name',
        'location',
        'timezone',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function userSiteRoles()
    {
        return $this->hasMany(UserSiteRole::class, 'site_id');
    }

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class, 'site_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'site_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'site_id');
    }

    public function workers()
    {
        return $this->hasMany(Worker::class, 'site_id');
    }

    public function messages()
    {
        return $this->hasMany(Message::class, 'site_id');
    }

    public function equipment()
    {
        return $this->hasMany(Equipment::class, 'site_id');
    }

    public function safetyIncidents()
    {
        return $this->hasMany(SafetyIncident::class, 'site_id');
    }

    public function documents()
    {
        return $this->hasMany(SiteDocument::class, 'site_id');
    }

    public function productionLogs()
    {
        return $this->hasMany(ProductionLog::class, 'site_id');
    }

    public function kpiTargets()
    {
        return $this->hasMany(KpiTarget::class, 'site_id');
    }
}
