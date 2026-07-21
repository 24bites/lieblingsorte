@extends('layouts.admin')

@php $isEdit = $region->exists; @endphp
@section('title', $isEdit ? 'Region bearbeiten' : 'Region erstellen')

@section('content')
    <h1 class="text-2xl font-semibold text-forest-900 mb-6">{{ $isEdit ? 'Region bearbeiten: '.$region->name : 'Neue Region erstellen' }}</h1>

    <form action="{{ $isEdit ? route('admin.regions.update', $region) : route('admin.regions.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8 max-w-4xl">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-5">
            <h2 class="font-semibold text-forest-900">Grunddaten</h2>

            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Name *</label>
                    <input type="text" name="name" value="{{ old('name', $region->name) }}" required class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Slug (URL)</label>
                    <input type="text" name="slug" value="{{ old('slug', $region->slug) }}" placeholder="wird automatisch erzeugt" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Typ *</label>
                    <select name="type" required class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                        @foreach (['Region', 'Stadt', 'Insel', 'Reisegebiet'] as $type)
                            <option value="{{ $type }}" @selected(old('type', $region->type) === $type)>{{ $type }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Land *</label>
                    <input type="text" name="country" value="{{ old('country', $region->country) }}" required class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Bundesland</label>
                    <input type="text" name="federal_state" value="{{ old('federal_state', $region->federal_state) }}" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Beste Reisezeit</label>
                    <input type="text" name="best_travel_time" value="{{ old('best_travel_time', $region->best_travel_time) }}" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-forest-800 mb-1">Kurzbeschreibung *</label>
                <input type="text" name="short_description" value="{{ old('short_description', $region->short_description) }}" required maxlength="255" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-forest-800 mb-1">Ausführliche Beschreibung *</label>
                <textarea name="description" rows="6" required class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">{{ old('description', $region->description) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-medium text-forest-800 mb-1">Anreiseinformationen</label>
                <textarea name="arrival_information" rows="3" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">{{ old('arrival_information', $region->arrival_information) }}</textarea>
            </div>

            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Breitengrad</label>
                    <input type="number" step="any" name="latitude" value="{{ old('latitude', $region->latitude) }}" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Längengrad</label>
                    <input type="number" step="any" name="longitude" value="{{ old('longitude', $region->longitude) }}" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-4">
            <h2 class="font-semibold text-forest-900">Labels</h2>
            <div class="flex flex-wrap gap-3">
                @foreach ($labels as $label)
                    <label class="flex items-center gap-2 text-sm bg-sand-50 rounded-full px-3 py-1.5 cursor-pointer">
                        <input type="checkbox" name="labels[]" value="{{ $label->id }}" @checked($region->relationLoaded('labels') && $region->labels->contains($label->id)) class="rounded text-forest-600">
                        {{ $label->name }}
                    </label>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-5">
            <h2 class="font-semibold text-forest-900">Bilder</h2>

            @if ($isEdit && $region->media->isNotEmpty())
                <div class="grid grid-cols-3 sm:grid-cols-5 gap-3">
                    @foreach ($region->media as $media)
                        <div class="relative rounded-xl overflow-hidden ring-2 {{ $media->is_cover ? 'ring-forest-600' : 'ring-transparent' }}">
                            <img src="{{ $media->url }}" alt="{{ $media->alt_text }}" class="w-full h-24 object-cover">
                            @if ($media->is_cover)
                                <span class="absolute top-1 left-1 bg-forest-700 text-white text-[10px] px-1.5 py-0.5 rounded">Titelbild</span>
                            @endif
                            <div class="absolute bottom-1 right-1 flex gap-1">
                                @unless ($media->is_cover)
                                    <form action="{{ route('admin.media.cover', $media) }}" method="POST">@csrf @method('PATCH')<button class="bg-white/90 rounded px-1 text-[10px]" title="Als Titelbild">★</button></form>
                                @endunless
                                <form action="{{ route('admin.media.destroy', $media) }}" method="POST" onsubmit="return confirm('Bild löschen?');">@csrf @method('DELETE')<button class="bg-white/90 rounded px-1 text-[10px] text-red-600" title="Löschen">✕</button></form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            <div>
                <label class="block text-sm font-medium text-forest-800 mb-1">Neues Titelbild hochladen</label>
                <input type="file" name="hero_image" accept="image/jpeg,image/png,image/webp" class="w-full text-sm">
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
                <input type="text" name="seo_title" value="{{ old('seo_title', $region->seo_title) }}" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-forest-800 mb-1">SEO-Beschreibung</label>
                <textarea name="seo_description" rows="2" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">{{ old('seo_description', $region->seo_description) }}</textarea>
            </div>
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm font-medium">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $region->is_published ?? true)) class="rounded text-forest-600">
                Veröffentlicht
            </label>
            <div class="flex items-center gap-2">
                <label class="text-sm text-forest-500">Reihenfolge</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $region->sort_order ?? 0) }}" class="w-20 rounded-xl border border-sand-300 px-3 py-2 text-sm">
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white font-semibold px-6 py-3 text-sm">{{ $isEdit ? 'Änderungen speichern' : 'Region erstellen' }}</button>
            <a href="{{ route('admin.regions.index') }}" class="rounded-xl border border-sand-300 hover:bg-sand-100 text-forest-700 font-semibold px-6 py-3 text-sm">Abbrechen</a>
        </div>
    </form>

    @if ($isEdit && \App\Support\OpenAiImageGenerator::isConfigured())
        <div class="max-w-4xl mt-8 bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-2">
            <h2 class="font-semibold text-forest-900">Bild mit KI generieren (OpenAI)</h2>
            <form action="{{ route('admin.regions.ai-image', $region) }}" method="POST" class="space-y-2">
                @csrf
                <textarea name="ai_prompt" rows="2" placeholder="z. B. Landschaftsfoto von {{ $region->name }}, {{ $region->country }}, professionelle Reisefotografie, natürliches Licht" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">{{ old('ai_prompt') }}</textarea>
                @error('ai_prompt') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <button type="submit" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold px-4 py-2">Mit KI generieren</button>
            </form>
        </div>
    @endif
@endsection
