@props(['class' => ''])

<div class="{{ $class }}">
    <form action="{{ route('newsletter.store') }}" method="POST" class="flex flex-col sm:flex-row gap-2 max-w-sm">
        @csrf
        <label for="newsletter-email" class="sr-only">E-Mail-Adresse</label>
        <input
            id="newsletter-email"
            type="email"
            name="email"
            required
            placeholder="deine@email.de"
            class="flex-1 rounded-full px-4 py-2.5 text-sm text-forest-900 bg-white/95 focus:outline-none focus:ring-2 focus:ring-sand-400"
        >
        <button type="submit" class="rounded-full bg-sand-400 hover:bg-sand-300 text-forest-900 text-sm font-semibold px-5 py-2.5 transition">
            Anmelden
        </button>
    </form>
    @error('email')
        <p class="text-sand-300 text-xs mt-2">{{ $message }}</p>
    @enderror
    @if (session('status'))
        <p class="text-sand-200 text-xs mt-2">{{ session('status') }}</p>
    @endif
</div>
