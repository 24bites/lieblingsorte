@extends('layouts.app')

@php
    $seoTitle = 'Datenschutzerklärung | '.\App\Models\Setting::get('site_name', 'Lieblingsorte');
    $seoDescription = 'Datenschutzerklärung gemäß DSGVO.';
@endphp

@section('content')
    <x-breadcrumbs :items="[
        ['label' => 'Startseite', 'url' => route('home')],
        ['label' => 'Datenschutz', 'url' => null],
    ]" />

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-10 prose prose-forest">
        <h1 class="font-display text-3xl font-semibold text-forest-900 mb-6">Datenschutzerklärung</h1>

        <h2 class="font-semibold text-forest-900 mt-8 mb-2">1. Verantwortlicher</h2>
        <p>
            Verantwortlich für die Datenverarbeitung auf dieser Website ist:<br>
            Alexander Hettinger, Im Seif 5, 54456 Tawern, Deutschland<br>
            E-Mail: <a href="mailto:mail@24bites.de" class="text-forest-700 underline">mail@24bites.de</a>
        </p>

        <h2 class="font-semibold text-forest-900 mt-8 mb-2">2. Erhebung und Speicherung personenbezogener Daten</h2>
        <p>
            Beim Aufruf dieser Website erfassen wir automatisiert technische Informationen (Server-Logfiles), die Ihr
            Browser übermittelt: IP-Adresse, Datum und Uhrzeit der Anfrage, Browsertyp und -version, Betriebssystem
            sowie die zuvor besuchte Seite (Referrer). Diese Daten dienen der technischen Bereitstellung und
            Absicherung der Website (Art. 6 Abs. 1 lit. f DSGVO, berechtigtes Interesse) und werden nicht mit anderen
            Datenquellen zusammengeführt.
        </p>
        <p>
            Wenn Sie uns über das Kontaktformular oder per E-Mail kontaktieren, verarbeiten wir die von Ihnen
            angegebenen Daten (z. B. Name, E-Mail-Adresse, Nachricht) ausschließlich zur Bearbeitung Ihrer Anfrage
            (Art. 6 Abs. 1 lit. b DSGVO).
        </p>

        <h2 class="font-semibold text-forest-900 mt-8 mb-2">3. Cookies und lokale Speicherung</h2>
        <p>Diese Website verwendet folgende Cookies bzw. Speichertechnologien:</p>
        <ul>
            <li>
                <strong>Technisch notwendige Session-Cookie</strong> (Laravel-Session): wird benötigt, damit die
                Website grundlegende Funktionen wie die Favoritenliste, das Kontaktformular und den Schutz vor
                Cross-Site-Request-Forgery (CSRF-Token) bereitstellen kann. Dieses Cookie wird nach Ende Ihres
                Besuchs bzw. nach Ablauf der Sitzung automatisch gelöscht und ist von der Einwilligungspflicht nach
                § 25 Abs. 2 Nr. 2 TTDSG ausgenommen (Art. 6 Abs. 1 lit. f DSGVO).
            </li>
            <li>
                <strong>Cookie-Einwilligung</strong>: Ihre Auswahl im Cookie-Banner wird lokal in Ihrem Browser
                gespeichert (Local Storage), damit Sie beim nächsten Besuch nicht erneut gefragt werden.
            </li>
            <li>
                <strong>Favoriten (nicht angemeldete Besucher)</strong>: Von Ihnen gemerkte Reisetipps werden
                ausschließlich in Ihrer Server-Session gespeichert und nicht dauerhaft oder geräteübergreifend
                gesichert.
            </li>
        </ul>

        <h2 class="font-semibold text-forest-900 mt-8 mb-2">4. Google Analytics</h2>
        <p>
            Sofern in den Cookie-Einstellungen zugestimmt wurde, setzen wir Google Analytics ein, einen
            Webanalysedienst der Google Ireland Limited, Gordon House, Barrow Street, Dublin 4, Irland ("Google").
            Google Analytics verwendet Cookies, die eine Analyse der Benutzung der Website ermöglichen. Die durch das
            Cookie erzeugten Informationen werden in der Regel an einen Server von Google übertragen und dort
            gespeichert.
        </p>
        <p>
            Diese Website nutzt Google Analytics ausschließlich mit aktivierter IP-Anonymisierung. Die
            Datenverarbeitung erfolgt nur nach Ihrer ausdrücklichen Einwilligung über den Cookie-Banner
            (Art. 6 Abs. 1 lit. a DSGVO, § 25 Abs. 1 TTDSG). Sie können Ihre Einwilligung jederzeit mit Wirkung für
            die Zukunft über den Link „Cookie-Einstellungen“ im Footer widerrufen.
        </p>

        <h2 class="font-semibold text-forest-900 mt-8 mb-2">5. Werbung</h2>
        <p>
            Diese Website hält die technische Möglichkeit vor, an einzelnen Stellen (z. B. Kopfbereich, Seitenleiste,
            zwischen redaktionellen Inhalten) Werbeanzeigen darzustellen. Sofern und sobald Werbeflächen aktiv
            geschaltet werden, informieren wir an dieser Stelle über den jeweiligen Anbieter, die Art der
            Datenverarbeitung (z. B. durch Drittanbieter-Cookies) und Ihre Widerspruchsmöglichkeiten. Aktuell sind
            keine Werbenetzwerke aktiv geschaltet.
        </p>

        <h2 class="font-semibold text-forest-900 mt-8 mb-2">6. Externe Inhalte (Kartendarstellung)</h2>
        <p>
            Zur Darstellung von Standortkarten binden wir Kartenmaterial von OpenStreetMap ein. Beim Aufruf einer
            Seite mit Karte wird eine Verbindung zu den Servern von OpenStreetMap-Mitwirkenden hergestellt, wobei
            technisch bedingt Ihre IP-Adresse übertragen wird. Weitere Informationen finden Sie in der
            Datenschutzerklärung von OpenStreetMap.
        </p>

        <h2 class="font-semibold text-forest-900 mt-8 mb-2">7. Ihre Rechte</h2>
        <p>
            Sie haben jederzeit das Recht auf unentgeltliche Auskunft über Ihre gespeicherten personenbezogenen
            Daten, deren Herkunft und Empfänger sowie den Zweck der Datenverarbeitung und ggf. ein Recht auf
            Berichtigung, Sperrung oder Löschung dieser Daten. Zudem steht Ihnen ein Beschwerderecht bei der
            zuständigen Aufsichtsbehörde zu. Bei Fragen zur Erhebung, Verarbeitung oder Nutzung Ihrer
            personenbezogenen Daten können Sie sich jederzeit unter der oben genannten Adresse an uns wenden.
        </p>

        <h2 class="font-semibold text-forest-900 mt-8 mb-2">8. Newsletter</h2>
        <p>
            Wenn Sie sich für unseren Newsletter anmelden, verwenden wir Ihre E-Mail-Adresse ausschließlich zum
            Versand der Reisetipps, denen Sie zugestimmt haben (Art. 6 Abs. 1 lit. a DSGVO). Sie können die
            Einwilligung jederzeit durch eine Nachricht an die oben genannte Kontaktadresse widerrufen.
        </p>
    </div>
@endsection
