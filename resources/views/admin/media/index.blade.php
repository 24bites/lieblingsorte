@extends('layouts.admin')

@section('title', 'Medien')

@section('content')
    <h1 class="text-2xl font-semibold text-forest-900 mb-6">Medienbibliothek</h1>

    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-4">
        @forelse ($media as $item)
            <div class="relative rounded-xl overflow-hidden ring-1 ring-sand-200 bg-white">
                <img src="{{ $item->url }}" alt="{{ $item->alt_text }}" class="w-full h-28 object-cover">
                <div class="p-2 text-xs text-forest-500">
                    <p class="truncate">{{ $item->mediable->title ?? $item->mediable->name ?? '—' }}</p>
                    @if ($item->is_cover)
                        <span class="text-forest-700 font-medium">Titelbild</span>
                    @endif
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
