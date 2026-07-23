@extends('layouts.app')

@php
    $seoTitle = $tip->seo_title ?: $tip->title.' – '.$region->name.' | Lieblingsorte';
    $seoDescription = $tip->seo_description ?: $tip->short_description;
    $seoImage = $tip->coverImage()?->url;
    $isFavorite = in_array($tip->id, $favoriteIds, true);
    $ogType = 'article';
    $articleModifiedTime = $tip->updated_at->toAtomString();

    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'TouristAttraction',
        'name' => $tip->title,
        'description' => $tip->short_description,
        'url' => route('tips.show', [$region, $tip]),
    ];
    if ($tip->hasCoordinates()) {
        $jsonLd['geo'] = ['@type' => 'GeoCoordinates', 'latitude' => (float) $tip->latitude, 'longitude' => (float) $tip->longitude];
    }
    if ($seoImage) {
        $jsonLd['image'] = $seoImage;
    }
    if ($tip->address) {
        $jsonLd['address'] = $tip->address;
    }
@endphp

@push('structured-data')
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush

@section('content')
    @if ($preview ?? false)
        <x-preview-banner :is-published="$tip->is_published" />
    @endif

    <x-breadcrumbs :items="[
        ['label' => 'Startseite', 'url' => route('home')],
        ['label' => $region->name, 'url' => ($preview ?? false) ? route('admin.regions.preview', $region) : route('regions.show', $region)],
        ['label' => $tip->title, 'url' => null],
    ]" />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 grid lg:grid-cols-3 gap-10">
        <div class="lg:col-span-2 space-y-8">
            <div>
                <div class="flex flex-wrap gap-2 mb-3">
                    @foreach ($tip->labels as $label)
                        <x-label-badge :label="$label" />
                    @endforeach
                </div>
                <h1 class="font-display text-3xl sm:text-4xl font-semibold text-forest-900">{{ $tip->title }}</h1>
                <p class="text-forest-500 mt-2 flex items-center gap-2">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21c-4.5-4.2-7-7.9-7-11a7 7 0 1 1 14 0c0 3.1-2.5 6.8-7 11Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    {{ $tip->location_name ?? $region->name }} &middot; {{ $region->name }}
                </p>
            </div>

            <x-gallery :media="$tip->media" :alt="$tip->title" />

            <div class="flex flex-wrap gap-3">
                <form action="{{ route('favorites.toggle', $tip) }}" method="POST">
                    @csrf
                    <button type="submit" class="flex items-center gap-2 rounded-full border border-forest-300 hover:bg-forest-50 px-5 py-2.5 text-sm font-semibold text-forest-800 transition">
                        <svg class="w-4 h-4 {{ $isFavorite ? 'text-red-500 fill-red-500' : 'fill-none' }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path d="M12 21s-7.5-4.9-10-9.3C.6 8.4 2 5 5.3 5c2 0 3.5 1.1 4.4 2.5C10.6 6.1 12.1 5 14.1 5 17.4 5 18.8 8.4 17.4 11.7 15 16.1 12 21 12 21Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        {{ $isFavorite ? 'Gemerkt' : 'Zur Merkliste' }}
                    </button>
                </form>
                @if ($tip->hasCoordinates())
                    <a href="https://www.openstreetmap.org/?mlat={{ $tip->latitude }}&mlon={{ $tip->longitude }}#map=15/{{ $tip->latitude }}/{{ $tip->longitude }}" target="_blank" rel="noopener" class="flex items-center gap-2 rounded-full bg-forest-700 hover:bg-forest-800 text-white px-5 py-2.5 text-sm font-semibold transition">
                        <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M9 20l-5.447-2.724A1 1 0 0 1 3 16.382V5.618a1 1 0 0 1 1.447-.894L9 7m0 13 6-3m-6 3V7m6 10 4.553 2.276A1 1 0 0 0 21 18.382V7.618a1 1 0 0 0-.553-.894L15 4m0 13V4m0 0L9 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Route planen
                    </a>
                @endif
            </div>

            <div class="prose prose-forest max-w-none text-forest-700 leading-relaxed space-y-4">
                <p class="text-lg text-forest-800 font-medium">{{ $tip->short_description }}</p>
                @foreach (explode("\n\n", $tip->description) as $paragraph)
                    <p>{{ $paragraph }}</p>
                @endforeach
            </div>

            @if (! empty($tip->highlights))
                <div>
                    <h2 class="font-display text-xl font-semibold text-forest-900 mb-3">Highlights</h2>
                    <ul class="space-y-2">
                        @foreach ($tip->highlights as $highlight)
                            <li class="flex items-start gap-2 text-forest-700">
                                <svg class="w-5 h-5 text-forest-500 shrink-0 mt-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m5 12 5 5L20 7" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                {{ $highlight }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($tip->categories->isNotEmpty())
                <div>
                    <h2 class="font-display text-xl font-semibold text-forest-900 mb-3">Kategorien</h2>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($tip->categories as $category)
                            <a href="{{ route('categories.show', $category) }}" class="rounded-full bg-white ring-1 ring-sand-300 px-4 py-1.5 text-sm text-forest-700 hover:bg-sand-100">{{ $category->name }}</a>
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($tip->hasCoordinates())
                <div>
                    <h2 class="font-display text-xl font-semibold text-forest-900 mb-3">Lage</h2>
                    <x-map :markers="[[
                        'lat' => $tip->latitude, 'lng' => $tip->longitude, 'title' => $tip->title,
                        'url' => null, 'image' => $tip->coverImage()?->url, 'category' => $tip->categories->first()?->name,
                    ]]" id="tip-map" :zoom="14" height="h-80" />
                </div>
            @endif
        </div>

        <aside class="space-y-6">
            <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-4 sticky top-24">
                <h2 class="font-display font-semibold text-forest-900">Auf einen Blick</h2>
                <dl class="space-y-3 text-sm">
                    @if ($tip->duration)
                        <div class="flex justify-between gap-4"><dt class="text-forest-500">Dauer</dt><dd class="font-medium text-forest-900 text-right">{{ $tip->duration }}</dd></div>
                    @endif
                    @if ($tip->difficulty)
                        <div class="flex justify-between gap-4"><dt class="text-forest-500">Schwierigkeit</dt><dd class="font-medium text-forest-900 text-right capitalize">{{ $tip->difficulty }}</dd></div>
                    @endif
                    @if ($tip->price_information)
                        <div class="flex justify-between gap-4"><dt class="text-forest-500">Preis</dt><dd class="font-medium text-forest-900 text-right">{{ $tip->price_information }}</dd></div>
                    @endif
                    @if ($tip->opening_hours)
                        <div class="flex justify-between gap-4"><dt class="text-forest-500">Öffnungszeiten</dt><dd class="font-medium text-forest-900 text-right">{{ $tip->opening_hours }}</dd></div>
                    @endif
                    @if ($tip->best_season)
                        <div class="flex justify-between gap-4"><dt class="text-forest-500">Beste Jahreszeit</dt><dd class="font-medium text-forest-900 text-right">{{ $tip->best_season }}</dd></div>
                    @endif
                    @if ($tip->parking_information)
                        <div class="flex justify-between gap-4"><dt class="text-forest-500">Parken</dt><dd class="font-medium text-forest-900 text-right">{{ $tip->parking_information }}</dd></div>
                    @endif
                    @if ($tip->rating)
                        <div class="flex justify-between gap-4"><dt class="text-forest-500">Bewertung</dt><dd class="font-medium text-forest-900 text-right">{{ number_format($tip->rating, 1) }} / 5</dd></div>
                    @endif
                </dl>

                <div class="flex flex-wrap gap-2 pt-2 border-t border-sand-200">
                    @if ($tip->family_friendly)
                        <span class="text-xs bg-forest-50 text-forest-700 rounded-full px-3 py-1">👨‍👩‍👧 Familienfreundlich</span>
                    @endif
                    @if ($tip->stroller_friendly)
                        <span class="text-xs bg-forest-50 text-forest-700 rounded-full px-3 py-1">🛒 Kinderwagengeeignet</span>
                    @endif
                    @if ($tip->dog_friendly)
                        <span class="text-xs bg-forest-50 text-forest-700 rounded-full px-3 py-1">🐕 Hunde erlaubt</span>
                    @endif
                    @if ($tip->indoor)
                        <span class="text-xs bg-forest-50 text-forest-700 rounded-full px-3 py-1">🏠 Indoor</span>
                    @endif
                    @if ($tip->free_entry)
                        <span class="text-xs bg-forest-50 text-forest-700 rounded-full px-3 py-1">✓ Kostenloser Eintritt</span>
                    @endif
                </div>

                @if ($tip->address || $tip->arrival_information || $tip->website_url || $tip->phone || $tip->email)
                    <div class="pt-2 border-t border-sand-200 space-y-2 text-sm">
                        @if ($tip->address)
                            <p class="text-forest-600">{{ $tip->address }}</p>
                        @endif
                        @if ($tip->arrival_information)
                            <p class="text-forest-500 text-xs">{{ $tip->arrival_information }}</p>
                        @endif
                        @if ($tip->website_url)
                            <a href="{{ $tip->website_url }}" target="_blank" rel="noopener" class="block font-medium text-forest-700 hover:text-forest-900">Website besuchen ↗</a>
                        @endif
                        @if ($tip->phone)
                            <a href="tel:{{ $tip->phone }}" class="block text-forest-700 hover:text-forest-900">{{ $tip->phone }}</a>
                        @endif
                        @if ($tip->email)
                            <a href="mailto:{{ $tip->email }}" class="block text-forest-700 hover:text-forest-900">{{ $tip->email }}</a>
                        @endif
                    </div>
                @endif
            </div>

            <x-ad-slot position="sidebar" />
        </aside>
    </div>

    @if ($similarTips->isNotEmpty())
        <section class="bg-white border-t border-sand-200 py-14">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="font-display text-2xl font-semibold text-forest-900 mb-6">Ähnliche Reisetipps</h2>
                <div class="grid sm:grid-cols-3 gap-6">
                    @foreach ($similarTips as $similar)
                        <x-tip-card :tip="$similar" />
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($otherTips->isNotEmpty())
        <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
            <h2 class="font-display text-2xl font-semibold text-forest-900 mb-6">Weitere Tipps in {{ $region->name }}</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($otherTips as $other)
                    <x-tip-card :tip="$other" />
                @endforeach
            </div>
            <div class="mt-6">
                <a href="{{ route('regions.show', $region) }}" class="text-sm font-semibold text-forest-700 hover:text-forest-900">Alle Tipps in {{ $region->name }} ansehen →</a>
            </div>
        </section>
    @endif
@endsection
