<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Writes full travel-guide reports via Anthropic's Messages API, following
 * the same shared style guide as OpenAiReportWriter (see ReportWriterPrompt)
 * - an admin picks a provider in Settings, not a different article style.
 * Claude has no dedicated JSON response mode like OpenAI, so draft() uses
 * the standard assistant-prefill trick ("{" as the start of Claude's turn)
 * to reliably get a bare JSON object back instead of prose-wrapped JSON.
 */
class ClaudeReportWriter
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';

    private const API_VERSION = '2023-06-01';

    public static function isConfigured(): bool
    {
        return AnthropicConfig::isConfigured();
    }

    public static function write(string $topic, ?string $context = null): string
    {
        $apiKey = AnthropicConfig::apiKey();

        if (blank($apiKey)) {
            throw new RuntimeException('ANTHROPIC_API_KEY ist nicht konfiguriert.');
        }

        $contextLine = filled($context) ? "Zusätzlicher Kontext: {$context}\n" : '';
        $model = config('services.anthropic.text_model', 'claude-sonnet-5');

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => self::API_VERSION,
        ])
            ->timeout(180)
            ->retry(2, 1000)
            ->post(self::API_URL, [
                'model' => $model,
                'max_tokens' => 8000,
                'temperature' => 0.7,
                'system' => ReportWriterPrompt::systemPrompt(),
                'messages' => [
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
                'Claude-Anfrage fehlgeschlagen: '.$response->json('error.message', (string) $response->status())
            );
        }

        $content = trim((string) $response->json('content.0.text'));
        $content = ReportWriterPrompt::stripCodeFence($content);

        if ($content === '') {
            throw new RuntimeException('Claude-Antwort enthielt keinen Text.');
        }

        self::recordUsage('report_write', $model, $response->json('usage', []));

        return $content;
    }

    public static function draft(string $topic, ?string $context = null): array
    {
        $apiKey = AnthropicConfig::apiKey();

        if (blank($apiKey)) {
            throw new RuntimeException('ANTHROPIC_API_KEY ist nicht konfiguriert.');
        }

        $contextLine = filled($context) ? "Zusätzlicher Kontext: {$context}\n" : '';
        $schema = ReportWriterPrompt::jsonSchema();
        $model = config('services.anthropic.text_model', 'claude-sonnet-5');

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => self::API_VERSION,
        ])
            ->timeout(180)
            ->retry(2, 1000)
            ->post(self::API_URL, [
                'model' => $model,
                'max_tokens' => 8000,
                'temperature' => 0.7,
                'system' => ReportWriterPrompt::systemPrompt(),
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => "Entwirf den vollständigen Reiseführer-Artikel zum Thema \"{$topic}\".\n{$contextLine}"
                            ."Antworte ausschließlich mit einem JSON-Objekt exakt nach folgendem Schema "
                            ."(Werte sind Beispiele für den Typ, keine Vorgabe für den Inhalt):\n{$schema}",
                    ],
                    // Prefilling the assistant's turn with "{" makes Claude continue
                    // straight into the JSON object instead of wrapping it in prose
                    // or a code fence - the leading brace is stitched back on below.
                    ['role' => 'assistant', 'content' => '{'],
                ],
            ]);

        if ($response->failed()) {
            throw new RuntimeException(
                'Claude-Anfrage fehlgeschlagen: '.$response->json('error.message', (string) $response->status())
            );
        }

        $raw = '{'.trim((string) $response->json('content.0.text'));
        $data = json_decode($raw, true);

        if (! is_array($data) || empty($data['title']) || empty($data['content'])) {
            throw new RuntimeException('Claude-Antwort konnte nicht als gültiger Berichts-Entwurf gelesen werden.');
        }

        self::recordUsage('report_draft', $model, $response->json('usage', []));

        return $data;
    }

    /**
     * Anthropic's usage fields (input_tokens/output_tokens) differ from
     * OpenAI's (prompt_tokens/completion_tokens) - mapped here so
     * AiUsageTracker's single recordChatUsage() signature covers both.
     */
    private static function recordUsage(string $feature, string $model, array $usage): void
    {
        $promptTokens = $usage['input_tokens'] ?? null;
        $completionTokens = $usage['output_tokens'] ?? null;

        AiUsageTracker::recordChatUsage($feature, $model, [
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => ($promptTokens !== null && $completionTokens !== null) ? $promptTokens + $completionTokens : null,
        ]);
    }
}
