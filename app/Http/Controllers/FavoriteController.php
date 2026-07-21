<?php

namespace App\Http\Controllers;

use App\Models\TravelTip;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $ids = $request->session()->get('favorite_tip_ids', []);

        $tips = TravelTip::published()
            ->whereIn('id', $ids)
            ->with(['region', 'media', 'labels'])
            ->get();

        return view('favorites.index', compact('tips'));
    }

    public function toggle(Request $request, TravelTip $travelTip)
    {
        $ids = collect($request->session()->get('favorite_tip_ids', []));

        if ($ids->contains($travelTip->id)) {
            $ids = $ids->reject(fn ($id) => $id === $travelTip->id);
            $isFavorite = false;
        } else {
            $ids->push($travelTip->id);
            $isFavorite = true;
        }

        $request->session()->put('favorite_tip_ids', $ids->values()->all());

        if ($request->wantsJson()) {
            return response()->json(['is_favorite' => $isFavorite, 'count' => $ids->count()]);
        }

        return back();
    }
}
