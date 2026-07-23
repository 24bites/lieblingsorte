<?php

namespace App\Support;

use App\Models\PinterestFeedFeature;
use App\Models\Region;
use App\Models\TravelTip;
use Illuminate\Support\Collection;

/**
 * The single source of truth for "what's in the Pinterest feed right now",
 * shared by PinterestFeedController (renders it) and the
 * social:pinterest-captions cron (needs to know which items are newly
 * eligible so it only generates captions for those, not the whole site).
 * Curated items (PinterestFeedFeature, admin-picked) always come first in
 * their chosen order; the rest is filled with the most recently updated
 * published Regions/TravelTips, up to the feed's total item cap.
 */
class PinterestFeedSelector
{
    private const LIMIT = 25;

    public static function eligibleItems(): Collection
    {
        $featured = PinterestFeedFeature::with('featurable.media')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (PinterestFeedFeature $feature) => $feature->featurable)
            ->filter(fn ($item) => $item !== null && $item->is_published)
            ->values();

        $excluded = $featured->map(fn ($item) => get_class($item).':'.$item->id);

        $auto = Region::published()->with('media')->get()
            ->concat(TravelTip::published()->with('media')->get())
            ->reject(fn ($item) => $excluded->contains(get_class($item).':'.$item->id))
            ->sortByDesc('updated_at')
            ->values();

        return $featured->concat($auto)->take(self::LIMIT)->values();
    }
}
