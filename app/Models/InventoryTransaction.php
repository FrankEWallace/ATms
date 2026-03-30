<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class InventoryTransaction extends Model
{
    use HasUuid;

    protected $table = 'inventory_transactions';

    public $timestamps = false;

    protected $fillable = [
        'inventory_item_id',
        'site_id',
        'quantity_change',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'quantity_change' => 'decimal:4',
        'created_at'      => 'datetime',
    ];

    public function inventoryItem()
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
