<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class ProductionLog extends Model
{
    use HasUuid;

    protected $table = 'production_logs';

    public $timestamps = true;

    protected $fillable = [
        'site_id',
        'log_date',
        'ore_tonnes',
        'waste_tonnes',
        'grade_g_t',
        'water_m3',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'log_date'     => 'date',
        'ore_tonnes'   => 'decimal:4',
        'waste_tonnes' => 'decimal:4',
        'grade_g_t'    => 'decimal:4',
        'water_m3'     => 'decimal:4',
        'created_at'   => 'datetime',
        'updated_at'   => 'datetime',
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
