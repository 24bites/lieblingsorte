@extends('layouts.admin')

@php $isEdit = $label->exists; @endphp
@section('title', $isEdit ? 'Label bearbeiten' : 'Label erstellen')

@section('content')
    <h1 class="text-2xl font-semibold text-forest-900 mb-6">{{ $isEdit ? 'Label bearbeiten' : 'Neues Label' }}</h1>

    <form action="{{ $isEdit ? route('admin.labels.update', $label) : route('admin.labels.store') }}" method="POST" class="space-y-5 max-w-xl bg-white rounded-2xl ring-1 ring-sand-200 p-6">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        <div>
            <label class="block text-sm font-medium text-forest-800 mb-1">Name *</label>
            <input type="text" name="name" value="{{ old('name', $label->name) }}" required class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-forest-800 mb-1">Slug (URL)</label>
            <input type="text" name="slug" value="{{ old('slug', $label->slug) }}" placeholder="wird automatisch erzeugt" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-forest-800 mb-1">Farbe *</label>
            <div class="flex items-center gap-3">
                <input type="color" name="color" value="{{ old('color', $label->color ?: '#2f5c3f') }}" class="w-14 h-10 rounded-lg border border-sand-300">
                <span class="text-sm text-forest-500">Hex-Farbwert für das Badge</span>
            </div>
        </div>

        <div class="flex gap-3 pt-2">
            <button type="submit" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white font-semibold px-6 py-2.5 text-sm">Speichern</button>
            <a href="{{ route('admin.labels.index') }}" class="rounded-xl border border-sand-300 hover:bg-sand-100 text-forest-700 font-semibold px-6 py-2.5 text-sm">Abbrechen</a>
        </div>
    </form>
@endsection
