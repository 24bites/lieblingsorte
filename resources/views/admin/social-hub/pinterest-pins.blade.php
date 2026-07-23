@extends('layouts.admin')

@section('title', 'Pinterest-Pins')

@php
    $statusLabels = [
        'draft' => 'Entwurf',
        'approved' => 'Freigegeben',
        'scheduled' => 'Geplant',
        'posted' => 'Veröffentlicht',
        'failed' => 'Fehlgeschlagen',
    ];
    $statusClasses = [
        'draft' => 'bg-sand-100 text-sand-700 ring-sand-200',
        'approved' => 'bg-forest-100 text-forest-700 ring-forest-200',
        'scheduled' => 'bg-blue-50 text-blue-700 ring-blue-200',
        'posted' => 'bg-forest-100 text-forest-700 ring-forest-200',
        'failed' => 'bg-red-50 text-red-700 ring-red-200',
    ];
@endphp

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-forest-900">Pinterest-Pins</h1>
            <p class="text-sm text-forest-500 mt-1">
                Pins mit Text-Overlay-Bild und SEO-Text vorbereiten, prüfen und freigeben. Veröffentlicht wird erst,
                sobald die Pinterest-App verbunden ist.
            </p>
        </div>
        <a href="{{ route('admin.social-hub.index') }}" class="text-sm font-semibold text-forest-700 hover:text-forest-900">← Zurück zum Social Hub</a>
    </div>

    @if (session('status'))
        <div class="bg-forest-50 ring-1 ring-forest-200 text-forest-800 rounded-2xl p-4 mb-6 text-sm">{{ session('status') }}</div>
    @endif
    @error('generate')
        <div class="bg-red-50 ring-1 ring-red-200 text-red-800 rounded-2xl p-4 mb-6 text-sm">{{ $message }}</div>
    @enderror
    @error('approve')
        <div class="bg-red-50 ring-1 ring-red-200 text-red-800 rounded-2xl p-4 mb-6 text-sm">{{ $message }}</div>
    @enderror

    @if (! $openAiConfigured)
        <div class="bg-amber-50 ring-1 ring-amber-300 text-amber-900 rounded-2xl p-4 mb-6 text-sm">
            Kein OpenAI-API-Key hinterlegt &ndash; Pin-Texte können nicht generiert werden. Key unter
            <a href="{{ route('admin.settings.edit') }}" class="underline font-medium">Einstellungen</a> hinterlegen.
        </div>
    @endif

    @unless ($pinterestConfigured)
        <div class="bg-sand-100 ring-1 ring-sand-300 text-sand-700 rounded-2xl p-4 mb-6 text-sm">
            Pinterest-App ist noch nicht verbunden. Pins können bereits vorbereitet und freigegeben werden &ndash;
            die eigentliche Veröffentlichung folgt, sobald die Verbindung eingerichtet ist.
        </div>
    @endunless

    <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 mb-8">
        <h2 class="font-semibold text-forest-900 mb-4">Neuen Pin erzeugen</h2>

        <div class="flex gap-2 mb-4">
            @foreach (['region' => 'Regionen', 'tip' => 'Reisetipps'] as $key => $label)
                <a href="{{ route('admin.pinterest-pins.index', ['type' => $key]) }}"
                    class="rounded-xl px-4 py-2 text-sm font-medium {{ $type === $key ? 'bg-forest-700 text-white' : 'bg-white ring-1 ring-sand-200 text-forest-700 hover:bg-sand-100' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <form method="GET" class="flex gap-2 mb-4">
            <input type="hidden" name="type" value="{{ $type }}">
            <input type="search" name="q" value="{{ request('q') }}" placeholder="Suchen…" class="rounded-xl border border-sand-300 py-2 px-4 text-sm w-64">
            <button type="submit" class="rounded-xl bg-white ring-1 ring-sand-300 hover:bg-sand-100 text-forest-700 text-sm font-semibold px-4 py-2">Suchen</button>
        </form>

        @if ($items->isEmpty())
            <p class="text-sm text-forest-400">Keine veröffentlichten Einträge gefunden.</p>
        @else
            <form action="{{ route('admin.pinterest-pins.store') }}" method="POST" class="space-y-4">
                @csrf
                <input type="hidden" name="type" value="{{ $type }}">

                <div>
                    <label class="block text-xs font-medium text-forest-800 mb-1">Ort / Region</label>
                    <select name="id" required class="w-full rounded-xl border border-sand-300 px-3 py-2 text-sm">
                        @foreach ($items as $item)
                            <option value="{{ $item->id }}">{{ $item->name ?? $item->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-forest-800 mb-1">Blickwinkel</label>
                    <select name="angle" required class="w-full rounded-xl border border-sand-300 px-3 py-2 text-sm">
                        @foreach ($angles as $key => $description)
                            <option value="{{ $key }}">{{ ucfirst($key) }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-forest-800 mb-1">Boards (Mehrfachauswahl möglich)</label>
                    @if ($boards->isEmpty())
                        <p class="text-sm text-forest-400">
                            Noch keine Boards angelegt. Zuerst unter
                            <a href="{{ route('admin.pinterest-boards.index') }}" class="underline font-medium">Boards verwalten</a> anlegen.
                        </p>
                    @else
                        <div class="flex flex-wrap gap-3">
                            @foreach ($boards as $board)
                                <label class="flex items-center gap-2 text-sm bg-sand-50 ring-1 ring-sand-200 rounded-xl px-3 py-2">
                                    <input type="checkbox" name="board_ids[]" value="{{ $board->id }}">
                                    {{ $board->name }}
                                </label>
                            @endforeach
                        </div>
                    @endif
                </div>

                <button type="submit" {{ $openAiConfigured && $boards->isNotEmpty() ? '' : 'disabled' }}
                    class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold px-4 py-2 disabled:opacity-40 disabled:cursor-not-allowed">
                    Pin erzeugen
                </button>
            </form>
        @endif
    </div>

    <div class="space-y-3">
        @forelse ($pins as $pin)
            @php $title = $pin->featurable?->name ?? $pin->featurable?->title ?? '(gelöscht)'; @endphp
            <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-5 flex gap-4 items-start flex-wrap">
                @if ($pin->image_url)
                    <img src="{{ $pin->image_url }}" alt="" class="w-20 h-30 object-cover rounded-lg ring-1 ring-sand-200" style="aspect-ratio: 2/3;">
                @endif
                <div class="flex-1 min-w-[200px]">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 class="font-semibold text-forest-900">{{ $title }}</h2>
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium ring-1 {{ $statusClasses[$pin->status] }}">
                            {{ $statusLabels[$pin->status] }}
                        </span>
                        <span class="text-xs text-forest-400">{{ $pin->variant_label }} · {{ $pin->board?->name }}</span>
                    </div>
                    <p class="text-sm text-forest-600 mt-1">{{ $pin->pin_title }}</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('admin.pinterest-pins.show', $pin) }}" class="rounded-full px-3 py-1.5 text-xs font-medium bg-white ring-1 ring-sand-300 text-forest-600 hover:bg-sand-100">Details</a>
                    @if ($pin->status === 'draft')
                        <form action="{{ route('admin.pinterest-pins.approve', $pin) }}" method="POST">
                            @csrf
                            <button type="submit" class="rounded-full px-3 py-1.5 text-xs font-medium bg-forest-700 hover:bg-forest-800 text-white">Freigeben</button>
                        </form>
                    @endif
                    <form action="{{ route('admin.pinterest-pins.destroy', $pin) }}" method="POST" onsubmit="return confirm('Pin wirklich löschen?')">
                        @csrf @method('DELETE')
                        <button type="submit" class="rounded-full px-3 py-1.5 text-xs font-medium bg-white ring-1 ring-red-200 text-red-600 hover:bg-red-50">Löschen</button>
                    </form>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-8 text-center text-forest-400">
                Noch keine Pins angelegt.
            </div>
        @endforelse
    </div>
@endsection
