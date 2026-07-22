<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NewsletterSubscriber extends Model
{
    protected $fillable = [
        'email',
        'confirmation_token',
        'subscribed_at',
        'confirmed_at',
        'unsubscribe_token',
        'unsubscribed_at',
        'consent_ip',
    ];

    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'confirmed_at' => 'datetime',
            'unsubscribed_at' => 'datetime',
        ];
    }
}
