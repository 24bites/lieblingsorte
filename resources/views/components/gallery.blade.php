@props(['media', 'alt' => ''])

@php
    $items = $media->values();
@endphp

@if ($items->isNotEmpty())
    <div x-data="{ active: 0, lightbox: false }" class="w-full">
        <div class="relative rounded-3xl overflow-hidden aspect-[16/10] bg-forest-100">
            <template x-for="(item, index) in {{ $items->pluck('url')->toJson() }}" :key="index">
                <img
                    x-show="active === index"
                    :src="item"
                    alt="{{ $alt }}"
                    class="w-full h-full object-cover cursor-zoom-in"
                    @click="lightbox = true"
                >
            </template>

            @if ($items->count() > 1)
                <button @click="active = (active - 1 + {{ $items->count() }}) % {{ $items->count() }}" aria-label="Vorheriges Bild" class="absolute left-3 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/85 hover:bg-white flex items-center justify-center shadow">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
                <button @click="active = (active + 1) % {{ $items->count() }}" aria-label="Nächstes Bild" class="absolute right-3 top-1/2 -translate-y-1/2 w-9 h-9 rounded-full bg-white/85 hover:bg-white flex items-center justify-center shadow">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m9 18 6-6-6-6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </button>
            @endif
        </div>

        @if ($items->count() > 1)
            <div class="mt-3 grid grid-cols-4 sm:grid-cols-6 gap-2">
                @foreach ($items as $index => $item)
                    <button @click="active = {{ $index }}" class="rounded-xl overflow-hidden aspect-square ring-2" :class="active === {{ $index }} ? 'ring-forest-600' : 'ring-transparent'">
                        <img src="{{ $item->url }}" alt="{{ $item->alt_text }}" class="w-full h-full object-cover" loading="lazy">
                    </button>
                @endforeach
            </div>
        @endif

        <div x-show="lightbox" x-cloak class="fixed inset-0 z-50 bg-black/90 flex items-center justify-center p-4" @click="lightbox = false" x-transition.opacity>
            <img :src="{{ $items->pluck('url')->toJson() }}[active]" alt="{{ $alt }}" class="max-h-full max-w-full object-contain">
            <button @click="lightbox = false" class="absolute top-5 right-5 text-white" aria-label="Schließen">
                <svg class="w-8 h-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 6l12 12M18 6 6 18" stroke-linecap="round"/></svg>
            </button>
        </div>
    </div>
@endif
