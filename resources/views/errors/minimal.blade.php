<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} | Lieblingsorte</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { theme: { extend: { colors: { forest: { 100:'#dfe9e1', 500:'#3f7350', 600:'#2f5c3f', 700:'#264a33', 900:'#152b1e' }, sand: { 50:'#fdfbf7' } } } } };
    </script>
</head>
<body class="bg-sand-50 min-h-screen flex items-center justify-center px-4">
    <div class="text-center max-w-md">
        <p class="font-display text-6xl font-semibold text-forest-200" style="color:#bcd3c1">{{ $code }}</p>
        <h1 class="text-2xl font-semibold text-forest-900 mt-2">{{ $title }}</h1>
        <p class="text-forest-500 mt-3">{{ $message }}</p>
        <a href="{{ route('home') }}" class="inline-block mt-6 rounded-full bg-forest-700 hover:bg-forest-800 text-white font-semibold px-6 py-3 text-sm transition">Zur Startseite</a>
    </div>
</body>
</html>
