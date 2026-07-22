@extends('layouts.admin')

@php $isEdit = $report->exists; @endphp
@section('title', $isEdit ? 'Reisebericht bearbeiten' : 'Reisebericht erstellen')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-forest-900">{{ $isEdit ? 'Reisebericht bearbeiten: '.$report->title : 'Neuen Reisebericht erstellen' }}</h1>
        @if ($isEdit)
            <a href="{{ route('admin.reports.preview', $report) }}" target="_blank" class="text-sm font-semibold text-forest-600 hover:text-forest-900">Vorschau ansehen &rarr;</a>
        @endif
    </div>

    @unless ($isEdit)
        @if (\App\Support\OpenAiReportWriter::isConfigured())
            <div class="max-w-4xl mb-8 bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-3">
                <h2 class="font-semibold text-forest-900">Ganzen Bericht mit KI vorschlagen (OpenAI)</h2>
                <p class="text-sm text-forest-500">
                    Erstellt aus einem Thema einen vollständigen Entwurf &ndash; Titel, Teaser, Text mit
                    Zwischenüberschriften und SEO-Felder &ndash; und legt ihn unveröffentlicht an, damit du ihn direkt
                    danach prüfen und anpassen kannst.
                </p>
                <form action="{{ route('admin.reports.ai-draft') }}" method="POST" class="space-y-2">
                    @csrf
                    <input type="text" name="ai_topic" required placeholder="Thema, z. B. Ein Wochenende auf Föhr im Winter" value="{{ old('ai_topic') }}" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                    @error('ai_topic') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    <textarea name="ai_context" rows="2" placeholder="Optionaler Kontext, z. B. besuchte Orte, Jahreszeit, besondere Erlebnisse" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">{{ old('ai_context') }}</textarea>
                    <input type="text" name="ai_author_name" placeholder="Autor/in (optional, Standard: {{ auth()->user()->name }})" value="{{ old('ai_author_name') }}" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                    <button type="submit" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold px-4 py-2">Entwurf erstellen</button>
                </form>
            </div>
        @else
            <div class="max-w-4xl mb-8 bg-amber-50 ring-1 ring-amber-300 text-amber-900 rounded-2xl p-4 text-sm">
                Kein OpenAI-API-Key hinterlegt &ndash; ein KI-Entwurf kann nicht erstellt werden. Key unter
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

    <form action="{{ $isEdit ? route('admin.reports.update', $report) : route('admin.reports.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8 max-w-4xl">
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

        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-3">
            <h2 class="font-semibold text-forest-900">Inhalt *</h2>
            <p class="text-sm text-forest-500">
                Absätze durch eine Leerzeile trennen. Eine Zeile, die mit <code>## </code> beginnt, wird als Zwischenüberschrift dargestellt.
            </p>
            <textarea name="content" rows="18" required class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm font-mono">{{ old('content', $report->content) }}</textarea>
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
                <label class="block text-sm font-medium text-forest-800 mb-1">SEO-Beschreibung</label>
                <textarea name="seo_description" rows="2" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">{{ old('seo_description', $report->seo_description) }}</textarea>
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

    @if ($isEdit && \App\Support\OpenAiReportWriter::isConfigured())
        <div class="max-w-4xl mt-8 bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-3">
            <h2 class="font-semibold text-forest-900">Text mit KI generieren (OpenAI)</h2>
            <p class="text-sm text-forest-500">Schreibt einen vollständigen, persönlich klingenden Reisebericht und ersetzt den Inhalt oben. Danach unbedingt prüfen und bei Bedarf anpassen.</p>
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
