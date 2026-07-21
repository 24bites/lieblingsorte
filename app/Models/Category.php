<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory, HasSlug;

    protected string $slugSourceColumn = 'name';

    protected $fillable = ['name', 'slug', 'icon', 'description'];

    public function travelTips(): BelongsToMany
    {
        return $this->belongsToMany(TravelTip::class, 'category_travel_tip');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
