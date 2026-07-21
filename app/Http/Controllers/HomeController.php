<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Label;
use App\Models\Region;
use App\Models\TravelTip;

class HomeController extends Controller
{
    public function index()
    {
        $regions = Region::published()
            ->withCount('publishedTravelTips')
            ->orderBy('sort_order')
            ->with('media')
            ->take(4)
            ->get();

        $featuredTips = TravelTip::query()
            ->published()
            ->featured()
            ->with(['region', 'media', 'labels'])
            ->orderBy('sort_order')
            ->take(8)
            ->get();

        $secretTips = TravelTip::query()
            ->published()
            ->whereHas('labels', fn ($q) => $q->where('slug', 'geheimtipp'))
            ->with(['region', 'media', 'labels'])
            ->inRandomOrder()
            ->take(4)
            ->get();

        $familyTips = TravelTip::query()
            ->published()
            ->where('family_friendly', true)
            ->with(['region', 'media', 'labels'])
            ->inRandomOrder()
            ->take(4)
            ->get();

        $categories = Category::withCount('travelTips')->orderBy('name')->get();

        return view('home', compact('regions', 'featuredTips', 'secretTips', 'familyTips', 'categories'));
    }
}
