<?php

namespace App\Http\Controllers;

use App\Models\TravelReport;
use Illuminate\Http\Request;

class TravelReportController extends Controller
{
    public function index(Request $request)
    {
        $query = TravelReport::published()->with(['region', 'media']);

        if ($search = $request->string('q')->trim()->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        $reports = $query->orderByDesc('published_at')->paginate(12)->withQueryString();

        return view('reports.index', compact('reports'));
    }

    public function show(Request $request, TravelReport $report, bool $preview = false)
    {
        abort_unless($preview || $report->is_published, 404);

        $report->loadMissing(['region', 'media']);

        $similarReports = TravelReport::published()
            ->where('id', '!=', $report->id)
            ->with('media')
            ->orderByDesc('published_at')
            ->take(3)
            ->get();

        return view('reports.show', compact('report', 'similarReports', 'preview'));
    }
}
