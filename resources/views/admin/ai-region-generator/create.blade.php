@extends('layouts.admin')

@section('title', 'KI-Regionsgenerator')

@section('content')
    <h1 class="text-2xl font-semibold text-forest-900 mb-2">KI-Regionsgenerator</h1>
    <p class="text-sm text-forest-600 mb-6 max-w-2xl">
        Gib einen Orts- oder Regionsnamen ein. OpenAI erstellt daraus einen kompletten Entwurf mit Beschreibungstexten
        und mehreren Reisetipps. Der Entwurf wird <strong>unveröffentlicht</strong> gespeichert &ndash; bitte alle
        Angaben (insbesondere Adressen, Öffnungszeiten, Preise und Koordinaten) vor der Veröffentlichung prüfen und
        korrigieren, da KI-generierte Inhalte Fehler enthalten können.
    </p>

    @unless ($aiConfigured ?? \App\Support\OpenAiRegionDrafter::isConfigured())
        <div class="max-w-2xl bg-amber-50 ring-1 ring-amber-200 rounded-2xl p-5 text-sm text-amber-900">
            Kein OpenAI-API-Key konfiguriert. Trage ihn unter
            <a href="{{ route('admin.settings.edit') }}" class="underline">Einstellungen &rarr; KI-Funktionen (OpenAI)</a>
            ein, um diese Funktion zu nutzen.
        </div>
    @else
        <form action="{{ route('admin.ai-region-generator.store') }}" method="POST" class="max-w-2xl bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-5">
            @csrf

            <div>
                <label class="block text-sm font-medium text-forest-800 mb-1">Ort oder Region *</label>
                <input type="text" name="place_name" value="{{ old('place_name') }}" required placeholder="z. B. Salzburg, Provence, Gran Canaria" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                @error('place_name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-forest-800 mb-1">Anzahl Reisetipps</label>
                <input type="number" name="tip_count" min="5" max="20" value="{{ old('tip_count', 15) }}" class="w-32 rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                <p class="text-xs text-forest-500 mt-1">5&ndash;20 Tipps, empfohlen: 15.</p>
            </div>

            <button type="submit" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white font-semibold px-6 py-3 text-sm">
                Entwurf generieren
            </button>
            <p class="text-xs text-forest-500">Die Anfrage kann je nach Tipp-Anzahl bis zu einer Minute dauern.</p>
        </form>
    @endunless
@endsection
