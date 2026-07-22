@props(['class' => '', 'dark' => true])

@php
    $labelColor = $dark ? 'text-sand-200' : 'text-forest-600';
    $linkColor = $dark ? 'hover:text-white' : 'text-forest-800 hover:text-forest-900';
    $messageColor = $dark ? 'text-sand-200' : 'text-forest-700';
    $errorColor = $dark ? 'text-sand-300' : 'text-red-600';
@endphp

<div class="{{ $class }}">
    <form action="{{ route('newsletter.store') }}" method="POST" class="max-w-sm">
        @csrf
        <div class="flex flex-col sm:flex-row gap-2">
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
        </div>

        {{-- Honeypot: unsichtbar für Menschen, verlockend für Formular-Bots. Wird es ausgefüllt, tut der Server so, als wäre alles gutgegangen. --}}
        <div class="absolute left-[-9999px]" aria-hidden="true">
            <label for="newsletter-homepage">Homepage</label>
            <input type="text" id="newsletter-homepage" name="homepage" tabindex="-1" autocomplete="off">
        </div>

        <label class="flex items-start gap-2 mt-3 text-xs {{ $labelColor }}">
            <input type="checkbox" name="consent" value="1" required class="mt-0.5 rounded border-sand-300">
            <span>
                Ich möchte den Newsletter mit Reisetipps erhalten und stimme der Verarbeitung meiner E-Mail-Adresse
                gemäß der <a href="{{ route('legal.datenschutz') }}" class="underline {{ $linkColor }}">Datenschutzerklärung</a> zu.
            </span>
        </label>
    </form>
    @error('email')
        <p class="{{ $errorColor }} text-xs mt-2">{{ $message }}</p>
    @enderror
    @error('consent')
        <p class="{{ $errorColor }} text-xs mt-2">{{ $message }}</p>
    @enderror
    @if (session('status'))
        <p class="{{ $messageColor }} text-xs mt-2">{{ session('status') }}</p>
    @endif
</div>
