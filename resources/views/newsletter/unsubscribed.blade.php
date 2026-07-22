@extends('layouts.app')

@php
    $seoTitle = 'Abgemeldet | '.\App\Models\Setting::get('site_name', 'Lieblingsorte');
    $seoDescription = 'Du wurdest vom Newsletter abgemeldet.';
@endphp

@section('content')
    <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-20 text-center">
        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-10">
            <h1 class="font-display text-2xl sm:text-3xl font-semibold text-forest-900 mb-3">Du wurdest abgemeldet</h1>
            <p class="text-forest-600 mb-6">
                Schade, dass du gehst! Du erhältst keine weiteren Newsletter-E-Mails mehr von uns.
                Du kannst dich jederzeit wieder <a href="{{ route('newsletter.show') }}" class="text-forest-800 underline hover:text-forest-900">anmelden</a>.
            </p>
            <a href="{{ route('home') }}" class="inline-block rounded-full bg-forest-700 hover:bg-forest-800 text-white font-semibold px-6 py-3 text-sm transition">
                Zurück zur Startseite
            </a>
        </div>
    </div>
@endsection
