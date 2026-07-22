@extends('layouts.admin')

@section('title', 'KI-Vorschläge')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-forest-900">KI-Vorschläge</h1>
            <p class="text-sm text-forest-500 mt-1">
                Von der KI automatisch erstellte Regionen. Bitte prüfen, bevor du sie freigibst &ndash; nach der Freigabe
                werden Titelbild, fehlende Reisetipps (bis 12) und Tipp-Bilder automatisch im Hintergrund ergänzt und
                die Region danach selbstständig veröffentlicht.
            </p>
        </div>
    </div>

    <div class="space-y-4">
        @forelse ($regions as $region)
            <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-5">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-forest-900">{{ $region->name }}</h2>
                        <p class="text-sm text-forest-500">{{ $region->type }} · {{ $region->country }}{{ $region->federal_state ? ', '.$region->federal_state : '' }}</p>
                        <p class="text-sm text-forest-600 mt-2">{{ $region->short_description }}</p>
                        <p class="text-xs text-forest-400 mt-2">{{ $region->travelTips()->count() }} Reisetipps · erstellt am {{ $region->created_at->format('d.m.Y H:i') }}</p>
                    </div>
                    <div class="flex flex-col items-end gap-2 shrink-0">
                        <a href="{{ route('admin.regions.edit', $region) }}" class="text-forest-600 hover:text-forest-900 text-sm font-medium">Details prüfen</a>
                        <div class="flex gap-2">
                            <form action="{{ route('admin.ai-suggestions.approve', $region) }}" method="POST">
                                @csrf
                                <button type="submit" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold px-4 py-2">Freigeben</button>
                            </form>
                            <form action="{{ route('admin.ai-suggestions.reject', $region) }}" method="POST" onsubmit="return confirm('Vorschlag „{{ $region->name }}“ wirklich ablehnen?');">
                                @csrf
                                <button type="submit" class="rounded-xl bg-white ring-1 ring-red-300 text-red-600 hover:bg-red-50 text-sm font-semibold px-4 py-2">Ablehnen</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-8 text-center text-forest-400">
                Keine offenen KI-Vorschläge.
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $regions->links() }}</div>
@endsection
