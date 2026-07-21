<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Favorite extends Model
{
    protected $fillable = ['user_id', 'session_id', 'travel_tip_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function travelTip(): BelongsTo
    {
        return $this->belongsTo(TravelTip::class);
    }
}
