@extends('layouts.app')

@php
    $seoTitle = 'Reiseberichte | '.\App\Models\Setting::get('site_name', 'Lieblingsorte');
    $seoDescription = 'Ausführliche, persönliche Reiseberichte von unterwegs – echte Erlebnisse statt Prospekttexte.';
@endphp

@section('content')
    <x-breadcrumbs :items="[
        ['label' => 'Startseite', 'url' => route('home')],
        ['label' => 'Reiseberichte', 'url' => null],
    ]" />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 mb-8">
            <div>
                <h1 class="font-display text-3xl sm:text-4xl font-semibold text-forest-900">Reiseberichte</h1>
                <p class="text-forest-500 mt-2">{{ $reports->total() }} ausführliche Berichte von unterwegs</p>
            </div>
            <form action="{{ route('reports.index') }}" method="GET" class="flex gap-2">
                <input type="search" name="q" value="{{ request('q') }}" placeholder="Reisebericht suchen…" class="rounded-full border border-sand-300 py-2 px-4 text-sm w-64 max-w-full focus:outline-none focus:ring-2 focus:ring-forest-400">
                <button type="submit" class="rounded-full bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold px-5 py-2">Suchen</button>
            </form>
        </div>

        @if ($reports->isEmpty())
            <p class="text-forest-500">Keine Reiseberichte gefunden.</p>
        @else
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($reports as $report)
                    <x-report-card :report="$report" />
                @endforeach
            </div>
            <div class="mt-10">{{ $reports->links() }}</div>
        @endif
    </div>
@endsection
