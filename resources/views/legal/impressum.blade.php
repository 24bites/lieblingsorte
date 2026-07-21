@extends('layouts.app')

@php
    $seoTitle = 'Impressum | '.\App\Models\Setting::get('site_name', 'Lieblingsorte');
    $seoDescription = 'Impressum und Anbieterkennzeichnung.';
@endphp

@section('content')
    <x-breadcrumbs :items="[
        ['label' => 'Startseite', 'url' => route('home')],
        ['label' => 'Impressum', 'url' => null],
    ]" />

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10 prose prose-forest">
        <h1 class="font-display text-3xl font-semibold text-forest-900 mb-6">Impressum</h1>

        <h2 class="font-semibold text-forest-900 mt-8 mb-2">Angaben gemäß § 5 TMG</h2>
        <p>
            Alexander Hettinger<br>
            Im Seif 5<br>
            54456 Tawern<br>
            Deutschland
        </p>

        <h2 class="font-semibold text-forest-900 mt-8 mb-2">Kontakt</h2>
        <p>E-Mail: <a href="mailto:mail@24bites.de" class="text-forest-700 underline">mail@24bites.de</a></p>

        <h2 class="font-semibold text-forest-900 mt-8 mb-2">Verantwortlich für den Inhalt nach § 55 Abs. 2 RStV</h2>
        <p>Alexander Hettinger (Anschrift wie oben)</p>

        <h2 class="font-semibold text-forest-900 mt-8 mb-2">Haftung für Inhalte</h2>
        <p>
            Als Diensteanbieter sind wir gemäß § 7 Abs. 1 TMG für eigene Inhalte auf diesen Seiten nach den allgemeinen
            Gesetzen verantwortlich. Nach §§ 8 bis 10 TMG sind wir als Diensteanbieter jedoch nicht verpflichtet,
            übermittelte oder gespeicherte fremde Informationen zu überwachen oder nach Umständen zu forschen, die auf
            eine rechtswidrige Tätigkeit hinweisen. Verpflichtungen zur Entfernung oder Sperrung der Nutzung von
            Informationen nach den allgemeinen Gesetzen bleiben hiervon unberührt. Eine diesbezügliche Haftung ist
            jedoch erst ab dem Zeitpunkt der Kenntnis einer konkreten Rechtsverletzung möglich. Bei Bekanntwerden von
            entsprechenden Rechtsverletzungen werden wir diese Inhalte umgehend entfernen.
        </p>

        <h2 class="font-semibold text-forest-900 mt-8 mb-2">Haftung für Links</h2>
        <p>
            Unser Angebot enthält Links zu externen Websites Dritter, auf deren Inhalte wir keinen Einfluss haben.
            Deshalb können wir für diese fremden Inhalte auch keine Gewähr übernehmen. Für die Inhalte der verlinkten
            Seiten ist stets der jeweilige Anbieter oder Betreiber der Seiten verantwortlich.
        </p>

        <h2 class="font-semibold text-forest-900 mt-8 mb-2">Urheberrecht</h2>
        <p>
            Die durch die Seitenbetreiber erstellten Inhalte und Werke auf diesen Seiten unterliegen dem deutschen
            Urheberrecht. Beiträge Dritter sind als solche gekennzeichnet. Sollten Sie trotzdem auf eine
            Urheberrechtsverletzung aufmerksam werden, bitten wir um einen entsprechenden Hinweis. Bei Bekanntwerden
            von Rechtsverletzungen werden wir derartige Inhalte umgehend entfernen.
        </p>
        <p class="text-sm text-forest-500 mt-4">
            Hinweis zu Bildquellen: Die auf dieser Website verwendeten Fotografien stammen, sofern nicht anders
            gekennzeichnet, von Wikimedia Commons und stehen unter freien Lizenzen (CC0, Public Domain, CC BY oder
            CC BY-SA). Eine vollständige Quellenangabe mit Fotograf/in und Lizenz findet sich auf der Seite
            <a href="{{ route('legal.bildquellen') }}" class="text-forest-700 underline">Bildquellen</a>.
        </p>
    </div>
@endsection
