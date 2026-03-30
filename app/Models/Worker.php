<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Worker extends Model
{
    use HasUuid;

    protected $table = 'workers';

    public $timestamps = false;

    protected $fillable = [
        'site_id',
        'user_id',
        'full_name',
        'position',
        'department',
        'hire_date',
        'status',
    ];

    protected $casts = [
        'hire_date'  => 'date',
        'created_at' => 'datetime',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function shiftRecords()
    {
        return $this->hasMany(ShiftRecord::class, 'worker_id');
    }

    public function plannedShifts()
    {
        return $this->hasMany(PlannedShift::class, 'worker_id');
    }
}
