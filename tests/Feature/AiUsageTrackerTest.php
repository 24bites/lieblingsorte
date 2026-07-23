<?php

namespace Tests\Feature;

use App\Models\AiUsageLog;
use App\Support\AiUsageTracker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiUsageTrackerTest extends TestCase
{
    use RefreshDatabase;

    public function test_records_chat_usage_with_estimated_cost_for_a_known_model(): void
    {
        AiUsageTracker::recordChatUsage('social_caption', 'gpt-4o-mini', [
            'prompt_tokens' => 1000, 'completion_tokens' => 1000, 'total_tokens' => 2000,
        ]);

        $log = AiUsageLog::sole();
        $this->assertSame('social_caption', $log->feature);
        $this->assertSame('gpt-4o-mini', $log->model);
        $this->assertSame(1000, $log->prompt_tokens);
        $this->assertSame(1000, $log->completion_tokens);
        $this->assertSame(2000, $log->total_tokens);
        // 1000 tokens @ $0.15/1M input + 1000 tokens @ $0.60/1M output = 0.00015 + 0.0006
        $this->assertEqualsWithDelta(0.00075, (float) $log->estimated_cost_usd, 0.0001);
    }

    public function test_records_chat_usage_with_null_cost_for_an_unknown_model(): void
    {
        AiUsageTracker::recordChatUsage('social_caption', 'some-future-model', [
            'prompt_tokens' => 100, 'completion_tokens' => 100, 'total_tokens' => 200,
        ]);

        $this->assertNull(AiUsageLog::sole()->estimated_cost_usd);
    }

    public function test_records_chat_usage_gracefully_when_usage_array_is_empty(): void
    {
        AiUsageTracker::recordChatUsage('region_draft', 'gpt-4o-mini', []);

        $log = AiUsageLog::sole();
        $this->assertNull($log->prompt_tokens);
        $this->assertNull($log->completion_tokens);
        $this->assertNull($log->total_tokens);
        $this->assertNull($log->estimated_cost_usd);
    }

    public function test_records_image_usage_with_a_flat_estimated_cost(): void
    {
        AiUsageTracker::recordImageUsage('image', 'gpt-image-1');

        $log = AiUsageLog::sole();
        $this->assertSame('image', $log->feature);
        $this->assertSame('gpt-image-1', $log->model);
        $this->assertNull($log->total_tokens);
        $this->assertSame(0.04, (float) $log->estimated_cost_usd);
    }
}
