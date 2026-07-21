<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin-Login | Lieblingsorte</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { forest: { 600: '#2f5c3f', 700: '#264a33', 900: '#152b1e' }, sand: { 50: '#fdfbf7', 200: '#eee2ca' } } } } };
    </script>
</head>
<body class="bg-forest-900 min-h-screen flex items-center justify-center px-4">
    <div class="w-full max-w-sm bg-white rounded-3xl shadow-xl p-8">
        <div class="flex items-center gap-2 mb-6">
            <svg class="w-7 h-7 text-forest-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path d="M12 21c-4.5-4.2-7-7.9-7-11a7 7 0 1 1 14 0c0 3.1-2.5 6.8-7 11Z" stroke-linecap="round" stroke-linejoin="round" />
                <circle cx="12" cy="10" r="2.3" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
            <span class="text-lg font-semibold text-forest-900">Lieblingsorte Admin</span>
        </div>

        @if ($errors->any())
            <div class="mb-4 rounded-xl bg-red-50 text-red-700 text-sm px-4 py-3">
                {{ $errors->first() }}
            </div>
        @endif

        <form action="{{ route('admin.login.store') }}" method="POST" class="space-y-4">
            @csrf
            <div>
                <label for="email" class="block text-sm font-medium text-forest-800 mb-1">E-Mail</label>
                <input id="email" name="email" type="email" required autofocus value="{{ old('email') }}" class="w-full rounded-xl border border-sand-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-forest-400">
            </div>
            <div>
                <label for="password" class="block text-sm font-medium text-forest-800 mb-1">Passwort</label>
                <input id="password" name="password" type="password" required class="w-full rounded-xl border border-sand-200 px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-forest-400">
            </div>
            <label class="flex items-center gap-2 text-sm text-forest-600">
                <input type="checkbox" name="remember" class="rounded"> Angemeldet bleiben
            </label>
            <button type="submit" class="w-full rounded-xl bg-forest-700 hover:bg-forest-800 text-white font-semibold py-2.5 text-sm transition">Anmelden</button>
        </form>

        <a href="{{ route('home') }}" class="block text-center text-xs text-forest-400 hover:text-forest-600 mt-6">← Zurück zur Website</a>
    </div>
</body>
</html>
