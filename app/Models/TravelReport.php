<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class TravelReport extends Model
{
    use HasFactory, HasSlug;

    protected string $slugSourceColumn = 'title';

    protected $fillable = [
        'title',
        'slug',
        'excerpt',
        'content',
        'author_name',
        'author_bio',
        'region_id',
        'seo_title',
        'seo_description',
        'og_description',
        'faq',
        'is_published',
        'ai_generated',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'faq' => 'array',
            'is_published' => 'boolean',
            'ai_generated' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'mediable')->orderBy('sort_order');
    }

    public function socialPosts(): MorphMany
    {
        return $this->morphMany(SocialPost::class, 'postable');
    }

    public function socialShareData(): array
    {
        return [
            'title' => $this->title,
            'description' => $this->excerpt,
            'url' => route('reports.show', $this),
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

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Builds a schema.org FAQPage entity from the stored FAQ pairs, or null
     * if there are none - the show page adds this as a second JSON-LD block
     * alongside the Article markup only when it has something to say.
     */
    public function faqJsonLd(): ?array
    {
        if (blank($this->faq)) {
            return null;
        }

        $entities = collect($this->faq)
            ->filter(fn ($pair) => filled($pair['question'] ?? null) && filled($pair['answer'] ?? null))
            ->map(fn ($pair) => [
                '@type' => 'Question',
                'name' => $pair['question'],
                'acceptedAnswer' => ['@type' => 'Answer', 'text' => $pair['answer']],
            ])
            ->values();

        if ($entities->isEmpty()) {
            return null;
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $entities->all(),
        ];
    }

    public function getReadingTimeMinutesAttribute(): int
    {
        $words = str_word_count(strip_tags((string) $this->content));

        return max(1, (int) ceil($words / 200));
    }
}
