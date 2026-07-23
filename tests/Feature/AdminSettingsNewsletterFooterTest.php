<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSettingsNewsletterFooterTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::create([
            'name' => 'Administrator', 'email' => 'admin@lieblingsorte.test',
            'password' => Hash::make('admin123'), 'role' => 'admin',
        ]);
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'site_name' => 'Lieblingsorte',
            'site_claim' => 'Claim',
            'site_description' => 'Beschreibung',
            'contact_email' => 'hallo@lieblingsorte.test',
            'images_ai_replace_interval' => 10,
            'regions_auto_generate_interval' => 10,
            'regions_complete_content_interval' => 10,
        ], $overrides);
    }

    public function test_footer_shows_newsletter_form_by_default(): void
    {
        $this->get(route('home'))->assertSee(route('newsletter.store'), false);
    }

    public function test_admin_can_hide_the_newsletter_form_from_the_footer(): void
    {
        $response = $this->actingAs($this->admin())->put(route('admin.settings.update'), $this->validPayload([
            'newsletter_footer_visible' => null,
        ]));

        $response->assertRedirect(route('admin.settings.edit'));
        $this->assertSame('0', Setting::get('newsletter_footer_visible'));
        $this->get(route('home'))->assertDontSee(route('newsletter.store'), false);
    }

    public function test_admin_can_show_the_newsletter_form_again(): void
    {
        Setting::set('newsletter_footer_visible', '0');

        $response = $this->actingAs($this->admin())->put(route('admin.settings.update'), $this->validPayload([
            'newsletter_footer_visible' => '1',
        ]));

        $response->assertRedirect(route('admin.settings.edit'));
        $this->assertSame('1', Setting::get('newsletter_footer_visible'));
        $this->get(route('home'))->assertSee(route('newsletter.store'), false);
    }

    public function test_hiding_the_footer_form_does_not_affect_the_dedicated_newsletter_page(): void
    {
        Setting::set('newsletter_footer_visible', '0');

        $this->get(route('newsletter.show'))->assertOk()->assertSee(route('newsletter.store'), false);
    }
}
