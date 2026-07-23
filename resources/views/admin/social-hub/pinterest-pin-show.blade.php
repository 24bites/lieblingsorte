@extends('layouts.admin')

@section('title', 'Pin-Details')

@php
    $statusLabels = [
        'draft' => 'Entwurf',
        'approved' => 'Freigegeben',
        'scheduled' => 'Geplant',
        'posted' => 'Veröffentlicht',
        'failed' => 'Fehlgeschlagen',
    ];
    $title = $pin->featurable?->name ?? $pin->featurable?->title ?? '(gelöscht)';
@endphp

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-forest-900">{{ $title }}</h1>
            <p class="text-sm text-forest-500 mt-1">{{ $pin->variant_label }} · {{ $pin->board?->name }} · {{ $statusLabels[$pin->status] }}</p>
        </div>
        <a href="{{ route('admin.pinterest-pins.index') }}" class="text-sm font-semibold text-forest-700 hover:text-forest-900">← Zurück zur Warteschlange</a>
    </div>

    @if (session('status'))
        <div class="bg-forest-50 ring-1 ring-forest-200 text-forest-800 rounded-2xl p-4 mb-6 text-sm">{{ session('status') }}</div>
    @endif
    @error('publish')
        <div class="bg-red-50 ring-1 ring-red-200 text-red-800 rounded-2xl p-4 mb-6 text-sm">{{ $message }}</div>
    @enderror

    <div class="grid lg:grid-cols-[320px,1fr] gap-8">
        <div>
            @if ($pin->image_url)
                <img src="{{ $pin->image_url }}" alt="" class="w-full rounded-2xl ring-1 ring-sand-200" style="aspect-ratio: 2/3; object-fit: cover;">
            @endif
            <div class="mt-4 space-y-2 text-sm text-forest-500">
                <p><span class="font-medium text-forest-800">Overlay-Headline:</span> {{ $pin->overlay_headline }}</p>
                @if ($pin->overlay_subline)
                    <p><span class="font-medium text-forest-800">Overlay-Subline:</span> {{ $pin->overlay_subline }}</p>
                @endif
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6">
                <form action="{{ route('admin.pinterest-pins.update', $pin) }}" method="POST" class="space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <label class="block text-xs font-medium text-forest-800 mb-1">Pin-Titel</label>
                        <input type="text" name="pin_title" value="{{ old('pin_title', $pin->pin_title) }}" maxlength="255" required class="w-full rounded-xl border border-sand-300 px-3 py-2 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-forest-800 mb-1">Pin-Beschreibung</label>
                        <textarea name="pin_description" rows="5" required class="w-full rounded-xl border border-sand-300 px-3 py-2 text-sm">{{ old('pin_description', $pin->pin_description) }}</textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-forest-800 mb-1">Geplant für</label>
                        <input type="date" name="scheduled_for" value="{{ old('scheduled_for', $pin->scheduled_for?->format('Y-m-d')) }}" class="rounded-xl border border-sand-300 px-3 py-2 text-sm">
                    </div>
                    <button type="submit" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold px-4 py-2">Speichern</button>
                </form>
            </div>

            <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 flex flex-wrap gap-3">
                @if ($pin->status === 'draft')
                    <form action="{{ route('admin.pinterest-pins.approve', $pin) }}" method="POST">
                        @csrf
                        <button type="submit" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold px-4 py-2">Freigeben</button>
                    </form>
                @endif
                @if ($pin->status === 'approved')
                    <form action="{{ route('admin.pinterest-pins.publish', $pin) }}" method="POST">
                        @csrf
                        <button type="submit" {{ $pinterestConfigured ? '' : 'disabled' }}
                            class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold px-4 py-2 disabled:opacity-40 disabled:cursor-not-allowed">
                            Veröffentlichen
                        </button>
                    </form>
                    @unless ($pinterestConfigured)
                        <p class="text-xs text-forest-400 self-center">Pinterest ist noch nicht verbunden.</p>
                    @endunless
                @endif
                <form action="{{ route('admin.pinterest-pins.destroy', $pin) }}" method="POST" onsubmit="return confirm('Pin wirklich löschen?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="rounded-xl bg-white ring-1 ring-red-200 text-red-600 hover:bg-red-50 text-sm font-semibold px-4 py-2">Löschen</button>
                </form>
            </div>
        </div>
    </div>
@endsection
