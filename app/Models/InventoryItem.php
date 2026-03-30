<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class InventoryItem extends Model
{
    use HasUuid;

    protected $table = 'inventory_items';

    public $timestamps = true;

    protected $fillable = [
        'site_id',
        'supplier_id',
        'name',
        'category',
        'sku',
        'quantity',
        'unit',
        'unit_cost',
        'reorder_level',
    ];

    protected $casts = [
        'quantity'      => 'decimal:4',
        'unit_cost'     => 'decimal:4',
        'reorder_level' => 'decimal:4',
        'created_at'    => 'datetime',
        'updated_at'    => 'datetime',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function transactions()
    {
        return $this->hasMany(InventoryTransaction::class, 'inventory_item_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'inventory_item_id');
    }
}
