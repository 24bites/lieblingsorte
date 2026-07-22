@extends('layouts.app')

@php
    $seoTitle = 'Newsletter abmelden | '.\App\Models\Setting::get('site_name', 'Lieblingsorte');
    $seoDescription = 'Newsletter-Abmeldung bestätigen.';
@endphp

@section('content')
    <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-10">
            <h1 class="font-display text-2xl sm:text-3xl font-semibold text-forest-900 mb-3">Newsletter abmelden</h1>
            <p class="text-forest-600 mb-6">
                Möchtest du <span class="font-medium text-forest-900">{{ $subscriber->email }}</span> wirklich
                vom Newsletter abmelden? Du erhältst danach keine weiteren Reisetipps mehr per E-Mail.
            </p>
            <form action="{{ route('newsletter.unsubscribe.destroy', $subscriber->unsubscribe_token) }}" method="POST" class="flex flex-col sm:flex-row gap-3 justify-center">
                @csrf
                <button type="submit" class="rounded-full bg-forest-700 hover:bg-forest-800 text-white font-semibold px-6 py-3 text-sm transition">
                    Ja, abmelden
                </button>
                <a href="{{ route('home') }}" class="rounded-full border border-sand-300 hover:bg-sand-100 text-forest-700 font-semibold px-6 py-3 text-sm transition">
                    Abbrechen
                </a>
            </form>
        </div>
    </div>
@endsection
