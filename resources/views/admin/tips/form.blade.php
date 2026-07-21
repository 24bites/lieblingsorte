@extends('layouts.admin')

@php $isEdit = $tip->exists; @endphp
@section('title', $isEdit ? 'Reisetipp bearbeiten' : 'Reisetipp erstellen')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-forest-900">{{ $isEdit ? 'Reisetipp bearbeiten: '.$tip->title : 'Neuen Reisetipp erstellen' }}</h1>
        @if ($isEdit)
            <a href="{{ route('admin.tips.preview', $tip) }}" target="_blank" class="text-sm font-semibold text-forest-600 hover:text-forest-900">Vorschau ansehen &rarr;</a>
        @endif
    </div>

    <form action="{{ $isEdit ? route('admin.tips.update', $tip) : route('admin.tips.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8 max-w-4xl">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-5">
            <h2 class="font-semibold text-forest-900">Grunddaten</h2>

            <div class="grid sm:grid-cols-2 gap-5">
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Region *</label>
                    <select name="region_id" required class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                        <option value="">Bitte wählen</option>
                        @foreach ($regions as $region)
                            <option value="{{ $region->id }}" @selected(old('region_id', $tip->region_id) == $region->id)>{{ $region->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Titel *</label>
                    <input type="text" name="title" value="{{ old('title', $tip->title) }}" required class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Slug (URL)</label>
                    <input type="text" name="slug" value="{{ old('slug', $tip->slug) }}" placeholder="wird automatisch erzeugt" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Ort</label>
                    <input type="text" name="location_name" value="{{ old('location_name', $tip->location_name) }}" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Adresse</label>
                    <input type="text" name="address" value="{{ old('address', $tip->address) }}" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-forest-800 mb-1">Kurzbeschreibung *</label>
                <input type="text" name="short_description" value="{{ old('short_description', $tip->short_description) }}" required maxlength="255" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-forest-800 mb-1">Ausführliche Beschreibung *</label>
                <textarea name="description" rows="6" required class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">{{ old('description', $tip->description) }}</textarea>
            </div>
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-3" x-data="{ highlights: {{ collect(old('highlights', $tip->highlights ?: ['']))->toJson() }} }">
            <h2 class="font-semibold text-forest-900">Highlights</h2>
            <template x-for="(highlight, index) in highlights" :key="index">
                <div class="flex gap-2">
                    <input type="text" :name="'highlights[' + index + ']'" x-model="highlights[index]" class="flex-1 rounded-xl border border-sand-300 px-4 py-2 text-sm" placeholder="Highlight">
                    <button type="button" @click="highlights.splice(index, 1)" class="text-red-500 px-2">✕</button>
                </div>
            </template>
            <button type="button" @click="highlights.push('')" class="text-sm font-medium text-forest-600 hover:text-forest-900">+ Highlight hinzufügen</button>
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-5">
            <h2 class="font-semibold text-forest-900">Details</h2>
            <div class="grid sm:grid-cols-3 gap-5">
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Dauer</label>
                    <input type="text" name="duration" value="{{ old('duration', $tip->duration) }}" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Schwierigkeit</label>
                    <select name="difficulty" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                        <option value="">–</option>
                        @foreach (['leicht', 'mittel', 'anspruchsvoll'] as $level)
                            <option value="{{ $level }}" @selected(old('difficulty', $tip->difficulty) === $level)>{{ ucfirst($level) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Beste Jahreszeit</label>
                    <input type="text" name="best_season" value="{{ old('best_season', $tip->best_season) }}" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Preisangabe</label>
                    <input type="text" name="price_information" value="{{ old('price_information', $tip->price_information) }}" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Öffnungszeiten</label>
                    <input type="text" name="opening_hours" value="{{ old('opening_hours', $tip->opening_hours) }}" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Parkmöglichkeiten</label>
                    <input type="text" name="parking_information" value="{{ old('parking_information', $tip->parking_information) }}" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Website</label>
                    <input type="url" name="website_url" value="{{ old('website_url', $tip->website_url) }}" placeholder="https://" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Telefon</label>
                    <input type="text" name="phone" value="{{ old('phone', $tip->phone) }}" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">E-Mail</label>
                    <input type="email" name="email" value="{{ old('email', $tip->email) }}" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Bewertung (0–5)</label>
                    <input type="number" step="0.1" min="0" max="5" name="rating" value="{{ old('rating', $tip->rating) }}" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Breitengrad</label>
                    <input type="number" step="any" name="latitude" value="{{ old('latitude', $tip->latitude) }}" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-forest-800 mb-1">Längengrad</label>
                    <input type="number" step="any" name="longitude" value="{{ old('longitude', $tip->longitude) }}" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-forest-800 mb-1">Anfahrt</label>
                <textarea name="arrival_information" rows="2" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">{{ old('arrival_information', $tip->arrival_information) }}</textarea>
            </div>

            <div class="grid sm:grid-cols-3 gap-3 pt-2">
                @php
                    $checkboxes = [
                        'family_friendly' => 'Familienfreundlich',
                        'stroller_friendly' => 'Kinderwagengeeignet',
                        'dog_friendly' => 'Hunde erlaubt',
                        'indoor' => 'Indoor',
                        'free_entry' => 'Kostenloser Eintritt',
                        'featured' => 'Hervorgehoben (Startseite)',
                    ];
                @endphp
                @foreach ($checkboxes as $field => $labelText)
                    <label class="flex items-center gap-2 text-sm bg-sand-50 rounded-xl px-3 py-2 cursor-pointer">
                        <input type="checkbox" name="{{ $field }}" value="1" @checked(old($field, $tip->$field)) class="rounded text-forest-600">
                        {{ $labelText }}
                    </label>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-4">
            <h2 class="font-semibold text-forest-900">Kategorien</h2>
            <div class="flex flex-wrap gap-3">
                @foreach ($categories as $category)
                    <label class="flex items-center gap-2 text-sm bg-sand-50 rounded-full px-3 py-1.5 cursor-pointer">
                        <input type="checkbox" name="categories[]" value="{{ $category->id }}" @checked($tip->relationLoaded('categories') && $tip->categories->contains($category->id)) class="rounded text-forest-600">
                        {{ $category->name }}
                    </label>
                @endforeach
            </div>

            <h2 class="font-semibold text-forest-900 pt-2">Labels</h2>
            <div class="flex flex-wrap gap-3">
                @foreach ($labels as $label)
                    <label class="flex items-center gap-2 text-sm bg-sand-50 rounded-full px-3 py-1.5 cursor-pointer">
                        <input type="checkbox" name="labels[]" value="{{ $label->id }}" @checked($tip->relationLoaded('labels') && $tip->labels->contains($label->id)) class="rounded text-forest-600">
                        {{ $label->name }}
                    </label>
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-5">
            <h2 class="font-semibold text-forest-900">Bilder</h2>

            @if ($isEdit && $tip->media->isNotEmpty())
                <div class="grid grid-cols-3 sm:grid-cols-5 gap-3">
                    @foreach ($tip->media as $media)
                        <div class="relative rounded-xl overflow-hidden ring-2 {{ $media->is_cover ? 'ring-forest-600' : 'ring-transparent' }}">
                            <img src="{{ $media->url }}" alt="{{ $media->alt_text }}" class="w-full h-24 object-cover">
                            @if ($media->is_cover)
                                <span class="absolute top-1 left-1 bg-forest-700 text-white text-[10px] px-1.5 py-0.5 rounded">Titelbild</span>
                            @endif
                            <div class="absolute bottom-1 right-1 flex gap-1">
                                @unless ($media->is_cover)
                                    <form action="{{ route('admin.media.cover', $media) }}" method="POST">@csrf @method('PATCH')<button class="bg-white/90 rounded px-1 text-[10px]" title="Als Titelbild">★</button></form>
                                @endunless
                                <form action="{{ route('admin.media.up', $media) }}" method="POST">@csrf @method('PATCH')<button class="bg-white/90 rounded px-1 text-[10px]" title="Nach oben">↑</button></form>
                                <form action="{{ route('admin.media.down', $media) }}" method="POST">@csrf @method('PATCH')<button class="bg-white/90 rounded px-1 text-[10px]" title="Nach unten">↓</button></form>
                                <form action="{{ route('admin.media.destroy', $media) }}" method="POST" onsubmit="return confirm('Bild löschen?');">@csrf @method('DELETE')<button class="bg-white/90 rounded px-1 text-[10px] text-red-600" title="Löschen">✕</button></form>
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
                <input type="text" name="seo_title" value="{{ old('seo_title', $tip->seo_title) }}" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-forest-800 mb-1">SEO-Beschreibung</label>
                <textarea name="seo_description" rows="2" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">{{ old('seo_description', $tip->seo_description) }}</textarea>
            </div>
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 flex items-center justify-between">
            <label class="flex items-center gap-2 text-sm font-medium">
                <input type="checkbox" name="is_published" value="1" @checked(old('is_published', $tip->is_published ?? true)) class="rounded text-forest-600">
                Veröffentlicht
            </label>
            <div class="flex items-center gap-2">
                <label class="text-sm text-forest-500">Reihenfolge</label>
                <input type="number" name="sort_order" value="{{ old('sort_order', $tip->sort_order ?? 0) }}" class="w-20 rounded-xl border border-sand-300 px-3 py-2 text-sm">
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white font-semibold px-6 py-3 text-sm">{{ $isEdit ? 'Änderungen speichern' : 'Reisetipp erstellen' }}</button>
            <a href="{{ route('admin.tips.index') }}" class="rounded-xl border border-sand-300 hover:bg-sand-100 text-forest-700 font-semibold px-6 py-3 text-sm">Abbrechen</a>
        </div>
    </form>

    @if ($isEdit && \App\Support\OpenAiImageGenerator::isConfigured())
        <div class="max-w-4xl mt-8 bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-2">
            <h2 class="font-semibold text-forest-900">Bild mit KI generieren (OpenAI)</h2>
            <form action="{{ route('admin.tips.ai-image', $tip) }}" method="POST" class="space-y-2">
                @csrf
                <textarea name="ai_prompt" rows="2" placeholder="z. B. Foto von {{ $tip->title }} in {{ $tip->region->name ?? '' }}, professionelle Reisefotografie, natürliches Licht" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">{{ old('ai_prompt') }}</textarea>
                @error('ai_prompt') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                <button type="submit" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold px-4 py-2">Mit KI generieren</button>
            </form>
        </div>
    @endif
@endsection
