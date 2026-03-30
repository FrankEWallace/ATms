<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class SiteDocument extends Model
{
    use HasUuid;

    protected $table = 'site_documents';

    public $timestamps = false;

    protected $fillable = [
        'site_id',
        'uploaded_by',
        'name',
        'category',
        'storage_path',
        'file_size',
        'mime_type',
    ];

    protected $casts = [
        'file_size'  => 'integer',
        'created_at' => 'datetime',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
