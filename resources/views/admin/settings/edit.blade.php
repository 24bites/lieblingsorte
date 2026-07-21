@extends('layouts.admin')

@section('title', 'Einstellungen')

@section('content')
    <h1 class="text-2xl font-semibold text-forest-900 mb-6">Einstellungen</h1>

    <form action="{{ route('admin.settings.update') }}" method="POST" class="space-y-8 max-w-2xl">
        @csrf
        @method('PUT')

        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-5">
            <h2 class="font-semibold text-forest-900">Allgemein</h2>
            <div>
                <label class="block text-sm font-medium text-forest-800 mb-1">Seitenname</label>
                <input type="text" name="site_name" value="{{ old('site_name', $settings['site_name']) }}" required class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-forest-800 mb-1">Claim / Slogan</label>
                <input type="text" name="site_claim" value="{{ old('site_claim', $settings['site_claim']) }}" required class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-forest-800 mb-1">Beschreibung</label>
                <textarea name="site_description" rows="3" required class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">{{ old('site_description', $settings['site_description']) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-forest-800 mb-1">Kontakt-E-Mail</label>
                <input type="email" name="contact_email" value="{{ old('contact_email', $settings['contact_email']) }}" required class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
            </div>
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-5">
            <h2 class="font-semibold text-forest-900">KI-Funktionen (OpenAI)</h2>
            <p class="text-sm text-forest-500">
                Wird für „Bild mit KI generieren“ und den „KI-Regionsgenerator“ benötigt. Der Key wird aus
                Sicherheitsgründen nie wieder im Klartext angezeigt &ndash; das Feld bleibt leer, auch wenn bereits
                einer hinterlegt ist. Ist hier keiner gesetzt, wird ersatzweise <code>OPENAI_API_KEY</code> aus der
                <code>.env</code>-Datei verwendet.
            </p>
            <div>
                <label class="block text-sm font-medium text-forest-800 mb-1">OpenAI API-Key</label>
                <input type="password" name="openai_api_key" autocomplete="off" value="{{ old('openai_api_key') }}"
                    placeholder="{{ $openaiKeyConfigured ? 'Hinterlegt ('.$openaiKeyPreview.') – zum Ändern neuen Key eingeben' : 'sk-...' }}"
                    class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm font-mono">
                @error('openai_api_key')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
                <p class="text-xs text-forest-500 mt-1">
                    @if ($openaiKeyConfigured)
                        Aktuell hinterlegt: <span class="font-mono">{{ $openaiKeyPreview }}</span>.
                    @else
                        Aktuell nicht über die Einstellungen hinterlegt.
                    @endif
                </p>
                @if ($openaiKeyConfigured)
                    <label class="flex items-center gap-2 text-sm text-forest-700 mt-2">
                        <input type="checkbox" name="remove_openai_api_key" value="1" class="rounded text-forest-600">
                        Hinterlegten Key entfernen
                    </label>
                @endif
            </div>
            <div class="pt-1 border-t border-sand-100">
                <label class="flex items-center gap-2 text-sm text-forest-700 mt-4">
                    <input type="checkbox" name="ai_crons_enabled" value="1" {{ old('ai_crons_enabled', $aiCronsEnabled) ? 'checked' : '' }} class="rounded text-forest-600">
                    Automatische KI-Crons aktiv (Bilder ersetzen, neue Regionen vorschlagen)
                </label>
                <p class="text-xs text-forest-500 mt-1">
                    Deaktivieren pausiert <code>images:ai-replace</code> und <code>regions:auto-generate</code> sofort, ohne Deployment.
                </p>
            </div>
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-5">
            <h2 class="font-semibold text-forest-900">Google Analytics</h2>
            <p class="text-sm text-forest-500">
                Leer lassen, um Tracking zu deaktivieren. Das Skript wird nur geladen, wenn Besucher der Analyse-Kategorie im Cookie-Banner zustimmen.
            </p>
            <div>
                <label class="block text-sm font-medium text-forest-800 mb-1">GA4-Messkennung</label>
                <input type="text" name="ga_measurement_id" value="{{ old('ga_measurement_id', $settings['ga_measurement_id']) }}" placeholder="G-XXXXXXXXXX" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm font-mono">
                @error('ga_measurement_id')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-5">
            <h2 class="font-semibold text-forest-900">Werbeflächen</h2>
            <p class="text-sm text-forest-500">
                Beliebiger HTML-/Script-Code (z. B. Google AdSense oder eine direkte Buchung) für die jeweilige Platzierung. Leere Felder zeigen keine Werbefläche an.
            </p>
            <div>
                <label class="block text-sm font-medium text-forest-800 mb-1">Header (auf jeder Seite)</label>
                <textarea name="ad_slot_header" rows="3" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm font-mono" placeholder="<script>...</script> oder <ins class=&quot;adsbygoogle&quot;>...</ins>">{{ old('ad_slot_header', $settings['ad_slot_header']) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-forest-800 mb-1">Sidebar (Reisetipp-Detailseite)</label>
                <textarea name="ad_slot_sidebar" rows="3" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm font-mono">{{ old('ad_slot_sidebar', $settings['ad_slot_sidebar']) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-forest-800 mb-1">In-Content (Regionsseite, zwischen Beschreibung und Reisetipps)</label>
                <textarea name="ad_slot_in_content" rows="3" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm font-mono">{{ old('ad_slot_in_content', $settings['ad_slot_in_content']) }}</textarea>
            </div>
        </div>

        <button type="submit" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white font-semibold px-6 py-2.5 text-sm">Speichern</button>
    </form>
@endsection
