<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WeddingOrder extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'order_number', 'customer_whatsapp', 'shopee_username', 'product_type',
        'photo_package', 'display_order', 'payload', 'status', 'submitted_at',
        'reviewed_at', 'notification_seen_at', 'cms_sync_status', 'cms_sync_error', 'cms_synced_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'notification_seen_at' => 'datetime',
        'cms_synced_at' => 'datetime',
    ];

    public function photos(): HasMany
    {
        return $this->hasMany(WeddingPhoto::class);
    }
}
