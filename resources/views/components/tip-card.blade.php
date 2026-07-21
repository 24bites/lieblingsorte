@props(['tip', 'showRegion' => false])

@php
    $cover = $tip->coverImage();
    $favoriteIds = session('favorite_tip_ids', []);
    $isFavorite = in_array($tip->id, $favoriteIds, true);
@endphp

<div class="group relative rounded-3xl overflow-hidden bg-white shadow-sm ring-1 ring-sand-200 hover:shadow-lg hover:-translate-y-0.5 transition duration-300">
    <a href="{{ route('tips.show', [$tip->region, $tip]) }}" class="block">
        <div class="relative aspect-[4/3] overflow-hidden">
            @if ($cover)
                <img src="{{ $cover->url }}" alt="{{ $cover->alt_text ?? $tip->title }}" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
            @else
                <div class="w-full h-full bg-forest-100"></div>
            @endif

            @if ($tip->labels->isNotEmpty())
                <div class="absolute top-3 left-3 flex flex-wrap gap-1.5 max-w-[85%]">
                    <x-label-badge :label="$tip->labels->first()" />
                </div>
            @endif
        </div>
    </a>

    <form action="{{ route('favorites.toggle', $tip) }}" method="POST" class="absolute top-3 right-3">
        @csrf
        <button
            type="submit"
            aria-label="{{ $isFavorite ? 'Von Favoriten entfernen' : 'Zu Favoriten hinzufügen' }}"
            class="w-8 h-8 rounded-full bg-white/90 backdrop-blur flex items-center justify-center hover:bg-white transition"
        >
            <svg class="w-4 h-4 {{ $isFavorite ? 'text-red-500 fill-red-500' : 'text-forest-700 fill-none' }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                <path d="M12 21s-7.5-4.9-10-9.3C.6 8.4 2 5 5.3 5c2 0 3.5 1.1 4.4 2.5C10.6 6.1 12.1 5 14.1 5 17.4 5 18.8 8.4 17.4 11.7 15 16.1 12 21 12 21Z" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </button>
    </form>

    <a href="{{ route('tips.show', [$tip->region, $tip]) }}" class="block p-5">
        @if ($showRegion)
            <p class="text-xs font-medium text-forest-500 uppercase tracking-wide mb-1">{{ $tip->region->name }} &middot; {{ $tip->location_name }}</p>
        @endif
        <h3 class="font-display text-lg font-semibold text-forest-900 leading-snug">{{ $tip->title }}</h3>
        <p class="text-sm text-forest-500 mt-1 line-clamp-2">{{ $tip->short_description }}</p>
        <div class="flex items-center gap-3 mt-3 text-xs text-forest-500">
            @if ($tip->duration)
                <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    {{ $tip->duration }}
                </span>
            @endif
            @if ($tip->rating)
                <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5 text-sand-500 fill-sand-500" viewBox="0 0 24 24"><path d="m12 2 3.1 6.3 6.9 1-5 4.9 1.2 6.8L12 17.8l-6.2 3.2L7 14.2l-5-4.9 6.9-1L12 2Z"/></svg>
                    {{ number_format($tip->rating, 1) }}
                </span>
            @endif
        </div>
    </a>
</div>
