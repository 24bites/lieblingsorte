@props(['report'])

@php
    $cover = $report->coverImage();
@endphp

<a href="{{ route('reports.show', $report) }}" class="group block rounded-3xl overflow-hidden bg-white shadow-sm ring-1 ring-sand-200 hover:shadow-lg hover:-translate-y-0.5 transition duration-300">
    <div class="relative aspect-[4/3] overflow-hidden">
        @if ($cover)
            <img
                src="{{ $cover->url }}"
                alt="{{ $cover->alt_text ?? $report->title }}"
                loading="lazy"
                class="w-full h-full object-cover group-hover:scale-105 transition duration-500"
            >
        @else
            <div class="w-full h-full bg-forest-100"></div>
        @endif
        @if ($report->region)
            <span class="absolute top-3 right-3 bg-white/90 backdrop-blur text-forest-800 text-xs font-semibold px-3 py-1 rounded-full">
                {{ $report->region->name }}
            </span>
        @endif
    </div>
    <div class="p-5">
        <h3 class="font-display text-lg font-semibold text-forest-900">{{ $report->title }}</h3>
        <p class="text-sm text-forest-500 mt-1 line-clamp-2">{{ $report->excerpt }}</p>
        <p class="text-xs text-forest-400 mt-3">
            {{ $report->author_name }}
            @if ($report->published_at)
                &middot; {{ $report->published_at->format('d.m.Y') }}
            @endif
        </p>
    </div>
</a>
