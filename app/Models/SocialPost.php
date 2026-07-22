<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SocialPost extends Model
{
    public const PLATFORMS = ['pinterest', 'facebook', 'x', 'telegram', 'whatsapp'];

    protected $fillable = [
        'postable_type',
        'postable_id',
        'platform',
        'caption',
        'link_url',
        'image_url',
        'status',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
        ];
    }

    public function postable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeForPlatform($query, string $platform)
    {
        return $query->where('platform', $platform);
    }
}
