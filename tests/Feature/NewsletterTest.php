<?php

namespace Tests\Feature;

use App\Mail\NewsletterConfirmationMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'email' => 'gast@example.com',
            'consent' => '1',
        ], $overrides);
    }

    public function test_signup_requires_werbeerlaubnis_consent(): void
    {
        Mail::fake();

        $response = $this->post(route('newsletter.store'), $this->payload(['consent' => null]));

        $response->assertSessionHasErrors('consent');
        $this->assertDatabaseMissing('newsletter_subscribers', ['email' => 'gast@example.com']);
        Mail::assertNothingSent();
    }

    public function test_filled_honeypot_field_is_silently_ignored(): void
    {
        Mail::fake();

        $response = $this->post(route('newsletter.store'), $this->payload(['homepage' => 'https://spam.example']));

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('status');
        $this->assertDatabaseMissing('newsletter_subscribers', ['email' => 'gast@example.com']);
        Mail::assertNothingSent();
    }

    public function test_valid_signup_creates_pending_subscriber_and_sends_confirmation_mail(): void
    {
        Mail::fake();

        $response = $this->post(route('newsletter.store'), $this->payload());

        $response->assertSessionHasNoErrors();
        $subscriber = NewsletterSubscriber::where('email', 'gast@example.com')->firstOrFail();
        $this->assertNotNull($subscriber->confirmation_token);
        $this->assertNotNull($subscriber->unsubscribe_token);
        $this->assertNull($subscriber->confirmed_at);
        $this->assertSame('127.0.0.1', $subscriber->consent_ip);
        Mail::assertQueued(NewsletterConfirmationMail::class, fn ($mail) => $mail->subscriber->is($subscriber));
    }

    public function test_confirm_link_activates_the_subscriber_and_redirects_to_thanks_page(): void
    {
        Mail::fake();
        $this->post(route('newsletter.store'), $this->payload());
        $subscriber = NewsletterSubscriber::where('email', 'gast@example.com')->firstOrFail();

        $response = $this->get(route('newsletter.confirm', $subscriber->confirmation_token));

        $response->assertRedirect(route('newsletter.thanks'));
        $subscriber->refresh();
        $this->assertNotNull($subscriber->confirmed_at);
        $this->assertNull($subscriber->confirmation_token);
    }

    public function test_confirm_with_unknown_token_is_404(): void
    {
        $this->get(route('newsletter.confirm', 'does-not-exist'))->assertNotFound();
    }

    public function test_signup_with_already_confirmed_email_shows_friendly_message_and_sends_no_mail(): void
    {
        NewsletterSubscriber::create([
            'email' => 'gast@example.com', 'subscribed_at' => now(), 'confirmed_at' => now(),
            'confirmation_token' => null, 'unsubscribe_token' => 'existing-token',
        ]);
        Mail::fake();

        $response = $this->post(route('newsletter.store'), $this->payload());

        $response->assertSessionHas('status', 'Du bist bereits für den Newsletter angemeldet.');
        $this->assertCount(1, NewsletterSubscriber::where('email', 'gast@example.com')->get());
        Mail::assertNothingSent();
    }

    public function test_signup_with_still_pending_email_resends_confirmation_mail(): void
    {
        $subscriber = NewsletterSubscriber::create([
            'email' => 'gast@example.com', 'subscribed_at' => now(),
            'confirmation_token' => 'old-token', 'unsubscribe_token' => 'existing-token',
        ]);
        Mail::fake();

        $this->post(route('newsletter.store'), $this->payload());

        $subscriber->refresh();
        $this->assertNotSame('old-token', $subscriber->confirmation_token);
        Mail::assertQueued(NewsletterConfirmationMail::class);
    }

    public function test_unsubscribe_show_does_not_change_state(): void
    {
        $subscriber = NewsletterSubscriber::create([
            'email' => 'gast@example.com', 'subscribed_at' => now(), 'confirmed_at' => now(),
            'unsubscribe_token' => 'unsub-token',
        ]);

        $this->get(route('newsletter.unsubscribe.show', 'unsub-token'))->assertOk();

        $this->assertNull($subscriber->fresh()->unsubscribed_at);
    }

    public function test_unsubscribe_destroy_marks_subscriber_as_unsubscribed(): void
    {
        $subscriber = NewsletterSubscriber::create([
            'email' => 'gast@example.com', 'subscribed_at' => now(), 'confirmed_at' => now(),
            'unsubscribe_token' => 'unsub-token',
        ]);

        $this->post(route('newsletter.unsubscribe.destroy', 'unsub-token'))->assertOk();

        $this->assertNotNull($subscriber->fresh()->unsubscribed_at);
    }

    public function test_unsubscribe_with_unknown_token_is_404(): void
    {
        $this->get(route('newsletter.unsubscribe.show', 'does-not-exist'))->assertNotFound();
        $this->post(route('newsletter.unsubscribe.destroy', 'does-not-exist'))->assertNotFound();
    }

    public function test_resubscribing_after_unsubscribe_reactivates_and_resends_confirmation(): void
    {
        $subscriber = NewsletterSubscriber::create([
            'email' => 'gast@example.com', 'subscribed_at' => now()->subMonth(), 'confirmed_at' => now()->subMonth(),
            'unsubscribe_token' => 'unsub-token', 'unsubscribed_at' => now()->subDay(),
        ]);
        Mail::fake();

        $this->post(route('newsletter.store'), $this->payload());

        $subscriber->refresh();
        $this->assertNull($subscriber->unsubscribed_at);
        $this->assertNotNull($subscriber->confirmation_token);
        Mail::assertQueued(NewsletterConfirmationMail::class);
    }

    public function test_signup_route_is_rate_limited(): void
    {
        Mail::fake();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('newsletter.store'), $this->payload(['email' => "gast{$i}@example.com"]));
        }

        $response = $this->post(route('newsletter.store'), $this->payload(['email' => 'gast5@example.com']));

        $response->assertStatus(429);
    }

    public function test_signup_page_and_footer_form_render(): void
    {
        $this->get(route('newsletter.show'))->assertOk()->assertSee('Ich möchte den Newsletter');
        $this->get(route('home'))->assertOk()->assertSee(route('newsletter.store'), false);
    }
}
