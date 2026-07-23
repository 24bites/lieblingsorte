<footer class="bg-forest-900 text-sand-100 mt-24">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 grid gap-12 lg:grid-cols-4">
        <div class="lg:col-span-2">
            <span class="font-display text-2xl font-semibold text-white">Lieblingsorte</span>
            <p class="mt-3 text-sand-200 max-w-sm text-sm leading-relaxed">
                {{ \App\Models\Setting::get('site_description', 'Handverlesene Lieblingsorte, echte Geheimtipps und besondere Erlebnisse.') }}
            </p>
            @if (\App\Models\Setting::get('newsletter_footer_visible', '1') === '1')
                <x-newsletter-form class="mt-6" />
            @endif
        </div>

        <div>
            <h3 class="text-sm font-semibold uppercase tracking-wide text-sand-300 mb-4">Entdecken</h3>
            <ul class="space-y-2 text-sm text-sand-200">
                <li><a href="{{ route('regions.index') }}" class="hover:text-white">Alle Regionen</a></li>
                <li><a href="{{ route('categories.index') }}" class="hover:text-white">Kategorien</a></li>
                <li><a href="{{ route('search') }}" class="hover:text-white">Suche</a></li>
                <li><a href="{{ route('favorites.index') }}" class="hover:text-white">Favoriten</a></li>
            </ul>
        </div>

        <div>
            <h3 class="text-sm font-semibold uppercase tracking-wide text-sand-300 mb-4">Regionen</h3>
            <ul class="space-y-2 text-sm text-sand-200">
                @foreach (\App\Models\Region::published()->orderBy('sort_order')->get() as $footerRegion)
                    <li><a href="{{ route('regions.show', $footerRegion) }}" class="hover:text-white">{{ $footerRegion->name }}</a></li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="border-t border-white/10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-sand-400">
            <p>&copy; {{ now()->year }} Lieblingsorte. Alle Angaben ohne Gewähr.</p>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
                <a href="{{ route('legal.impressum') }}" class="hover:text-white">Impressum</a>
                <a href="{{ route('legal.datenschutz') }}" class="hover:text-white">Datenschutz</a>
                <button type="button" onclick="window.dispatchEvent(new CustomEvent('open-cookie-settings'))" class="hover:text-white underline decoration-dotted">Cookie-Einstellungen</button>
            </div>
        </div>
    </div>
</footer>
