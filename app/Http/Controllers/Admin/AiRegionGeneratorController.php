<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\TravelTip;
use App\Support\OpenAiRegionDrafter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        try {
            $draft = OpenAiRegionDrafter::draft($validated['place_name'], $tipCount);
        } catch (Throwable $e) {
            return back()->withErrors(['place_name' => $e->getMessage()])->withInput();
        }

        try {
            $region = DB::transaction(function () use ($draft) {
                $region = Region::create([
                    'name' => $draft['name'],
                    'type' => in_array($draft['type'] ?? null, ['Region', 'Stadt', 'Insel', 'Reisegebiet'], true)
                        ? $draft['type'] : 'Region',
                    'country' => $draft['country'] ?? '',
                    'federal_state' => $draft['federal_state'] ?? null,
                    'best_travel_time' => $draft['best_travel_time'] ?? null,
                    'short_description' => mb_substr($draft['short_description'] ?? $draft['name'], 0, 255),
                    'description' => $draft['description'] ?? '',
                    'arrival_information' => $draft['arrival_information'] ?? null,
                    'latitude' => $draft['latitude'] ?? null,
                    'longitude' => $draft['longitude'] ?? null,
                    'seo_title' => $draft['seo_title'] ?? null,
                    'seo_description' => $draft['seo_description'] ?? null,
                    'is_published' => false,
                    'sort_order' => (int) (Region::max('sort_order')) + 1,
                ]);

                foreach ($draft['tips'] as $index => $tipDraft) {
                    if (empty($tipDraft['title'])) {
                        continue;
                    }

                    TravelTip::create([
                        'region_id' => $region->id,
                        'title' => $tipDraft['title'],
                        'short_description' => mb_substr($tipDraft['short_description'] ?? $tipDraft['title'], 0, 255),
                        'description' => $tipDraft['description'] ?? '',
                        'location_name' => $tipDraft['location_name'] ?? null,
                        'address' => $tipDraft['address'] ?? null,
                        'latitude' => $tipDraft['latitude'] ?? null,
                        'longitude' => $tipDraft['longitude'] ?? null,
                        'duration' => $tipDraft['duration'] ?? null,
                        'difficulty' => in_array($tipDraft['difficulty'] ?? null, ['leicht', 'mittel', 'anspruchsvoll'], true)
                            ? $tipDraft['difficulty'] : null,
                        'best_season' => $tipDraft['best_season'] ?? null,
                        'price_information' => $tipDraft['price_information'] ?? null,
                        'opening_hours' => $tipDraft['opening_hours'] ?? null,
                        'parking_information' => $tipDraft['parking_information'] ?? null,
                        'arrival_information' => $tipDraft['arrival_information'] ?? null,
                        'website_url' => $tipDraft['website_url'] ?? null,
                        'phone' => $tipDraft['phone'] ?? null,
                        'email' => $tipDraft['email'] ?? null,
                        'rating' => $tipDraft['rating'] ?? null,
                        'family_friendly' => (bool) ($tipDraft['family_friendly'] ?? false),
                        'stroller_friendly' => (bool) ($tipDraft['stroller_friendly'] ?? false),
                        'dog_friendly' => (bool) ($tipDraft['dog_friendly'] ?? false),
                        'indoor' => (bool) ($tipDraft['indoor'] ?? false),
                        'free_entry' => (bool) ($tipDraft['free_entry'] ?? false),
                        'featured' => (bool) ($tipDraft['featured'] ?? false),
                        'highlights' => array_values(array_filter((array) ($tipDraft['highlights'] ?? []))),
                        'is_published' => false,
                        'sort_order' => $index,
                    ]);
                }

                return $region;
            });
        } catch (Throwable $e) {
            Log::error('KI-Regionsgenerator: Speichern des Entwurfs fehlgeschlagen.', ['error' => $e->getMessage()]);

            return back()->withErrors(['place_name' => 'Der Entwurf konnte nicht gespeichert werden: '.$e->getMessage()])->withInput();
        }

        return redirect()
            ->route('admin.regions.edit', $region)
            ->with('status', 'KI-Entwurf wurde erstellt (unveröffentlicht). Bitte alle Angaben prüfen, bevor du die Region veröffentlichst.');
    }
}
