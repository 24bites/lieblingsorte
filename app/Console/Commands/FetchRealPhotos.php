<?php

namespace App\Console\Commands;

use App\Models\Region;
use App\Models\TravelTip;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Replaces the generated placeholder illustrations with real, freely licensed
 * photographs from Wikimedia Commons. Only images with an open license
 * (CC0 / Public Domain / CC-BY / CC-BY-SA) are used; attribution is written
 * to CREDITS.md. See README "Bildverwaltung" for background.
 */
class FetchRealPhotos extends Command
{
    protected $signature = 'images:fetch-real {--only=} {--limit-per-item=3}';

    protected $description = 'Replace generated illustrations with real, freely licensed photos from Wikimedia Commons';

    private array $allowedLicenses = [
        'CC0', 'Public domain', 'PD', 'PD US', 'PD Art', 'PD-old', 'PDM',
        'CC BY-SA 4.0', 'CC BY-SA 3.0', 'CC BY-SA 2.5', 'CC BY-SA 2.0', 'CC BY-SA 1.0',
        'CC BY 4.0', 'CC BY 3.0', 'CC BY 2.5', 'CC BY 2.0', 'CC BY 1.0',
    ];

    private array $blockedWords = [
        'karte', 'map', 'wappen', 'logo', 'plan', 'diagramm', 'lageplan', 'flagge', 'skizze', 'icon',
        'coat of arms', 'grafik', 'schema', 'positionskarte',
        // scans of historical books/documents rendered as .jpg page thumbnails, or non-photographic artwork
        '(ia ', 'holzstich', 'kupferstich', 'stich -', 'gemälde', 'zeichnung', 'lithographie', 'radierung',
        'gravur', 'buchseite', 'titelseite', 'titelblatt', 'manuscript', 'handschrift', 'jahrbuch',
        'zeitschrift', 'verhandlungen', 'studien zur', 'postkarte', 'briefmarke', 'wahlplakat', 'plakat',
        'tablet', 'gedenktafel', 'inschrift', 'plaque', 'signage', 'banner',
    ];

    private array $credits = [];

    public function handle(): int
    {
        $items = $this->items();
        $only = $this->option('only');

        if ($only) {
            $items = array_values(array_filter($items, fn ($i) => $i['slug'] === $only));
        }

        foreach ($items as $item) {
            $this->processItem($item);
            usleep(250000);
        }

        $this->writeCredits();
        $this->info('Fertig. '.count($this->credits).' Bilder gespeichert.');

        return self::SUCCESS;
    }

    private function processItem(array $item): void
    {
        $this->info("→ {$item['title']}  (Suche: \"{$item['query']}\")");

        $candidates = $this->searchCommons($item['query'], 15);
        $good = array_values(array_filter($candidates, fn ($c) => $this->isGoodCandidate($c)));

        if (empty($good)) {
            $this->warn('  Keine passenden Bilder gefunden — Illustration bleibt erhalten.');

            return;
        }

        $model = $item['type'] === 'region'
            ? Region::where('slug', $item['slug'])->first()
            : TravelTip::where('slug', $item['slug'])->first();

        if (! $model) {
            $this->error("  Datensatz nicht gefunden: {$item['slug']}");

            return;
        }

        $limit = $item['count'] ?? (int) $this->option('limit-per-item');
        $selected = array_slice($good, 0, $limit);

        foreach ($model->media as $old) {
            Storage::disk('public')->delete($old->file_path);
        }
        $model->media()->delete();

        $directory = $item['type'] === 'region' ? "regions/{$item['slug']}" : "tips/{$item['slug']}";
        $stored = 0;

        foreach ($selected as $index => $candidate) {
            $path = $this->downloadAndStore($candidate, $directory, $item['slug'], $stored);

            if (! $path) {
                continue;
            }

            $model->media()->create([
                'file_path' => $path,
                'alt_text' => $item['alt'],
                'sort_order' => $stored,
                'is_cover' => $stored === 0,
            ]);

            if ($stored === 0 && $item['type'] === 'region') {
                $model->update(['hero_image' => $path]);
            }

            $this->credits[] = [
                'used_for' => $item['title'],
                'file' => $path,
                'source_title' => $candidate['title'],
                'author' => trim(strip_tags($candidate['author'] ?: 'unbekannt')),
                'license' => $candidate['license'],
                'source_url' => $candidate['descriptionurl'],
            ];

            $this->line("  ✓ {$path}  [{$candidate['license']}]");
            $stored++;
        }

        if ($stored === 0) {
            $this->warn('  Download fehlgeschlagen für alle Kandidaten — Illustration bleibt erhalten.');
        }
    }

    private function searchCommons(string $query, int $limit): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'LieblingsorteMVP/1.0 (Bildungsprojekt; Kontakt: hallo@lieblingsorte.test)',
            ])->timeout(30)->retry(2, 500)->get('https://commons.wikimedia.org/w/api.php', [
                'action' => 'query',
                'generator' => 'search',
                'gsrsearch' => $query,
                'gsrnamespace' => 6,
                'gsrlimit' => $limit,
                'prop' => 'imageinfo',
                'iiprop' => 'url|extmetadata|size',
                'iiurlwidth' => 1600,
                'format' => 'json',
            ]);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->warn("  ⚠ Suche fehlgeschlagen (Netzwerk-Timeout) für: {$query}");

            return [];
        }

        if (! $response->ok()) {
            return [];
        }

        $pages = $response->json('query.pages') ?? [];
        $results = [];

        foreach ($pages as $page) {
            $info = $page['imageinfo'][0] ?? null;
            if (! $info) {
                continue;
            }

            $meta = $info['extmetadata'] ?? [];

            $results[] = [
                'title' => $page['title'] ?? '',
                'width' => $info['thumbwidth'] ?? $info['width'] ?? 0,
                'height' => $info['thumbheight'] ?? $info['height'] ?? 0,
                'thumburl' => $info['thumburl'] ?? $info['url'] ?? null,
                'url' => $info['url'] ?? null,
                'descriptionurl' => $info['descriptionurl'] ?? '',
                'license' => $meta['LicenseShortName']['value'] ?? 'unbekannt',
                'author' => $meta['Artist']['value'] ?? null,
            ];
        }

        return $results;
    }

    private function isGoodCandidate(array $candidate): bool
    {
        if (! $candidate['thumburl']) {
            return false;
        }

        // Check the *source* filename, not the thumbnail URL: Commons renders PDF/DjVu
        // book pages as .jpg thumbnails too, so the thumbnail extension alone is not reliable.
        $titleLower = Str::lower($candidate['title']);
        if (! Str::endsWith($titleLower, ['.jpg', '.jpeg'])) {
            return false;
        }

        if (! in_array($candidate['license'], $this->allowedLicenses, true)) {
            return false;
        }

        if ($candidate['width'] < 900) {
            return false;
        }

        $ratio = $candidate['height'] > 0 ? $candidate['width'] / $candidate['height'] : 0;
        if ($ratio < 0.7 || $ratio > 2.4) {
            return false;
        }

        foreach ($this->blockedWords as $word) {
            if (Str::contains($titleLower, $word)) {
                return false;
            }
        }

        return true;
    }

    private function downloadAndStore(array $candidate, string $directory, string $baseSlug, int $index): ?string
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'LieblingsorteMVP/1.0 (Bildungsprojekt; Kontakt: hallo@lieblingsorte.test)',
            ])->timeout(30)->retry(2, 500)->get($candidate['thumburl']);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $this->warn("  ⚠ Download fehlgeschlagen (Netzwerk-Timeout): {$candidate['thumburl']}");

            return null;
        }

        if (! $response->ok()) {
            return null;
        }

        $bytes = $response->body();
        $image = @imagecreatefromstring($bytes);

        if ($image === false) {
            return null;
        }

        $filename = "{$baseSlug}-".($index + 1).'.jpg';
        $relativePath = "{$directory}/{$filename}";
        $absolutePath = storage_path("app/public/{$relativePath}");

        if (! is_dir(dirname($absolutePath))) {
            mkdir(dirname($absolutePath), 0755, true);
        }

        imagejpeg($image, $absolutePath, 85);
        imagedestroy($image);

        return $relativePath;
    }

    private function writeCredits(): void
    {
        $sidecarPath = storage_path('app/credits.json');
        $existing = file_exists($sidecarPath) ? json_decode(file_get_contents($sidecarPath), true) : [];
        $existing = is_array($existing) ? $existing : [];
        $existing = array_column($existing, null, 'file');

        // Drop stale entries for files that no longer exist (e.g. re-fetched with a different index count).
        $existing = array_filter($existing, fn ($c) => Storage::disk('public')->exists($c['file']));

        $merged = $existing;
        foreach ($this->credits as $credit) {
            $merged[$credit['file']] = $credit;
        }
        ksort($merged);

        file_put_contents($sidecarPath, json_encode(array_values($merged), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        if (empty($merged)) {
            return;
        }

        $lines = [
            '# Bildnachweise',
            '',
            'Diese Datei dokumentiert die Quellen aller von Wikimedia Commons bezogenen Fotos in `storage/app/public/regions` und `storage/app/public/tips`. Alle Bilder stehen unter einer freien Lizenz (CC0, Public Domain, CC BY oder CC BY-SA) und dürfen unter Einhaltung der jeweiligen Lizenzbedingungen (insbesondere Namensnennung bei CC BY/CC BY-SA) weiterverwendet werden.',
            '',
            'Erzeugt von `php artisan images:fetch-real`.',
            '',
        ];

        foreach ($merged as $credit) {
            $lines[] = "## {$credit['file']}";
            $lines[] = "- Verwendet für: {$credit['used_for']}";
            $lines[] = "- Wikimedia-Commons-Titel: {$credit['source_title']}";
            $lines[] = "- Autor/in: {$credit['author']}";
            $lines[] = "- Lizenz: {$credit['license']}";
            $lines[] = "- Quelle: {$credit['source_url']}";
            $lines[] = '';
        }

        file_put_contents(base_path('CREDITS.md'), implode("\n", $lines));
    }

    private function items(): array
    {
        return [
            ['type' => 'region', 'slug' => 'suedtirol', 'title' => 'Südtirol', 'query' => 'Dolomiten', 'alt' => 'Dolomitenlandschaft in Südtirol', 'count' => 2],
            ['type' => 'region', 'slug' => 'allgaeu', 'title' => 'Allgäu', 'query' => 'Allgäu', 'alt' => 'Alpenlandschaft im Allgäu', 'count' => 2],

            ['type' => 'tip', 'slug' => 'kalterer-see', 'title' => 'Kalterer See', 'query' => 'Kalterer See Südtirol', 'alt' => 'Der Kalterer See in Südtirol', 'count' => 3],
            ['type' => 'tip', 'slug' => 'marlinger-waalweg', 'title' => 'Marlinger Waalweg', 'query' => 'Marlinger Waalweg Meran', 'alt' => 'Der Marlinger Waalweg bei Meran', 'count' => 1],
            ['type' => 'tip', 'slug' => 'gaerten-von-schloss-trauttmansdorff', 'title' => 'Gärten von Schloss Trauttmansdorff', 'query' => 'Schloss Trauttmansdorff Gärten Meran', 'alt' => 'Die Gärten von Schloss Trauttmansdorff in Meran', 'count' => 3],
            ['type' => 'tip', 'slug' => 'seiser-alm', 'title' => 'Seiser Alm', 'query' => 'Seiser Alm Langkofel', 'alt' => 'Die Seiser Alm mit Blick auf den Langkofel', 'count' => 3],
            ['type' => 'tip', 'slug' => 'hans-und-paula-steger-weg', 'title' => 'Hans-und-Paula-Steger-Weg', 'query' => 'Alpe di Siusi', 'alt' => 'Panoramaweg auf der Seiser Alm', 'count' => 1],
            ['type' => 'tip', 'slug' => 'meraner-altstadt', 'title' => 'Meraner Altstadt', 'query' => 'Meran Lauben Altstadt', 'alt' => 'Die Altstadt von Meran mit den Lauben', 'count' => 1],
            ['type' => 'tip', 'slug' => 'algunder-waalweg', 'title' => 'Algunder Waalweg', 'query' => 'Algund Waalweg', 'alt' => 'Der Waalweg bei Algund', 'count' => 1],
            ['type' => 'tip', 'slug' => 'partschinser-wasserfall', 'title' => 'Partschinser Wasserfall', 'query' => 'Partschinser Wasserfall', 'alt' => 'Der Partschinser Wasserfall', 'count' => 1],
            ['type' => 'tip', 'slug' => 'vigiljoch', 'title' => 'Vigiljoch', 'query' => 'Vigiljoch Lana Südtirol', 'alt' => 'Das Vigiljoch oberhalb von Lana', 'count' => 1],
            ['type' => 'tip', 'slug' => 'hafling', 'title' => 'Hafling', 'query' => 'Hafling Südtirol', 'alt' => 'Das Hochplateau von Hafling', 'count' => 1],
            ['type' => 'tip', 'slug' => 'knottnkino', 'title' => 'Knottnkino', 'query' => 'Hafling Aussicht Etschtal', 'alt' => 'Aussicht über das Etschtal bei Hafling', 'count' => 1],
            ['type' => 'tip', 'slug' => 'schloss-tirol', 'title' => 'Schloss Tirol', 'query' => 'Schloss Tirol Burg', 'alt' => 'Schloss Tirol bei Meran', 'count' => 1],
            ['type' => 'tip', 'slug' => 'bozner-lauben', 'title' => 'Bozner Lauben', 'query' => 'Bozen Lauben Altstadt', 'alt' => 'Die Bozner Lauben', 'count' => 1],
            ['type' => 'tip', 'slug' => 'oetzi-museum', 'title' => 'Ötzi-Museum', 'query' => 'Südtiroler Archäologiemuseum Bozen Gebäude', 'alt' => 'Das Südtiroler Archäologiemuseum in Bozen', 'count' => 1],
            ['type' => 'tip', 'slug' => 'rittner-horn', 'title' => 'Rittner Horn', 'query' => 'Rittner Horn', 'alt' => 'Das Rittner Horn', 'count' => 1],
            ['type' => 'tip', 'slug' => 'erdpyramiden-am-ritten', 'title' => 'Erdpyramiden am Ritten', 'query' => 'Erdpyramiden Ritten', 'alt' => 'Die Erdpyramiden am Ritten', 'count' => 3],
            ['type' => 'tip', 'slug' => 'karersee', 'title' => 'Karersee', 'query' => 'Karersee Dolomiten', 'alt' => 'Der Karersee vor dem Latemar', 'count' => 3],
            ['type' => 'tip', 'slug' => 'st-magdalena-in-villnoess', 'title' => 'St. Magdalena in Villnöss', 'query' => 'St. Magdalena Villnöss Geisler', 'alt' => 'St. Magdalena in Villnöss vor den Geislerspitzen', 'count' => 3],
            ['type' => 'tip', 'slug' => 'pragser-wildsee', 'title' => 'Pragser Wildsee', 'query' => 'Pragser Wildsee', 'alt' => 'Der Pragser Wildsee', 'count' => 3],
            ['type' => 'tip', 'slug' => 'tappeinerweg', 'title' => 'Tappeinerweg', 'query' => 'Tappeinerweg Meran', 'alt' => 'Der Tappeinerweg über Meran', 'count' => 1],

            ['type' => 'tip', 'slug' => 'schloss-neuschwanstein', 'title' => 'Schloss Neuschwanstein', 'query' => 'Schloss Neuschwanstein', 'alt' => 'Schloss Neuschwanstein', 'count' => 3],
            ['type' => 'tip', 'slug' => 'schloss-hohenschwangau', 'title' => 'Schloss Hohenschwangau', 'query' => 'Schloss Hohenschwangau', 'alt' => 'Schloss Hohenschwangau', 'count' => 1],
            ['type' => 'tip', 'slug' => 'hopfensee', 'title' => 'Hopfensee', 'query' => 'Hopfensee Füssen', 'alt' => 'Der Hopfensee bei Füssen', 'count' => 1],
            ['type' => 'tip', 'slug' => 'forggensee', 'title' => 'Forggensee', 'query' => 'Forggensee Füssen', 'alt' => 'Der Forggensee bei Füssen', 'count' => 1],
            ['type' => 'tip', 'slug' => 'breitachklamm', 'title' => 'Breitachklamm', 'query' => 'Breitachklamm Oberstdorf', 'alt' => 'Die Breitachklamm bei Oberstdorf', 'count' => 3],
            ['type' => 'tip', 'slug' => 'oberstdorf', 'title' => 'Oberstdorf', 'query' => 'Oberstdorf Marktplatz', 'alt' => 'Der Ort Oberstdorf im Allgäu', 'count' => 1],
            ['type' => 'tip', 'slug' => 'nebelhorn', 'title' => 'Nebelhorn', 'query' => 'Nebelhorn Gipfel', 'alt' => 'Das Nebelhorn bei Oberstdorf', 'count' => 1],
            ['type' => 'tip', 'slug' => 'alpsee', 'title' => 'Alpsee', 'query' => 'Alpsee Hohenschwangau', 'alt' => 'Der Alpsee bei Hohenschwangau', 'count' => 1],
            ['type' => 'tip', 'slug' => 'grosser-alpsee', 'title' => 'Großer Alpsee', 'query' => 'Großer Alpsee Immenstadt', 'alt' => 'Der Große Alpsee bei Immenstadt', 'count' => 1],
            ['type' => 'tip', 'slug' => 'kempten', 'title' => 'Kempten', 'query' => 'Kempten Allgäu Altstadt', 'alt' => 'Die Altstadt von Kempten', 'count' => 1],
            ['type' => 'tip', 'slug' => 'fuessen', 'title' => 'Füssen', 'query' => 'Füssen Altstadt Allgäu', 'alt' => 'Die Altstadt von Füssen', 'count' => 1],
            ['type' => 'tip', 'slug' => 'tegelberg', 'title' => 'Tegelberg', 'query' => 'Tegelberg Schwangau', 'alt' => 'Der Tegelberg bei Schwangau', 'count' => 1],
            ['type' => 'tip', 'slug' => 'skywalk-allgaeu', 'title' => 'Skywalk Allgäu', 'query' => 'Grünten', 'alt' => 'Der Grünten im Allgäu', 'count' => 1],
            ['type' => 'tip', 'slug' => 'huendle', 'title' => 'Hündle', 'query' => 'Hündle Oberstaufen', 'alt' => 'Das Hündle bei Oberstaufen', 'count' => 1],
            ['type' => 'tip', 'slug' => 'starzlachklamm', 'title' => 'Starzlachklamm', 'query' => 'Starzlachklamm', 'alt' => 'Die Starzlachklamm bei Burgberg', 'count' => 1],
            ['type' => 'tip', 'slug' => 'eistobel', 'title' => 'Eistobel', 'query' => 'Eistobel Isny Allgäu', 'alt' => 'Der Eistobel bei Isny', 'count' => 1],
            ['type' => 'tip', 'slug' => 'alpsee-bergwelt', 'title' => 'Alpsee Bergwelt', 'query' => 'Immenstadt Alpsee', 'alt' => 'Die Alpsee Bergwelt bei Immenstadt', 'count' => 1],
            ['type' => 'tip', 'slug' => 'lechfall', 'title' => 'Lechfall', 'query' => 'Lechfall Füssen', 'alt' => 'Der Lechfall in Füssen', 'count' => 1],
            ['type' => 'tip', 'slug' => 'buchenegger-wasserfaelle', 'title' => 'Buchenegger Wasserfälle', 'query' => 'Buchenegger Wasserfälle Allgäu', 'alt' => 'Die Buchenegger Wasserfälle', 'count' => 1],
            ['type' => 'tip', 'slug' => 'fellhorn', 'title' => 'Fellhorn', 'query' => 'Fellhorn Oberstdorf Allgäu', 'alt' => 'Das Fellhorn bei Oberstdorf', 'count' => 1],

            // Trier
            ['type' => 'region', 'slug' => 'trier', 'title' => 'Trier', 'query' => 'Trier Mosel', 'alt' => 'Blick über Trier an der Mosel', 'count' => 2],
            ['type' => 'tip', 'slug' => 'porta-nigra', 'title' => 'Porta Nigra', 'query' => 'Porta Nigra Trier', 'alt' => 'Die Porta Nigra in Trier', 'count' => 3],
            ['type' => 'tip', 'slug' => 'trierer-dom-st-peter', 'title' => 'Trierer Dom St. Peter', 'query' => 'Trierer Dom', 'alt' => 'Der Trierer Dom St. Peter', 'count' => 1],
            ['type' => 'tip', 'slug' => 'kaiserthermen', 'title' => 'Kaiserthermen', 'query' => 'Kaiserthermen Trier', 'alt' => 'Die Kaiserthermen in Trier', 'count' => 1],
            ['type' => 'tip', 'slug' => 'amphitheater-trier', 'title' => 'Amphitheater Trier', 'query' => 'Amphitheater Trier', 'alt' => 'Das römische Amphitheater in Trier', 'count' => 1],
            ['type' => 'tip', 'slug' => 'liebfrauenkirche-trier', 'title' => 'Liebfrauenkirche Trier', 'query' => 'Liebfrauenkirche Trier', 'alt' => 'Die Liebfrauenkirche in Trier', 'count' => 1],
            ['type' => 'tip', 'slug' => 'barbarathermen', 'title' => 'Barbarathermen', 'query' => 'Barbarathermen Trier', 'alt' => 'Die Barbarathermen in Trier', 'count' => 1],
            ['type' => 'tip', 'slug' => 'roemerbruecke-trier', 'title' => 'Römerbrücke Trier', 'query' => 'Römerbrücke Trier', 'alt' => 'Die Römerbrücke in Trier', 'count' => 1],
            ['type' => 'tip', 'slug' => 'hauptmarkt-trier', 'title' => 'Hauptmarkt Trier', 'query' => 'Hauptmarkt Trier', 'alt' => 'Der Hauptmarkt in Trier', 'count' => 1],
            ['type' => 'tip', 'slug' => 'karl-marx-haus', 'title' => 'Karl-Marx-Haus', 'query' => 'Karl-Marx-Haus Trier Bruckenstrasse', 'alt' => 'Das Karl-Marx-Haus in Trier', 'count' => 1],
            ['type' => 'tip', 'slug' => 'basilika-trier-aula-palatina', 'title' => 'Basilika Trier (Aula Palatina)', 'query' => 'Konstantin Basilika Trier', 'alt' => 'Die Basilika (Aula Palatina) in Trier', 'count' => 1],
            ['type' => 'tip', 'slug' => 'kurfuerstliches-palais-mit-palastgarten', 'title' => 'Kurfürstliches Palais mit Palastgarten', 'query' => 'Kurfürstliches Palais Trier', 'alt' => 'Das Kurfürstliche Palais in Trier', 'count' => 1],
            ['type' => 'tip', 'slug' => 'petrisberg-aussichtspunkt', 'title' => 'Petrisberg Aussichtspunkt', 'query' => 'Petrisberg Trier', 'alt' => 'Der Petrisberg über Trier', 'count' => 1],
            ['type' => 'tip', 'slug' => 'igeler-saeule', 'title' => 'Igeler Säule', 'query' => 'Igeler Säule', 'alt' => 'Die Igeler Säule bei Trier', 'count' => 1],
            ['type' => 'tip', 'slug' => 'mosel-radweg-bei-trier', 'title' => 'Mosel-Radweg bei Trier', 'query' => 'Mosel Trier Weinberge', 'alt' => 'Die Mosel bei Trier', 'count' => 1],
            ['type' => 'tip', 'slug' => 'olewiger-weinberge', 'title' => 'Olewiger Weinberge', 'query' => 'Olewig Trier', 'alt' => 'Die Weinberge von Olewig bei Trier', 'count' => 1],
            ['type' => 'tip', 'slug' => 'museum-am-dom-trier', 'title' => 'Museum am Dom Trier', 'query' => 'Museum am Dom Trier', 'alt' => 'Das Museum am Dom in Trier', 'count' => 1],
            ['type' => 'tip', 'slug' => 'basilika-st-matthias', 'title' => 'Basilika St. Matthias', 'query' => 'St Matthias Trier', 'alt' => 'Die Basilika St. Matthias in Trier', 'count' => 1],
            ['type' => 'tip', 'slug' => 'rheinisches-landesmuseum-trier', 'title' => 'Rheinisches Landesmuseum Trier', 'query' => 'Rheinisches Landesmuseum Trier', 'alt' => 'Das Rheinische Landesmuseum in Trier', 'count' => 1],
            ['type' => 'tip', 'slug' => 'zurlaubener-ufer', 'title' => 'Zurlaubener Ufer', 'query' => 'Zurlauben Trier', 'alt' => 'Das Zurlaubener Ufer in Trier', 'count' => 1],
            ['type' => 'tip', 'slug' => 'weisshaus-trier', 'title' => 'Weisshaus Trier', 'query' => 'Trier Weisshaus Wald', 'alt' => 'Das Weisshaus über Trier', 'count' => 1],

            // Luxemburg
            ['type' => 'region', 'slug' => 'luxemburg', 'title' => 'Luxemburg', 'query' => 'Luxembourg City', 'alt' => 'Altstadt von Luxemburg', 'count' => 2],
            ['type' => 'tip', 'slug' => 'bock-kasematten', 'title' => 'Bock-Kasematten', 'query' => 'Bock Kasematten Luxembourg', 'alt' => 'Die Bock-Kasematten in Luxemburg', 'count' => 1],
            ['type' => 'tip', 'slug' => 'chemin-de-la-corniche', 'title' => 'Chemin de la Corniche', 'query' => 'Chemin de la Corniche Luxembourg', 'alt' => 'Der Chemin de la Corniche in Luxemburg', 'count' => 1],
            ['type' => 'tip', 'slug' => 'kathedrale-notre-dame-luxemburg', 'title' => 'Kathedrale Notre-Dame Luxemburg', 'query' => 'Cathédrale Notre-Dame Luxembourg', 'alt' => 'Die Kathedrale Notre-Dame in Luxemburg', 'count' => 1],
            ['type' => 'tip', 'slug' => 'grossherzoglicher-palast', 'title' => 'Großherzoglicher Palast', 'query' => 'Grand Ducal Palace Luxembourg', 'alt' => 'Der Großherzogliche Palast in Luxemburg', 'count' => 1],
            ['type' => 'tip', 'slug' => 'place-darmes', 'title' => 'Place d\'Armes', 'query' => 'Place d\'Armes Luxembourg', 'alt' => 'Der Place d\'Armes in Luxemburg', 'count' => 1],
            ['type' => 'tip', 'slug' => 'grund-luxemburg', 'title' => 'Grund (Luxemburg)', 'query' => 'Grund Luxembourg', 'alt' => 'Der Stadtteil Grund in Luxemburg', 'count' => 1],
            ['type' => 'tip', 'slug' => 'adolphe-bruecke', 'title' => 'Adolphe-Brücke', 'query' => 'Pont Adolphe Luxembourg main bow', 'alt' => 'Die Adolphe-Brücke in Luxemburg', 'count' => 1],
            ['type' => 'tip', 'slug' => 'mudam-luxemburg', 'title' => 'Mudam Luxemburg', 'query' => 'Mudam Luxembourg', 'alt' => 'Das Mudam in Luxemburg', 'count' => 1],
            ['type' => 'tip', 'slug' => 'schloss-vianden', 'title' => 'Schloss Vianden', 'query' => 'Vianden Castle', 'alt' => 'Schloss Vianden in Luxemburg', 'count' => 3],
            ['type' => 'tip', 'slug' => 'mullerthal-trail', 'title' => 'Mullerthal Trail', 'query' => 'Mullerthal Luxembourg', 'alt' => 'Das Mullerthal in Luxemburg', 'count' => 3],
            ['type' => 'tip', 'slug' => 'echternach', 'title' => 'Echternach', 'query' => 'Echternach Luxembourg', 'alt' => 'Die Altstadt von Echternach', 'count' => 1],
            ['type' => 'tip', 'slug' => 'schiessentuempel', 'title' => 'Schiessentümpel', 'query' => 'Schiessentümpel', 'alt' => 'Der Wasserfall Schiessentümpel', 'count' => 1],
            ['type' => 'tip', 'slug' => 'schloss-beaufort', 'title' => 'Schloss Beaufort', 'query' => 'Beaufort Castle Luxembourg', 'alt' => 'Schloss Beaufort in Luxemburg', 'count' => 1],
            ['type' => 'tip', 'slug' => 'schloss-larochette', 'title' => 'Schloss Larochette', 'query' => 'Larochette Castle', 'alt' => 'Die Burgruine Larochette', 'count' => 1],
            ['type' => 'tip', 'slug' => 'remich-an-der-mosel', 'title' => 'Remich an der Mosel', 'query' => 'Remich Luxembourg', 'alt' => 'Remich an der Mosel', 'count' => 1],
            ['type' => 'tip', 'slug' => 'esch-sur-sure', 'title' => 'Esch-sur-Sûre', 'query' => 'Esch-sur-Sûre', 'alt' => 'Der Ort Esch-sur-Sûre', 'count' => 1],
            ['type' => 'tip', 'slug' => 'berdorf', 'title' => 'Berdorf', 'query' => 'Berdorf Luxembourg', 'alt' => 'Der Ort Berdorf im Mullerthal', 'count' => 1],
            ['type' => 'tip', 'slug' => 'schloss-clervaux', 'title' => 'Schloss Clervaux', 'query' => 'Clervaux Castle', 'alt' => 'Schloss Clervaux in Luxemburg', 'count' => 1],
            ['type' => 'tip', 'slug' => 'kirchberg-plateau', 'title' => 'Kirchberg-Plateau', 'query' => 'Philharmonie Luxembourg', 'alt' => 'Die Philharmonie auf dem Kirchberg', 'count' => 1],
            ['type' => 'tip', 'slug' => 'abtei-neumuenster', 'title' => 'Abtei Neumünster', 'query' => 'Neumünster Abbey Luxembourg', 'alt' => 'Die Abtei Neumünster in Luxemburg', 'count' => 1],

            // Düsseldorf
            ['type' => 'region', 'slug' => 'duesseldorf', 'title' => 'Düsseldorf', 'query' => 'Düsseldorf Skyline Rhein', 'alt' => 'Skyline von Düsseldorf', 'count' => 2],
            ['type' => 'tip', 'slug' => 'altstadt-duesseldorf', 'title' => 'Altstadt Düsseldorf', 'query' => 'Düsseldorf Altstadt', 'alt' => 'Die Altstadt von Düsseldorf', 'count' => 3],
            ['type' => 'tip', 'slug' => 'koenigsallee', 'title' => 'Königsallee', 'query' => 'Königsallee Düsseldorf', 'alt' => 'Die Königsallee in Düsseldorf', 'count' => 1],
            ['type' => 'tip', 'slug' => 'rheinturm', 'title' => 'Rheinturm', 'query' => 'Rheinturm Düsseldorf', 'alt' => 'Der Rheinturm in Düsseldorf', 'count' => 1],
            ['type' => 'tip', 'slug' => 'medienhafen', 'title' => 'Medienhafen', 'query' => 'Medienhafen Düsseldorf', 'alt' => 'Der Medienhafen in Düsseldorf', 'count' => 3],
            ['type' => 'tip', 'slug' => 'schlossturm-burgplatz', 'title' => 'Schlossturm Burgplatz', 'query' => 'Schlossturm Düsseldorf', 'alt' => 'Der Schlossturm am Burgplatz in Düsseldorf', 'count' => 1],
            ['type' => 'tip', 'slug' => 'kunstsammlung-k20', 'title' => 'Kunstsammlung K20', 'query' => 'K20 Düsseldorf Grabbeplatz', 'alt' => 'Die Kunstsammlung K20 in Düsseldorf', 'count' => 1],
            ['type' => 'tip', 'slug' => 'kunstsammlung-k21', 'title' => 'Kunstsammlung K21', 'query' => 'K21 Ständehaus Düsseldorf', 'alt' => 'Die Kunstsammlung K21 in Düsseldorf', 'count' => 1],
            ['type' => 'tip', 'slug' => 'hofgarten-duesseldorf', 'title' => 'Hofgarten Düsseldorf', 'query' => 'Hofgarten Düsseldorf', 'alt' => 'Der Hofgarten in Düsseldorf', 'count' => 1],
            ['type' => 'tip', 'slug' => 'schloss-benrath', 'title' => 'Schloss Benrath', 'query' => 'Schloss Benrath', 'alt' => 'Schloss Benrath in Düsseldorf', 'count' => 1],
            ['type' => 'tip', 'slug' => 'rheinuferpromenade', 'title' => 'Rheinuferpromenade', 'query' => 'Rheinuferpromenade Düsseldorf', 'alt' => 'Die Rheinuferpromenade in Düsseldorf', 'count' => 1],
            ['type' => 'tip', 'slug' => 'st-lambertus-basilika', 'title' => 'St. Lambertus Basilika', 'query' => 'St Lambertus Kirche Düsseldorf Altstadt Turm', 'alt' => 'Die Basilika St. Lambertus in Düsseldorf', 'count' => 1],
            ['type' => 'tip', 'slug' => 'japanisches-viertel-duesseldorf', 'title' => 'Japanisches Viertel Düsseldorf', 'query' => 'Immermannstraße Düsseldorf', 'alt' => 'Das Japanische Viertel in Düsseldorf', 'count' => 1],
            ['type' => 'tip', 'slug' => 'aquazoo-loebbecke-museum', 'title' => 'Aquazoo Löbbecke Museum', 'query' => 'Aquazoo Düsseldorf', 'alt' => 'Der Aquazoo in Düsseldorf', 'count' => 1],
            ['type' => 'tip', 'slug' => 'kaiserswerth', 'title' => 'Kaiserswerth', 'query' => 'Kaiserswerth Düsseldorf', 'alt' => 'Der Stadtteil Kaiserswerth in Düsseldorf', 'count' => 1],
            ['type' => 'tip', 'slug' => 'filmmuseum-duesseldorf', 'title' => 'Filmmuseum Düsseldorf', 'query' => 'Filmmuseum Düsseldorf', 'alt' => 'Das Filmmuseum in Düsseldorf', 'count' => 1],
            ['type' => 'tip', 'slug' => 'nrw-forum-duesseldorf', 'title' => 'NRW-Forum Düsseldorf', 'query' => 'NRW Forum Düsseldorf Ehrenhof', 'alt' => 'Das NRW-Forum in Düsseldorf', 'count' => 1],
            ['type' => 'tip', 'slug' => 'nordpark-mit-japanischem-garten', 'title' => 'Nordpark mit Japanischem Garten', 'query' => 'Nordpark Düsseldorf Japanischer Garten', 'alt' => 'Der Japanische Garten im Düsseldorfer Nordpark', 'count' => 1],
            ['type' => 'tip', 'slug' => 'volksgarten-duesseldorf', 'title' => 'Volksgarten Düsseldorf', 'query' => 'Volksgarten Düsseldorf', 'alt' => 'Der Volksgarten in Düsseldorf', 'count' => 1],
            ['type' => 'tip', 'slug' => 'grafenberger-wald', 'title' => 'Grafenberger Wald', 'query' => 'Grafenberger Wald Düsseldorf', 'alt' => 'Der Grafenberger Wald in Düsseldorf', 'count' => 1],
            ['type' => 'tip', 'slug' => 'tonhalle-duesseldorf', 'title' => 'Tonhalle Düsseldorf', 'query' => 'Tonhalle Düsseldorf Ehrenhof', 'alt' => 'Die Tonhalle in Düsseldorf', 'count' => 1],

            // Lissabon
            ['type' => 'region', 'slug' => 'lissabon', 'title' => 'Lissabon', 'query' => 'Lisboa cityscape', 'alt' => 'Blick über die Dächer von Lissabon', 'count' => 2],
            ['type' => 'tip', 'slug' => 'torre-de-belem', 'title' => 'Torre de Belém', 'query' => 'Torre de Belém', 'alt' => 'Der Torre de Belém in Lissabon', 'count' => 3],
            ['type' => 'tip', 'slug' => 'mosteiro-dos-jeronimos', 'title' => 'Mosteiro dos Jerónimos', 'query' => 'Mosteiro dos Jerónimos', 'alt' => 'Das Kloster Jerónimos in Lissabon', 'count' => 3],
            ['type' => 'tip', 'slug' => 'praca-do-comercio', 'title' => 'Praça do Comércio', 'query' => 'Praça do Comércio Lisboa', 'alt' => 'Die Praça do Comércio in Lissabon', 'count' => 1],
            ['type' => 'tip', 'slug' => 'alfama', 'title' => 'Alfama', 'query' => 'Alfama Lisboa', 'alt' => 'Das Alfama-Viertel in Lissabon', 'count' => 3],
            ['type' => 'tip', 'slug' => 'castelo-de-sao-jorge', 'title' => 'Castelo de São Jorge', 'query' => 'Castelo de São Jorge', 'alt' => 'Das Castelo de São Jorge in Lissabon', 'count' => 1],
            ['type' => 'tip', 'slug' => 'elevador-de-santa-justa', 'title' => 'Elevador de Santa Justa', 'query' => 'Elevador de Santa Justa', 'alt' => 'Der Elevador de Santa Justa in Lissabon', 'count' => 1],
            ['type' => 'tip', 'slug' => 'bairro-alto', 'title' => 'Bairro Alto', 'query' => 'Bairro Alto Lisboa', 'alt' => 'Das Bairro Alto in Lissabon', 'count' => 1],
            ['type' => 'tip', 'slug' => 'padrao-dos-descobrimentos', 'title' => 'Padrão dos Descobrimentos', 'query' => 'Padrão dos Descobrimentos', 'alt' => 'Das Padrão dos Descobrimentos in Lissabon', 'count' => 1],
            ['type' => 'tip', 'slug' => 'oceanario-de-lisboa', 'title' => 'Oceanário de Lisboa', 'query' => 'Oceanário de Lisboa', 'alt' => 'Das Oceanário de Lisboa', 'count' => 1],
            ['type' => 'tip', 'slug' => 'lx-factory', 'title' => 'LX Factory', 'query' => 'LX Factory Lisboa', 'alt' => 'Die LX Factory in Lissabon', 'count' => 1],
            ['type' => 'tip', 'slug' => 'miradouro-da-senhora-do-monte', 'title' => 'Miradouro da Senhora do Monte', 'query' => 'Miradouro da Senhora do Monte', 'alt' => 'Der Miradouro da Senhora do Monte', 'count' => 1],
            ['type' => 'tip', 'slug' => 'electrico-28', 'title' => 'Eléctrico 28', 'query' => 'Eléctrico 28 Lisboa tram', 'alt' => 'Die Straßenbahnlinie 28 in Lissabon', 'count' => 1],
            ['type' => 'tip', 'slug' => 'se-de-lisboa', 'title' => 'Sé de Lisboa', 'query' => 'Sé de Lisboa', 'alt' => 'Die Kathedrale Sé de Lisboa', 'count' => 1],
            ['type' => 'tip', 'slug' => 'praca-do-rossio', 'title' => 'Praça do Rossio', 'query' => 'Rossio Lisboa', 'alt' => 'Die Praça do Rossio in Lissabon', 'count' => 1],
            ['type' => 'tip', 'slug' => 'ponte-25-de-abril', 'title' => 'Ponte 25 de Abril', 'query' => 'Ponte 25 de Abril', 'alt' => 'Die Ponte 25 de Abril in Lissabon', 'count' => 1],
            ['type' => 'tip', 'slug' => 'parque-das-nacoes', 'title' => 'Parque das Nações', 'query' => 'Parque das Nações Lisboa', 'alt' => 'Der Parque das Nações in Lissabon', 'count' => 1],
            ['type' => 'tip', 'slug' => 'miradouro-de-santa-luzia', 'title' => 'Miradouro de Santa Luzia', 'query' => 'Miradouro de Santa Luzia', 'alt' => 'Der Miradouro de Santa Luzia', 'count' => 1],
            ['type' => 'tip', 'slug' => 'palacio-da-pena-in-sintra', 'title' => 'Palácio da Pena in Sintra', 'query' => 'Palácio da Pena Sintra', 'alt' => 'Der Palácio da Pena in Sintra', 'count' => 3],
            ['type' => 'tip', 'slug' => 'cristo-rei', 'title' => 'Cristo Rei', 'query' => 'Cristo Rei statue Almada', 'alt' => 'Die Cristo-Rei-Statue in Almada', 'count' => 1],
            ['type' => 'tip', 'slug' => 'jardim-da-estrela', 'title' => 'Jardim da Estrela', 'query' => 'Jardim da Estrela Lisboa', 'alt' => 'Der Jardim da Estrela in Lissabon', 'count' => 1],

            // Faro
            ['type' => 'region', 'slug' => 'faro', 'title' => 'Faro', 'query' => 'Faro Algarve', 'alt' => 'Die Altstadt von Faro', 'count' => 2],
            ['type' => 'tip', 'slug' => 'cidade-velha-de-faro', 'title' => 'Cidade Velha de Faro', 'query' => 'Faro Cidade Velha', 'alt' => 'Die Cidade Velha von Faro', 'count' => 1],
            ['type' => 'tip', 'slug' => 'se-catedral-de-faro', 'title' => 'Sé Catedral de Faro', 'query' => 'Sé de Faro', 'alt' => 'Die Kathedrale von Faro', 'count' => 1],
            ['type' => 'tip', 'slug' => 'arco-da-vila', 'title' => 'Arco da Vila', 'query' => 'Arco da Vila Faro', 'alt' => 'Der Arco da Vila in Faro', 'count' => 1],
            ['type' => 'tip', 'slug' => 'naturpark-ria-formosa', 'title' => 'Naturpark Ria Formosa', 'query' => 'Ria Formosa', 'alt' => 'Der Naturpark Ria Formosa', 'count' => 3],
            ['type' => 'tip', 'slug' => 'praia-de-faro', 'title' => 'Praia de Faro', 'query' => 'Praia de Faro', 'alt' => 'Die Praia de Faro', 'count' => 1],
            ['type' => 'tip', 'slug' => 'ilha-deserta', 'title' => 'Ilha Deserta', 'query' => 'Ilha Deserta Faro Barreta', 'alt' => 'Die Ilha Deserta vor Faro', 'count' => 1],
            ['type' => 'tip', 'slug' => 'ilha-da-culatra', 'title' => 'Ilha da Culatra', 'query' => 'Ilha da Culatra', 'alt' => 'Die Ilha da Culatra', 'count' => 1],
            ['type' => 'tip', 'slug' => 'museu-municipal-de-faro', 'title' => 'Museu Municipal de Faro', 'query' => 'Museu Municipal Faro', 'alt' => 'Das Museu Municipal de Faro', 'count' => 1],
            ['type' => 'tip', 'slug' => 'palacio-de-estoi', 'title' => 'Palácio de Estói', 'query' => 'Palácio de Estói', 'alt' => 'Der Palácio de Estói', 'count' => 1],
            ['type' => 'tip', 'slug' => 'ruinas-romanas-de-milreu', 'title' => 'Ruínas Romanas de Milreu', 'query' => 'Milreu ruins Estoi', 'alt' => 'Die römischen Ruinen von Milreu', 'count' => 1],
            ['type' => 'tip', 'slug' => 'olhao', 'title' => 'Olhão', 'query' => 'Olhão Algarve', 'alt' => 'Die Stadt Olhão', 'count' => 1],
            ['type' => 'tip', 'slug' => 'tavira', 'title' => 'Tavira', 'query' => 'Tavira Algarve', 'alt' => 'Die Altstadt von Tavira', 'count' => 1],
            ['type' => 'tip', 'slug' => 'ilha-de-tavira', 'title' => 'Ilha de Tavira', 'query' => 'Ilha de Tavira', 'alt' => 'Die Ilha de Tavira', 'count' => 1],
            ['type' => 'tip', 'slug' => 'praia-do-barril', 'title' => 'Praia do Barril', 'query' => 'Praia do Barril anchors', 'alt' => 'Die Praia do Barril mit Ankerfriedhof', 'count' => 1],
            ['type' => 'tip', 'slug' => 'loule', 'title' => 'Loulé', 'query' => 'Loulé Algarve market', 'alt' => 'Die Marktstadt Loulé', 'count' => 1],
            ['type' => 'tip', 'slug' => 'farol-da-ilha-da-culatra', 'title' => 'Farol da Ilha da Culatra', 'query' => 'Farol Ilha da Culatra', 'alt' => 'Der Leuchtturm der Ilha da Culatra', 'count' => 1],
            ['type' => 'tip', 'slug' => 'fuseta', 'title' => 'Fuseta', 'query' => 'Fuseta Algarve', 'alt' => 'Das Fischerdorf Fuseta', 'count' => 1],
            ['type' => 'tip', 'slug' => 'capela-dos-ossos-igreja-do-carmo', 'title' => 'Capela dos Ossos (Igreja do Carmo)', 'query' => 'Capela dos Ossos Faro', 'alt' => 'Die Capela dos Ossos in Faro', 'count' => 1],
            ['type' => 'tip', 'slug' => 'jardim-manuel-bivar', 'title' => 'Jardim Manuel Bívar', 'query' => 'Jardim Manuel Bívar Faro', 'alt' => 'Der Jardim Manuel Bívar in Faro', 'count' => 1],
            ['type' => 'tip', 'slug' => 'cacela-velha', 'title' => 'Cacela Velha', 'query' => 'Cacela Velha', 'alt' => 'Das Dorf Cacela Velha', 'count' => 1],

            // Mauritius
            ['type' => 'region', 'slug' => 'mauritius', 'title' => 'Mauritius', 'query' => 'Mauritius island lagoon', 'alt' => 'Türkise Lagune auf Mauritius', 'count' => 2],
            ['type' => 'tip', 'slug' => 'le-morne-brabant', 'title' => 'Le Morne Brabant', 'query' => 'Le Morne Brabant', 'alt' => 'Der Le Morne Brabant auf Mauritius', 'count' => 3],
            ['type' => 'tip', 'slug' => 'chamarel-seven-coloured-earths', 'title' => 'Chamarel Seven Coloured Earths', 'query' => 'Chamarel Seven Coloured Earth', 'alt' => 'Die Seven Coloured Earths bei Chamarel', 'count' => 3],
            ['type' => 'tip', 'slug' => 'black-river-gorges-national-park', 'title' => 'Black River Gorges National Park', 'query' => 'Black River Gorges Mauritius', 'alt' => 'Der Black River Gorges National Park', 'count' => 1],
            ['type' => 'tip', 'slug' => 'ile-aux-cerfs', 'title' => 'Île aux Cerfs', 'query' => 'Ile aux Cerfs Mauritius', 'alt' => 'Die Île aux Cerfs auf Mauritius', 'count' => 3],
            ['type' => 'tip', 'slug' => 'port-louis-caudan-waterfront', 'title' => 'Port Louis Caudan Waterfront', 'query' => 'Caudan Waterfront Port Louis', 'alt' => 'Die Caudan Waterfront in Port Louis', 'count' => 1],
            ['type' => 'tip', 'slug' => 'grand-baie', 'title' => 'Grand Baie', 'query' => 'Grand Baie Mauritius', 'alt' => 'Die Bucht von Grand Baie', 'count' => 1],
            ['type' => 'tip', 'slug' => 'trou-aux-biches', 'title' => 'Trou aux Biches', 'query' => 'Trou aux Biches', 'alt' => 'Der Strand Trou aux Biches', 'count' => 1],
            ['type' => 'tip', 'slug' => 'sir-seewoosagur-ramgoolam-botanical-garden', 'title' => 'Sir Seewoosagur Ramgoolam Botanical Garden', 'query' => 'Pamplemousses Botanical Garden', 'alt' => 'Der botanische Garten von Pamplemousses', 'count' => 1],
            ['type' => 'tip', 'slug' => 'chamarel-wasserfall', 'title' => 'Chamarel Wasserfall', 'query' => 'Chamarel Waterfall', 'alt' => 'Der Chamarel-Wasserfall', 'count' => 1],
            ['type' => 'tip', 'slug' => 'casela-nature-parks', 'title' => 'Casela Nature Parks', 'query' => 'Casela Mauritius', 'alt' => 'Die Casela Nature Parks', 'count' => 1],
            ['type' => 'tip', 'slug' => 'belle-mare-beach', 'title' => 'Belle Mare Beach', 'query' => 'Belle Mare Mauritius beach', 'alt' => 'Der Strand von Belle Mare', 'count' => 1],
            ['type' => 'tip', 'slug' => 'grand-bassin-ganga-talao', 'title' => 'Grand Bassin (Ganga Talao)', 'query' => 'Grand Bassin Ganga Talao Mauritius', 'alt' => 'Der Kratersee Grand Bassin', 'count' => 1],
            ['type' => 'tip', 'slug' => 'flic-en-flac', 'title' => 'Flic en Flac', 'query' => 'Flic en Flac Mauritius', 'alt' => 'Der Strand von Flic en Flac', 'count' => 1],
            ['type' => 'tip', 'slug' => 'ile-aux-aigrettes', 'title' => 'Île aux Aigrettes', 'query' => 'Ile aux Aigrettes Mauritius', 'alt' => 'Die Île aux Aigrettes', 'count' => 1],
            ['type' => 'tip', 'slug' => 'rochester-falls', 'title' => 'Rochester Falls', 'query' => 'Rochester Falls Mauritius', 'alt' => 'Die Rochester Falls', 'count' => 1],
            ['type' => 'tip', 'slug' => 'blue-bay-marine-park', 'title' => 'Blue Bay Marine Park', 'query' => 'Blue Bay Mauritius', 'alt' => 'Der Blue Bay Marine Park', 'count' => 1],
            ['type' => 'tip', 'slug' => 'aapravasi-ghat', 'title' => 'Aapravasi Ghat', 'query' => 'Aapravasi Ghat', 'alt' => 'Der Aapravasi Ghat in Port Louis', 'count' => 1],
            ['type' => 'tip', 'slug' => 'la-vanille-nature-park', 'title' => 'La Vanille Nature Park', 'query' => 'La Vanille Nature Park Mauritius', 'alt' => 'Der La Vanille Nature Park', 'count' => 1],
            ['type' => 'tip', 'slug' => 'tamarin-bay', 'title' => 'Tamarin Bay', 'query' => 'Tamarin Bay Mauritius', 'alt' => 'Die Bucht von Tamarin', 'count' => 1],
            ['type' => 'tip', 'slug' => 'gris-gris-und-roche-qui-pleure', 'title' => 'Gris Gris und Roche qui Pleure', 'query' => 'Gris Gris Souillac Mauritius', 'alt' => 'Die Steilküste bei Gris Gris', 'count' => 1],
        ];
    }
}
