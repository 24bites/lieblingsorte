<?php

namespace App\Http\Controllers;

use App\Http\Requests\NewsletterSubscribeRequest;
use App\Mail\NewsletterConfirmationMail;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NewsletterController extends Controller
{
    public function show()
    {
        return view('newsletter.show');
    }

    public function store(NewsletterSubscribeRequest $request)
    {
        // Honeypot: bots fill every field they can find, real visitors never
        // see this one. Pretend everything worked without touching the DB
        // or sending mail, so the bot gets no signal it was caught.
        if ($request->filled('homepage')) {
            return $this->pendingResponse();
        }

        $email = $request->validated('email');
        $subscriber = NewsletterSubscriber::where('email', $email)->first();

        if ($subscriber && $subscriber->confirmed_at && ! $subscriber->unsubscribed_at) {
            return back()->with('status', 'Du bist bereits für den Newsletter angemeldet.');
        }

        if ($subscriber) {
            $subscriber->update([
                'confirmation_token' => Str::random(40),
                'unsubscribe_token' => $subscriber->unsubscribe_token ?? Str::random(40),
                'subscribed_at' => now(),
                'unsubscribed_at' => null,
                'consent_ip' => $request->ip(),
            ]);
        } else {
            $subscriber = NewsletterSubscriber::create([
                'email' => $email,
                'confirmation_token' => Str::random(40),
                'unsubscribe_token' => Str::random(40),
                'subscribed_at' => now(),
                'consent_ip' => $request->ip(),
            ]);
        }

        Mail::to($subscriber->email)->send(new NewsletterConfirmationMail($subscriber));

        return $this->pendingResponse();
    }

    public function confirm(string $token)
    {
        $subscriber = NewsletterSubscriber::where('confirmation_token', $token)->firstOrFail();

        $subscriber->update([
            'confirmed_at' => now(),
            'confirmation_token' => null,
        ]);

        return redirect()->route('newsletter.thanks');
    }

    public function thanks()
    {
        return view('newsletter.thanks');
    }

    public function unsubscribeShow(string $token)
    {
        $subscriber = NewsletterSubscriber::where('unsubscribe_token', $token)->firstOrFail();

        return view('newsletter.unsubscribe-confirm', compact('subscriber'));
    }

    public function unsubscribeDestroy(string $token)
    {
        $subscriber = NewsletterSubscriber::where('unsubscribe_token', $token)->firstOrFail();

        $subscriber->update(['unsubscribed_at' => now()]);

        return view('newsletter.unsubscribed');
    }

    private function pendingResponse()
    {
        return back()->with('status', 'Fast geschafft! Bitte bestätige deine Anmeldung über den Link in der E-Mail, die wir dir gerade geschickt haben.');
    }
}
