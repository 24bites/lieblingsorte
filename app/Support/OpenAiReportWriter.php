<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Writes full travel-guide reports via OpenAI's chat completions endpoint,
 * following the shared style guide in ReportWriterPrompt. Content is
 * returned as HTML (h2/h3/p/ul/table) - the admin edits it in a WYSIWYG
 * editor and the public page renders it directly.
 */
class OpenAiReportWriter
{
    public static function isConfigured(): bool
    {
        return OpenAiConfig::isConfigured();
    }

    /**
     * Regenerates just the body content of an existing report (title/SEO
     * fields stay untouched) - used by the "Text neu generieren" action.
     */
    public static function write(string $topic, ?string $context = null): string
    {
        $apiKey = OpenAiConfig::apiKey();

        if (blank($apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY ist nicht konfiguriert.');
        }

        $contextLine = filled($context) ? "Zusätzlicher Kontext: {$context}\n" : '';
        $model = config('services.openai.text_model', 'gpt-4o-mini');

        $response = Http::withToken($apiKey)
            ->timeout(180)
            ->retry(2, 1000)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'temperature' => 0.7,
                'max_tokens' => 8000,
                'messages' => [
                    ['role' => 'system', 'content' => ReportWriterPrompt::systemPrompt()],
                    [
                        'role' => 'user',
                        'content' => "Schreibe den vollständigen Reiseführer-Artikel zum Thema \"{$topic}\".\n{$contextLine}"
                            .'Antworte ausschließlich mit dem fertigen HTML-Inhalt des Artikels (das "content"-Feld '
                            .'aus dem Schema), ohne Titel, ohne JSON, ohne Erklärung.',
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'OpenAI-Anfrage fehlgeschlagen: '.$response->json('error.message', (string) $response->status())
            );
        }

        $content = trim((string) $response->json('choices.0.message.content'));
        $content = ReportWriterPrompt::stripCodeFence($content);

        if ($content === '') {
            throw new RuntimeException('OpenAI-Antwort enthielt keinen Text.');
        }

        AiUsageTracker::recordChatUsage('report_write', $model, $response->json('usage', []));

        return $content;
    }

    /**
     * Drafts a full report - title, teaser, HTML body, SEO/OG fields, FAQ,
     * plus image and internal-link suggestions for the editor to act on -
     * from just a topic, for the "Neuer Reisebericht erstellen" page. Author
     * identity is deliberately left out of the draft (see TravelReportController):
     * that stays a human choice, not something to fabricate.
     */
    public static function draft(string $topic, ?string $context = null): array
    {
        $apiKey = OpenAiConfig::apiKey();

        if (blank($apiKey)) {
            throw new RuntimeException('OPENAI_API_KEY ist nicht konfiguriert.');
        }

        $contextLine = filled($context) ? "Zusätzlicher Kontext: {$context}\n" : '';
        $schema = ReportWriterPrompt::jsonSchema();
        $model = config('services.openai.text_model', 'gpt-4o-mini');

        $response = Http::withToken($apiKey)
            ->timeout(180)
            ->retry(2, 1000)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'response_format' => ['type' => 'json_object'],
                'temperature' => 0.7,
                'max_tokens' => 8000,
                'messages' => [
                    ['role' => 'system', 'content' => ReportWriterPrompt::systemPrompt()],
                    [
                        'role' => 'user',
                        'content' => "Entwirf den vollständigen Reiseführer-Artikel zum Thema \"{$topic}\".\n{$contextLine}"
                            ."Antworte ausschließlich mit einem JSON-Objekt exakt nach folgendem Schema "
                            ."(Werte sind Beispiele für den Typ, keine Vorgabe für den Inhalt):\n{$schema}",
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'OpenAI-Anfrage fehlgeschlagen: '.$response->json('error.message', (string) $response->status())
            );
        }

        $data = json_decode((string) $response->json('choices.0.message.content'), true);

        if (! is_array($data) || empty($data['title']) || empty($data['content'])) {
            throw new RuntimeException('OpenAI-Antwort konnte nicht als gültiger Berichts-Entwurf gelesen werden.');
        }

        AiUsageTracker::recordChatUsage('report_draft', $model, $response->json('usage', []));

        return $data;
    }
}
