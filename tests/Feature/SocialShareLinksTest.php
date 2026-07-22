<?php

namespace Tests\Feature;

use App\Support\SocialShareLinks;
use InvalidArgumentException;
use Tests\TestCase;

class SocialShareLinksTest extends TestCase
{
    public function test_builds_facebook_share_link(): void
    {
        $link = SocialShareLinks::build('facebook', 'https://example.test/toskana', 'Schöne Toskana');

        $this->assertStringStartsWith('https://www.facebook.com/sharer/sharer.php?', $link);
        $this->assertStringContainsString(urlencode('https://example.test/toskana'), $link);
    }

    public function test_builds_x_share_link_with_text_and_url(): void
    {
        $link = SocialShareLinks::build('x', 'https://example.test/toskana', 'Schöne Toskana');

        $this->assertStringStartsWith('https://twitter.com/intent/tweet?', $link);
        $this->assertStringContainsString('text=', $link);
        $this->assertStringContainsString('url=', $link);
    }

    public function test_builds_whatsapp_share_link_combining_caption_and_url(): void
    {
        $link = SocialShareLinks::build('whatsapp', 'https://example.test/toskana', 'Schöne Toskana');

        $this->assertStringStartsWith('https://wa.me/?text=', $link);
        $this->assertStringContainsString(urlencode('https://example.test/toskana'), $link);
    }

    public function test_builds_telegram_share_link(): void
    {
        $link = SocialShareLinks::build('telegram', 'https://example.test/toskana', 'Schöne Toskana');

        $this->assertStringStartsWith('https://t.me/share/url?', $link);
    }

    public function test_builds_pinterest_share_link_including_image_when_present(): void
    {
        $link = SocialShareLinks::build('pinterest', 'https://example.test/toskana', 'Schöne Toskana', 'https://example.test/bild.jpg');

        $this->assertStringStartsWith('https://pinterest.com/pin/create/button/?', $link);
        $this->assertStringContainsString(urlencode('https://example.test/bild.jpg'), $link);
    }

    public function test_pinterest_link_omits_media_param_when_no_image(): void
    {
        $link = SocialShareLinks::build('pinterest', 'https://example.test/toskana', 'Schöne Toskana');

        $this->assertStringNotContainsString('media=', $link);
    }

    public function test_unknown_platform_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);

        SocialShareLinks::build('instagram', 'https://example.test', 'Text');
    }
}
