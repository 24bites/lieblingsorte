<?php

namespace Tests\Feature;

use App\Support\TelegramConfig;
use App\Support\TelegramPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class TelegramPublisherTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_not_configured_by_default(): void
    {
        $this->assertFalse(TelegramConfig::isConfigured());
    }

    public function test_storing_token_and_chat_id_makes_it_configured(): void
    {
        TelegramConfig::store('123456:ABC-token', '@meinkanal');

        $this->assertTrue(TelegramConfig::isConfigured());
        $this->assertSame('@meinkanal', TelegramConfig::chatId());
        $this->assertSame('123456:ABC-token', TelegramConfig::botToken());
    }

    public function test_preview_masks_the_token(): void
    {
        TelegramConfig::store('123456:ABC-token', '@meinkanal');

        $this->assertSame('••••oken', TelegramConfig::preview());
    }

    public function test_clear_removes_stored_credentials(): void
    {
        TelegramConfig::store('123456:ABC-token', '@meinkanal');
        TelegramConfig::clear();

        $this->assertFalse(TelegramConfig::isConfigured());
        $this->assertNull(TelegramConfig::botToken());
    }

    public function test_send_uses_send_message_when_no_image(): void
    {
        TelegramConfig::store('123456:ABC-token', '@meinkanal');
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        TelegramPublisher::send('Schöne Toskana', 'https://example.test/toskana');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendMessage')
            && $request['chat_id'] === '@meinkanal'
            && str_contains($request['text'], 'https://example.test/toskana'));
    }

    public function test_send_uses_send_photo_when_image_present(): void
    {
        TelegramConfig::store('123456:ABC-token', '@meinkanal');
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true])]);

        TelegramPublisher::send('Schöne Toskana', 'https://example.test/toskana', 'https://example.test/bild.jpg');

        Http::assertSent(fn ($request) => str_contains($request->url(), '/sendPhoto')
            && $request['photo'] === 'https://example.test/bild.jpg');
    }

    public function test_send_throws_when_not_configured(): void
    {
        $this->expectException(RuntimeException::class);

        TelegramPublisher::send('Text', 'https://example.test');
    }

    public function test_send_throws_on_telegram_error_response(): void
    {
        TelegramConfig::store('123456:ABC-token', '@meinkanal');
        Http::fake(['api.telegram.org/*' => Http::response(['ok' => false, 'description' => 'chat not found'], 400)]);

        $this->expectException(RuntimeException::class);

        TelegramPublisher::send('Text', 'https://example.test');
    }
}
