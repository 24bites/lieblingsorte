@extends('layouts.app')

@php
    $seoTitle = \App\Models\Setting::get('site_name', 'Lieblingsorte').' – Die besten Reisetipps für Städte und Regionen';
    $seoDescription = \App\Models\Setting::get('site_description');
@endphp

@section('content')
    <style>
        @keyframes hero-kenburns { from { transform: scale(1); } to { transform: scale(1.08); } }
        .hero-slide-active { animation: hero-kenburns 5.5s ease-out forwards; }
    </style>

    <section class="relative">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-10 lg:pt-16 pb-16 grid lg:grid-cols-2 gap-10 items-center">
            <div>
                <h1 class="font-display text-4xl sm:text-5xl font-semibold text-forest-900 leading-tight">
                    Die besten Reisetipps für Städte und Regionen
                </h1>
                <p class="mt-5 text-lg text-forest-600 max-w-lg">
                    Handverlesene Lieblingsorte, echte Geheimtipps und besondere Erlebnisse.
                </p>

                <form action="{{ route('search') }}" method="GET" class="mt-8 flex flex-col sm:flex-row gap-3 max-w-lg">
                    <div class="relative flex-1">
                        <svg class="w-4 h-4 text-forest-400 absolute left-4 top-1/2 -translate-y-1/2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7" /><path d="m21 21-4.3-4.3" stroke-linecap="round" /></svg>
                        <input type="search" name="q" placeholder="Wohin geht deine nächste Reise?" class="w-full rounded-full border border-sand-300 bg-white py-3.5 pl-11 pr-4 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-forest-400">
                    </div>
                    <button type="submit" class="rounded-full bg-forest-700 hover:bg-forest-800 text-white font-semibold px-6 py-3.5 text-sm transition">Suchen</button>
                </form>

                <div class="mt-6 flex flex-wrap gap-3">
                    <a href="{{ route('regions.index') }}" class="rounded-full bg-forest-700 hover:bg-forest-800 text-white font-semibold px-6 py-3 text-sm transition">Regionen entdecken</a>
                    <a href="{{ route('search') }}?label=geheimtipp" class="rounded-full border border-forest-300 hover:bg-forest-50 text-forest-800 font-semibold px-6 py-3 text-sm transition">Geheimtipps ansehen</a>
                </div>
            </div>

            @php($heroImages = $regions->map->coverImage()->filter()->values())
            <div
                class="relative rounded-[2.5rem] overflow-hidden shadow-xl aspect-[4/3]"
                @if ($heroImages->count() > 1)
                    x-data="{
                        active: 0,
                        count: {{ $heroImages->count() }},
                        timer: null,
                        paused: false,
                        reduceMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
                        start() {
                            if (this.reduceMotion) return;
                            this.timer = setInterval(() => {
                                if (!this.paused) this.active = (this.active + 1) % this.count;
                            }, 4500);
                        },
                    }"
                    x-init="start(); document.addEventListener('visibilitychange', () => paused = document.hidden)"
                    @mouseenter="paused = true"
                    @mouseleave="paused = false"
                @endif
            >
                @forelse ($heroImages as $index => $image)
                    <img
                        src="{{ $image->url }}"
                        alt="{{ $image->alt_text }}"
                        class="hero-slide absolute inset-0 w-full h-full object-cover"
                        @if ($heroImages->count() > 1)
                            x-show="active === {{ $index }}"
                            :class="{ 'hero-slide-active': active === {{ $index }} && !reduceMotion }"
                            x-transition:enter="transition-opacity duration-1000"
                            x-transition:enter-start="opacity-0"
                            x-transition:enter-end="opacity-100"
                            x-transition:leave="transition-opacity duration-1000"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                        @endif
                    >
                @empty
                    <div class="w-full h-full bg-forest-100"></div>
                @endforelse
            </div>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
        <div class="flex items-end justify-between mb-8">
            <h2 class="font-display text-2xl sm:text-3xl font-semibold text-forest-900">Beliebte Reiseziele</h2>
            <a href="{{ route('regions.index') }}" class="text-sm font-semibold text-forest-700 hover:text-forest-900 flex items-center gap-1">
                Alle Regionen anzeigen
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </a>
        </div>
        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
            @foreach ($regions as $region)
                <x-region-card :region="$region" />
            @endforeach
        </div>
    </section>

    @if ($featuredTips->isNotEmpty())
        <section class="bg-white border-y border-sand-200 py-14">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="font-display text-2xl sm:text-3xl font-semibold text-forest-900 mb-8">Ausgewählte Reisetipps</h2>
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach ($featuredTips as $tip)
                        <x-tip-card :tip="$tip" :show-region="true" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($secretTips->isNotEmpty())
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
            <h2 class="font-display text-2xl sm:text-3xl font-semibold text-forest-900 mb-8">Aktuelle Geheimtipps</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                @foreach ($secretTips as $tip)
                    <x-tip-card :tip="$tip" :show-region="true" />
                @endforeach
            </div>
        </section>
    @endif

    @if ($familyTips->isNotEmpty())
        <section class="bg-white border-y border-sand-200 py-14">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="font-display text-2xl sm:text-3xl font-semibold text-forest-900 mb-8">Familienfreundliche Ziele</h2>
                <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    @foreach ($familyTips as $tip)
                        <x-tip-card :tip="$tip" :show-region="true" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
        <h2 class="font-display text-2xl sm:text-3xl font-semibold text-forest-900 mb-8">Kategorien</h2>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            @foreach ($categories as $category)
                <a href="{{ route('categories.show', $category) }}" class="rounded-2xl bg-white ring-1 ring-sand-200 p-5 hover:shadow-md hover:-translate-y-0.5 transition text-center">
                    <p class="font-display font-semibold text-forest-900">{{ $category->name }}</p>
                    <p class="text-xs text-forest-500 mt-1">{{ $category->travel_tips_count }} Tipps</p>
                </a>
            @endforeach
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14 grid sm:grid-cols-3 gap-8">
        <div class="flex items-start gap-4">
            <div class="w-11 h-11 rounded-full bg-forest-100 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-forest-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8l-6.2 3.2L7 14.2l-5-4.9 6.9-1L12 2Z" stroke-linejoin="round"/></svg>
            </div>
            <div>
                <h3 class="font-display font-semibold text-forest-900">Handverlesene Tipps</h3>
                <p class="text-sm text-forest-500 mt-1">Von Reisebegeisterten für dich ausgewählt.</p>
            </div>
        </div>
        <div class="flex items-start gap-4">
            <div class="w-11 h-11 rounded-full bg-forest-100 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-forest-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M17 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2M11 3a4 4 0 1 1 0 8 4 4 0 0 1 0-8Zm8 18v-2a4 4 0 0 0-3-3.87M15.5 3.13a4 4 0 0 1 0 7.75" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div>
                <h3 class="font-display font-semibold text-forest-900">Für jeden Reisetyp</h3>
                <p class="text-sm text-forest-500 mt-1">Familien, Paare, Alleinreisende und Abenteurer.</p>
            </div>
        </div>
        <div class="flex items-start gap-4">
            <div class="w-11 h-11 rounded-full bg-forest-100 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-forest-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s-7.5-4.9-10-9.3C.6 8.4 2 5 5.3 5c2 0 3.5 1.1 4.4 2.5C10.6 6.1 12.1 5 14.1 5 17.4 5 18.8 8.4 17.4 11.7 15 16.1 12 21 12 21Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div>
                <h3 class="font-display font-semibold text-forest-900">Immer aktuell</h3>
                <p class="text-sm text-forest-500 mt-1">Regelmäßig neue Tipps und Geheimtipps.</p>
            </div>
        </div>
    </section>
@endsection
