<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class PlannedShift extends Model
{
    use HasUuid;

    protected $table = 'planned_shifts';

    public $timestamps = false;

    protected $fillable = [
        'site_id',
        'worker_id',
        'shift_date',
        'start_time',
        'end_time',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'shift_date' => 'date',
        'created_at' => 'datetime',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function worker()
    {
        return $this->belongsTo(Worker::class, 'worker_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
