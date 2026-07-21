<?php

namespace App\Support;

use App\Models\Region;
use App\Models\TravelTip;
use Illuminate\Support\Facades\DB;

/**
 * Persists an OpenAiRegionDrafter draft array as an unpublished Region plus
 * its TravelTips. Shared by the manual admin "KI-Regionsgenerator" flow and
 * the regions:auto-generate cron so both save drafts the same way.
 */
class RegionDraftPersister
{
    public static function persist(array $draft, bool $aiGenerated = false): Region
    {
        return DB::transaction(function () use ($draft, $aiGenerated) {
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
                'ai_generated' => $aiGenerated,
                'sort_order' => (int) (Region::max('sort_order')) + 1,
            ]);

            foreach ($draft['tips'] ?? [] as $index => $tipDraft) {
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
    }
}
