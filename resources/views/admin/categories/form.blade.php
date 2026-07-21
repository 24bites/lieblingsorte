@extends('layouts.admin')

@php $isEdit = $category->exists; @endphp
@section('title', $isEdit ? 'Kategorie bearbeiten' : 'Kategorie erstellen')

@section('content')
    <h1 class="text-2xl font-semibold text-forest-900 mb-6">{{ $isEdit ? 'Kategorie bearbeiten' : 'Neue Kategorie' }}</h1>

    <form action="{{ $isEdit ? route('admin.categories.update', $category) : route('admin.categories.store') }}" method="POST" class="space-y-5 max-w-xl bg-white rounded-2xl ring-1 ring-sand-200 p-6">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div>
            <label class="block text-sm font-medium text-forest-800 mb-1">Name *</label>
            <input type="text" name="name" value="{{ old('name', $category->name) }}" required class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-forest-800 mb-1">Slug (URL)</label>
            <input type="text" name="slug" value="{{ old('slug', $category->slug) }}" placeholder="wird automatisch erzeugt" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-forest-800 mb-1">Icon (Lucide-Name)</label>
            <input type="text" name="icon" value="{{ old('icon', $category->icon) }}" placeholder="z. B. mountain-snow" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-forest-800 mb-1">Beschreibung</label>
            <textarea name="description" rows="3" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">{{ old('description', $category->description) }}</textarea>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white font-semibold px-6 py-2.5 text-sm">Speichern</button>
            <a href="{{ route('admin.categories.index') }}" class="rounded-xl border border-sand-300 hover:bg-sand-100 text-forest-700 font-semibold px-6 py-2.5 text-sm">Abbrechen</a>
        </div>
    </form>
@endsection
