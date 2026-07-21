<!doctype html>
<html lang="de" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    @include('partials.seo')

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        forest: {
                            50: '#f2f6f3', 100: '#dfe9e1', 200: '#bcd3c1', 300: '#8fb599',
                            400: '#5f9070', 500: '#3f7350', 600: '#2f5c3f', 700: '#264a33',
                            800: '#1f3b29', 900: '#152b1e',
                        },
                        sand: {
                            50: '#fdfbf7', 100: '#f7f1e6', 200: '#eee2ca', 300: '#e2cfa8',
                            400: '#d3b57e', 500: '#c19a54', 600: '#a67f42', 700: '#856436',
                            800: '#6b502f', 900: '#584229',
                        },
                    },
                    fontFamily: {
                        display: ['Fraunces', 'serif'],
                        sans: ['Inter', 'system-ui', 'sans-serif'],
                    },
                },
            },
        };
    </script>
    <style>
        [x-cloak] { display: none !important; }
        body { font-family: 'Inter', system-ui, sans-serif; }
        .font-display { font-family: 'Fraunces', serif; }
    </style>

    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
    <script defer src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <script defer src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.14.1/dist/cdn.min.js"></script>
</head>
<body class="bg-sand-50 text-forest-900 antialiased">
    <a href="#main" class="sr-only focus:not-sr-only focus:absolute focus:z-50 focus:bg-forest-800 focus:text-white focus:px-4 focus:py-2">Zum Inhalt springen</a>

    <x-site-header />

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <x-ad-slot position="header" />
    </div>

    @if (session('status'))
        <div class="bg-forest-600 text-white text-sm text-center py-2 px-4">{{ session('status') }}</div>
    @endif

    <main id="main">
        @yield('content')
    </main>

    <x-site-footer />

    <x-cookie-consent />

    @php $gaMeasurementId = \App\Models\Setting::get('ga_measurement_id', ''); @endphp
    @if ($gaMeasurementId !== '')
        <script>
            (function () {
                var measurementId = @json($gaMeasurementId);
                var loaded = false;

                function loadGoogleAnalytics() {
                    if (loaded || !window.hasAnalyticsConsent()) return;
                    loaded = true;

                    var script = document.createElement('script');
                    script.async = true;
                    script.src = 'https://www.googletagmanager.com/gtag/js?id=' + measurementId;
                    document.head.appendChild(script);

                    window.dataLayer = window.dataLayer || [];
                    function gtag() { window.dataLayer.push(arguments); }
                    window.gtag = gtag;
                    gtag('js', new Date());
                    gtag('config', measurementId, { anonymize_ip: true });
                }

                document.addEventListener('DOMContentLoaded', loadGoogleAnalytics);
                window.addEventListener('cookie-consent-updated', loadGoogleAnalytics);
            })();
        </script>
    @endif

    @stack('scripts')
</body>
</html>
