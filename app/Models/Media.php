<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = [
        'mediable_type',
        'mediable_id',
        'file_path',
        'alt_text',
        'caption',
        'sort_order',
        'is_cover',
        'source',
        'credit_author',
        'credit_license',
        'credit_source_title',
        'credit_source_url',
    ];

    protected function casts(): array
    {
        return [
            'is_cover' => 'boolean',
        ];
    }

    public function mediable(): MorphTo
    {
        return $this->morphTo();
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function hasCredit(): bool
    {
        return filled($this->credit_author) || filled($this->credit_license) || filled($this->credit_source_url);
    }

    /**
     * Wikimedia's "Artist" metadata field occasionally embeds a full license
     * paragraph instead of just a name - keep credit_author readable.
     */
    public static function truncateCreditText(?string $value, int $limit = 300): ?string
    {
        if (blank($value)) {
            return $value;
        }

        return mb_strlen($value) > $limit ? mb_substr($value, 0, $limit - 1).'…' : $value;
    }
}
