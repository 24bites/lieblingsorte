@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <h1 class="text-2xl font-semibold text-forest-900 mb-8">Dashboard</h1>

    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-10">
        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6">
            <p class="text-sm text-forest-500">Regionen</p>
            <p class="text-3xl font-semibold text-forest-900 mt-1">{{ $stats['regions'] }}</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6">
            <p class="text-sm text-forest-500">Reisetipps</p>
            <p class="text-3xl font-semibold text-forest-900 mt-1">{{ $stats['travel_tips'] }}</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6">
            <p class="text-sm text-forest-500">Kategorien</p>
            <p class="text-3xl font-semibold text-forest-900 mt-1">{{ $stats['categories'] }}</p>
        </div>
        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6">
            <p class="text-sm text-forest-500">Veröffentlicht</p>
            <p class="text-3xl font-semibold text-forest-900 mt-1">{{ $stats['published'] }}</p>
        </div>
    </div>

    <div class="flex flex-wrap gap-3 mb-10">
        <a href="{{ route('admin.regions.create') }}" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold px-5 py-2.5">+ Neue Region</a>
        <a href="{{ route('admin.tips.create') }}" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold px-5 py-2.5">+ Neuer Reisetipp</a>
        <a href="{{ route('admin.categories.create') }}" class="rounded-xl border border-forest-300 hover:bg-forest-50 text-forest-800 text-sm font-semibold px-5 py-2.5">+ Neue Kategorie</a>
        <a href="{{ route('admin.labels.create') }}" class="rounded-xl border border-forest-300 hover:bg-forest-50 text-forest-800 text-sm font-semibold px-5 py-2.5">+ Neues Label</a>
    </div>

    <div class="grid lg:grid-cols-2 gap-8">
        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6">
            <h2 class="font-semibold text-forest-900 mb-4">Zuletzt bearbeitete Regionen</h2>
            <ul class="divide-y divide-sand-100">
                @forelse ($recentRegions as $region)
                    <li class="py-3 flex items-center justify-between text-sm">
                        <span>{{ $region->name }}</span>
                        <a href="{{ route('admin.regions.edit', $region) }}" class="text-forest-600 hover:text-forest-900 font-medium">Bearbeiten</a>
                    </li>
                @empty
                    <li class="py-3 text-sm text-forest-400">Keine Regionen vorhanden.</li>
                @endforelse
            </ul>
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6">
            <h2 class="font-semibold text-forest-900 mb-4">Zuletzt bearbeitete Reisetipps</h2>
            <ul class="divide-y divide-sand-100">
                @forelse ($recentTips as $tip)
                    <li class="py-3 flex items-center justify-between text-sm">
                        <span>{{ $tip->title }} <span class="text-forest-400">· {{ $tip->region->name }}</span></span>
                        <a href="{{ route('admin.tips.edit', $tip) }}" class="text-forest-600 hover:text-forest-900 font-medium">Bearbeiten</a>
                    </li>
                @empty
                    <li class="py-3 text-sm text-forest-400">Keine Reisetipps vorhanden.</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
