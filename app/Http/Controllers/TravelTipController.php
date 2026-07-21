<?php

namespace App\Http\Controllers;

use App\Models\Region;
use App\Models\TravelTip;
use Illuminate\Http\Request;

class TravelTipController extends Controller
{
    public function show(Request $request, Region $region, string $tipSlug)
    {
        abort_unless($region->is_published, 404);

        $tip = TravelTip::where('region_id', $region->id)
            ->where('slug', $tipSlug)
            ->with(['media', 'labels', 'categories', 'region'])
            ->firstOrFail();

        abort_unless($tip->is_published, 404);

        $favoriteIds = $request->session()->get('favorite_tip_ids', []);

        $similarTips = TravelTip::published()
            ->where('region_id', $region->id)
            ->where('id', '!=', $tip->id)
            ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $tip->categories->pluck('id')))
            ->with(['media', 'labels'])
            ->take(3)
            ->get();

        if ($similarTips->count() < 3) {
            $more = TravelTip::published()
                ->where('region_id', $region->id)
                ->where('id', '!=', $tip->id)
                ->whereNotIn('id', $similarTips->pluck('id'))
                ->with(['media', 'labels'])
                ->inRandomOrder()
                ->take(3 - $similarTips->count())
                ->get();
            $similarTips = $similarTips->concat($more);
        }

        $otherTips = TravelTip::published()
            ->where('region_id', $region->id)
            ->where('id', '!=', $tip->id)
            ->with(['media'])
            ->orderBy('sort_order')
            ->take(6)
            ->get();

        return view('tips.show', compact('tip', 'region', 'similarTips', 'otherTips', 'favoriteIds'));
    }
}
