@extends('layouts.admin')

@section('title', 'Medien')

@section('content')
    <h1 class="text-2xl font-semibold text-forest-900 mb-6">Medienbibliothek</h1>

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        @forelse ($media as $item)
            <div x-data="{ editingCredit: false }" class="relative rounded-xl overflow-hidden ring-1 ring-sand-200 bg-white">
                <img src="{{ $item->url }}" alt="{{ $item->alt_text }}" class="w-full h-28 object-cover">
                <div class="p-2 text-xs text-forest-500">
                    <p class="truncate">{{ $item->mediable->title ?? $item->mediable->name ?? '—' }}</p>
                    <div class="flex items-center justify-between mt-1">
                        <span class="text-forest-700 font-medium">{{ $item->is_cover ? 'Titelbild' : '' }}</span>
                        <button type="button" @click="editingCredit = !editingCredit" class="text-forest-600 hover:text-forest-900 underline">
                            {{ $item->hasCredit() ? 'Quelle' : 'Quelle fehlt' }}
                        </button>
                    </div>
                </div>

                <div x-show="editingCredit" x-cloak class="border-t border-sand-100 p-2">
                    <form action="{{ route('admin.media.credit', $item) }}" method="POST" class="space-y-1.5">
                        @csrf @method('PATCH')
                        <input type="text" name="credit_author" value="{{ old('credit_author', $item->credit_author) }}" placeholder="Autor/in" class="w-full rounded border border-sand-300 px-2 py-1 text-[11px]">
                        <input type="text" name="credit_license" value="{{ old('credit_license', $item->credit_license) }}" placeholder="Lizenz (z. B. CC BY-SA 4.0)" class="w-full rounded border border-sand-300 px-2 py-1 text-[11px]">
                        <input type="text" name="credit_source_title" value="{{ old('credit_source_title', $item->credit_source_title) }}" placeholder="Quelltitel" class="w-full rounded border border-sand-300 px-2 py-1 text-[11px]">
                        <input type="url" name="credit_source_url" value="{{ old('credit_source_url', $item->credit_source_url) }}" placeholder="https://…" class="w-full rounded border border-sand-300 px-2 py-1 text-[11px]">
                        <button type="submit" class="w-full rounded bg-forest-700 hover:bg-forest-800 text-white text-[11px] font-medium py-1">Speichern</button>
                    </form>
                </div>

                <form action="{{ route('admin.media.destroy', $item) }}" method="POST" class="absolute top-1 right-1" onsubmit="return confirm('Bild löschen?');">
                    @csrf @method('DELETE')
                    <button class="bg-white/90 rounded px-1.5 py-0.5 text-[10px] text-red-600">✕</button>
                </form>
            </div>
        @empty
            <p class="text-forest-400 col-span-full text-center py-10">Keine Medien vorhanden.</p>
        @endforelse
    </div>

    <div class="mt-6">{{ $media->links() }}</div>
@endsection
