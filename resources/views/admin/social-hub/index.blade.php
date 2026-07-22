@extends('layouts.admin')

@section('title', 'Social Hub')

@php
    $platformLabels = [
        'pinterest' => 'Pinterest',
        'facebook' => 'Facebook',
        'x' => 'X',
        'telegram' => 'Telegram',
        'whatsapp' => 'WhatsApp',
    ];
    $typeLabels = ['region' => 'Regionen', 'tip' => 'Reisetipps', 'report' => 'Reiseberichte'];
@endphp

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-forest-900">Social Hub</h1>
            <p class="text-sm text-forest-500 mt-1">
                Pro Klick einen passenden Beitrag je Plattform erzeugen lassen, prüfen und veröffentlichen.
            </p>
        </div>
    </div>

    @if (! $openAiConfigured)
        <div class="bg-amber-50 ring-1 ring-amber-300 text-amber-900 rounded-2xl p-4 mb-6 text-sm">
            Kein OpenAI-API-Key hinterlegt &ndash; Texte können nicht generiert werden. Key unter
            <a href="{{ route('admin.settings.edit') }}" class="underline font-medium">Einstellungen</a> hinterlegen.
        </div>
    @endif

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div class="flex gap-2">
            @foreach ($typeLabels as $key => $label)
                <a href="{{ route('admin.social-hub.index', ['type' => $key]) }}"
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
            @php $title = $item->name ?? $item->title; @endphp
            <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-5">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <h2 class="font-semibold text-forest-900">{{ $title }}</h2>
                        <p class="text-xs text-forest-400">Aktualisiert am {{ $item->updated_at->format('d.m.Y') }}</p>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($platforms as $platform)
                            @php $existing = $item->socialPosts->firstWhere('platform', $platform); @endphp
                            @if ($existing)
                                <a href="{{ route('admin.social-hub.show', $existing) }}"
                                    class="rounded-full px-3 py-1.5 text-xs font-medium ring-1 {{ $existing->status === 'sent' ? 'bg-forest-100 text-forest-700 ring-forest-200' : ($existing->status === 'failed' ? 'bg-red-50 text-red-700 ring-red-200' : 'bg-sand-100 text-sand-700 ring-sand-200') }}">
                                    {{ $platformLabels[$platform] }}
                                    @if ($existing->status === 'sent') ✓ gesendet
                                    @elseif ($existing->status === 'failed') ✕ fehlgeschlagen
                                    @else · Entwurf
                                    @endif
                                </a>
                            @else
                                <form action="{{ route('admin.social-hub.generate') }}" method="POST">
                                    @csrf
                                    <input type="hidden" name="type" value="{{ $type }}">
                                    <input type="hidden" name="id" value="{{ $item->id }}">
                                    <input type="hidden" name="platform" value="{{ $platform }}">
                                    <button type="submit" {{ $openAiConfigured ? '' : 'disabled' }} class="rounded-full px-3 py-1.5 text-xs font-medium bg-white ring-1 ring-sand-300 text-forest-600 hover:bg-sand-100 disabled:opacity-40 disabled:cursor-not-allowed">
                                        + {{ $platformLabels[$platform] }}
                                    </button>
                                </form>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-8 text-center text-forest-400">
                Keine veröffentlichten {{ $typeLabels[$type] }} gefunden.
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $items->links() }}</div>
@endsection
