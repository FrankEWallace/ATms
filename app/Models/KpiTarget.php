<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class KpiTarget extends Model
{
    use HasUuid;

    protected $table = 'kpi_targets';

    public $timestamps = true;

    protected $fillable = [
        'site_id',
        'month',
        'revenue_target',
        'expense_budget',
        'shift_target',
        'equipment_uptime_pct',
        'ore_tonnes_target',
        'created_by',
    ];

    protected $casts = [
        'month'                => 'date',
        'revenue_target'       => 'decimal:2',
        'expense_budget'       => 'decimal:2',
        'shift_target'         => 'integer',
        'equipment_uptime_pct' => 'decimal:2',
        'ore_tonnes_target'    => 'decimal:4',
        'created_at'           => 'datetime',
        'updated_at'           => 'datetime',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
