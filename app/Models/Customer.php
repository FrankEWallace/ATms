<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasUuid;

    protected $table = 'customers';

    public $timestamps = true;

    protected $fillable = [
        'site_id',
        'org_id',
        'name',
        'type',
        'contact_name',
        'contact_email',
        'contact_phone',
        'contract_start',
        'contract_end',
        'daily_rate',
        'notes',
        'status',
    ];

    protected $casts = [
        'contract_start' => 'date',
        'contract_end'   => 'date',
        'daily_rate'     => 'decimal:4',
        'created_at'     => 'datetime',
        'updated_at'     => 'datetime',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'customer_id');
    }
}
