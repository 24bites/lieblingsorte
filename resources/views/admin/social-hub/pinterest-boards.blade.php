@extends('layouts.admin')

@section('title', 'Pinterest-Boards')

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-forest-900">Pinterest-Boards</h1>
            <p class="text-sm text-forest-500 mt-1">
                Ein Board pro Region plus themenbasierte Boards. Boards werden hier lokal verwaltet und später,
                sobald die Pinterest-App verbunden ist, mit echten Pinterest-Boards synchronisiert.
            </p>
        </div>
        <a href="{{ route('admin.social-hub.index') }}" class="text-sm font-semibold text-forest-700 hover:text-forest-900">← Zurück zum Social Hub</a>
    </div>

    @if (session('status'))
        <div class="bg-forest-50 ring-1 ring-forest-200 text-forest-800 rounded-2xl p-4 mb-6 text-sm">{{ session('status') }}</div>
    @endif
    @error('board')
        <div class="bg-red-50 ring-1 ring-red-200 text-red-800 rounded-2xl p-4 mb-6 text-sm">{{ $message }}</div>
    @enderror

    @if ($regionsWithoutBoard->isNotEmpty())
        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 mb-8">
            <h2 class="font-semibold text-forest-900 mb-4">Regionen ohne Board ({{ $regionsWithoutBoard->count() }})</h2>
            <div class="flex flex-wrap gap-2">
                @foreach ($regionsWithoutBoard as $region)
                    <form action="{{ route('admin.pinterest-boards.store') }}" method="POST">
                        @csrf
                        <input type="hidden" name="type" value="region">
                        <input type="hidden" name="region_id" value="{{ $region->id }}">
                        <input type="hidden" name="name" value="{{ $region->name }}">
                        <button type="submit" class="rounded-full px-3 py-1.5 text-xs font-medium bg-white ring-1 ring-sand-300 text-forest-600 hover:bg-sand-100">
                            + Board „{{ $region->name }}" anlegen
                        </button>
                    </form>
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid lg:grid-cols-2 gap-8 mb-8">
        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6">
            <h2 class="font-semibold text-forest-900 mb-4">Regionen-Boards ({{ $boards->where('type', 'region')->count() }})</h2>
            <ul class="divide-y divide-sand-100">
                @forelse ($boards->where('type', 'region') as $board)
                    <li class="py-3 flex items-center justify-between gap-3 text-sm">
                        <div>
                            <p class="font-medium text-forest-900">{{ $board->name }}</p>
                            <p class="text-xs text-forest-400">
                                {{ $board->isConnectedToPinterest() ? 'Mit Pinterest verbunden' : 'Noch nicht mit Pinterest verbunden' }}
                            </p>
                        </div>
                        <form action="{{ route('admin.pinterest-boards.destroy', $board) }}" method="POST" onsubmit="return confirm('Board wirklich löschen?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Löschen</button>
                        </form>
                    </li>
                @empty
                    <li class="py-3 text-sm text-forest-400">Noch keine Regionen-Boards.</li>
                @endforelse
            </ul>
        </div>

        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6">
            <h2 class="font-semibold text-forest-900 mb-4">Themen-Boards ({{ $boards->where('type', 'topic')->count() }})</h2>
            <ul class="divide-y divide-sand-100 mb-4">
                @forelse ($boards->where('type', 'topic') as $board)
                    <li class="py-3 flex items-center justify-between gap-3 text-sm">
                        <div>
                            <p class="font-medium text-forest-900">{{ $board->name }}</p>
                            @if ($board->description)
                                <p class="text-xs text-forest-500">{{ $board->description }}</p>
                            @endif
                            <p class="text-xs text-forest-400">
                                {{ $board->isConnectedToPinterest() ? 'Mit Pinterest verbunden' : 'Noch nicht mit Pinterest verbunden' }}
                            </p>
                        </div>
                        <form action="{{ route('admin.pinterest-boards.destroy', $board) }}" method="POST" onsubmit="return confirm('Board wirklich löschen?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium">Löschen</button>
                        </form>
                    </li>
                @empty
                    <li class="py-3 text-sm text-forest-400">Noch keine Themen-Boards.</li>
                @endforelse
            </ul>

            <form action="{{ route('admin.pinterest-boards.store') }}" method="POST" class="space-y-3 border-t border-sand-100 pt-4">
                @csrf
                <input type="hidden" name="type" value="topic">
                <div>
                    <label class="block text-xs font-medium text-forest-800 mb-1">Neues Themen-Board</label>
                    <input type="text" name="name" placeholder="z. B. Geheimtipps Europa" class="w-full rounded-xl border border-sand-300 px-3 py-2 text-sm" required>
                    @error('name')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <textarea name="description" rows="2" placeholder="Kurze Beschreibung (optional)" class="w-full rounded-xl border border-sand-300 px-3 py-2 text-sm"></textarea>
                </div>
                <button type="submit" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold px-4 py-2">Board anlegen</button>
            </form>
        </div>
    </div>
@endsection
