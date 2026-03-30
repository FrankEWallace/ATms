<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasUuid;

    protected $table = 'transactions';

    public $timestamps = false;

    protected $fillable = [
        'site_id',
        'reference_no',
        'description',
        'category',
        'type',
        'status',
        'quantity',
        'unit_price',
        'currency',
        'transaction_date',
        'created_by',
    ];

    protected $casts = [
        'quantity'         => 'decimal:4',
        'unit_price'       => 'decimal:4',
        'transaction_date' => 'date',
        'created_at'       => 'datetime',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getAmountAttribute(): float
    {
        return (float) $this->quantity * (float) $this->unit_price;
    }
}
