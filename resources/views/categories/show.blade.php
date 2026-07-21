@extends('layouts.app')

@php
    $seoTitle = $category->name.' – Reisetipps | Lieblingsorte';
    $seoDescription = $category->description ?: 'Reisetipps aus der Kategorie '.$category->name.'.';
@endphp

@section('content')
    <x-breadcrumbs :items="[
        ['label' => 'Startseite', 'url' => route('home')],
        ['label' => 'Kategorien', 'url' => route('categories.index')],
        ['label' => $category->name, 'url' => null],
    ]" />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="font-display text-3xl sm:text-4xl font-semibold text-forest-900">{{ $category->name }}</h1>
        @if ($category->description)
            <p class="text-forest-500 mt-2 max-w-2xl">{{ $category->description }}</p>
        @endif

        @if ($tips->isEmpty())
            <p class="text-forest-500 mt-10">Für diese Kategorie sind aktuell keine Reisetipps veröffentlicht.</p>
        @else
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 mt-10">
                @foreach ($tips as $tip)
                    <x-tip-card :tip="$tip" :show-region="true" />
                @endforeach
            </div>
            <div class="mt-10">{{ $tips->links() }}</div>
        @endif
    </div>
@endsection
