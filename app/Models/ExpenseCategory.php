<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class ExpenseCategory extends Model
{
    use HasUuid;

    protected $table = 'expense_categories';

    public $timestamps = true;

    protected $fillable = [
        'org_id',
        'name',
        'description',
        'color',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'org_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'expense_category_id');
    }
}
