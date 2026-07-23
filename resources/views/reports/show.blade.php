@extends('layouts.app')

@php
    $seoTitle = $report->seo_title ?: $report->title.' | '.\App\Models\Setting::get('site_name', 'Lieblingsorte');
    $seoDescription = $report->seo_description ?: $report->excerpt;
    $ogDescription = $report->og_description ?: $seoDescription;
    $seoImage = $report->coverImage()?->url;
    $ogType = 'article';
    $articlePublishedTime = $report->published_at?->toAtomString();
    $articleModifiedTime = $report->updated_at->toAtomString();
    $articleAuthor = $report->author_name;

    $jsonLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $report->title,
        'description' => $report->excerpt,
        'author' => ['@type' => 'Person', 'name' => $report->author_name],
        'mainEntityOfPage' => route('reports.show', $report),
    ];
    if ($report->published_at) {
        $jsonLd['datePublished'] = $report->published_at->toAtomString();
    }
    $jsonLd['dateModified'] = $report->updated_at->toAtomString();
    if ($seoImage) {
        $jsonLd['image'] = $seoImage;
    }

    $faqJsonLd = $report->faqJsonLd();
@endphp

@push('structured-data')
    <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @if ($faqJsonLd)
        <script type="application/ld+json">{!! json_encode($faqJsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
    @endif
@endpush

@section('content')
    @if ($preview ?? false)
        <x-preview-banner :is-published="$report->is_published" />
    @endif

    <x-breadcrumbs :items="[
        ['label' => 'Startseite', 'url' => route('home')],
        ['label' => 'Reiseberichte', 'url' => route('reports.index')],
        ['label' => $report->title, 'url' => null],
    ]" />

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
        <div>
            @if ($report->region)
                <a href="{{ route('regions.show', $report->region) }}" class="text-xs font-semibold uppercase tracking-wide text-forest-600 hover:text-forest-900">{{ $report->region->name }}</a>
            @endif
            <h1 class="font-display text-3xl sm:text-4xl font-semibold text-forest-900 mt-2">{{ $report->title }}</h1>
            <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-forest-500 mt-3">
                <span class="font-medium text-forest-700">{{ $report->author_name }}</span>
                @if ($report->author_bio)
                    <span>&middot; {{ $report->author_bio }}</span>
                @endif
                @if ($report->published_at)
                    <span>&middot; {{ $report->published_at->format('d.m.Y') }}</span>
                @endif
                <span>&middot; {{ $report->reading_time_minutes }} Min. Lesezeit</span>
            </div>
        </div>

        <x-gallery :media="$report->media" :alt="$report->title" />

        <div class="prose prose-lg max-w-none prose-headings:font-display prose-headings:text-forest-900 prose-p:text-forest-700 prose-a:text-forest-700 prose-a:no-underline hover:prose-a:underline prose-img:rounded-2xl prose-td:align-top">
            {!! $report->content !!}
        </div>

        @if ($report->faq)
            <div class="border-t border-sand-200 pt-8">
                <h2 class="font-display text-2xl font-semibold text-forest-900 mb-4">Häufig gestellte Fragen</h2>
                <div class="space-y-2">
                    @foreach ($report->faq as $pair)
                        <details class="group rounded-xl ring-1 ring-sand-200 p-4">
                            <summary class="cursor-pointer font-medium text-forest-900 marker:content-none flex items-center justify-between gap-2">
                                {{ $pair['question'] }}
                                <span class="text-forest-400 group-open:rotate-180 transition-transform">⌄</span>
                            </summary>
                            <p class="text-forest-700 mt-3">{{ $pair['answer'] }}</p>
                        </details>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    @if ($similarReports->isNotEmpty())
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-10 border-t border-sand-200">
            <h2 class="font-display text-2xl font-semibold text-forest-900 mb-6">Weitere Reiseberichte</h2>
            <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($similarReports as $similar)
                    <x-report-card :report="$similar" />
                @endforeach
            </div>
        </div>
    @endif
@endsection
