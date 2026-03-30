<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasUuid;

    protected $table = 'orders';

    public $timestamps = false;

    protected $fillable = [
        'site_id',
        'supplier_id',
        'channel_id',
        'order_number',
        'status',
        'total_amount',
        'expected_date',
        'received_date',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'total_amount'  => 'decimal:4',
        'expected_date' => 'date',
        'received_date' => 'date',
        'created_at'    => 'datetime',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function channel()
    {
        return $this->belongsTo(Channel::class, 'channel_id');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
