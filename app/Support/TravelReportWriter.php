<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Picks OpenAI or Claude for report generation based on the admin-configured
 * "report_ai_provider" setting, so TravelReportController stays provider-
 * agnostic. Both writers share the same prompt/schema (ReportWriterPrompt),
 * so switching providers changes cost/quality trade-offs, not article style.
 */
class TravelReportWriter
{
    public const PROVIDERS = ['openai', 'claude'];

    public static function provider(): string
    {
        $provider = Setting::get('report_ai_provider', 'openai');

        return in_array($provider, self::PROVIDERS, true) ? $provider : 'openai';
    }

    public static function isConfigured(): bool
    {
        return self::provider() === 'claude'
            ? ClaudeReportWriter::isConfigured()
            : OpenAiReportWriter::isConfigured();
    }

    public static function write(string $topic, ?string $context = null): string
    {
        return self::provider() === 'claude'
            ? ClaudeReportWriter::write($topic, $context)
            : OpenAiReportWriter::write($topic, $context);
    }

    public static function draft(string $topic, ?string $context = null): array
    {
        return self::provider() === 'claude'
            ? ClaudeReportWriter::draft($topic, $context)
            : OpenAiReportWriter::draft($topic, $context);
    }
}
