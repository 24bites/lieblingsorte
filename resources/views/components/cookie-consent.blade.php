<div
    x-data="cookieConsent()"
    x-init="init()"
    x-show="visible"
    x-cloak
    x-transition
    class="fixed inset-x-0 bottom-0 z-50 p-4 sm:p-6"
    role="dialog"
    aria-modal="true"
    aria-label="Cookie-Einstellungen"
>
    <div class="max-w-3xl mx-auto bg-white rounded-2xl shadow-2xl ring-1 ring-sand-200 p-6">
        <template x-if="!settingsOpen">
            <div>
                <h2 class="font-display font-semibold text-forest-900 text-lg mb-2">Cookie-Einstellungen</h2>
                <p class="text-sm text-forest-600 mb-4">
                    Wir verwenden technisch notwendige Cookies, damit die Website funktioniert (z. B. Favoritenliste,
                    Formularschutz). Mit Ihrer Einwilligung nutzen wir zusätzlich Analyse-Cookies, um die Website zu
                    verbessern. Mehr dazu in unserer
                    <a href="{{ route('legal.datenschutz') }}" class="underline text-forest-700">Datenschutzerklärung</a>.
                </p>
                <div class="flex flex-wrap gap-3">
                    <button type="button" @click="acceptAll()" class="rounded-full bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold px-5 py-2.5">Alle akzeptieren</button>
                    <button type="button" @click="rejectAll()" class="rounded-full border border-sand-300 hover:bg-sand-100 text-forest-800 text-sm font-semibold px-5 py-2.5">Nur notwendige</button>
                    <button type="button" @click="settingsOpen = true" class="rounded-full text-forest-700 hover:text-forest-900 text-sm font-semibold px-5 py-2.5 underline">Einstellungen</button>
                </div>
            </div>
        </template>

        <template x-if="settingsOpen">
            <div>
                <h2 class="font-display font-semibold text-forest-900 text-lg mb-4">Cookie-Einstellungen anpassen</h2>
                <div class="space-y-3 mb-5">
                    <div class="flex items-start justify-between gap-4 bg-sand-50 rounded-xl px-4 py-3">
                        <div>
                            <p class="font-medium text-forest-900 text-sm">Notwendig</p>
                            <p class="text-xs text-forest-500">Immer aktiv &ndash; für Session, Favoriten und CSRF-Schutz erforderlich.</p>
                        </div>
                        <input type="checkbox" checked disabled class="mt-1 rounded text-forest-400">
                    </div>
                    <div class="flex items-start justify-between gap-4 bg-sand-50 rounded-xl px-4 py-3">
                        <div>
                            <p class="font-medium text-forest-900 text-sm">Analyse</p>
                            <p class="text-xs text-forest-500">Google Analytics zur anonymisierten Nutzungsauswertung.</p>
                        </div>
                        <input type="checkbox" x-model="consent.analytics" class="mt-1 rounded text-forest-600">
                    </div>
                    <div class="flex items-start justify-between gap-4 bg-sand-50 rounded-xl px-4 py-3">
                        <div>
                            <p class="font-medium text-forest-900 text-sm">Marketing</p>
                            <p class="text-xs text-forest-500">Für zukünftige personalisierte Werbeeinblendungen.</p>
                        </div>
                        <input type="checkbox" x-model="consent.marketing" class="mt-1 rounded text-forest-600">
                    </div>
                </div>
                <div class="flex flex-wrap gap-3">
                    <button type="button" @click="saveCustom()" class="rounded-full bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold px-5 py-2.5">Auswahl speichern</button>
                    <button type="button" @click="settingsOpen = false" class="rounded-full border border-sand-300 hover:bg-sand-100 text-forest-800 text-sm font-semibold px-5 py-2.5">Zurück</button>
                </div>
            </div>
        </template>
    </div>
</div>

<script>
    function cookieConsent() {
        return {
            visible: false,
            settingsOpen: false,
            consent: { necessary: true, analytics: false, marketing: false },

            init() {
                var stored = this.loadConsent();
                if (stored) {
                    this.consent = stored;
                    this.visible = false;
                } else {
                    this.visible = true;
                }

                window.addEventListener('open-cookie-settings', () => {
                    var current = this.loadConsent();
                    if (current) this.consent = current;
                    this.settingsOpen = true;
                    this.visible = true;
                });
            },

            loadConsent() {
                try {
                    var raw = localStorage.getItem('cookie_consent');
                    return raw ? JSON.parse(raw) : null;
                } catch (e) {
                    return null;
                }
            },

            persist() {
                localStorage.setItem('cookie_consent', JSON.stringify(this.consent));
                window.dispatchEvent(new CustomEvent('cookie-consent-updated', { detail: this.consent }));
            },

            acceptAll() {
                this.consent = { necessary: true, analytics: true, marketing: true };
                this.persist();
                this.visible = false;
                this.settingsOpen = false;
            },

            rejectAll() {
                this.consent = { necessary: true, analytics: false, marketing: false };
                this.persist();
                this.visible = false;
                this.settingsOpen = false;
            },

            saveCustom() {
                this.consent.necessary = true;
                this.persist();
                this.visible = false;
                this.settingsOpen = false;
            },
        };
    }

    window.hasAnalyticsConsent = function () {
        try {
            var raw = localStorage.getItem('cookie_consent');
            var consent = raw ? JSON.parse(raw) : null;
            return !!(consent && consent.analytics);
        } catch (e) {
            return false;
        }
    };
</script>
