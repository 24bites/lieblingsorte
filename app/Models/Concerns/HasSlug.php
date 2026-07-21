<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::creating(function ($model) {
            if (blank($model->slug)) {
                $model->slug = $model->generateUniqueSlug();
            }
        });

        static::updating(function ($model) {
            if (blank($model->slug)) {
                $model->slug = $model->generateUniqueSlug();
            }
        });
    }

    public function generateUniqueSlug(): string
    {
        $source = $this->{$this->slugSourceColumn()};
        $dictionary = ['ü' => 'ue', 'Ü' => 'Ue', 'ä' => 'ae', 'Ä' => 'Ae', 'ö' => 'oe', 'Ö' => 'Oe', 'ß' => 'ss'];
        $base = Str::slug($source, '-', 'de', $dictionary) ?: Str::slug(Str::random(8));
        $slug = $base;
        $i = 2;

        while ($this->slugExists($slug)) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }

    protected function slugExists(string $slug): bool
    {
        $query = static::query()->where('slug', $slug);

        foreach ($this->slugUniqueScopeColumns() as $column) {
            $query->where($column, $this->{$column});
        }

        if ($this->exists) {
            $query->where($this->getKeyName(), '!=', $this->getKey());
        }

        return $query->exists();
    }

    protected function slugSourceColumn(): string
    {
        return property_exists($this, 'slugSourceColumn') ? $this->slugSourceColumn : 'name';
    }

    protected function slugUniqueScopeColumns(): array
    {
        return property_exists($this, 'slugUniqueScopeColumns') ? $this->slugUniqueScopeColumns : [];
    }
}
