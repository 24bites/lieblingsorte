<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Region;
use App\Models\TravelTip;
use App\Support\PinterestFeedSelector;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Public, unauthenticated RSS 2.0 feed of up to 25 Regions/TravelTips
 * ("Ziele"), meant to be added as an "RSS feed" source in Pinterest's own
 * business account settings (Content > RSS feeds), where Pinterest
 * periodically re-fetches it and auto-creates Pins from new items - no
 * Pinterest API credentials needed for this, just the feed URL. Item
 * selection (curated-first, then most recently updated) lives in
 * PinterestFeedSelector, shared with the social:pinterest-captions cron.
 */
class PinterestFeedController extends Controller
{
    public function index()
    {
        $regions = PinterestFeedSelector::eligibleItems()
            ->map(function (Region|TravelTip $item) {
                $shareData = $item->socialShareData();

                return [
                    'title' => $shareData['title'],
                    'link' => $shareData['url'],
                    'description' => $this->captionFor($item, $shareData),
                    'pubDate' => $item->updated_at->toRfc2822String(),
                    'image' => $this->coverImageData($item),
                ];
            });

        $xml = view('feeds.pinterest', compact('regions'))->render();

        return Response::make($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }

    /**
     * Prefer a Pinterest caption already generated for this item (via the
     * Social Hub "+Pinterest" button or the social:pinterest-captions cron -
     * both write to the same SocialPost row) over a plain, AI-free fallback
     * so a brand new feed item never shows up with no text at all.
     */
    private function captionFor(Region|TravelTip $item, array $shareData): string
    {
        $caption = $item->socialPosts()->where('platform', 'pinterest')->value('caption');

        if (filled($caption)) {
            return $caption;
        }

        return trim("📍 {$shareData['title']} — {$shareData['description']} #Reisetipps");
    }

    private function coverImageData(Region|TravelTip $item): ?array
    {
        $cover = $this->feedImage($item);

        if (! $cover) {
            return null;
        }

        $path = $cover->displayPath();
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mimeType = match ($extension) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return [
            'url' => $cover->url,
            'type' => $mimeType,
            'length' => Storage::disk('public')->exists($path)
                ? Storage::disk('public')->size($path)
                : 0,
        ];
    }

    /**
     * Wikimedia photos carry attribution requirements that a bare Pinterest
     * auto-pin can't preserve, so they must never be used as the feed image -
     * prefer the item's cover image if it isn't Wikimedia-sourced, otherwise
     * fall back to another non-Wikimedia image on it, if any exists.
     */
    private function feedImage(Region|TravelTip $item): ?Media
    {
        $eligible = $item->media->where('source', '!=', 'wikimedia');

        return $eligible->firstWhere('is_cover', true) ?? $eligible->first();
    }
}
