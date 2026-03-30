<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasUuid;

    protected $table = 'suppliers';

    public $timestamps = false;

    protected $fillable = [
        'org_id',
        'name',
        'contact_name',
        'email',
        'phone',
        'address',
        'category',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function inventoryItems()
    {
        return $this->hasMany(InventoryItem::class, 'supplier_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'supplier_id');
    }
}
