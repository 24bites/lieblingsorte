@extends('layouts.app')

@php
    $seoTitle = 'Kategorien – Wandern, Kulinarik, Kultur & mehr | Lieblingsorte';
    $seoDescription = 'Entdecke Reisetipps nach Kategorien: Wandern, Natur, Kulinarik, Kultur, Seen, Familie und mehr.';
@endphp

@section('content')
    <x-breadcrumbs :items="[
        ['label' => 'Startseite', 'url' => route('home')],
        ['label' => 'Kategorien', 'url' => null],
    ]" />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="font-display text-3xl sm:text-4xl font-semibold text-forest-900 mb-8">Kategorien</h1>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-5">
            @foreach ($categories as $category)
                <a href="{{ route('categories.show', $category) }}" class="rounded-2xl bg-white ring-1 ring-sand-200 p-6 hover:shadow-md hover:-translate-y-0.5 transition">
                    <h2 class="font-display font-semibold text-lg text-forest-900">{{ $category->name }}</h2>
                    <p class="text-sm text-forest-500 mt-2 line-clamp-2">{{ $category->description }}</p>
                    <p class="text-xs text-forest-400 mt-3">{{ $category->travel_tips_count }} Reisetipps</p>
                </a>
            @endforeach
        </div>
    </div>
@endsection
