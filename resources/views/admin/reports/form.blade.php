@extends('layouts.admin')

@php $isEdit = $report->exists; @endphp
@section('title', $isEdit ? 'Reisebericht bearbeiten' : 'Reisebericht erstellen')

@push('styles')
    <link rel="stylesheet" href="https://unpkg.com/quill@2.0.3/dist/quill.snow.css">
    <style>
        #quill-toolbar { border-radius: 0.75rem 0.75rem 0 0; border-color: #e2cfa8; }
        #quill-editor { border-radius: 0 0 0.75rem 0.75rem; border-color: #e2cfa8; font-size: 0.95rem; }
        #quill-editor .ql-editor h2 { font-size: 1.25rem; font-weight: 600; }
        #quill-editor .ql-editor h3 { font-size: 1.1rem; font-weight: 600; }
    </style>
@endpush

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-forest-900">{{ $isEdit ? 'Reisebericht bearbeiten: '.$report->title : 'Neuen Reisebericht erstellen' }}</h1>
        @if ($isEdit)
            <a href="{{ route('admin.reports.preview', $report) }}" target="_blank" class="text-sm font-semibold text-forest-600 hover:text-forest-900">Vorschau ansehen &rarr;</a>
        @endif
    </div>

    @if (session('imageSuggestions') || session('internalLinkSuggestions'))
        <div class="max-w-4xl mb-8 bg-forest-50 ring-1 ring-forest-200 rounded-2xl p-6 space-y-4 text-sm">
            <h2 class="font-semibold text-forest-900">Vorschläge der KI (nur diese Anzeige, nicht gespeichert)</h2>
            @if (session('imageSuggestions'))
                <div>
                    <p class="font-medium text-forest-800 mb-1">Bildmotive</p>
                    <ul class="list-disc list-inside text-forest-700 space-y-0.5">
                        @foreach (session('imageSuggestions') as $suggestion)
                            <li>{{ $suggestion }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if (session('internalLinkSuggestions'))
                <div>
                    <p class="font-medium text-forest-800 mb-1">Interne Verlinkungen</p>
                    <ul class="list-disc list-inside text-forest-700 space-y-0.5">
                        @foreach (session('internalLinkSuggestions') as $suggestion)
                            <li>{{ $suggestion }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    @endif

    @unless ($isEdit)
        @if (\App\Support\TravelReportWriter::isConfigured())
            <div class="max-w-4xl mb-8 bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-3">
                <h2 class="font-semibold text-forest-900">Ganzen Bericht mit KI vorschlagen ({{ ucfirst(\App\Support\TravelReportWriter::provider() === 'claude' ? 'Claude' : 'OpenAI') }})</h2>
                <p class="text-sm text-forest-500">
                    Erstellt aus einem Thema einen vollständigen Entwurf &ndash; Titel, Teaser, vollständigen
                    Artikeltext, FAQ und SEO-Felder &ndash; und legt ihn unveröffentlicht an, damit du ihn direkt
                    danach prüfen und anpassen kannst.
                </p>
                <form action="{{ route('admin.reports.ai-draft') }}" method="POST" class="space-y-2">
                    @csrf
                    <input type="text" name="ai_topic" required placeholder="Thema, z. B. Südtirol als Reiseziel" value="{{ old('ai_topic') }}" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                    @error('ai_topic') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    <textarea name="ai_context" rows="2" placeholder="Optionaler Kontext, z. B. Schwerpunkte, Zielgruppe" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">{{ old('ai_context') }}</textarea>
                    <input type="text" name="ai_author_name" placeholder="Autor/in (optional, Standard: {{ auth()->user()->name }})" value="{{ old('ai_author_name') }}" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                    <button type="submit" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold px-4 py-2">Entwurf erstellen</button>
                </form>
            </div>
        @else
            <div class="max-w-4xl mb-8 bg-amber-50 ring-1 ring-amber-300 text-amber-900 rounded-2xl p-4 text-sm">
                Kein API-Key für den gewählten KI-Anbieter hinterlegt &ndash; ein KI-Entwurf kann nicht erstellt werden. Key unter
                <a href="{{ route('admin.settings.edit') }}" class="underline font-medium">Einstellungen</a> hinterlegen,
                oder den Bericht unten manuell anlegen.
            </div>
        @endif

        <div class="max-w-4xl mb-6 flex items-center gap-3 text-sm text-forest-400">
            <div class="flex-1 border-t border-sand-200"></div>
            oder manuell ausfüllen
            <div class="flex-1 border-t border-sand-200"></div>
        </div>
    @endunless

    <form id="report-form" action="{{ $isEdit ? route('admin.reports.update', $report) : route('admin.reports.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8 max-w-4xl">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-5">
            <h2 class="font-semibold text-forest-900">Grunddaten</h2>

            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Titel *</label>
                    <input type="text" name="title" value="{{ old('title', $report->title) }}" required class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Slug (URL)</label>
                    <input type="text" name="slug" value="{{ old('slug', $report->slug) }}" placeholder="wird automatisch erzeugt" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Zugehörige Region (optional)</label>
                    <select name="region_id" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                        <option value="">Keine</option>
                        @foreach ($regions as $region)
                            <option value="{{ $region->id }}" @selected(old('region_id', $report->region_id) == $region->id)>{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Autor/in *</label>
                    <input type="text" name="author_name" value="{{ old('author_name', $report->author_name) }}" required class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-sm font-medium text-forest-800 mb-1">Kurzinfo zur Autorin/zum Autor</label>
                    <input type="text" name="author_bio" value="{{ old('author_bio', $report->author_bio) }}" placeholder="z. B. Reist am liebsten mit dem Rucksack durch Südeuropa" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-forest-800 mb-1">Kurzbeschreibung (Teaser) *</label>
                <input type="text" name="excerpt" value="{{ old('excerpt', $report->excerpt) }}" required maxlength="255" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                <p class="text-xs text-forest-500 mt-1">Erscheint auf der Übersichtsseite und dient als Fallback für die SEO-Beschreibung.</p>
            </div>
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-3" x-data="reportEditor()">
            <h2 class="font-semibold text-forest-900">Inhalt *</h2>
            <p class="text-sm text-forest-500">
                Formatierung wie in einem Textverarbeitungsprogramm. Die beiden Zusatz-Buttons rechts fügen einen
                Affiliate-Link (mit korrektem <code>rel="sponsored"</code>) bzw. einen beliebigen HTML-Block ein
                (z. B. ein Werbe-/Partner-Snippet oder eine Tabelle).
            </p>

            <div class="flex items-center justify-between flex-wrap gap-2">
                <div id="quill-toolbar" class="flex-1 min-w-[280px]">
                    <span class="ql-formats">
                        <select class="ql-header">
                            <option value="2">Überschrift 2</option>
                            <option value="3">Überschrift 3</option>
                            <option selected></option>
                        </select>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-bold"></button>
                        <button class="ql-italic"></button>
                        <button class="ql-underline"></button>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-list" value="ordered"></button>
                        <button class="ql-list" value="bullet"></button>
                        <button class="ql-blockquote"></button>
                        <button class="ql-link"></button>
                    </span>
                    <span class="ql-formats">
                        <button class="ql-clean"></button>
                    </span>
                </div>
                <div class="flex gap-2">
                    <button type="button" @click="openAffiliateLinkModal()" class="rounded-lg ring-1 ring-sand-300 hover:bg-sand-100 text-forest-700 text-xs font-semibold px-3 py-1.5 whitespace-nowrap">+ Affiliate-Link</button>
                    <button type="button" @click="htmlBlockOpen = true" class="rounded-lg ring-1 ring-sand-300 hover:bg-sand-100 text-forest-700 text-xs font-semibold px-3 py-1.5 whitespace-nowrap">+ HTML-Block</button>
                </div>
            </div>

            <div id="quill-editor" style="min-height: 420px;"></div>
            <textarea name="content" id="content-input" required class="hidden">{{ old('content', $report->content) }}</textarea>

            <div x-show="htmlBlockOpen" x-cloak class="fixed inset-0 bg-black/40 z-50 flex items-center justify-center p-4">
                <div class="bg-white rounded-2xl p-6 max-w-lg w-full space-y-3" @click.outside="htmlBlockOpen = false">
                    <h3 class="font-semibold text-forest-900">HTML-Block einfügen</h3>
                    <p class="text-xs text-forest-500">Z. B. ein Affiliate-Werbeblock, ein Buchungs-Widget oder eine vorbereitete Tabelle.</p>
                    <textarea x-model="htmlBlockValue" rows="8" class="w-full rounded-xl border border-sand-300 px-3 py-2 text-xs font-mono" placeholder="<div>...</div>"></textarea>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="htmlBlockOpen = false" class="rounded-xl px-4 py-2 text-sm text-forest-600">Abbrechen</button>
                        <button type="button" @click="insertHtmlBlock()" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold px-4 py-2">Einfügen</button>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-4" x-data="{ faqs: {{ old('faq') ? json_encode(old('faq')) : json_encode($report->faq ?? []) }} }">
            <div>
                <h2 class="font-semibold text-forest-900">Häufig gestellte Fragen (FAQ)</h2>
                <p class="text-sm text-forest-500">Erscheinen auf der Seite als eigener Abschnitt und als FAQ-Schema.org-Markup für Suchmaschinen.</p>
            </div>

            <template x-for="(item, index) in faqs" :key="index">
                <div class="rounded-xl border border-sand-200 p-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="text-xs font-medium text-forest-800">Frage</label>
                        <button type="button" @click="faqs.splice(index, 1)" class="text-xs text-red-600">Entfernen</button>
                    </div>
                    <input type="text" :name="'faq['+index+'][question]'" x-model="item.question" class="w-full rounded-xl border border-sand-300 px-3 py-2 text-sm">
                    <label class="block text-xs font-medium text-forest-800">Antwort</label>
                    <textarea :name="'faq['+index+'][answer]'" x-model="item.answer" rows="2" class="w-full rounded-xl border border-sand-300 px-3 py-2 text-sm"></textarea>
                </div>
            </template>

            <button type="button" @click="faqs.push({question: '', answer: ''})" class="rounded-xl ring-1 ring-sand-300 hover:bg-sand-100 text-forest-700 text-sm font-medium px-4 py-2">+ Frage hinzufügen</button>
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-5">
            <h2 class="font-semibold text-forest-900">Bilder</h2>

            @if ($isEdit && $report->media->isNotEmpty())
                <div class="grid grid-cols-3 sm:grid-cols-5 gap-3">
                    @foreach ($report->media as $media)
                        <div class="relative rounded-xl overflow-hidden ring-2 {{ $media->is_cover ? 'ring-forest-600' : 'ring-transparent' }}">
                            <img src="{{ $media->url }}" alt="{{ $media->alt_text }}" class="w-full h-24 object-cover">
                            @if ($media->is_cover)
                                <span class="absolute top-1 left-1 bg-forest-700 text-white text-[10px] px-1.5 py-0.5 rounded">Titelbild</span>
                            @endif
                            <div class="absolute bottom-1 right-1 flex gap-1">
                                @unless ($media->is_cover)
                                    <button type="submit" form="media-cover-{{ $media->id }}" class="bg-white/90 rounded px-1 text-[10px]" title="Als Titelbild">★</button>
                                @endunless
                                <button type="submit" form="media-up-{{ $media->id }}" class="bg-white/90 rounded px-1 text-[10px]" title="Nach oben">↑</button>
                                <button type="submit" form="media-down-{{ $media->id }}" class="bg-white/90 rounded px-1 text-[10px]" title="Nach unten">↓</button>
                                <button type="submit" form="media-destroy-{{ $media->id }}" class="bg-white/90 rounded px-1 text-[10px] text-red-600" title="Löschen">✕</button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-forest-800 mb-1">Neues Titelbild hochladen</label>
                <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp" class="w-full text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-forest-800 mb-1">Galerie-Bilder hinzufügen</label>
                <input type="file" name="gallery_images[]" accept="image/jpeg,image/png,image/webp" multiple class="w-full text-sm">
            </div>

            @if ($isEdit && ! \App\Support\OpenAiImageGenerator::isConfigured())
                <p class="text-xs text-forest-500 border-t border-sand-200 pt-4">
                    Hinweis: Trage einen OpenAI-API-Key unter
                    <a href="{{ route('admin.settings.edit') }}" class="underline">Einstellungen</a> ein, um Bilder per KI zu generieren.
                </p>
            @endif
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-5">
            <h2 class="font-semibold text-forest-900">SEO</h2>
            <div>
                <label class="block text-sm font-medium text-forest-800 mb-1">SEO-Titel</label>
                <input type="text" name="seo_title" value="{{ old('seo_title', $report->seo_title) }}" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-forest-800 mb-1">SEO-Beschreibung (Meta-Description, max. 155 Zeichen)</label>
                <textarea name="seo_description" rows="2" maxlength="255" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">{{ old('seo_description', $report->seo_description) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-forest-800 mb-1">OpenGraph-Beschreibung (Social-Media-Vorschau)</label>
                <textarea name="og_description" rows="2" maxlength="500" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">{{ old('og_description', $report->og_description) }}</textarea>
                <p class="text-xs text-forest-500 mt-1">Leer lassen, um stattdessen die SEO-Beschreibung zu verwenden.</p>
                @error('og_description')
                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6">
            <label class="flex items-center gap-2 text-sm font-medium">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $report->is_published ?? true)) class="rounded text-forest-600">
                Veröffentlicht
            </label>
            @if ($isEdit && $report->published_at)
                <p class="text-xs text-forest-500 mt-2">Erstveröffentlicht am {{ $report->published_at->format('d.m.Y') }}.</p>
            @endif
        </div>

        <div class="flex gap-3">
            <button type="submit" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white font-semibold px-6 py-3 text-sm">{{ $isEdit ? 'Änderungen speichern' : 'Reisebericht erstellen' }}</button>
            <a href="{{ route('admin.reports.index') }}" class="rounded-xl border border-sand-300 hover:bg-sand-100 text-forest-700 font-semibold px-6 py-3 text-sm">Abbrechen</a>
        </div>
    </form>

    @if ($isEdit)
        @foreach ($report->media as $media)
            @unless ($media->is_cover)
                <form id="media-cover-{{ $media->id }}" action="{{ route('admin.media.cover', $media) }}" method="POST" class="hidden">@csrf @method('PATCH')</form>
            @endunless
            <form id="media-up-{{ $media->id }}" action="{{ route('admin.media.up', $media) }}" method="POST" class="hidden">@csrf @method('PATCH')</form>
            <form id="media-down-{{ $media->id }}" action="{{ route('admin.media.down', $media) }}" method="POST" class="hidden">@csrf @method('PATCH')</form>
            <form id="media-destroy-{{ $media->id }}" action="{{ route('admin.media.destroy', $media) }}" method="POST" class="hidden" onsubmit="return confirm('Bild löschen?');">@csrf @method('DELETE')</form>
        @endforeach
    @endif

    @if ($isEdit && \App\Support\TravelReportWriter::isConfigured())
        <div class="max-w-4xl mt-8 bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-3">
            <h2 class="font-semibold text-forest-900">Text mit KI generieren ({{ ucfirst(\App\Support\TravelReportWriter::provider() === 'claude' ? 'Claude' : 'OpenAI') }})</h2>
            <p class="text-sm text-forest-500">Schreibt den vollständigen Artikeltext neu und ersetzt den Inhalt oben. Danach unbedingt prüfen und bei Bedarf anpassen.</p>
            <form action="{{ route('admin.reports.ai-text', $report) }}" method="POST" class="space-y-2">
                @csrf
                <input type="text" name="ai_topic" required placeholder="Thema, z. B. Ein Wochenende auf Föhr im Winter" value="{{ old('ai_topic', $report->title) }}" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                @error('ai_topic') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <textarea name="ai_context" rows="2" placeholder="Optionaler Kontext, z. B. besuchte Orte, Jahreszeit, besondere Erlebnisse" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">{{ old('ai_context') }}</textarea>
                <button type="submit" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold px-4 py-2">Text generieren</button>
            </form>
        </div>
    @endif

    @if ($isEdit && \App\Support\OpenAiImageGenerator::isConfigured())
        <div class="max-w-4xl mt-8 bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-2">
            <h2 class="font-semibold text-forest-900">Bild mit KI generieren (OpenAI)</h2>
            <form action="{{ route('admin.reports.ai-image', $report) }}" method="POST" class="space-y-2">
                @csrf
                <textarea name="ai_prompt" rows="2" placeholder="z. B. Foto passend zu „{{ $report->title }}“, professionelle Reisefotografie, natürliches Licht" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">{{ old('ai_prompt') }}</textarea>
                @error('ai_prompt') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <button type="submit" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold px-4 py-2">Mit KI generieren</button>
            </form>
        </div>
    @endif
@endsection

@push('scripts')
    <script src="https://unpkg.com/quill@2.0.3/dist/quill.js"></script>
    <script>
        // Quill only preserves markup it has a Delta representation for - a raw,
        // unrecognized block-level tag (e.g. a <div> ad snippet) gets silently
        // discarded the moment Quill re-syncs its model to the DOM. Registering it
        // as an atomic embed blot instead makes Quill treat the whole HTML string
        // as one opaque, always-preserved unit (not editable from the inside, same
        // as an image embed).
        const HtmlBlockBlot = Quill.import('blots/block/embed');
        class ReportHtmlBlock extends HtmlBlockBlot {
            static create(value) {
                const node = super.create();
                node.innerHTML = value;
                node.setAttribute('contenteditable', 'false');
                return node;
            }
            static value(node) {
                return node.innerHTML;
            }
        }
        ReportHtmlBlock.blotName = 'reportHtmlBlock';
        ReportHtmlBlock.tagName = 'div';
        ReportHtmlBlock.className = 'ql-report-html-block';
        Quill.register(ReportHtmlBlock);

        function reportEditor() {
            return {
                quill: null,
                htmlBlockOpen: false,
                htmlBlockValue: '',
                init() {
                    const input = document.getElementById('content-input');

                    this.quill = new Quill(this.$el.querySelector('#quill-editor'), {
                        theme: 'snow',
                        modules: { toolbar: '#quill-toolbar' },
                    });
                    this.quill.clipboard.dangerouslyPasteHTML(input.value);

                    // The hidden textarea is CSS-hidden, not the `hidden` attribute -
                    // a required, display:none field silently blocks native form
                    // submission before any 'submit' listener runs, so syncing only
                    // at submit time is too late. Keep it live on every edit instead.
                    const sync = () => { input.value = this.quill.root.innerHTML; };
                    this.quill.on('text-change', sync);
                    sync();
                },
                openAffiliateLinkModal() {
                    const text = window.prompt('Anzeigetext des Links:');
                    if (!text) return;
                    const url = window.prompt('Ziel-URL (inkl. https://):');
                    if (!url) return;

                    const range = this.quill.getSelection(true);
                    const anchor = document.createElement('a');
                    anchor.href = url;
                    anchor.target = '_blank';
                    anchor.rel = 'sponsored noopener';
                    anchor.textContent = text;
                    this.quill.clipboard.dangerouslyPasteHTML(range.index, anchor.outerHTML);
                },
                insertHtmlBlock() {
                    if (!this.htmlBlockValue.trim()) {
                        this.htmlBlockOpen = false;
                        return;
                    }
                    const range = this.quill.getSelection(true) || { index: this.quill.getLength() };
                    this.quill.insertEmbed(range.index, 'reportHtmlBlock', this.htmlBlockValue, 'user');
                    this.quill.setSelection(range.index + 1, 0);
                    this.htmlBlockValue = '';
                    this.htmlBlockOpen = false;
                },
            };
        }
    </script>
@endpush
