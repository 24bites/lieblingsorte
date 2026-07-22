<x-mail::message>
# Fast geschafft!

Danke für dein Interesse an unserem Newsletter mit den besten Reisetipps. Bitte bestätige deine Anmeldung mit einem Klick:

<x-mail::button :url="$confirmUrl">
Anmeldung bestätigen
</x-mail::button>

Falls du dich nicht angemeldet hast, kannst du diese E-Mail einfach ignorieren – es passiert nichts weiter.

Viele Grüße,<br>
{{ config('app.name') }}
</x-mail::message>
