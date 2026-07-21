@extends('layouts.app')

@php
    $seoTitle = $query !== '' ? 'Suche: '.$query.' | Lieblingsorte' : 'Suche | Lieblingsorte';
    $seoDescription = 'Durchsuche alle Regionen und Reisetipps auf Lieblingsorte.';
@endphp

@section('content')
    <x-breadcrumbs :items="[
        ['label' => 'Startseite', 'url' => route('home')],
        ['label' => 'Suche', 'url' => null],
    ]" />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <form action="{{ route('search') }}" method="GET" class="max-w-xl mb-10">
            <label for="search-page-input" class="sr-only">Suche</label>
            <div class="relative">
                <svg class="w-4 h-4 text-forest-400 absolute left-4 top-1/2 -translate-y-1/2" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7" /><path d="m21 21-4.3-4.3" stroke-linecap="round" /></svg>
                <input id="search-page-input" type="search" name="q" value="{{ $query }}" placeholder="Nach Städten, Regionen oder Erlebnissen suchen…" class="w-full rounded-full border border-sand-300 bg-white py-3 pl-11 pr-4 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-forest-400">
            </div>
        </form>

        @if ($query === '')
            <p class="text-forest-500">Gib einen Suchbegriff ein, um Regionen und Reisetipps zu finden.</p>
        @elseif ($regions->isEmpty() && $tips->isEmpty())
            <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-10 text-center">
                <p class="text-forest-700 font-medium">Keine Ergebnisse für „{{ $query }}“.</p>
                <p class="text-forest-500 text-sm mt-2">Versuche es mit einem anderen Suchbegriff oder entdecke unsere beliebtesten Regionen:</p>
                <div class="grid sm:grid-cols-3 gap-6 mt-6 text-left">
                    @foreach ($alternatives as $region)
                        <x-region-card :region="$region" />
                    @endforeach
                </div>
            </div>
        @else
            <p class="text-forest-500 mb-8">{{ $regions->count() + $tips->count() }} Ergebnisse für „{{ $query }}“</p>

            @if ($regions->isNotEmpty())
                <div class="mb-12">
                    <h2 class="font-display text-xl font-semibold text-forest-900 mb-4">Regionen</h2>
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach ($regions as $region)
                            <x-region-card :region="$region" />
                        @endforeach
                    </div>
                </div>
            @endif

            @if ($tips->isNotEmpty())
                <div>
                    <h2 class="font-display text-xl font-semibold text-forest-900 mb-4">Reisetipps</h2>
                    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach ($tips as $tip)
                            <x-tip-card :tip="$tip" :show-region="true" />
                        @endforeach
                    </div>
                </div>
            @endif
        @endif
    </div>
@endsection
