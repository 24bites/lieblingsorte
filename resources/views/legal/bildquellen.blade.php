@extends('layouts.app')

@php
    $seoTitle = 'Bildquellen | '.\App\Models\Setting::get('site_name', 'Lieblingsorte');
    $seoDescription = 'Quellennachweise aller verwendeten Fotografien von Wikimedia Commons.';
@endphp

@section('content')
    <x-breadcrumbs :items="[
        ['label' => 'Startseite', 'url' => route('home')],
        ['label' => 'Bildquellen', 'url' => null],
    ]" />

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="font-display text-3xl font-semibold text-forest-900 mb-4">Bildquellen</h1>
        <p class="text-sm text-forest-600 mb-8 max-w-2xl">
            Diese Seite dokumentiert die Quellen aller von Wikimedia Commons bezogenen Fotografien auf
            {{ \App\Models\Setting::get('site_name', 'Lieblingsorte') }}. Alle aufgeführten Bilder stehen unter einer
            freien Lizenz (CC0, Public Domain, CC&nbsp;BY oder CC&nbsp;BY-SA) und werden hier unter Einhaltung der
            jeweiligen Lizenzbedingungen &ndash; insbesondere der Namensnennung bei CC&nbsp;BY/CC&nbsp;BY-SA &ndash;
            mit Fotograf/in, Lizenz und Link zur Originalquelle aufgeführt.
        </p>

        @if ($total === 0)
            <p class="text-sm text-forest-500 bg-sand-100 rounded-xl px-5 py-4">
                Aktuell sind keine Bildquellen hinterlegt.
            </p>
        @else
            <div class="space-y-10">
                @foreach ($grouped as $usedFor => $items)
                    <section>
                        <h2 class="font-semibold text-forest-900 text-lg mb-3">{{ $usedFor }}</h2>
                        <div class="space-y-3">
                            @foreach ($items as $credit)
                                <div class="flex gap-4 bg-white ring-1 ring-sand-200 rounded-xl p-4">
                                    @if (\Illuminate\Support\Facades\Storage::disk('public')->exists($credit['file']))
                                        <img src="{{ asset('storage/'.$credit['file']) }}" alt="" class="w-24 h-24 object-cover rounded-lg flex-shrink-0">
                                    @endif
                                    <div class="text-sm text-forest-700 space-y-1">
                                        <p><span class="text-forest-500">Wikimedia-Commons-Titel:</span> {{ $credit['source_title'] }}</p>
                                        <p><span class="text-forest-500">Autor/in:</span> {{ $credit['author'] }}</p>
                                        <p><span class="text-forest-500">Lizenz:</span> {{ $credit['license'] }}</p>
                                        <p>
                                            <a href="{{ $credit['source_url'] }}" target="_blank" rel="noopener noreferrer nofollow" class="text-forest-700 underline">
                                                Zur Originalquelle
                                            </a>
                                        </p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endforeach
            </div>
        @endif
    </div>
@endsection
