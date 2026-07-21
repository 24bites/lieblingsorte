# Lieblingsorte

Ein redaktionelles Reiseportal mit kuratierten Reisetipps für Städte und Regionen. Jede Region und jeder Reisetipp erhält automatisch eine eigene URL — neue Inhalte werden ausschließlich über den Adminbereich gepflegt, ohne Codeänderungen.

Beispiel-URLs nach dem Seed:

```
/                          Startseite
/suedtirol                 Region Südtirol (20 Reisetipps)
/suedtirol/kalterer-see    Reisetipp-Detailseite
/allgaeu                   Region Allgäu (20 Reisetipps)
/allgaeu/breitachklamm     Reisetipp-Detailseite
```

Der Seeder legt insgesamt **8 Regionen mit je 20 Reisetipps (160 Reisetipps)** an:

| Region | Land | Slug |
|---|---|---|
| Südtirol | Italien | `/suedtirol` |
| Allgäu | Deutschland | `/allgaeu` |
| Trier | Deutschland | `/trier` |
| Luxemburg | Luxemburg | `/luxemburg` |
| Düsseldorf | Deutschland | `/duesseldorf` |
| Lissabon | Portugal | `/lissabon` |
| Faro | Portugal | `/faro` |
| Mauritius | Mauritius | `/mauritius` |

## Technischer Stack

- **PHP 8.2+** (getestet mit dem in XAMPP gebündelten PHP 8.2.4)
- **Laravel 12** (Composer, Eloquent, Migrations, Seeders, Form Requests, Auth)
- **MySQL/MariaDB** (getestet mit XAMPP/MariaDB 10.4)
- **Blade** Templates, komponentenbasiert
- **Tailwind CSS**, **Alpine.js** und **Leaflet** über CDN eingebunden (kein Node.js/Vite-Build nötig, siehe „Abweichungen vom Standard-Stack“ unten)
- **OpenStreetMap** als kostenlose Kartenquelle

### Abweichung vom ursprünglich geplanten Stack

Der Entwicklungsauftrag sah Vite als Build-Tool für Tailwind/Alpine vor. Da auf der Zielumgebung bewusst kein Node.js installiert werden sollte ("nur vorhandene Bordmittel nutzen": PHP und MySQL), wurden Tailwind CSS, Alpine.js und Leaflet stattdessen per CDN `<script>`/`<link>`-Tag eingebunden. Das Laravel-Backend (Routing, Eloquent, Migrations, Auth, Validation) ist davon unberührt und vollständig funktionsfähig. Für einen produktiven Einsatz sollte Tailwind später über die CLI/PostCSS statt der Play-CDN kompiliert werden (siehe „Bekannte Einschränkungen“).

## Voraussetzungen

- PHP >= 8.2 mit den Erweiterungen: `pdo_mysql`, `mbstring`, `openssl`, `gd`, `fileinfo`, `curl`, `zip`, `bcmath`
- Composer 2
- MySQL 8 oder MariaDB 10.4+
- Ein lokaler Webserver (XAMPP/MAMP) oder `php artisan serve`

## Installation

```bash
composer install
cp .env.example .env
php artisan key:generate
```

`.env` anpassen (Datenbankzugang):

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=place2be
DB_USERNAME=root
DB_PASSWORD=
# Bei lokalem XAMPP/MAMP ggf. zusätzlich den Unix-Socket angeben:
# DB_SOCKET=/Applications/XAMPP/xamppfiles/var/mysql/mysql.sock
```

Datenbank anlegen (falls noch nicht vorhanden):

```bash
mysql -u root -e "CREATE DATABASE place2be CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

Migrationen, Seed-Daten, Storage-Link und Server:

```bash
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

Die Anwendung ist danach unter `http://127.0.0.1:8000` erreichbar (Port muss zu `APP_URL` in `.env` passen, sonst werden Bild-URLs falsch generiert).

Optional — echte Fotos statt generierter Illustrationen nachladen (benötigt Internetzugang, siehe „Bildverwaltung“):

```bash
php artisan images:fetch-real
```

## Adminzugang (nur lokale Entwicklung)

Der Seeder legt zwei Benutzer an:

| Rolle  | E-Mail                          | Passwort   |
|--------|----------------------------------|------------|
| admin  | `admin@lieblingsorte.test`       | `admin123` |
| editor | `redaktion@lieblingsorte.test`   | `editor123`|

Adminbereich: `http://127.0.0.1:8000/admin`

**Diese Zugangsdaten sind ausschließlich für die lokale Entwicklungsumgebung bestimmt und dürfen niemals in einer produktiven Umgebung verwendet werden.**

## Tests ausführen

```bash
php artisan test
```

Die Test-Suite (31 Tests, PHPUnit, Feature-Tests) läuft gegen eine In-Memory-SQLite-Datenbank (siehe `phpunit.xml`) und benötigt keine MySQL-Verbindung. Abgedeckt sind u. a.:

- Start-, Regions- und Reisetipp-Seiten laden korrekt
- Nicht veröffentlichte Regionen/Reisetipps sind öffentlich nicht erreichbar
- Suche liefert Ergebnisse bzw. Alternativen bei leerer Trefferliste
- Filter (Label, kostenlos) funktionieren
- Admin-Login funktioniert, geschützte Routen sind für Gäste gesperrt
- Regionen und Reisetipps können im Adminbereich angelegt/bearbeitet/gelöscht werden
- Slugs werden automatisch erzeugt, Kollisionen werden aufgelöst (`-2`, `-3`, …)
- Bild-Uploads werden nach Dateityp, Größe und Abmessungen validiert

## Projektstruktur (Auszug)

```
app/
  Http/Controllers/          öffentliche Controller (Home, Region, TravelTip, Search, Category, Favorite, Newsletter, Sitemap)
  Http/Controllers/Admin/    Adminbereich (Dashboard, Region, TravelTip, Category, Label, Media, Settings)
  Http/Controllers/Auth/     Admin-Login/-Logout
  Http/Requests/             Form Requests mit serverseitiger Validierung
  Models/                    Eloquent-Modelle inkl. HasSlug-Trait für automatische, kollisionssichere Slugs
  Support/                   ImageGenerator (Platzhalterbilder), ImageUploadService (Admin-Uploads)
  Console/Commands/          FetchRealPhotos (`images:fetch-real`, lädt echte Fotos von Wikimedia Commons nach)
database/
  migrations/                vollständiges Schema (Regionen, Reisetipps, Kategorien, Labels, Medien, Favoriten, …)
  seeders/                   CategorySeeder, LabelSeeder, UserSeeder, SettingSeeder, je ein Seeder pro Region (Suedtirol, Allgaeu, Trier, Luxemburg, Duesseldorf, Lissabon, Faro, Mauritius)
resources/views/
  layouts/, components/      Basislayout, Header/Footer, Karten-/Galerie-/Filter-Komponenten
  home, regions, tips, categories, search, favorites, admin/*, errors/*
routes/web.php               vollständige Routendefinition (öffentlich + Admin), dynamische {region}/{tip}-Routen am Ende
tests/Feature/               31 automatisierte Tests
```

## Bildverwaltung

Der Seeder selbst erzeugt zunächst **lokal generierte, editoriell gestaltete Platzhalterbilder** (`app/Support/ImageGenerator.php`) — deterministische, GD-basierte Illustrationen (Gradient-Himmel, Bergsilhouetten, See-/Wasserfall-/Ortschafts-Motive je nach Kategorie). Das garantiert, dass `php artisan migrate:fresh --seed` immer offline und ohne externe Abhängigkeiten ein visuell vollständiges Ergebnis liefert — keine grauen Platzhalter, keine externen Hotlinks, jedes Bild hat einen sprechenden Dateinamen und einen sinnvollen Alt-Text.

**Echte Fotos nachladen:** Zusätzlich gibt es den Befehl

```bash
php artisan images:fetch-real
```

Er durchsucht für jede Region und jeden Reisetipp Wikimedia Commons nach passenden, frei lizenzierten Fotos (nur CC0, Public Domain, CC BY oder CC BY-SA werden akzeptiert), filtert nach Quelldateiformat (nur echte .jpg-Fotos, keine als .jpg gerenderten PDF-/Buchseiten), Mindestauflösung, Seitenverhältnis und unerwünschten Begriffen (Karten, Wappen, Logos, Stiche, Gemälde, …), lädt die Treffer herunter und ersetzt damit die Illustrationen. Alle Quellen werden mit Fotograf/in, Lizenz und Commons-Link in `storage/app/credits.json` gespeichert, zusätzlich lokal als [`CREDITS.md`](CREDITS.md) dokumentiert (nur für die Repository-Ansicht) und öffentlich auf der Seite **„Bildquellen“** (`/bildquellen`, verlinkt aus dem Impressum) angezeigt — dort direkt aus `credits.json` gerendert, sodass die Seite nach jedem `images:fetch-real`-Lauf automatisch aktuell ist. Für einzelne, sehr spezielle Orte ohne passendes freies Foto (z. B. „Knottnkino“, „Füssen“) bleibt die generierte Illustration automatisch als Fallback erhalten. Einzelne Orte lassen sich gezielt erneut abrufen: `php artisan images:fetch-real --only=kalterer-see`.

**Manuell ersetzen:** Im Adminbereich kann unter „Regionen bearbeiten“ bzw. „Reisetipps bearbeiten“ jederzeit ein neues Titelbild oder weitere Galeriebilder hochgeladen werden (JPG/PNG/WebP, geprüft auf Dateityp, Größe und Mindestabmessungen) — das neue Bild ersetzt automatisch das vorhandene Titelbild. Die Medien-Tabelle ist polymorph (`Media` gehört zu `Region` oder `TravelTip`) und damit unabhängig vom Bildursprung.

Uploads werden unter `storage/app/public/{regions,tips}/{slug}/` gespeichert und über `php artisan storage:link` öffentlich unter `/storage/...` bereitgestellt. Wenn die PHP-GD-Erweiterung WebP unterstützt (`function_exists('imagewebp')`), wird für Uploads automatisch zusätzlich eine WebP-Variante erzeugt.

## KI-Funktionen (optional, OpenAI)

Hinterlegst du einen OpenAI-API-Key, schaltet der Adminbereich zwei zusätzliche Funktionen frei. Ohne Key bleiben beide unsichtbar bzw. zeigen nur einen Hinweis — der Rest der Seite funktioniert unverändert. Der Key kann auf zwei Wegen hinterlegt werden:

1. **Admin → Einstellungen → „KI-Funktionen (OpenAI)“** (empfohlen): wird verschlüsselt in der Datenbank gespeichert, ist ohne Neustart sofort aktiv und wird aus Sicherheitsgründen nie wieder im Klartext angezeigt (nur eine maskierte Vorschau der letzten 4 Zeichen). Ein hier hinterlegter Key hat Vorrang vor der `.env`-Variable.
2. **`.env`-Datei** (Fallback, z. B. für Server-weite Konfiguration ohne Admin-Login):
   ```env
   OPENAI_API_KEY=sk-...
   OPENAI_IMAGE_MODEL=gpt-image-1
   OPENAI_TEXT_MODEL=gpt-4o-mini
   ```

Die Auflösung erfolgt zentral über `App\Support\OpenAiConfig::apiKey()`.

**Bild mit KI generieren:** In „Regionen bearbeiten“ bzw. „Reisetipps bearbeiten“ steht im Bereich „Bilder“ ein Prompt-Feld zur Verfügung. Nach dem Absenden erzeugt `App\Support\OpenAiImageGenerator` über die OpenAI-Bild-API ein Foto/Illustration und legt es als weiteres Medium (bzw. als Titelbild, falls noch keines existiert) ab — identisch zum manuellen Upload, nur ohne eigene Bilddatei.

**KI-Regionsgenerator:** Über den Menüpunkt „KI-Regionsgenerator“ lässt sich aus einem einzelnen Orts-/Regionsnamen (z. B. „Salzburg“) automatisch ein vollständiger Entwurf erzeugen — inklusive Beschreibungstexten und 5–20 Reisetipps (`App\Support\OpenAiRegionDrafter`). **Der Entwurf wird immer unveröffentlicht gespeichert.** KI-generierte Fakten (Adressen, Öffnungszeiten, Preise, Koordinaten) können falsch oder veraltet sein und müssen vor der Veröffentlichung redaktionell geprüft werden.

## SEO

- Dynamische `<title>`, Meta-Description, Canonical-URL, OpenGraph- und Twitter-Card-Tags pro Seite (`resources/views/partials/seo.blade.php`)
- JSON-LD strukturierte Daten: `TouristDestination` (Regionsseiten), `TouristAttraction` (Reisetipp-Seiten), `BreadcrumbList` (alle Seiten mit Breadcrumb)
- `robots.txt` (sperrt `/admin` und `/favoriten`, verweist auf die Sitemap)
- `/sitemap.xml` wird zur Laufzeit aus den veröffentlichten Regionen und Reisetipps generiert — neue veröffentlichte Inhalte erscheinen automatisch

## Cookie-Consent, Google Analytics & Werbeflächen

- **Cookie-Banner** (`resources/views/components/cookie-consent.blade.php`): DSGVO/TTDSG-konformes Einwilligungsbanner (Alpine.js, Einwilligung wird im `localStorage` des Browsers gespeichert). Besucher können „Alle akzeptieren“, „Nur notwendige“ oder eine individuelle Auswahl (Analyse/Marketing getrennt) treffen. Über den Link „Cookie-Einstellungen“ im Footer lässt sich die Auswahl jederzeit ändern.
- **Impressum & Datenschutz** (`/impressum`, `/datenschutz`): Rechtstexte, inhaltlich an die tatsächliche Technik dieser Seite angepasst (Laravel-Session-Cookie, Consent-Speicherung, Wikimedia-Bildquellen, OpenStreetMap, optionale Werbeflächen). **Wichtig:** Diese Texte sind eine sorgfältige, aber nicht-anwaltliche Anpassung vorhandener Texte von 24bites.de und ersetzen keine rechtliche Prüfung durch einen Anwalt/eine Anwältin — insbesondere sobald tatsächlich Werbenetzwerke oder weitere Tracking-Dienste aktiviert werden, sollten die Texte noch einmal geprüft werden.
- **Google Analytics (GA4):** Messwert-ID unter „Einstellungen“ im Adminbereich eintragen (`ga_measurement_id`, Format `G-XXXXXXX`). Das gtag.js-Skript wird ausschließlich geladen, wenn (a) eine ID hinterlegt ist **und** (b) der Besucher der Analyse-Kategorie im Cookie-Banner zugestimmt hat (`window.hasAnalyticsConsent()`). IP-Anonymisierung ist fest aktiviert. Ohne hinterlegte ID passiert nichts.
- **Werbeflächen:** Unter „Einstellungen“ lassen sich HTML/Script-Snippets für drei Positionen hinterlegen (`ad_slot_header`, `ad_slot_sidebar`, `ad_slot_in_content`) — die `<x-ad-slot>`-Komponente rendert an der jeweiligen Stelle im Layout nur dann etwas, wenn ein Snippet gesetzt ist. Aktuell ist kein Werbenetzwerk aktiv; die Felder sind bewusst leer, bis ein konkreter Anbieter (z. B. Google AdSense) ausgewählt wird.

## Deployment-Hinweise

- `APP_ENV=production` und `APP_DEBUG=false` setzen, `APP_KEY` neu generieren falls nicht vorhanden
- `php artisan config:cache`, `route:cache`, `view:cache` nach jedem Deployment ausführen
- `storage/` und `bootstrap/cache/` müssen beschreibbar sein
- Tailwind/Alpine/Leaflet werden aktuell per CDN geladen; für Produktionsumgebungen ohne Internetzugang zum Client-Browser müsste stattdessen ein lokaler Build (Tailwind CLI) eingerichtet werden
- `.env` niemals ins Repository committen; Zugangsdaten aus diesem README gelten ausschließlich lokal

### Veröffentlichung auf einer IONOS-Domain per Git

Diese Anleitung ist bewusst nur eine Dokumentation — es wurde **noch kein echtes Deployment durchgeführt**. Welcher der beiden Wege infrage kommt, hängt vom gebuchten IONOS-Produkt ab; das lässt sich im IONOS-Kundenkonto unter „Hosting“ bzw. „Server“ prüfen.

**Vorab zu klären:**

- Läuft PHP >= 8.2 auf dem Zielserver? (IONOS Webhosting-Pakete erlauben meist die Wahl der PHP-Version im Kundenkonto.)
- Gibt es SSH-Zugang? (Bei IONOS „Webhosting“-Tarifen teils nur eingeschränkt/gar nicht, bei „VPS“/„Cloud Server“/„Managed Server“ ja.)
- Ist eine eigene MySQL-Datenbank angelegt (Zugangsdaten liegen im IONOS-Kundenkonto unter „Datenbanken“)?
- Zeigt das „Document Root“ der Domain auf ein Unterverzeichnis, das frei wählbar ist? Bei Laravel **muss** das Document Root auf den `public/`-Ordner des Projekts zeigen, nicht auf den Projektstamm.

**Weg A — IONOS-Server mit SSH-Zugang (VPS / Cloud Server / Managed Server):**

1. Per SSH auf den Server verbinden: `ssh benutzername@deine-domain.de` (Zugangsdaten aus dem IONOS-Kundenkonto)
2. Projekt klonen (Repository muss vorher z. B. auf GitHub/GitLab liegen):
   ```bash
   cd /pfad/zu/deinem/webroot
   git clone <repository-url> lieblingsorte
   cd lieblingsorte
   ```
3. Abhängigkeiten installieren und Projekt vorbereiten:
   ```bash
   composer install --no-dev --optimize-autoloader
   cp .env.example .env
   php artisan key:generate
   ```
4. `.env` mit den echten Produktionswerten füllen (Datenbankzugang aus dem IONOS-Kundenkonto, `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://deine-domain.de`, ggf. `OPENAI_API_KEY` und später `GA-Measurement-ID` über den Adminbereich)
5. Datenbank einrichten und befüllen:
   ```bash
   php artisan migrate --force
   php artisan db:seed --force   # optional: nur beim allerersten Deployment, sonst Adminuser doppelt anlegen vermeiden
   php artisan storage:link
   ```
6. Performance-Caches erzeugen:
   ```bash
   php artisan config:cache
   php artisan route:cache
   php artisan view:cache
   ```
7. Webserver so konfigurieren (bzw. im IONOS-Kundenkonto das Document Root setzen), dass er auf `lieblingsorte/public` zeigt — **nicht** auf `lieblingsorte/` selbst.
8. Für spätere Updates genügt auf dem Server:
   ```bash
   git pull
   composer install --no-dev --optimize-autoloader
   php artisan migrate --force
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```

**Weg B — IONOS „Deploy Now“ (Git-Integration ohne eigenen SSH-Zugriff):**

IONOS bietet mit „Deploy Now“ eine Möglichkeit, ein Repository (z. B. auf GitHub) direkt mit einer IONOS-Domain zu verknüpfen; bei jedem Push wird automatisch neu deployt. Das Produkt ist primär für Node-/statische Projekte ausgelegt, unterstützt aber auch PHP-Projekte mit Composer-Unterstützung.

1. Im IONOS-Kundenkonto den Menüpunkt „Deploy Now“ öffnen und ein neues Projekt anlegen
2. GitHub-Konto verbinden und das Lieblingsorte-Repository auswählen
3. Als Projekttyp „PHP“ auswählen; Build-Befehl `composer install --no-dev --optimize-autoloader`, Startverzeichnis `public/`
4. Umgebungsvariablen (`.env`-Werte: `APP_KEY`, `DB_*`, `APP_URL`, optional `OPENAI_API_KEY`) über die Deploy-Now-Oberfläche als „Environment Variables“ hinterlegen — **nicht** die `.env`-Datei ins Repository committen
5. Datenbank separat im IONOS-Kundenkonto anlegen und die Zugangsdaten in Schritt 4 eintragen
6. Nach dem ersten Deploy einmalig per bereitgestellter Konsole/Webhook `php artisan migrate --force`, `php artisan db:seed --force` (nur beim ersten Mal) und `php artisan storage:link` ausführen
7. Jeder weitere `git push` auf den verbundenen Branch löst automatisch ein neues Deployment aus

**Falls weder SSH noch Deploy Now zur Verfügung stehen** (reines Einsteiger-Webhosting ohne Git-Unterstützung): In diesem Fall lässt sich kein direktes Git-Deployment einrichten. Alternative wäre ein CI-Workflow (z. B. GitHub Actions), der bei jedem Push das Projekt baut und die Dateien per SFTP/FTP hochlädt — das wurde hier bewusst nicht automatisiert, da es Zugangsdaten voraussetzt, die nicht Teil dieses Repositories sein dürfen.

## Bekannte Einschränkungen

- Tailwind wird über die Play-CDN geladen (`cdn.tailwindcss.com`), die laut Tailwind-Dokumentation nicht für den Produktivbetrieb gedacht ist — funktional aber vollständig ausreichend für dieses MVP. Empfohlener nächster Schritt: Migration auf die Tailwind-CLI mit einem einmaligen Build-Schritt (kein volles Vite/Node-Setup nötig).
- Nach `php artisan images:fetch-real` sind 215 von 220 Titel-/Galeriebildern (über alle 8 Regionen) echte, frei lizenzierte Fotos von Wikimedia Commons (siehe `CREDITS.md`). Wenige Orte ohne passendes freies Foto (z. B. Knottnkino, Füssen, Weisshaus Trier, Volksgarten Düsseldorf, Tamarin Bay) behalten die generierte Illustration als Fallback. Ohne diesen Zusatzschritt (z. B. offline) bleiben alle Bilder generierte Illustrationen — siehe „Bildverwaltung“.
- WebP-Erzeugung für Uploads hängt von der GD-Konfiguration des Zielservers ab; ist `imagewebp()` nicht verfügbar, wird nur das Originalformat gespeichert (keine Fehlfunktion, nur keine zusätzliche WebP-Variante).
- Die Rollen `admin` und `editor` existieren und werden beim Login geprüft, aktuell haben aber beide Rollen im MVP identische Rechte im Adminbereich (keine feingranulare Rechtetrennung).
- Die Favoritenfunktion ist rein session-basiert (kein dauerhaftes Konto-Feature für Besucher), wie im Auftrag als MVP-Lösung vorgesehen.

## Empfohlene nächste Schritte

1. Für die verbleibenden Orte ohne freies Foto (siehe „Bekannte Einschränkungen“) bei Bedarf eigenes Bildmaterial einpflegen (siehe „Bildverwaltung“)
2. Tailwind CLI-Build statt Play-CDN für den Produktivbetrieb
3. Rechtetrennung zwischen `admin` und `editor` ausbauen (z. B. Löschen nur für Admins)
4. Volltextsuche ggf. auf MySQL `FULLTEXT`-Indizes umstellen, wenn der Datenbestand deutlich wächst
5. Automatisierte Bildoptimierung/Responsive-Images (`srcset`) für noch bessere Performance
6. E-Mail-Versand für Newsletter-Bestätigung (Double-Opt-In) ergänzen
7. Impressum/Datenschutz von einem Anwalt/einer Anwältin final prüfen lassen, sobald echte Werbenetzwerke aktiv geschaltet werden (siehe „Cookie-Consent, Google Analytics & Werbeflächen“)
8. GA4-Messwert-ID und ggf. `OPENAI_API_KEY` im Produktivsystem hinterlegen (siehe „KI-Funktionen“ und „Cookie-Consent, Google Analytics & Werbeflächen“)
9. Erstes echtes Deployment auf IONOS gemäß „Veröffentlichung auf einer IONOS-Domain per Git“ durchführen und dabei prüfen, welcher der beiden Wege (SSH oder Deploy Now) zum gebuchten Tarif passt
