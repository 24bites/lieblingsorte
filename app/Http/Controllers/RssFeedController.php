<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\TravelReport;
use App\Models\TravelTip;
use Illuminate\Support\Facades\Response;

/**
 * Public RSS 2.0 feed of the newest published content across all three
 * content types (Regionen, Reiseziele/TravelTips, Reiseberichte), meant for
 * real RSS readers/subscribers - unlike the Pinterest-specific feed
 * (PinterestFeedController), which is curated and consumed only by
 * Pinterest's own re-fetcher.
 */
class RssFeedController extends Controller
{
    private const PER_TYPE_LIMIT = 20;

    private const FEED_LIMIT = 40;

    public function index()
    {
        $regions = Region::published()
            ->orderByDesc('updated_at')
            ->take(self::PER_TYPE_LIMIT)
            ->get()
            ->map(fn (Region $region) => $this->toItem($region, 'Region', $region->updated_at));

        $tips = TravelTip::published()
            ->with('region')
            ->orderByDesc('updated_at')
            ->take(self::PER_TYPE_LIMIT)
            ->get()
            ->map(fn (TravelTip $tip) => $this->toItem($tip, 'Reiseziel', $tip->updated_at));

        $reports = TravelReport::published()
            ->orderByDesc('published_at')
            ->take(self::PER_TYPE_LIMIT)
            ->get()
            ->map(fn (TravelReport $report) => $this->toItem($report, 'Reisebericht', $report->published_at));

        $items = $regions->concat($tips)->concat($reports)
            ->sortByDesc(fn (array $item) => $item['sortDate'])
            ->take(self::FEED_LIMIT)
            ->values();

        $xml = view('feeds.latest', compact('items'))->render();

        return Response::make($xml, 200, ['Content-Type' => 'application/rss+xml; charset=UTF-8']);
    }

    private function toItem(Region|TravelTip|TravelReport $item, string $type, $date): array
    {
        $shareData = $item->socialShareData();

        return [
            'type' => $type,
            'title' => $shareData['title'],
            'link' => $shareData['url'],
            'description' => $shareData['description'],
            'image' => $shareData['image'],
            'pubDate' => $date->toRfc2822String(),
            'sortDate' => $date,
        ];
    }
}
