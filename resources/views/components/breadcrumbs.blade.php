@props(['items'])
{{-- $items: array of ['label' => string, 'url' => string|null] --}}

<nav aria-label="Breadcrumb" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6">
    <ol class="flex flex-wrap items-center gap-1.5 text-sm text-forest-500">
        @foreach ($items as $index => $item)
            <li class="flex items-center gap-1.5">
                @if (! $loop->first)
                    <svg class="w-3.5 h-3.5 text-forest-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                @endif
                @if (! empty($item['url']) && ! $loop->last)
                    <a href="{{ $item['url'] }}" class="hover:text-forest-800 transition">{{ $item['label'] }}</a>
                @else
                    <span class="text-forest-800 font-medium" aria-current="page">{{ $item['label'] }}</span>
                @endif
            </li>
        @endforeach
    </ol>
</nav>

@php
    $breadcrumbJsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => collect($items)->values()->map(fn ($item, $i) => [
            '@type' => 'ListItem',
            'position' => $i + 1,
            'name' => $item['label'],
            'item' => $item['url'] ?? url()->current(),
        ])->all(),
    ];
@endphp
@push('structured-data')
    <script type="application/ld+json">{!! json_encode($breadcrumbJsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endpush
