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

    @php
        $featureLabels = [
            'region_draft' => 'Regions-Entwürfe', 'region_place_name' => 'Ortsvorschläge',
            'region_tip_draft' => 'Tipp-Entwürfe', 'report_draft' => 'Berichts-Entwürfe',
            'report_write' => 'Berichts-Texte', 'social_caption' => 'Social-Captions', 'image' => 'Bilder',
        ];
    @endphp
    <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 mb-10">
        <h2 class="font-semibold text-forest-900 mb-1">KI-Nutzung (geschätzt)</h2>
        <p class="text-xs text-forest-400 mb-4">Schätzung auf Basis der von OpenAI zurückgegebenen Token-Zahlen &ndash; kein Ersatz für die echte OpenAI-Abrechnung.</p>
        <div class="grid sm:grid-cols-2 gap-5 mb-5">
            <div class="rounded-xl border border-sand-200 p-4">
                <p class="text-xs text-forest-500">Heute</p>
                <p class="text-2xl font-semibold text-forest-900 mt-1">${{ number_format($aiUsage['today']['cost'], 2) }}</p>
                <p class="text-xs text-forest-500 mt-1">{{ number_format($aiUsage['today']['tokens']) }} Tokens &middot; {{ $aiUsage['today']['calls'] }} Aufrufe</p>
            </div>
            <div class="rounded-xl border border-sand-200 p-4">
                <p class="text-xs text-forest-500">Dieser Monat</p>
                <p class="text-2xl font-semibold text-forest-900 mt-1">${{ number_format($aiUsage['month']['cost'], 2) }}</p>
                <p class="text-xs text-forest-500 mt-1">{{ number_format($aiUsage['month']['tokens']) }} Tokens &middot; {{ $aiUsage['month']['calls'] }} Aufrufe</p>
            </div>
        </div>
        @if ($aiUsage['byFeature']->isNotEmpty())
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-forest-400 uppercase tracking-wide">
                        <th class="pb-2">Funktion</th>
                        <th class="pb-2 text-right">Aufrufe</th>
                        <th class="pb-2 text-right">Tokens</th>
                        <th class="pb-2 text-right">Kosten (geschätzt)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-sand-100">
                    @foreach ($aiUsage['byFeature'] as $row)
                        <tr>
                            <td class="py-2">{{ $featureLabels[$row->feature] ?? $row->feature }}</td>
                            <td class="py-2 text-right">{{ $row->calls }}</td>
                            <td class="py-2 text-right">{{ number_format($row->tokens ?? 0) }}</td>
                            <td class="py-2 text-right">${{ number_format($row->cost ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <p class="text-sm text-forest-400">Diesen Monat noch keine KI-Aufrufe.</p>
        @endif
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
