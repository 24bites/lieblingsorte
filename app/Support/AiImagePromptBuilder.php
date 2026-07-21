<?php

namespace App\Support;

use App\Models\Region;
use App\Models\TravelTip;
use Illuminate\Database\Eloquent\Model;

/**
 * Builds image-generation prompts grounded only in facts already stored on
 * the Region/TravelTip record. Never asks the model to depict a specific
 * named landmark, sign, or building it cannot actually know the appearance
 * of - that is how AI photo generators fabricate wrong architecture, invented
 * text, or non-existent details. Instead it describes the kind of place and
 * lets the model render a generic, plausible scene.
 */
class AiImagePromptBuilder
{
    public static function forModel(Model $model): string
    {
        return $model instanceof TravelTip
            ? self::forTravelTip($model)
            : self::forRegion($model);
    }

    private static function forRegion(Region $region): string
    {
        $place = trim($region->name.($region->federal_state ? ', '.$region->federal_state : '').', '.$region->country);
        $context = self::sentence($region->short_description);

        return self::wrap(
            "Reisefoto der Gegend \"{$place}\" (Typ: {$region->type}).".$context,
        );
    }

    private static function forTravelTip(TravelTip $tip): string
    {
        $place = trim($tip->location_name ?: $tip->title);
        $region = $tip->region;
        $regionName = $region ? trim($region->name.', '.$region->country) : null;
        $context = self::sentence($tip->short_description);

        $where = $regionName ? "{$place} in der Gegend {$regionName}" : $place;

        return self::wrap("Reisefoto von \"{$where}\".".$context);
    }

    private static function sentence(?string $text): string
    {
        if (blank($text)) {
            return '';
        }

        return ' Kontext: '.trim($text);
    }

    private static function wrap(string $subject): string
    {
        return $subject.' '.
            'Erstelle ein realistisches, dokumentarisches Reisefoto dieser Art von Ort - natürliches Licht, '.
            'professionelle Reisefotografie, keine Illustration, kein Gemälde, kein 3D-Rendering. '.
            'Zeige eine glaubwürdige, generische Ansicht, die zur Beschreibung passt. '.
            'Wichtig: Erfinde keine konkreten Wahrzeichen, Gebäudenamen, Schilder, Beschriftungen, Logos oder Texte, '.
            'die nicht explizit oben genannt wurden. Kein Text und keine Schrift im Bild. '.
            'Keine erkennbaren Gesichter oder Personen im Vordergrund.';
    }
}
