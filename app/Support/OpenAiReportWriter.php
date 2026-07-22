<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Writes a full travel report body via OpenAI's chat completions endpoint.
 * Unlike OpenAiRegionDrafter (structured facts as JSON), this is prose: a
 * personal, first-person account meant to read like a real travel blog
 * entry, not "AI writing". The system prompt spells out concrete anti-tells
 * (stock openers, bullet-heavy structure, repetitive sentence shapes,
 * meta-commentary) rather than just saying "sound human", since that
 * generic instruction alone tends not to change the output much.
 */
class OpenAiReportWriter
{
    public static function isConfigured(): bool
    {
        return OpenAiConfig::isConfigured();
    }

    public static function write(string $topic, ?string $context = null): string
    {
        $apiKey = OpenAiConfig::apiKey();

        if (blank($apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY ist nicht konfiguriert.');
        }

        $contextLine = filled($context) ? "Zusätzlicher Kontext: {$context}\n" : '';

        $response = Http::withToken($apiKey)
            ->timeout(120)
            ->retry(2, 1000)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => config('services.openai.text_model', 'gpt-4o-mini'),
                'temperature' => 1.0,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => <<<'PROMPT'
                        Du schreibst persönliche, sehr menschlich klingende Reiseberichte auf Deutsch für ein
                        Reiseportal. Du erzählst aus der Ich-Perspektive, mit konkreten, sinnlichen Details
                        (Geräusche, Gerüche, Wetter, kleine Beobachtungen, ein Gespräch, ein Missgeschick),
                        unterschiedlich langen Sätzen und einem natürlichen, leicht unperfekten Rhythmus - wie ein
                        guter persönlicher Reiseblog, nicht wie ein Prospekt oder eine Pressemitteilung.

                        Vermeide unbedingt typische Merkmale von KI-generiertem Text:
                        - keine Einleitungsfloskeln wie "Tauchen wir ein", "Begleite mich" oder "Stell dir vor"
                        - kein "Fazit:" oder abschließenden Absatz mit Ratschlägen/Zusammenfassung an die Leserschaft
                        - keine Aufzählungen mit Bulletpoints oder nummerierten Listen
                        - keine sich wiederholenden Satzanfänge (nicht durchgehend "Zunächst... Danach... Schließlich...")
                        - keine Häufung von Superlativen wie "atemberaubend", "unvergesslich", "einzigartig", "magisch"
                        - keine Gedankenstrich-Aufzählungen oder abgehackte Schlagwort-Sätze im Werbe-Stil
                        - keine Meta-Kommentare über das Schreiben selbst oder direkte Anrede der Leserschaft am Ende
                        - keine erfundenen exakten Preise, Öffnungszeiten oder Namen von Personen/Betrieben

                        Struktur: Gliedere den Text in 2 bis 4 Abschnitte. Jeder Abschnitt beginnt mit einer kurzen,
                        konkreten Zwischenüberschrift auf einer eigenen Zeile im Format "## Überschrift" (ohne
                        Anführungszeichen). Trenne alle Absätze durch eine Leerzeile. Antworte ausschließlich mit dem
                        Fließtext des Berichts (kein Titel, keine Meta-Erklärung), Länge ca. 500-800 Wörter.
                        PROMPT,
                    ],
                    [
                        'role' => 'user',
                        'content' => "Schreibe einen Reisebericht zum Thema \"{$topic}\".\n{$contextLine}",
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'OpenAI-Anfrage fehlgeschlagen: '.$response->json('error.message', (string) $response->status())
            );
        }

        $content = trim((string) $response->json('choices.0.message.content'));

        if ($content === '') {
            throw new RuntimeException('OpenAI-Antwort enthielt keinen Text.');
        }

        return $content;
    }
}
