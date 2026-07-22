<?php

namespace App\Http\Controllers;

use App\Models\Media;
use App\Models\Region;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

/**
 * Public, unauthenticated RSS 2.0 feed of the 25 most recently updated
 * published regions, meant to be added as an "RSS feed" source in
 * Pinterest's own business account settings (Content > RSS feeds), where
 * Pinterest periodically re-fetches it and auto-creates Pins from new items -
 * no Pinterest API credentials needed for this, just the feed URL.
 */
class PinterestFeedController extends Controller
{
    public function index()
    {
        $regions = Region::published()
            ->with('media')
            ->orderByDesc('updated_at')
            ->take(25)
            ->get()
            ->map(fn (Region $region) => [
                'title' => $region->name,
                'link' => route('regions.show', $region),
                'description' => $region->short_description,
                'pubDate' => $region->updated_at->toRfc2822String(),
                'image' => $this->coverImageData($region),
            ]);

        $xml = view('feeds.pinterest', compact('regions'))->render();

        return Response::make($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }

    private function coverImageData(Region $region): ?array
    {
        $cover = $this->feedImage($region);

        if (! $cover) {
            return null;
        }

        $extension = strtolower(pathinfo($cover->file_path, PATHINFO_EXTENSION));
        $mimeType = match ($extension) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };

        return [
            'url' => $cover->url,
            'type' => $mimeType,
            'length' => Storage::disk('public')->exists($cover->file_path)
                ? Storage::disk('public')->size($cover->file_path)
                : 0,
        ];
    }

    /**
     * Wikimedia photos carry attribution requirements that a bare Pinterest
     * auto-pin can't preserve, so they must never be used as the feed image -
     * prefer the region's cover image if it isn't Wikimedia-sourced, otherwise
     * fall back to another non-Wikimedia image on the region, if any exists.
     */
    private function feedImage(Region $region): ?Media
    {
        $eligible = $region->media->where('source', '!=', 'wikimedia');

        return $eligible->firstWhere('is_cover', true) ?? $eligible->first();
    }
}
