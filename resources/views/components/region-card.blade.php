@props(['region'])

@php
    $cover = $region->coverImage();
@endphp

<a href="{{ route('regions.show', $region) }}" class="group block rounded-3xl overflow-hidden bg-white shadow-sm ring-1 ring-sand-200 hover:shadow-lg hover:-translate-y-0.5 transition duration-300">
    <div class="relative aspect-[4/3] overflow-hidden">
        @if ($cover)
            <img
                src="{{ $cover->url }}"
                alt="{{ $cover->alt_text ?? $region->name }}"
                loading="lazy"
                class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
            >
        @else
            <div class="w-full h-full bg-forest-100"></div>
        @endif
        <span class="absolute top-3 right-3 bg-white/90 backdrop-blur text-forest-800 text-xs font-semibold px-3 py-1 rounded-full">
            {{ $region->travel_tips_count ?? $region->published_travel_tips_count ?? 0 }} Tipps
        </span>
    </div>
    <div class="p-5">
        <h3 class="font-display text-lg font-semibold text-forest-900">{{ $region->name }}</h3>
        <p class="text-sm text-forest-500 mt-1 line-clamp-2">{{ $region->short_description }}</p>
    </div>
</a>
