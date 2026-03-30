<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class ShiftRecord extends Model
{
    use HasUuid;

    protected $table = 'shift_records';

    public $timestamps = false;

    protected $fillable = [
        'worker_id',
        'site_id',
        'shift_date',
        'hours_worked',
        'output_metric',
        'metric_unit',
        'notes',
    ];

    protected $casts = [
        'shift_date'    => 'date',
        'hours_worked'  => 'decimal:2',
        'output_metric' => 'decimal:4',
        'created_at'    => 'datetime',
    ];

    public function worker()
    {
        return $this->belongsTo(Worker::class, 'worker_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }
}
