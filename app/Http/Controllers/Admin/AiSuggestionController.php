<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Region;

class AiSuggestionController extends Controller
{
    public function index()
    {
        $regions = Region::pendingAiReview()->orderByDesc('created_at')->paginate(15);

        return view('admin.ai-suggestions.index', compact('regions'));
    }

    public function approve(Region $region)
    {
        $region->update(['approved_at' => now()]);

        return back()->with('status', "\"{$region->name}\" wurde freigegeben und wird im Hintergrund fertiggestellt (Titelbild, Reisetipps, Tipp-Bilder) und danach automatisch veröffentlicht.");
    }

    public function reject(Region $region)
    {
        $region->update(['rejected_at' => now()]);

        return back()->with('status', "\"{$region->name}\" wurde abgelehnt und ausgeblendet.");
    }
}
