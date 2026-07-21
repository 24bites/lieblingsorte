<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Label extends Model
{
    use HasFactory, HasSlug;

    protected string $slugSourceColumn = 'name';

    protected $fillable = ['name', 'slug', 'color'];

    public function travelTips(): BelongsToMany
    {
        return $this->belongsToMany(TravelTip::class, 'label_travel_tip');
    }

    public function regions(): BelongsToMany
    {
        return $this->belongsToMany(Region::class, 'label_region');
    }
}
