@extends('layouts.admin')

@section('title', 'Labels')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-forest-900">Labels</h1>
        <a href="{{ route('admin.labels.create') }}" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold px-5 py-2.5">+ Neues Label</a>
    </div>

    <div class="bg-white rounded-2xl ring-1 ring-sand-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-sand-100 text-left text-forest-600">
                <tr>
                    <th class="px-5 py-3 font-medium">Label</th>
                    <th class="px-5 py-3 font-medium">Farbe</th>
                    <th class="px-5 py-3 font-medium">Reisetipps</th>
                    <th class="px-5 py-3 font-medium text-right">Aktionen</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-sand-100">
                @forelse ($labels as $label)
                    <tr>
                        <td class="px-5 py-3"><x-label-badge :label="$label" /></td>
                        <td class="px-5 py-3 text-forest-500">{{ $label->color }}</td>
                        <td class="px-5 py-3 text-forest-500">{{ $label->travel_tips_count }}</td>
                        <td class="px-5 py-3 text-right space-x-3 whitespace-nowrap">
                            <a href="{{ route('admin.labels.edit', $label) }}" class="text-forest-600 hover:text-forest-900 font-medium">Bearbeiten</a>
                            <form action="{{ route('admin.labels.destroy', $label) }}" method="POST" class="inline" onsubmit="return confirm('Label löschen?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Löschen</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-forest-400">Keine Labels vorhanden.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
