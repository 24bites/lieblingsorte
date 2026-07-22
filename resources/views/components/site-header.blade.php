@php
    $favoriteCount = count(session('favorite_tip_ids', []));
@endphp
<header x-data="{ mobileOpen: false }" class="sticky top-0 z-40 bg-sand-50/90 backdrop-blur border-b border-sand-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between gap-4">
        <a href="{{ route('home') }}" class="flex items-center gap-2 shrink-0">
            <svg class="w-7 h-7 text-forest-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M12 21c-4.5-4.2-7-7.9-7-11a7 7 0 1 1 14 0c0 3.1-2.5 6.8-7 11Z" stroke-linecap="round" stroke-linejoin="round" />
                <circle cx="12" cy="10" r="2.3" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <span class="font-display text-xl font-semibold text-forest-800">Lieblingsorte</span>
        </a>

        <nav class="hidden lg:flex items-center gap-7 text-sm font-medium text-forest-700">
            <a href="{{ route('regions.index') }}" class="hover:text-forest-900 transition {{ request()->routeIs('regions.index') ? 'text-forest-900' : '' }}">Entdecken</a>
            <a href="{{ route('regions.index') }}" class="hover:text-forest-900 transition">Regionen</a>
            <a href="{{ route('categories.index') }}" class="hover:text-forest-900 transition {{ request()->routeIs('categories.*') ? 'text-forest-900' : '' }}">Kategorien</a>
            <a href="{{ route('reports.index') }}" class="hover:text-forest-900 transition {{ request()->routeIs('reports.*') ? 'text-forest-900' : '' }}">Reiseberichte</a>
        </nav>

        <form action="{{ route('search') }}" method="GET" class="hidden md:flex flex-1 max-w-sm relative" x-data="searchSuggestions()">
            <label for="header-search" class="sr-only">Nach Städten oder Regionen suchen</label>
            <input
                id="header-search"
                type="search"
                name="q"
                autocomplete="off"
                x-model="q"
                @input.debounce.300ms="fetchSuggestions()"
                @focus="open = q.length > 1"
                @click.outside="open = false"
                value="{{ request('q') }}"
                placeholder="Nach Städten oder Regionen suchen…"
                class="w-full rounded-full border border-sand-300 bg-white py-2 pl-10 pr-4 text-sm placeholder:text-forest-400/70 focus:outline-none focus:ring-2 focus:ring-forest-400"
            >
            <svg class="w-4 h-4 text-forest-400 absolute left-3.5 top-1/2 -translate-y-1/2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7" /><path d="m21 21-4.3-4.3" stroke-linecap="round" /></svg>

            <div x-show="open" x-cloak class="absolute top-full mt-2 w-full bg-white rounded-2xl shadow-xl border border-sand-200 overflow-hidden">
                <template x-for="item in suggestions" :key="item.url">
                    <a :href="item.url" class="flex items-center justify-between px-4 py-2.5 text-sm hover:bg-sand-100">
                        <span x-text="item.label"></span>
                        <span class="text-xs text-forest-400" x-text="item.type"></span>
                    </a>
                </template>
                <template x-if="suggestions.length === 0 && q.length > 1">
                    <p class="px-4 py-3 text-sm text-forest-400">Keine Vorschläge gefunden.</p>
                </template>
            </div>
        </form>

        <div class="flex items-center gap-3">
            <a href="{{ route('favorites.index') }}" class="relative p-2 rounded-full hover:bg-sand-100 transition" aria-label="Favoriten">
                <svg class="w-5 h-5 text-forest-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 21s-7.5-4.9-10-9.3C.6 8.4 2 5 5.3 5c2 0 3.5 1.1 4.4 2.5C10.6 6.1 12.1 5 14.1 5 17.4 5 18.8 8.4 17.4 11.7 15 16.1 12 21 12 21Z" stroke-linecap="round" stroke-linejoin="round"/></svg>
                @if ($favoriteCount > 0)
                    <span class="absolute -top-0.5 -right-0.5 bg-forest-600 text-white text-[10px] leading-none rounded-full w-4 h-4 flex items-center justify-center">{{ $favoriteCount }}</span>
                @endif
            </a>

            <button @click="mobileOpen = true" class="lg:hidden p-2 rounded-full hover:bg-sand-100" aria-label="Menü öffnen">
                <svg class="w-6 h-6 text-forest-800" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/></svg>
            </button>
        </div>
    </div>

    <div x-show="mobileOpen" x-cloak class="fixed inset-0 z-50 lg:hidden" x-transition.opacity>
        <div class="absolute inset-0 bg-forest-900/50" @click="mobileOpen = false"></div>
        <div class="absolute right-0 top-0 h-full w-72 bg-white shadow-xl p-6 flex flex-col gap-5" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0">
            <div class="flex items-center justify-between">
                <span class="font-display text-lg font-semibold">Menü</span>
                <button @click="mobileOpen = false" aria-label="Menü schließen" class="p-2 rounded-full hover:bg-sand-100">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 6l12 12M18 6 6 18" stroke-linecap="round"/></svg>
                </button>
            </div>
            <form action="{{ route('search') }}" method="GET">
                <input type="search" name="q" placeholder="Suchen…" class="w-full rounded-full border border-sand-300 py-2 px-4 text-sm">
            </form>
            <nav class="flex flex-col gap-3 text-forest-700 font-medium">
                <a href="{{ route('regions.index') }}" class="py-2">Regionen</a>
                <a href="{{ route('categories.index') }}" class="py-2">Kategorien</a>
                <a href="{{ route('reports.index') }}" class="py-2">Reiseberichte</a>
                <a href="{{ route('favorites.index') }}" class="py-2">Favoriten ({{ $favoriteCount }})</a>
            </nav>
        </div>
    </div>
</header>

<script>
    function searchSuggestions() {
        return {
            q: '{{ request('q') }}',
            open: false,
            suggestions: [],
            async fetchSuggestions() {
                if (this.q.length < 2) { this.suggestions = []; this.open = false; return; }
                const res = await fetch(`{{ route('search.suggestions') }}?q=${encodeURIComponent(this.q)}`);
                this.suggestions = await res.json();
                this.open = true;
            },
        };
    }
</script>
