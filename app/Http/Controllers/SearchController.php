<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\SearchLog;
use App\Models\TravelTip;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->string('q')->trim()->toString();

        $regions = collect();
        $tips = collect();

        if ($query !== '') {
            $regions = Region::published()
                ->with('media')
                ->where(function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('short_description', 'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%")
                        ->orWhere('federal_state', 'like', "%{$query}%");
                })
                ->take(6)
                ->get();

            $tips = TravelTip::published()
                ->with(['region', 'media', 'labels'])
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                        ->orWhere('short_description', 'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%")
                        ->orWhere('location_name', 'like', "%{$query}%")
                        ->orWhereHas('categories', fn ($c) => $c->where('name', 'like', "%{$query}%"))
                        ->orWhereHas('labels', fn ($l) => $l->where('name', 'like', "%{$query}%"));
                })
                ->take(24)
                ->get();

            SearchLog::create([
                'query' => $query,
                'results_count' => $regions->count() + $tips->count(),
                'ip_address' => $request->ip(),
            ]);
        }

        $alternatives = [];
        if ($query !== '' && $regions->isEmpty() && $tips->isEmpty()) {
            $alternatives = Region::published()->withCount('publishedTravelTips')->take(3)->get();
        }

        return view('search.index', compact('query', 'regions', 'tips', 'alternatives'));
    }

    public function suggestions(Request $request)
    {
        $query = $request->string('q')->trim()->toString();

        if ($query === '' || mb_strlen($query) < 2) {
            return response()->json([]);
        }

        $regions = Region::published()
            ->where('name', 'like', "%{$query}%")
            ->take(4)
            ->get(['name', 'slug'])
            ->map(fn ($r) => ['type' => 'Region', 'label' => $r->name, 'url' => route('regions.show', $r)]);

        $tips = TravelTip::published()
            ->with('region')
            ->where('title', 'like', "%{$query}%")
            ->take(6)
            ->get()
            ->map(fn ($t) => ['type' => 'Reisetipp', 'label' => $t->title.' · '.$t->region->name, 'url' => route('tips.show', [$t->region, $t])]);

        return response()->json($regions->concat($tips)->values());
    }
}
