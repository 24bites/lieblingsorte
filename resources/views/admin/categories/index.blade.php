@extends('layouts.admin')

@section('title', 'Kategorien')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-forest-900">Kategorien</h1>
        <a href="{{ route('admin.categories.create') }}" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold px-5 py-2.5">+ Neue Kategorie</a>
    </div>

    <div class="bg-white rounded-2xl ring-1 ring-sand-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-sand-100 text-left text-forest-600">
                <tr>
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Icon</th>
                    <th class="px-5 py-3 font-medium">Reisetipps</th>
                    <th class="px-5 py-3 font-medium text-right">Aktionen</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-sand-100">
                @forelse ($categories as $category)
                    <tr>
                        <td class="px-5 py-3 font-medium text-forest-900">{{ $category->name }}</td>
                        <td class="px-5 py-3 text-forest-500">{{ $category->icon }}</td>
                        <td class="px-5 py-3 text-forest-500">{{ $category->travel_tips_count }}</td>
                        <td class="px-5 py-3 text-right space-x-3 whitespace-nowrap">
                            <a href="{{ route('admin.categories.edit', $category) }}" class="text-forest-600 hover:text-forest-900 font-medium">Bearbeiten</a>
                            <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="inline" onsubmit="return confirm('Kategorie löschen?');">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Löschen</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-forest-400">Keine Kategorien vorhanden.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
