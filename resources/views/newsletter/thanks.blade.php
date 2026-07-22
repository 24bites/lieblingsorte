@extends('layouts.app')

@php
    $seoTitle = 'Danke | '.\App\Models\Setting::get('site_name', 'Lieblingsorte');
    $seoDescription = 'Deine Newsletter-Anmeldung wurde bestätigt.';
@endphp

@section('content')
    <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-10">
            <div class="w-14 h-14 rounded-full bg-forest-100 flex items-center justify-center mx-auto mb-5">
                <svg class="w-7 h-7 text-forest-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6 9 17l-5-5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <h1 class="font-display text-2xl sm:text-3xl font-semibold text-forest-900 mb-3">Danke!</h1>
            <p class="text-forest-600 mb-6">
                Deine Anmeldung ist bestätigt. Du erhältst ab sofort unsere besten Reisetipps per E-Mail.
            </p>
            <a href="{{ route('home') }}" class="inline-block rounded-full bg-forest-700 hover:bg-forest-800 text-white font-semibold px-6 py-3 text-sm transition">
                Zurück zur Startseite
            </a>
        </div>
    </div>
@endsection
