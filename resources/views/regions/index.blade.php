@extends('layouts.app')

@php
    $seoTitle = 'Alle Regionen & Städte im Überblick | Lieblingsorte';
    $seoDescription = 'Entdecke alle Reiseregionen und Städte mit handverlesenen Reisetipps auf Lieblingsorte.';
@endphp

@section('content')
    <x-breadcrumbs :items="[
        ['label' => 'Startseite', 'url' => route('home')],
        ['label' => 'Regionen', 'url' => null],
    ]" />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
            <div>
                <h1 class="font-display text-3xl sm:text-4xl font-semibold text-forest-900">Alle Regionen</h1>
                <p class="text-forest-500 mt-2">{{ $regions->total() }} Regionen mit handverlesenen Reisetipps</p>
            </div>
            <form action="{{ route('regions.index') }}" method="GET" class="flex gap-2">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Region suchen…" class="rounded-full border border-sand-300 py-2 px-4 text-sm w-64 max-w-full focus:outline-none focus:ring-2 focus:ring-forest-400">
                <button type="submit" class="rounded-full bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold px-5 py-2">Suchen</button>
            </form>
        </div>

        @if ($regions->isEmpty())
            <p class="text-forest-500">Keine Regionen gefunden.</p>
        @else
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($regions as $region)
                    <x-region-card :region="$region" />
                @endforeach
            </div>
            <div class="mt-10">{{ $regions->links() }}</div>
        @endif
    </div>
@endsection
