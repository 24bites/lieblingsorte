<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Support\OpenAiRegionDrafter;
use App\Support\RegionDraftPersister;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiRegionGeneratorController extends Controller
{
    public function create()
    {
        return view('admin.ai-region-generator.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'place_name' => ['required', 'string', 'max:150'],
            'tip_count' => ['nullable', 'integer', 'min:5', 'max:20'],
        ]);

        $tipCount = $validated['tip_count'] ?? 15;

        // Drafting a full region with up to 20 tips can take longer than PHP's
        // default max_execution_time (commonly 30s on shared php.ini configs).
        set_time_limit(240);

        try {
            $draft = OpenAiRegionDrafter::draft($validated['place_name'], $tipCount);
        } catch (Throwable $e) {
            return back()->withErrors(['place_name' => $e->getMessage()])->withInput();
        }

        try {
            $region = RegionDraftPersister::persist($draft);
        } catch (Throwable $e) {
            Log::error('KI-Regionsgenerator: Speichern des Entwurfs fehlgeschlagen.', ['error' => $e->getMessage()]);

            return back()->withErrors(['place_name' => 'Der Entwurf konnte nicht gespeichert werden: '.$e->getMessage()])->withInput();
        }

        return redirect()
            ->route('admin.regions.edit', $region)
            ->with('status', 'KI-Entwurf wurde erstellt (unveröffentlicht). Bitte alle Angaben prüfen, bevor du die Region veröffentlichst.');
    }
}
