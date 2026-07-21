@extends('layouts.admin')

@section('title', 'Regionen')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-forest-900">Regionen</h1>
        <a href="{{ route('admin.regions.create') }}" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold px-5 py-2.5">+ Neue Region</a>
    </div>

    <form method="GET" class="mb-6">
        <input type="search" name="q" value="{{ request('q') }}" placeholder="Region suchen…" class="rounded-xl border border-sand-300 py-2 px-4 text-sm w-72 max-w-full">
    </form>

    <div class="bg-white rounded-2xl ring-1 ring-sand-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-sand-100 text-left text-forest-600">
                <tr>
                    <th class="px-5 py-3 font-medium">Region</th>
                    <th class="px-5 py-3 font-medium">Typ</th>
                    <th class="px-5 py-3 font-medium">Tipps</th>
                    <th class="px-5 py-3 font-medium">Status</th>
                    <th class="px-5 py-3 font-medium">Aktualisiert</th>
                    <th class="px-5 py-3 font-medium text-right">Aktionen</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-sand-100">
                @forelse ($regions as $region)
                    <tr>
                        <td class="px-5 py-3 font-medium text-forest-900">{{ $region->name }}</td>
                        <td class="px-5 py-3 text-forest-500">{{ $region->type }}</td>
                        <td class="px-5 py-3 text-forest-500">{{ $region->travel_tips_count }}</td>
                        <td class="px-5 py-3">
                            <span class="px-2.5 py-1 rounded-full text-xs font-medium {{ $region->is_published ? 'bg-forest-100 text-forest-700' : 'bg-sand-100 text-sand-700' }}">
                                {{ $region->is_published ? 'Veröffentlicht' : 'Entwurf' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-forest-500">{{ $region->updated_at->format('d.m.Y') }}</td>
                        <td class="px-5 py-3 text-right space-x-3 whitespace-nowrap">
                            <a href="{{ route('admin.regions.preview', $region) }}" target="_blank" class="text-forest-500 hover:text-forest-800">{{ $region->is_published ? 'Ansehen' : 'Vorschau' }}</a>
                            <a href="{{ route('admin.regions.edit', $region) }}" class="text-forest-600 hover:text-forest-900 font-medium">Bearbeiten</a>
                            <form action="{{ route('admin.regions.destroy', $region) }}" method="POST" class="inline" onsubmit="return confirm('Region „{{ $region->name }}“ inkl. aller Reisetipps wirklich löschen?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Löschen</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-5 py-8 text-center text-forest-400">Keine Regionen gefunden.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">{{ $regions->links() }}</div>
@endsection
