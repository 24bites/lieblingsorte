@extends('layouts.admin')

@section('title', 'Social-Hub-Beitrag')

@php
    $platformLabels = [
        'pinterest' => 'Pinterest',
        'facebook' => 'Facebook',
        'x' => 'X',
        'telegram' => 'Telegram',
        'whatsapp' => 'WhatsApp',
    ];
    $postable = $socialPost->postable;
    $postableTitle = $postable?->name ?? $postable?->title ?? '(Inhalt gelöscht)';
@endphp

@section('content')
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-semibold text-forest-900">{{ $platformLabels[$socialPost->platform] }}: {{ $postableTitle }}</h1>
            <p class="text-sm text-forest-500 mt-1">
                Status:
                <span class="font-medium {{ $socialPost->status === 'sent' ? 'text-forest-700' : ($socialPost->status === 'failed' ? 'text-red-600' : 'text-sand-700') }}">
                    @if ($socialPost->status === 'sent') Gesendet am {{ $socialPost->sent_at->format('d.m.Y H:i') }}
                    @elseif ($socialPost->status === 'failed') Fehlgeschlagen
                    @else Entwurf
                    @endif
                </span>
            </p>
        </div>
        <a href="{{ route('admin.social-hub.index', ['type' => $postable instanceof \App\Models\Region ? 'region' : ($postable instanceof \App\Models\TravelReport ? 'report' : 'tip')]) }}" class="text-sm font-semibold text-forest-600 hover:text-forest-900">&larr; Zurück zum Social Hub</a>
    </div>

    @if ($socialPost->status === 'failed' && $socialPost->error_message)
        <div class="bg-red-50 ring-1 ring-red-200 text-red-800 rounded-2xl p-4 mb-6 text-sm">{{ $socialPost->error_message }}</div>
    @endif

    <div class="grid lg:grid-cols-3 gap-6 max-w-5xl">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-4">
                <form action="{{ route('admin.social-hub.update', $socialPost) }}" method="POST" class="space-y-3">
                    @csrf
                    @method('PUT')
                    <label class="block text-sm font-medium text-forest-800">Text</label>
                    <textarea name="caption" rows="8" class="w-full rounded-xl border border-sand-300 px-4 py-2.5 text-sm">{{ old('caption', $socialPost->caption) }}</textarea>
                    @error('caption') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    <button type="submit" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold px-4 py-2">Text speichern</button>
                </form>
            </div>

            <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-6 space-y-3">
                <h2 class="font-semibold text-forest-900">Veröffentlichen</h2>
                @if ($canSendViaTelegram)
                    <p class="text-sm text-forest-500">Telegram ist konfiguriert &ndash; der Beitrag kann direkt gesendet werden.</p>
                    <form action="{{ route('admin.social-hub.send', $socialPost) }}" method="POST">
                        @csrf
                        <button type="submit" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold px-4 py-2">Jetzt an Telegram senden</button>
                    </form>
                    @error('send') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                @else
                    <p class="text-sm text-forest-500">
                        Öffnet den offiziellen Freigabe-Dialog der Plattform mit vorausgefülltem Text &ndash; dort einmal
                        bestätigen, um wirklich zu posten (kein API-Zugang hinterlegt, daher kein vollautomatischer Versand).
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <a href="{{ $shareLink }}" target="_blank" rel="noopener noreferrer" class="rounded-xl bg-forest-700 hover:bg-forest-800 text-white text-sm font-semibold px-4 py-2">
                            Bei {{ $platformLabels[$socialPost->platform] }} öffnen
                        </a>
                        @if ($socialPost->status !== 'sent')
                            <form action="{{ route('admin.social-hub.mark-sent', $socialPost) }}" method="POST">
                                @csrf
                                <button type="submit" class="rounded-xl border border-sand-300 hover:bg-sand-100 text-forest-700 text-sm font-semibold px-4 py-2">Als gesendet markieren</button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>

            <form action="{{ route('admin.social-hub.destroy', $socialPost) }}" method="POST" onsubmit="return confirm('Entwurf wirklich löschen?');">
                @csrf @method('DELETE')
                <button type="submit" class="text-sm text-red-600 hover:text-red-800 font-medium">Entwurf löschen</button>
            </form>
        </div>

        <div class="space-y-4">
            @if ($socialPost->image_url)
                <img src="{{ $socialPost->image_url }}" alt="" class="w-full rounded-2xl ring-1 ring-sand-200 object-cover aspect-square">
            @endif
            <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-4 text-sm text-forest-600 space-y-1">
                <p class="text-forest-400 text-xs">Verlinkte Seite</p>
                <a href="{{ $socialPost->link_url }}" target="_blank" class="underline break-all">{{ $socialPost->link_url }}</a>
            </div>
        </div>
    </div>
@endsection
