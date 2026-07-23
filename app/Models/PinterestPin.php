<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class PinterestPin extends Model
{
    protected $fillable = [
        'featurable_type', 'featurable_id', 'board_id', 'variant_label',
        'overlay_headline', 'overlay_subline', 'generated_image_path',
        'pin_title', 'pin_description', 'status', 'scheduled_for',
        'posted_at', 'pinterest_pin_id', 'error_message',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_for' => 'date',
            'posted_at' => 'datetime',
        ];
    }

    public function featurable(): MorphTo
    {
        return $this->morphTo();
    }

    public function board(): BelongsTo
    {
        return $this->belongsTo(PinterestBoard::class, 'board_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->generated_image_path
            ? Storage::disk('public')->url($this->generated_image_path)
            : null;
    }
}
