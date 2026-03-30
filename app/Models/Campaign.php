<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Campaign extends Model
{
    use HasUuid;

    protected $table = 'campaigns';

    public $timestamps = false;

    protected $fillable = [
        'org_id',
        'title',
        'description',
        'status',
        'start_date',
        'end_date',
        'target_sites',
        'created_by',
    ];

    protected $casts = [
        'start_date'   => 'date',
        'end_date'     => 'date',
        'target_sites' => 'array',
        'created_at'   => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
