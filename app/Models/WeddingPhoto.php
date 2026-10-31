<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WeddingPhoto extends Model
{
    protected $fillable = ['wedding_order_id', 'storage_path', 'original_name', 'mime_type', 'file_size'];

    public function order(): BelongsTo
    {
        return $this->belongsTo(WeddingOrder::class, 'wedding_order_id');
    }
}
