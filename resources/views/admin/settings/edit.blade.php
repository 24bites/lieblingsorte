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
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-5">
            <h2 class="font-semibold text-forest-900">Telegram (für Social Hub)</h2>
            <p class="text-sm text-forest-500">
                Damit der Social Hub Beiträge direkt an einen Telegram-Kanal/Chat senden kann (statt nur einen
                Share-Link zu öffnen). Bot-Token über <a href="https://t.me/BotFather" target="_blank" rel="noopener noreferrer" class="underline">@BotFather</a>
                anlegen und den Bot als Admin zum Zielkanal hinzufügen.
            </p>
            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Bot-Token</label>
                    <input type="password" name="telegram_bot_token" autocomplete="off" value="{{ old('telegram_bot_token') }}"
                        placeholder="{{ $telegramConfigured ? 'Hinterlegt ('.$telegramTokenPreview.') – zum Ändern neuen Token eingeben' : '123456:ABC-...' }}"
                        class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm font-mono">
                    @error('telegram_bot_token')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Chat-/Kanal-ID</label>
                    <input type="text" name="telegram_chat_id" value="{{ old('telegram_chat_id', $telegramChatId) }}" placeholder="@meinkanal oder -1001234567890" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm font-mono">
                    @error('telegram_chat_id')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <p class="text-xs text-forest-500">
                @if ($telegramConfigured)
                    Aktuell hinterlegt: <span class="font-mono">{{ $telegramTokenPreview }}</span>.
                @else
                    Aktuell nicht hinterlegt &ndash; im Social Hub steht für Telegram dann nur der Share-Link zur Verfügung.
                @endif
            </p>
            @if ($telegramConfigured)
                <label class="flex items-center gap-2 text-sm text-forest-700">
                    <input type="checkbox" name="remove_telegram" value="1" class="rounded text-forest-600">
                    Hinterlegte Telegram-Zugangsdaten entfernen
                </label>
            @endif
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-5">
            <h2 class="font-semibold text-forest-900">Newsletter-Versand (Resend)</h2>
            <p class="text-sm text-forest-500">
                Wird für den Versand der Newsletter-Bestätigungsmail (Double-Opt-In) benötigt. Der Key wird aus
                Sicherheitsgründen nie wieder im Klartext angezeigt &ndash; das Feld bleibt leer, auch wenn bereits
                einer hinterlegt ist. Ist hier keiner gesetzt, wird ersatzweise <code>RESEND_API_KEY</code> aus der
                <code>.env</code>-Datei verwendet.
            </p>
            <div>
                <label class="block text-sm font-medium text-forest-800 mb-1">Resend API-Key</label>
                <input type="password" name="resend_api_key" autocomplete="off" value="{{ old('resend_api_key') }}"
                    placeholder="{{ $resendConfigured ? 'Hinterlegt ('.$resendKeyPreview.') – zum Ändern neuen Key eingeben' : 're_...' }}"
                    class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm font-mono">
                @error('resend_api_key')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
                <p class="text-xs text-forest-500 mt-1">
                    @if ($resendConfigured)
                        Aktuell hinterlegt: <span class="font-mono">{{ $resendKeyPreview }}</span>.
                    @else
                        Aktuell nicht über die Einstellungen hinterlegt.
                    @endif
                </p>
                @if ($resendConfigured)
                    <label class="flex items-center gap-2 text-sm text-forest-700 mt-2">
                        <input type="checkbox" name="remove_resend_api_key" value="1" class="rounded text-forest-600">
                        Hinterlegten Key entfernen
                    </label>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-5">
            <h2 class="font-semibold text-forest-900">KI-Crons</h2>
            <p class="text-sm text-forest-500">
                Steuert die automatischen Hintergrundaufgaben einzeln. Änderungen wirken beim nächsten
                Scheduler-Durchlauf (jede Minute per Crontab), ohne Deployment.
            </p>

            <div class="rounded-xl border border-sand-200 p-4 space-y-3">
                <label class="flex items-center gap-2 text-sm font-medium text-forest-800">
                    <input type="checkbox" name="images_ai_replace_enabled" value="1" {{ old('images_ai_replace_enabled', $aiCrons['images_ai_replace']['enabled']) ? 'checked' : '' }} class="rounded text-forest-600">
                    Bilder automatisch durch KI ersetzen (<code>images:ai-replace</code>)
                </label>
                <p class="text-xs text-forest-500">Ersetzt Wikimedia-Fotos und generierte Platzhalter-Illustrationen schrittweise durch KI-Bilder.</p>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-forest-700">Alle</label>
                    <input type="number" name="images_ai_replace_interval" min="1" max="59" value="{{ old('images_ai_replace_interval', $aiCrons['images_ai_replace']['interval']) }}" class="w-20 rounded-xl border border-sand-300 px-3 py-1.5 text-sm">
                    <span class="text-sm text-forest-700">Minuten</span>
                </div>
                @error('images_ai_replace_interval')
                    <p class="text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-xl border border-sand-200 p-4 space-y-3">
                <label class="flex items-center gap-2 text-sm font-medium text-forest-800">
                    <input type="checkbox" name="regions_auto_generate_enabled" value="1" {{ old('regions_auto_generate_enabled', $aiCrons['regions_auto_generate']['enabled']) ? 'checked' : '' }} class="rounded text-forest-600">
                    Neue Regionen per KI vorschlagen (<code>regions:auto-generate</code>)
                </label>
                <p class="text-xs text-forest-500">Erstellt unveröffentlichte Regionsentwürfe zur Prüfung in „KI-Vorschläge“ (max. 10 pro Tag).</p>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-forest-700">Alle</label>
                    <input type="number" name="regions_auto_generate_interval" min="1" max="59" value="{{ old('regions_auto_generate_interval', $aiCrons['regions_auto_generate']['interval']) }}" class="w-20 rounded-xl border border-sand-300 px-3 py-1.5 text-sm">
                    <span class="text-sm text-forest-700">Minuten</span>
                </div>
                @error('regions_auto_generate_interval')
                    <p class="text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="rounded-xl border border-sand-200 p-4 space-y-3">
                <label class="flex items-center gap-2 text-sm font-medium text-forest-800">
                    <input type="checkbox" name="regions_complete_content_enabled" value="1" {{ old('regions_complete_content_enabled', $aiCrons['regions_complete_content']['enabled']) ? 'checked' : '' }} class="rounded text-forest-600">
                    Regionen automatisch fertigstellen (<code>regions:complete-content</code>)
                </label>
                <p class="text-xs text-forest-500">
                    Erstellt für freigegebene KI-Regionen und manuell angelegte Regionen automatisch ein Titelbild,
                    füllt fehlende Reisetipps bis auf 12 auf (inkl. KI-Bild je Tipp) und veröffentlicht die Region,
                    sobald alles fertig ist.
                </p>
                <div class="flex items-center gap-2">
                    <label class="text-sm text-forest-700">Alle</label>
                    <input type="number" name="regions_complete_content_interval" min="1" max="59" value="{{ old('regions_complete_content_interval', $aiCrons['regions_complete_content']['interval']) }}" class="w-20 rounded-xl border border-sand-300 px-3 py-1.5 text-sm">
                    <span class="text-sm text-forest-700">Minuten</span>
                </div>
                @error('regions_complete_content_interval')
                    <p class="text-xs text-red-600">{{ $message }}</p>
                @enderror
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
