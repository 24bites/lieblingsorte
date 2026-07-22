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
        'is_published',
        'ai_generated',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
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
     * Splits content into paragraph/heading blocks for rendering. A block is
     * a subheading if its first line starts with "## " (the convention used
     * by the admin editor and the AI writer); everything else is a paragraph.
     * Avoids pulling in a Markdown parser for what is otherwise plain text.
     */
    public function contentBlocks(): array
    {
        $blocks = preg_split('/\n\s*\n/', trim($this->content)) ?: [];

        return collect($blocks)
            ->filter(fn ($block) => trim($block) !== '')
            ->map(function ($block) {
                $block = trim($block);

                if (str_starts_with($block, '## ')) {
                    return ['type' => 'heading', 'text' => trim(substr($block, 3))];
                }

                return ['type' => 'paragraph', 'text' => $block];
            })
            ->values()
            ->all();
    }

    public function getReadingTimeMinutesAttribute(): int
    {
        $words = str_word_count(strip_tags((string) $this->content));

        return max(1, (int) ceil($words / 200));
    }
}
