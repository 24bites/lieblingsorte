@extends('layouts.app')

@php
    $seoTitle = 'Meine Favoriten | Lieblingsorte';
    $seoDescription = 'Deine gemerkten Reisetipps auf Lieblingsorte.';
@endphp

@section('content')
    <x-breadcrumbs :items="[
        ['label' => 'Startseite', 'url' => route('home')],
        ['label' => 'Favoriten', 'url' => null],
    ]" />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <h1 class="font-display text-3xl sm:text-4xl font-semibold text-forest-900 mb-8">Meine Favoriten</h1>

        @if ($tips->isEmpty())
            <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-10 text-center">
                <p class="text-forest-600">Du hast noch keine Reisetipps gemerkt.</p>
                <a href="{{ route('regions.index') }}" class="inline-block mt-4 text-sm font-semibold text-forest-700 hover:text-forest-900">Regionen entdecken →</a>
            </div>
        @else
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($tips as $tip)
                    <x-tip-card :tip="$tip" :show-region="true" />
                @endforeach
            </div>
        @endif
    </div>
@endsection
