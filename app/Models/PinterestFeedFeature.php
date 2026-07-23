<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class PinterestFeedFeature extends Model
{
    protected $fillable = ['featurable_type', 'featurable_id', 'sort_order'];

    public function featurable(): MorphTo
    {
        return $this->morphTo();
    }
}
