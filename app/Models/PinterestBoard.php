<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PinterestBoard extends Model
{
    protected $fillable = ['pinterest_board_id', 'name', 'type', 'region_id', 'description'];

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function pins(): HasMany
    {
        return $this->hasMany(PinterestPin::class, 'board_id');
    }

    public function isConnectedToPinterest(): bool
    {
        return filled($this->pinterest_board_id);
    }
}
