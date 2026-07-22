<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') | Lieblingsorte</title>
    <meta name="robots" content="noindex, nofollow">

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

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
                            400: '#d3b57e', 500: '#c19a54',
                        },
                    },
                },
            },
        };
    </script>
    <script defer src="https://unpkg.com/alpinejs@3.14.1/dist/cdn.min.js"></script>
    <style>[x-cloak]{display:none!important}</style>
</head>
<body class="bg-sand-50 text-forest-900 antialiased" x-data="{ sidebarOpen: false }">
    <div class="lg:hidden flex items-center justify-between bg-forest-900 text-white px-4 h-14">
        <span class="font-semibold">Lieblingsorte Admin</span>
        <button @click="sidebarOpen = true" aria-label="Menü öffnen">
            <svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 7h16M4 12h16M4 17h16" stroke-linecap="round"/></svg>
        </button>
    </div>

    <div class="flex min-h-screen">
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'" class="fixed lg:sticky top-0 z-40 h-screen w-64 bg-forest-900 text-sand-100 flex flex-col transition-transform duration-200">
            <div class="h-16 flex items-center justify-between px-6 border-b border-white/10">
                <a href="{{ route('admin.dashboard') }}" class="font-display font-semibold text-white">Lieblingsorte</a>
                <button @click="sidebarOpen = false" class="lg:hidden" aria-label="Menü schließen">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 6l12 12M18 6 6 18" stroke-linecap="round"/></svg>
                </button>
            </div>

            <nav class="flex-1 px-3 py-6 space-y-1 text-sm">
                @php
                    $navItems = [
                        ['route' => 'admin.dashboard', 'label' => 'Dashboard', 'active' => 'admin.dashboard'],
                        ['route' => 'admin.regions.index', 'label' => 'Regionen', 'active' => 'admin.regions.*'],
                        ['route' => 'admin.tips.index', 'label' => 'Reisetipps', 'active' => 'admin.tips.*'],
                        ['route' => 'admin.reports.index', 'label' => 'Reiseberichte', 'active' => 'admin.reports.*'],
                        ['route' => 'admin.ai-region-generator.create', 'label' => 'KI-Regionsgenerator', 'active' => 'admin.ai-region-generator.*'],
                        ['route' => 'admin.ai-suggestions.index', 'label' => 'KI-Vorschläge', 'active' => 'admin.ai-suggestions.*'],
                        ['route' => 'admin.categories.index', 'label' => 'Kategorien', 'active' => 'admin.categories.*'],
                        ['route' => 'admin.labels.index', 'label' => 'Labels', 'active' => 'admin.labels.*'],
                        ['route' => 'admin.media.index', 'label' => 'Medien', 'active' => 'admin.media.*'],
                        ['route' => 'admin.social-hub.index', 'label' => 'Social Hub', 'active' => 'admin.social-hub.*'],
                        ['route' => 'admin.settings.edit', 'label' => 'Einstellungen', 'active' => 'admin.settings.*'],
                    ];
                    if (auth()->user()?->isAdmin()) {
                        $navItems[] = ['route' => 'admin.users.index', 'label' => 'Benutzer', 'active' => 'admin.users.*'];
                    }
                @endphp
                @foreach ($navItems as $item)
                    <a href="{{ route($item['route']) }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 transition {{ request()->routeIs($item['active']) ? 'bg-forest-700 text-white' : 'text-sand-200 hover:bg-forest-800' }}">
                        {{ $item['label'] }}
                    </a>
                @endforeach
            </nav>

            <div class="px-6 py-5 border-t border-white/10 text-xs text-sand-300">
                <p class="text-white font-medium">{{ auth()->user()->name }}</p>
                <p>{{ auth()->user()->email }}</p>
                <form action="{{ route('admin.logout') }}" method="POST" class="mt-3">
                    @csrf
                    <button type="submit" class="text-sand-300 hover:text-white underline">Abmelden</button>
                </form>
                <a href="{{ route('home') }}" class="block mt-2 text-sand-300 hover:text-white">← Zur Website</a>
            </div>
        </aside>

        <div class="fixed inset-0 bg-black/40 z-30 lg:hidden" x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"></div>

        <div class="flex-1 min-w-0">
            <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-10 py-8">
                @if (session('status'))
                    <div class="mb-6 rounded-xl bg-forest-100 text-forest-800 text-sm px-4 py-3">{{ session('status') }}</div>
                @endif
                @if ($errors->any())
                    <div class="mb-6 rounded-xl bg-red-50 text-red-700 text-sm px-4 py-3">
                        <ul class="list-disc list-inside">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
