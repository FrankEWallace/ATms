<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasUuid;

    protected $table = 'messages';

    public $timestamps = false;

    protected $fillable = [
        'site_id',
        'sender_id',
        'content',
        'channel',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function site()
    {
        return $this->belongsTo(Site::class, 'site_id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
