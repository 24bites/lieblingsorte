@extends('layouts.app')

@php
    $seoTitle = $region->seo_title ?: $region->name.' Reisetipps | Lieblingsorte';
    $seoDescription = $region->seo_description ?: $region->short_description;
    $seoImage = $region->coverImage()?->url;

    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'TouristDestination',
        'name' => $region->name,
        'description' => $region->short_description,
        'url' => route('regions.show', $region),
    ];
    if ($region->latitude && $region->longitude) {
        $jsonLd['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float) $region->latitude, 'longitude' => (float) $region->longitude];
    }
    if ($seoImage) {
        $jsonLd['image'] = $seoImage;
    }
@endphp

@push('structured-data')
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
    <x-breadcrumbs :items="[
        ['label' => 'Startseite', 'url' => route('home')],
        ['label' => 'Regionen', 'url' => route('regions.index')],
        ['label' => $region->name, 'url' => null],
    ]" />

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
        <div class="relative rounded-[2rem] overflow-hidden aspect-[21/9] shadow-lg">
            @if ($region->coverImage())
                <img src="{{ $region->coverImage()->url }}" alt="{{ $region->coverImage()->alt_text }}" class="w-full h-full object-cover">
            @else
                <div class="w-full h-full bg-forest-100"></div>
            @endif
            <div class="absolute inset-0 bg-gradient-to-t from-forest-900/70 via-forest-900/10 to-transparent"></div>
            <div class="absolute bottom-6 left-6 right-6 sm:bottom-10 sm:left-10 text-white">
                <p class="uppercase text-xs tracking-widest text-sand-200">{{ $region->type }} &middot; {{ $region->country }}</p>
                <h1 class="font-display text-3xl sm:text-5xl font-semibold mt-1">{{ $region->name }}</h1>
                <p class="mt-2 max-w-xl text-sand-100">{{ $region->short_description }}</p>
            </div>
        </div>

        <div class="flex flex-wrap gap-x-8 gap-y-3 mt-6 text-sm text-forest-600">
            <span class="flex items-center gap-2"><svg class="w-4 h-4 text-forest-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-4M9 5v4H5M9 5l6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>{{ $region->travelTips()->published()->count() }} Tipps</span>
            @if ($region->best_travel_time)
                <span class="flex items-center gap-2"><svg class="w-4 h-4 text-forest-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3" stroke-linecap="round" stroke-linejoin="round"/></svg>Beste Reisezeit: {{ $region->best_travel_time }}</span>
            @endif
            @if ($region->federal_state)
                <span class="flex items-center gap-2"><svg class="w-4 h-4 text-forest-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21c-4.5-4.2-7-7.9-7-11a7 7 0 1 1 14 0c0 3.1-2.5 6.8-7 11Z" stroke-linecap="round" stroke-linejoin="round"/></svg>{{ $region->federal_state }}</span>
            @endif
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 grid lg:grid-cols-3 gap-10">
        <div class="lg:col-span-2 space-y-4 text-forest-700 leading-relaxed">
            @foreach (explode("\n\n", $region->description) as $paragraph)
                <p>{{ $paragraph }}</p>
            @endforeach

            @if ($region->arrival_information)
                <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-5 mt-6">
                    <h2 class="font-display font-semibold text-forest-900 mb-2">Anreise</h2>
                    <p class="text-sm text-forest-600">{{ $region->arrival_information }}</p>
                </div>
            @endif
        </div>

        @if ($region->labels->isNotEmpty())
            <div class="flex flex-wrap content-start gap-2 lg:justify-end">
                @foreach ($region->labels as $label)
                    <x-label-badge :label="$label" />
                @endforeach
            </div>
        @endif
    </section>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <x-ad-slot position="in_content" />
    </div>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-6">
        <form method="GET" action="{{ route('regions.show', $region) }}" class="space-y-4" id="filter-form">
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('regions.show', $region) }}" class="px-4 py-2 rounded-full text-sm font-medium transition {{ ! request('label') ? 'bg-forest-700 text-white' : 'bg-white ring-1 ring-sand-300 text-forest-700 hover:bg-sand-100' }}">Alle</a>
                @foreach ($availableLabels as $label)
                    <a href="{{ request()->fullUrlWithQuery(['label' => $label->slug, 'page' => null]) }}" class="px-4 py-2 rounded-full text-sm font-medium transition {{ request('label') === $label->slug ? 'bg-forest-700 text-white' : 'bg-white ring-1 ring-sand-300 text-forest-700 hover:bg-sand-100' }}">{{ $label->name }}</a>
                @endforeach
            </div>

            <div class="flex flex-wrap items-center gap-4 text-sm">
                @if (request('label'))
                    <input type="hidden" name="label" value="{{ request('label') }}">
                @endif

                <select name="kategorie" onchange="document.getElementById('filter-form').submit()" class="rounded-full border border-sand-300 py-2 px-4 bg-white">
                    <option value="">Alle Kategorien</option>
                    @foreach ($availableCategories as $category)
                        <option value="{{ $category->slug }}" @selected(request('kategorie') === $category->slug)>{{ $category->name }}</option>
                    @endforeach
                </select>

                <label class="flex items-center gap-2 bg-white rounded-full px-4 py-2 border border-sand-300 cursor-pointer">
                    <input type="checkbox" name="kostenlos" value="1" onchange="document.getElementById('filter-form').submit()" @checked(request('kostenlos')) class="rounded text-forest-600">
                    Kostenlos
                </label>
                <label class="flex items-center gap-2 bg-white rounded-full px-4 py-2 border border-sand-300 cursor-pointer">
                    <input type="checkbox" name="hund" value="1" onchange="document.getElementById('filter-form').submit()" @checked(request('hund')) class="rounded text-forest-600">
                    Hunde erlaubt
                </label>
                <label class="flex items-center gap-2 bg-white rounded-full px-4 py-2 border border-sand-300 cursor-pointer">
                    <input type="checkbox" name="kinderwagen" value="1" onchange="document.getElementById('filter-form').submit()" @checked(request('kinderwagen')) class="rounded text-forest-600">
                    Kinderwagengeeignet
                </label>
                <label class="flex items-center gap-2 bg-white rounded-full px-4 py-2 border border-sand-300 cursor-pointer">
                    <input type="checkbox" name="indoor" value="1" onchange="document.getElementById('filter-form').submit()" @checked(request('indoor')) class="rounded text-forest-600">
                    Schlechtwetter
                </label>

                <select name="sortierung" onchange="document.getElementById('filter-form').submit()" class="rounded-full border border-sand-300 py-2 px-4 bg-white ml-auto">
                    <option value="" @selected(! request('sortierung'))>Empfehlung</option>
                    <option value="bewertung" @selected(request('sortierung') === 'bewertung')>Beste Bewertung</option>
                    <option value="name" @selected(request('sortierung') === 'name')>Name (A–Z)</option>
                </select>
            </div>
        </form>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-14">
        @if ($tips->isEmpty())
            <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-10 text-center">
                <p class="text-forest-600">Für diese Filterkombination wurden keine Reisetipps gefunden.</p>
                <a href="{{ route('regions.show', $region) }}" class="inline-block mt-4 text-sm font-semibold text-forest-700 hover:text-forest-900">Filter zurücksetzen</a>
            </div>
        @else
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($tips as $tip)
                    <x-tip-card :tip="$tip" />
                @endforeach
            </div>
            <div class="mt-10">{{ $tips->links() }}</div>
        @endif
    </section>

    @php
        $mapMarkers = $region->travelTips()->published()->get()->map(fn ($t) => [
            'lat' => $t->latitude, 'lng' => $t->longitude, 'title' => $t->title,
            'url' => route('tips.show', [$region, $t]), 'image' => $t->coverImage()?->url,
            'category' => $t->categories->first()?->name,
        ]);
    @endphp
    @if ($mapMarkers->isNotEmpty())
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-14">
            <h2 class="font-display text-2xl font-semibold text-forest-900 mb-6">Alle Reisetipps auf der Karte</h2>
            <x-map :markers="$mapMarkers" id="region-map" height="h-[28rem]" />
        </section>
    @endif

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-14">
        <h2 class="font-display text-2xl font-semibold text-forest-900 mb-6">Häufige Fragen zu {{ $region->name }}</h2>
        <div class="space-y-3" x-data="{ open: null }">
            @php
                $faqs = [
                    ['q' => 'Wann ist die beste Reisezeit für '.$region->name.'?', 'a' => $region->best_travel_time ?: 'Die Region ist ganzjährig einen Besuch wert, abhängig von den geplanten Aktivitäten.'],
                    ['q' => 'Wie reise ich am besten nach '.$region->name.' an?', 'a' => $region->arrival_information ?: 'Details zur Anreise findest du bei den jeweiligen Reisetipps.'],
                    ['q' => 'Gibt es familienfreundliche Ausflugsziele in '.$region->name.'?', 'a' => 'Ja, nutze den Filter "Familie" oben, um passende Reisetipps zu finden.'],
                ];
            @endphp
            @foreach ($faqs as $index => $faq)
                <div class="bg-white rounded-2xl ring-1 ring-sand-200 overflow-hidden">
                    <button @click="open = open === {{ $index }} ? null : {{ $index }}" class="w-full flex items-center justify-between px-5 py-4 text-left font-medium text-forest-900">
                        {{ $faq['q'] }}
                        <svg class="w-4 h-4 shrink-0 transition" :class="open === {{ $index }} ? 'rotate-180' : ''" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m6 9 6 6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </button>
                    <div x-show="open === {{ $index }}" x-cloak x-transition class="px-5 pb-4 text-sm text-forest-600">{{ $faq['a'] }}</div>
                </div>
            @endforeach
        </div>
    </section>

    @if ($similarRegions->isNotEmpty())
        <section class="bg-white border-t border-sand-200 py-14">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="font-display text-2xl font-semibold text-forest-900 mb-6">Ähnliche Regionen</h2>
                <div class="grid sm:grid-cols-3 gap-6">
                    @foreach ($similarRegions as $similar)
                        <x-region-card :region="$similar" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
