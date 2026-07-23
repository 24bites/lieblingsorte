<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiUsageLog;
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

        $aiUsage = [
            'today' => [
                'calls' => AiUsageLog::whereDate('created_at', today())->count(),
                'tokens' => (int) AiUsageLog::whereDate('created_at', today())->sum('total_tokens'),
                'cost' => (float) AiUsageLog::whereDate('created_at', today())->sum('estimated_cost_usd'),
            ],
            'month' => [
                'calls' => AiUsageLog::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->count(),
                'tokens' => (int) AiUsageLog::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('total_tokens'),
                'cost' => (float) AiUsageLog::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])->sum('estimated_cost_usd'),
            ],
            'byFeature' => AiUsageLog::whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
                ->selectRaw('feature, COUNT(*) as calls, SUM(total_tokens) as tokens, SUM(estimated_cost_usd) as cost')
                ->groupBy('feature')
                ->orderByDesc('cost')
                ->get(),
        ];

        return view('admin.dashboard', compact('stats', 'recentRegions', 'recentTips', 'aiUsage'));
    }
}
