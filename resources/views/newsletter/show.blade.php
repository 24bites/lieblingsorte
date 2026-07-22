@extends('layouts.app')

@php
    $seoTitle = 'Newsletter | '.\App\Models\Setting::get('site_name', 'Lieblingsorte');
    $seoDescription = 'Melde dich für unseren Newsletter mit den besten Reisetipps an.';
@endphp

@section('content')
    <x-breadcrumbs :items="[
        ['label' => 'Startseite', 'url' => route('home')],
        ['label' => 'Newsletter', 'url' => null],
    ]" />

    <div class="max-w-xl mx-auto px-4 sm:px-6 lg:px-8 py-14">
        <div class="bg-white rounded-2xl ring-1 ring-sand-200 p-8 text-center">
            <h1 class="font-display text-2xl sm:text-3xl font-semibold text-forest-900 mb-3">Newsletter</h1>
            <p class="text-forest-600 mb-6">
                Handverlesene Reisetipps, neue Regionen und Geheimtipps direkt in dein Postfach –
                unregelmäßig, dafür ohne Spam. Du kannst dich jederzeit über einen Link in jeder
                E-Mail wieder abmelden.
            </p>
            <x-newsletter-form class="mx-auto text-left" :dark="false" />
        </div>
    </div>
@endsection
