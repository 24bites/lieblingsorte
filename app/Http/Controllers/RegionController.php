<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Label;
use App\Models\Region;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    public function index(Request $request)
    {
        $query = Region::published()->withCount('publishedTravelTips')->with('media');

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('short_description', 'like', "%{$search}%");
            });
        }

        $regions = $query->orderBy('sort_order')->paginate(12)->withQueryString();

        return view('regions.index', compact('regions'));
    }

    public function show(Request $request, Region $region, bool $preview = false)
    {
        abort_unless($preview || $region->is_published, 404);

        $tipsQuery = $preview
            ? $region->travelTips()->with(['media', 'labels', 'categories'])
            : $region->travelTips()->published()->with(['media', 'labels', 'categories']);

        if ($labelSlug = $request->string('label')->toString()) {
            $tipsQuery->whereHas('labels', fn ($q) => $q->where('slug', $labelSlug));
        }

        if ($categorySlug = $request->string('kategorie')->toString()) {
            $tipsQuery->whereHas('categories', fn ($q) => $q->where('slug', $categorySlug));
        }

        if ($request->boolean('kostenlos')) {
            $tipsQuery->where('free_entry', true);
        }

        if ($request->boolean('hund')) {
            $tipsQuery->where('dog_friendly', true);
        }

        if ($request->boolean('kinderwagen')) {
            $tipsQuery->where('stroller_friendly', true);
        }

        if ($request->boolean('indoor')) {
            $tipsQuery->where('indoor', true);
        }

        $sort = $request->string('sortierung')->toString();
        match ($sort) {
            'bewertung' => $tipsQuery->orderByDesc('rating'),
            'name' => $tipsQuery->orderBy('title'),
            default => $tipsQuery->orderBy('sort_order'),
        };

        $tips = $tipsQuery->paginate(12)->withQueryString();

        $availableLabels = Label::whereHas('travelTips', fn ($q) => $q->where('region_id', $region->id))->orderBy('name')->get();
        $availableCategories = Category::whereHas('travelTips', fn ($q) => $q->where('region_id', $region->id))->orderBy('name')->get();

        $similarRegions = Region::published()
            ->where('id', '!=', $region->id)
            ->withCount('publishedTravelTips')
            ->with('media')
            ->take(3)
            ->get();

        return view('regions.show', compact('region', 'tips', 'availableLabels', 'availableCategories', 'similarRegions', 'preview'));
    }
}
