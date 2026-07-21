<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TravelTip extends Model
{
    use HasFactory, HasSlug;

    protected string $slugSourceColumn = 'title';

    protected array $slugUniqueScopeColumns = ['region_id'];

    protected $fillable = [
        'region_id',
        'title',
        'slug',
        'short_description',
        'description',
        'location_name',
        'address',
        'latitude',
        'longitude',
        'duration',
        'difficulty',
        'price_information',
        'opening_hours',
        'parking_information',
        'arrival_information',
        'website_url',
        'phone',
        'email',
        'rating',
        'family_friendly',
        'stroller_friendly',
        'dog_friendly',
        'indoor',
        'free_entry',
        'featured',
        'best_season',
        'highlights',
        'seo_title',
        'seo_description',
        'is_published',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'family_friendly' => 'boolean',
            'stroller_friendly' => 'boolean',
            'dog_friendly' => 'boolean',
            'indoor' => 'boolean',
            'free_entry' => 'boolean',
            'featured' => 'boolean',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'rating' => 'decimal:1',
            'highlights' => 'array',
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_travel_tip');
    }

    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'label_travel_tip');
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->orderBy('sort_order');
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function coverImage(): ?Media
    {
        return $this->media->firstWhere('is_cover', true) ?? $this->media->first();
    }

    public function hasCoordinates(): bool
    {
        return ! is_null($this->latitude) && ! is_null($this->longitude);
    }

    public function scopePublished($query)
    {
        return $query->where('is_published', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
