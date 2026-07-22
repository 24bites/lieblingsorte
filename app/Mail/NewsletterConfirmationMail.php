<?php

namespace App\Mail;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewsletterConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public NewsletterSubscriber $subscriber)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Bitte bestätige deine Newsletter-Anmeldung',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.newsletter.confirm',
            with: [
                'confirmUrl' => route('newsletter.confirm', $this->subscriber->confirmation_token),
            ],
        );
    }
}
