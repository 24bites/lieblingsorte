@php
    $seoTitle = $seoTitle ?? \App\Models\Setting::get('site_name', 'Lieblingsorte');
    $seoDescription = $seoDescription ?? \App\Models\Setting::get('site_description', '');
    $canonicalUrl = $canonicalUrl ?? url()->current();
    $seoImage = $seoImage ?? asset('images/og-default.jpg');
    $ogType = $ogType ?? 'website';
@endphp
<title>{{ $seoTitle }}</title>
<meta name="description" content="{{ \Illuminate\Support\Str::limit($seoDescription, 160) }}">
<link rel="canonical" href="{{ $canonicalUrl }}">

{{-- og:type=article + article:* tags are what Pinterest's Rich Pins reads for the
     "Article" rich pin format - without these it falls back to a bare link preview. --}}
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:title" content="{{ $seoTitle }}">
<meta property="og:description" content="{{ \Illuminate\Support\Str::limit($seoDescription, 200) }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:image" content="{{ $seoImage }}">
<meta property="og:site_name" content="{{ \App\Models\Setting::get('site_name', 'Lieblingsorte') }}">
@if ($ogType === 'article')
    @isset($articlePublishedTime)
        <meta property="article:published_time" content="{{ $articlePublishedTime }}">
    @endisset
    @isset($articleModifiedTime)
        <meta property="article:modified_time" content="{{ $articleModifiedTime }}">
    @endisset
    @isset($articleAuthor)
        <meta property="article:author" content="{{ $articleAuthor }}">
    @endisset
@endif

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
<meta name="twitter:description" content="{{ \Illuminate\Support\Str::limit($seoDescription, 200) }}">
<meta name="twitter:image" content="{{ $seoImage }}">

@stack('structured-data')
