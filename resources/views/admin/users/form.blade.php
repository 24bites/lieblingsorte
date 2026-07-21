@extends('layouts.admin')

@php $isEdit = $editUser->exists; @endphp
@section('title', $isEdit ? 'Benutzer bearbeiten' : 'Benutzer erstellen')

@section('content')
    <h1 class="text-2xl font-semibold text-forest-900 mb-6">{{ $isEdit ? 'Benutzer bearbeiten: '.$editUser->name : 'Neuer Benutzer' }}</h1>

    <form action="{{ $isEdit ? route('admin.users.update', $editUser) : route('admin.users.store') }}" method="POST" class="space-y-5 max-w-xl bg-white rounded-2xl ring-1 ring-sand-200 p-6">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div>
            <label class="block text-sm font-medium text-forest-800 mb-1">Name *</label>
            <input type="text" name="name" value="{{ old('name', $editUser->name) }}" required class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
            @error('name') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-forest-800 mb-1">E-Mail *</label>
            <input type="email" name="email" value="{{ old('email', $editUser->email) }}" required class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
            @error('email') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-forest-800 mb-1">Rolle *</label>
            <select name="role" required class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
                <option value="admin" @selected(old('role', $editUser->role) === 'admin')>Administrator</option>
                <option value="editor" @selected(old('role', $editUser->role ?: 'editor') === 'editor')>Redakteur</option>
            </select>
            @error('role') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-forest-800 mb-1">Passwort {{ $isEdit ? '' : '*' }}</label>
            <input type="password" name="password" autocomplete="new-password" {{ $isEdit ? '' : 'required' }} class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
            @error('password') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
            @if ($isEdit)
                <p class="text-xs text-forest-500 mt-1">Leer lassen, um das aktuelle Passwort beizubehalten.</p>
            @endif
        </div>
        <div>
            <label class="block text-sm font-medium text-forest-800 mb-1">Passwort bestätigen {{ $isEdit ? '' : '*' }}</label>
            <input type="password" name="password_confirmation" autocomplete="new-password" {{ $isEdit ? '' : 'required' }} class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white font-semibold px-6 py-2.5 text-sm">Speichern</button>
            <a href="{{ route('admin.users.index') }}" class="rounded-xl border border-sand-300 hover:bg-sand-100 text-forest-700 font-semibold px-6 py-2.5 text-sm">Abbrechen</a>
        </div>
    </form>
@endsection
