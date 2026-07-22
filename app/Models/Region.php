<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Region extends Model
{
    use HasFactory, HasSlug;

    protected string $slugSourceColumn = 'name';

    protected $fillable = [
        'name',
        'slug',
        'type',
        'country',
        'federal_state',
        'short_description',
        'description',
        'hero_image',
        'latitude',
        'longitude',
        'best_travel_time',
        'arrival_information',
        'seo_title',
        'seo_description',
        'is_published',
        'ai_generated',
        'rejected_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'ai_generated' => 'boolean',
            'rejected_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
        ];
    }

    public function travelTips(): HasMany
    {
        return $this->hasMany(TravelTip::class);
    }

    public function travelReports(): HasMany
    {
        return $this->hasMany(TravelReport::class);
    }

    public function publishedTravelTips(): HasMany
    {
        return $this->travelTips()->where('is_published', true)->orderBy('sort_order');
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'label_region');
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->orderBy('sort_order');
    }

    public function coverImage(): ?Media
    {
        return $this->media->firstWhere('is_cover', true) ?? $this->media->first();
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopePendingAiReview($query)
    {
        return $query->where('ai_generated', true)->where('is_published', false)->whereNull('rejected_at');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
