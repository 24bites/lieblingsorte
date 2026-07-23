@extends('layouts.admin')

@section('title', 'Pinterest-Feed-Kuration')

@php
    $typeLabels = ['region' => 'Regionen', 'tip' => 'Reisetipps'];
@endphp

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-forest-900">Pinterest-Feed-Kuration</h1>
            <p class="text-sm text-forest-500 mt-1">
                Regionen und Reisetipps gezielt oben in den Pinterest-Feed heben, unabhängig vom Aktualisierungsdatum.
                Der Rest des Feeds füllt sich automatisch mit den zuletzt aktualisierten veröffentlichten Einträgen.
            </p>
        </div>
        <a href="{{ route('admin.social-hub.index') }}" class="text-sm font-semibold text-forest-700 hover:text-forest-900">← Zurück zum Social Hub</a>
    </div>

    @if (session('status'))
        <div class="bg-forest-50 ring-1 ring-forest-200 text-forest-800 rounded-2xl p-4 mb-6 text-sm">{{ session('status') }}</div>
    @endif

    <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 mb-8">
        <h2 class="font-semibold text-forest-900 mb-4">Aktuell im Feed hervorgehoben ({{ $featured->count() }})</h2>
        @forelse ($featured as $feature)
            @php $item = $feature->featurable; @endphp
            <div class="flex items-center justify-between gap-4 py-2.5 border-b border-sand-100 last:border-0">
                <div>
                    <p class="font-medium text-forest-900">{{ $item?->name ?? $item?->title ?? 'Gelöschter Eintrag' }}</p>
                    <p class="text-xs text-forest-400">{{ $item instanceof \App\Models\Region ? 'Region' : 'Reisetipp' }}</p>
                </div>
                <div class="flex items-center gap-1">
                    <form action="{{ route('admin.pinterest-feed-curation.up', $feature) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" class="rounded-lg px-2 py-1.5 text-forest-500 hover:bg-sand-100" title="Nach oben">↑</button>
                    </form>
                    <form action="{{ route('admin.pinterest-feed-curation.down', $feature) }}" method="POST">
                        @csrf @method('PATCH')
                        <button type="submit" class="rounded-lg px-2 py-1.5 text-forest-500 hover:bg-sand-100" title="Nach unten">↓</button>
                    </form>
                    <form action="{{ route('admin.pinterest-feed-curation.destroy', $feature) }}" method="POST">
                        @csrf @method('DELETE')
                        <button type="submit" class="rounded-lg px-2 py-1.5 text-red-600 hover:bg-red-50" title="Entfernen">Entfernen</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="text-sm text-forest-400">Noch nichts manuell hervorgehoben &ndash; der Feed füllt sich rein automatisch.</p>
        @endforelse
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex gap-2">
            @foreach ($typeLabels as $key => $label)
                <a href="{{ route('admin.pinterest-feed-curation.index', ['type' => $key]) }}"
                    class="rounded-xl px-4 py-2 text-sm font-medium {{ $type === $key ? 'bg-forest-700 text-white' : 'bg-white ring-1 ring-sand-200 text-forest-700 hover:bg-sand-100' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
        <form method="GET" class="flex gap-2">
            <input type="hidden" name="type" value="{{ $type }}">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Suchen…" class="rounded-xl border border-sand-300 py-2 px-4 text-sm w-56">
            <button type="submit" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold px-4 py-2">Suchen</button>
        </form>
    </div>

    <div class="space-y-3">
        @forelse ($items as $item)
            @php
                $title = $item->name ?? $item->title;
                $isFeatured = $featuredKeys->contains($item::class.':'.$item->id);
            @endphp
            <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-5 flex items-center justify-between gap-4 flex-wrap">
                <div>
                    <h2 class="font-semibold text-forest-900">{{ $title }}</h2>
                    <p class="text-xs text-forest-400">Aktualisiert am {{ $item->updated_at->format('d.m.Y') }}</p>
                </div>
                @if ($isFeatured)
                    <span class="rounded-full px-3 py-1.5 text-xs font-medium bg-forest-100 text-forest-700 ring-1 ring-forest-200">Im Feed hervorgehoben</span>
                @else
                    <form action="{{ route('admin.pinterest-feed-curation.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="type" value="{{ $type }}">
                        <input type="hidden" name="id" value="{{ $item->id }}">
                        <button type="submit" class="rounded-full px-3 py-1.5 text-xs font-medium bg-white ring-1 ring-sand-300 text-forest-600 hover:bg-sand-100">
                            + Zum Feed hinzufügen
                        </button>
                    </form>
                @endif
            </div>
        @empty
            <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-8 text-center text-forest-400">
                Keine veröffentlichten {{ $typeLabels[$type] }} gefunden.
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $items->links() }}</div>
@endsection
