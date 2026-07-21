<?php

namespace App\Http\Controllers;

use App\Http\Requests\NewsletterSubscribeRequest;
use App\Models\NewsletterSubscriber;

class NewsletterController extends Controller
{
    public function store(NewsletterSubscribeRequest $request)
    {
        NewsletterSubscriber::create([
            'email' => $request->validated('email'),
            'subscribed_at' => now(),
        ]);

        return back()->with('status', 'Danke! Du erhältst ab sofort unsere Reisetipps per E-Mail.');
    }
}
