<?php

namespace App\Support;

use App\Models\AiUsageLog;

/**
 * Records every OpenAI call's token usage (and a best-effort cost estimate)
 * so the admin dashboard can show a running overview. Purely observational -
 * never blocks or alters a call, and a failure here must never break the
 * feature that triggered it, so callers should treat this as fire-and-forget.
 *
 * Cost estimates use a small hardcoded per-model price table below. These
 * are approximations of OpenAI's published pricing at the time this was
 * written, not a live pricing feed - update PRICE_PER_MILLION_TOKENS (and the
 * image price) by hand if OpenAI changes its pricing.
 */
class AiUsageTracker
{
    /** USD per 1,000,000 tokens: [input, output]. */
    private const PRICE_PER_MILLION_TOKENS = [
        'gpt-4o-mini' => [0.15, 0.60],
        'gpt-4o' => [2.50, 10.00],
    ];

    /** USD per generated image at the default 1024x1024 size. */
    private const IMAGE_PRICE = [
        'gpt-image-1' => 0.04,
    ];

    public static function recordChatUsage(string $feature, string $model, array $usage): void
    {
        $promptTokens = $usage['prompt_tokens'] ?? null;
        $completionTokens = $usage['completion_tokens'] ?? null;
        $totalTokens = $usage['total_tokens'] ?? null;

        AiUsageLog::create([
            'feature' => $feature,
            'model' => $model,
            'prompt_tokens' => $promptTokens,
            'completion_tokens' => $completionTokens,
            'total_tokens' => $totalTokens,
            'estimated_cost_usd' => self::estimateChatCost($model, $promptTokens, $completionTokens),
        ]);
    }

    public static function recordImageUsage(string $feature, string $model): void
    {
        AiUsageLog::create([
            'feature' => $feature,
            'model' => $model,
            'estimated_cost_usd' => self::IMAGE_PRICE[$model] ?? null,
        ]);
    }

    private static function estimateChatCost(string $model, ?int $promptTokens, ?int $completionTokens): ?float
    {
        if (! isset(self::PRICE_PER_MILLION_TOKENS[$model]) || $promptTokens === null || $completionTokens === null) {
            return null;
        }

        [$inputPrice, $outputPrice] = self::PRICE_PER_MILLION_TOKENS[$model];

        return round(($promptTokens * $inputPrice + $completionTokens * $outputPrice) / 1_000_000, 4);
    }
}
