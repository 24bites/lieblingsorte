@extends('layouts.admin')

@section('title', 'Benutzer')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-semibold text-forest-900">Benutzer</h1>
        <a href="{{ route('admin.users.create') }}" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold px-5 py-2.5">+ Neuer Benutzer</a>
    </div>

    <div class="bg-white rounded-2xl ring-1 ring-sand-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-sand-100 text-left text-forest-600">
                <tr>
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">E-Mail</th>
                    <th class="px-5 py-3 font-medium">Rolle</th>
                    <th class="px-5 py-3 font-medium text-right">Aktionen</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-sand-100">
                @forelse ($users as $user)
                    <tr>
                        <td class="px-5 py-3 font-medium text-forest-900">
                            {{ $user->name }}
                            @if ($user->id === auth()->id())
                                <span class="text-xs text-forest-400">(du)</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-forest-500">{{ $user->email }}</td>
                        <td class="px-5 py-3">
                            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $user->role === 'admin' ? 'bg-forest-100 text-forest-800' : 'bg-sand-100 text-forest-600' }}">
                                {{ $user->role === 'admin' ? 'Administrator' : 'Redakteur' }}
                            </span>
                        </td>
                        <td class="px-5 py-3 text-right space-x-3 whitespace-nowrap">
                            <a href="{{ route('admin.users.edit', $user) }}" class="text-forest-600 hover:text-forest-900 font-medium">Bearbeiten</a>
                            @unless ($user->id === auth()->id())
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Benutzer löschen?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 font-medium">Löschen</button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-5 py-8 text-center text-forest-400">Keine Benutzer vorhanden.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
