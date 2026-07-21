<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Region;
use App\Models\TravelTip;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'regions' => Region::count(),
            'travel_tips' => TravelTip::count(),
            'categories' => Category::count(),
            'published' => Region::where('is_published', true)->count() + TravelTip::where('is_published', true)->count(),
        ];

        $recentRegions = Region::orderByDesc('updated_at')->take(5)->get();
        $recentTips = TravelTip::with('region')->orderByDesc('updated_at')->take(5)->get();

        return view('admin.dashboard', compact('stats', 'recentRegions', 'recentTips'));
    }
}
