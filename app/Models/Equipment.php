<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Equipment extends Model
{
    use HasUuid;

    protected $table = 'equipment';

    public $timestamps = true;

    protected $fillable = [
        'site_id',
        'name',
        'type',
        'serial_number',
        'status',
        'last_service_date',
        'next_service_date',
        'notes',
    ];

    protected $casts = [
        'last_service_date' => 'date',
        'next_service_date' => 'date',
        'created_at'        => 'datetime',
        'updated_at'        => 'datetime',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }
}
