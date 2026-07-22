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
        'approved_at',
        'content_completed_at',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'ai_generated' => 'boolean',
            'rejected_at' => 'datetime',
            'approved_at' => 'datetime',
            'content_completed_at' => 'datetime',
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

    public function socialPosts(): MorphMany
    {
        return $this->morphMany(SocialPost::class, 'postable');
    }

    /**
     * Common facts the Social Hub needs to build a post, regardless of
     * which content type (Region/TravelTip/TravelReport) is being shared.
     */
    public function socialShareData(): array
    {
        return [
            'title' => $this->name,
            'description' => $this->short_description,
            'url' => route('regions.show', $this),
            'image' => $this->coverImage()?->url,
        ];
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
        return $query->where('ai_generated', true)->where('is_published', false)
            ->whereNull('rejected_at')->whereNull('approved_at');
    }

    /**
     * Regions the images/tips auto-completion cron (regions:complete-content)
     * should still work on: approved KI suggestions, or any manually created
     * region - but never one already finished, rejected, or still awaiting
     * KI-Vorschläge review.
     */
    public function scopeNeedsContentCompletion($query)
    {
        return $query->whereNull('content_completed_at')
            ->whereNull('rejected_at')
            ->where(function ($q) {
                $q->where('ai_generated', false)
                    ->orWhereNotNull('approved_at');
            });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
