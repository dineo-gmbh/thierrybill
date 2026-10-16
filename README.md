# thierrybill

WordPress-Projekt (WordPress 7.1.x) für Thierry Bill – Musiker, Tattoo-Artist, Grafikdesigner – Domain thierrybill.ch, auf DDEV. Redesign der bestehenden Seite (bisher Theme «Wezen» mit Elementor), Inhalte von dort übernommen.

## Lokale Entwicklung

```bash
ddev start
```

- URL: https://thierrybill.ddev.site
- Admin: https://thierrybill.ddev.site/wp-admin/ (lokal: `admin` / `admin`)
- Docroot: `web/`
- PHP 8.3, MariaDB 11.8
- WP-CLI: `ddev wp <befehl>`

`wp-config.php` und `wp-config-ddev.php` werden von DDEV generiert und sind nicht im Repository. Lokal ist `WP_DEVELOPMENT_MODE` auf `theme` gesetzt (in `wp-config.php`), damit Pattern-Änderungen ohne Cache-Leeren erscheinen.

## Theme

Eigenes Block-Theme unter `web/wp-content/themes/thierrybill/`, schlicht im Stil eines Fotografen-/Art-Director-Portfolios: gebrochenes Weiss (#f5f5f3), schwarz, viel Weissraum, Fotos in Schwarz-Weiss. Grosse Titel in Fraktur, alles andere in Inter.

- `theme.json`: Farben, Schriften **Inter** (Text, Navigation) und **UnifrakturCook** (nur Hero-Titel, Klasse `tb-display`), beide lokal in `assets/fonts/` (keine Verbindung zu Google), Grundschrift 14 px. Fraktur nie in Versalien setzen. Varianten-Vergleich: `docs/schrift-varianten.png`, `docs/schrift-varianten-2.png`
- `templates/`: front-page, page-no-title (Seiten aus Patterns), page/single (z. B. Impressum), index, 404
- `parts/`: header (Name fett links, Navigation rechts, «Kontakt» fett), footer (eine Zeile: © und Links)
- `patterns/`: page-start, page-musik, page-tattoo, page-grafik, page-kontakt; intern footer-meta, not-found
- `style.css`: Hero, Raster, Typografie, Bildfolge, Formular
- `assets/js/thierrybill.js`: Bildfolge im Hero (Klick = nächstes Bild, sonst alle 4 s; nicht bei `prefers-reduced-motion`; ohne JS bleibt das erste Bild)

**Hero (`tb_hero()`):** Jede Seite beginnt mit einem sehr grossen Titel (Startseite: «Thierry Bill»), darunter rechtsbündig eine kleine Unterzeile («Musiker + Tattoo-Artist + Grafikdesigner»). Rechts eine Bildfolge mit Zähler «01/05», auf der Startseite unten links Pfeil-Links (Musik ↘ Tattoo ↘ Grafik ↘). Startseite bildschirmhoch, Unterseiten etwas niedriger, Kontakt ohne Bild.

**Danach je Unterseite:** «Über» (Aussage + Text, Spalte 5–10), dann «Details» als Liste Begriff / Wert.

**Raster:** Jeder Abschnitt ist eine Gruppe `tb-spread` (12 Spalten), Zellen sind Gruppen `tb-cell` mit `tb-at-N` (Startspalte) und `tb-w-N` (Breite). Bilder: `tb-img` plus Format `tb-r-45` (4:5), `tb-r-43`, `tb-r-32`; alle Fotos werden per CSS schwarz-weiss angezeigt (`filter: grayscale(1)` in `.tb-img img` – entfernen für Farbe). Unter 782 px wird alles einspaltig.

Helfer in `functions.php` für die Patterns: `tb_hero()`, `tb_spread()`, `tb_cell()`, `tb_p()`, `tb_title()`, `tb_big()`, `tb_details()`, `tb_tile()`, `tb_placeholder()`, `tb_image_block()`, `tb_page_url()`.

## Seiten

| Seite | Slug | Inhalt |
|---|---|---|
| Start | `/` | Hero «Thierry Bill» mit Bildfolge (6 Fotos), drei Bereiche mit Foto, Über mich |
| Musik | `/musik/` | Hero mit 3 Live-Fotos, Über, Details (Band, Studium, Studio, Bühnen) |
| Tattoo | `/tattoo/` | Hero mit Tattoo-Fotos, Über, Anfrage |
| Grafik | `/grafik/` | Hero mit Foto, Über, Details |
| Kontakt | `/kontakt/` | Hero ohne Bild, Formular (Thema: Musik/Booking, Studio, Tattoo, Grafik, Anderes), Instagram, Facebook |
| Impressum, Datenschutz | `/impressum/`, `/datenschutz/` | Rechtstexte aus `scripts/legal-content.php` (Schweizer DSG, soweit anwendbar DSGVO) |

Texte sind gegenüber der bisherigen Seite gestrafft, inhaltlich unverändert.

## Bilder

Quelldateien in `scripts/data/img/` (von der bisherigen Seite): `live-rot`, `live-blau`, `live-pink`, `portrait`, `portrait-duo`, dazu `tattoo-1` (Tattoo-Foto). Neues Bild: Datei dort ablegen, in `$images` im Setup-Skript eintragen (Schlüssel = Dateiname ohne .jpg, Titel, Alt-Text) und im Pattern in die Bildliste von `tb_hero()` aufnehmen, z. B. Tattoo: `[ 'tattoo-1', 'tattoo-2' ]`. Der Zähler passt sich an. Das Setup-Skript lädt sie einmalig in die Mediathek (Meta `_tb_key`, mit Alt-Texten). Weitere Bilder einfach im Editor hinzufügen.

## Inhalte einrichten

```bash
ddev wp theme activate thierrybill
ddev wp eval-file scripts/setup-content.php
```

Lädt die Bilder, legt das Kontaktformular, alle Seiten aus den Theme-Patterns, die Navigation und die Optionen an (Startseite, Permalinks `/%postname%/`, Zeitzone Zürich, Sprache de_CH, Kommentare aus) und entfernt die WordPress-Beispielinhalte. Idempotent, **überschreibt bestehende Seiteninhalte**.

## SEO

Ziel: jede Seite eindeutig beschrieben, sauber gegliedert, schnell. Lighthouse (mobil, mit freigeschalteter Indexierung gemessen): **SEO 100, Performance 99–100, Barrierefreiheit 100, Best Practices 100** auf allen Seiten, LCP 1.4–2.1 s, CLS 0.

**Pro Seite** (The SEO Framework, gesetzt im Setup-Skript, Abschnitt «SEO»):

| Seite | Title | Social-Bild |
|---|---|---|
| Start | Thierry Bill – Musiker, Tattoo-Artist & Grafikdesigner | Porträt |
| Musik | Musik – Jazz-Gitarrist Thierry Bill | Live (pink) |
| Tattoo | Tattoo – Custom Tattoos & Linework von Thierry Bill | Tattoo-Foto |
| Grafik | Grafikdesign – Thierry Bill, Mediamatiker EFZ | Fotoshooting |
| Kontakt | Kontakt – Thierry Bill | Porträt |

Dazu eigene Meta-Beschreibungen (statt automatisch aus dem Seitentext), Beitragsbild = og:image (als JPEG-Original, weil WhatsApp WebP-Vorschauen nicht zuverlässig zeigt). Impressum und Datenschutz: `noindex`, nicht in der Sitemap.

**Gliederung:** genau ein H1 pro Seite (Hero-Titel), Kernaussage als H2 (z. B. «Individuelle Tattoos nach eigenen Entwürfen.»), «Tattoo anfragen» und «Details» als H2, reine Beschriftungen («Über») ohne Überschrift. Alle Bilder mit Alt-Text.

**Strukturierte Daten:** Person «Thierry Bill» mit Instagram/Facebook (sameAs), Porträt, Berufen, Band Cold Touch, Swiss Jazz School Bern (alumniOf), HSLU (affiliation, laufendes Studium), Fachgebiete. Die von The SEO Framework fälschlich zusätzlich ausgegebene «Organization» wird durch die Person ersetzt (eine Entität statt zwei).

**MU-Plugin** `web/wp-content/mu-plugins/thierrybill-seo.php` (unabhängig vom Theme):

- Schema-Erweiterung (siehe oben)
- 301-Weiterleitungen der alten URLs: `/about-me/` → `/`, `/musician/` → `/musik/`, `/tattoo-artist/` → `/tattoo/`, `/graphic-designer/` → `/grafik/`, `/contact/` → `/kontakt/`, alte Demo-Blogbeiträge und Feeds → `/`
- schlanker `<head>`: keine Feed-, oEmbed- und REST-Links
- Contact Form 7 und Honeypot laden JS/CSS nur auf `/kontakt/`
- Honeypot-Feld mit `tabindex="-1"` (statt 1000)
- neue Bilder als **WebP** (rund halb so gross wie JPEG); bestehende Bilder nach Änderungen: `ddev wp media regenerate --yes`

**Theme (Ladezeit):** Schriften Inter und UnifrakturCook (17 KB) vorgeladen; Bildgrösse `tb-600` als Zwischenstufe im srcset; passende `sizes` je Bildtyp; nur das erste Hero-Bild lädt sofort (`fetchpriority="high"`), alle anderen lazy; Grau auf Kontrast 4.9:1 angehoben.

**Sitemap/robots:** `/sitemap.xml` (Start, Musik, Tattoo, Grafik, Kontakt), in `robots.txt` eingetragen. Lokal ist die Seite absichtlich `noindex` (`blog_public` = 0).

## Plugins

Contact Form 7, Flamingo (Anfragen-Archiv), CF7 Apps Honeypot, The SEO Framework, Limit Login Attempts Reloaded.

## Rechtstexte

Impressum und Datenschutz stehen in `scripts/legal-content.php` (Verantwortlich: Thierry Bill, Zielebachweg 1, 3428 Wiler, tjb1612@gmail.com). Die Datenschutzerklärung beschreibt, was die Seite tatsächlich tut: Hosting bei Mittwald (Deutschland), Server-Logs, Kontaktformular mit Archiv (Flamingo) und Honeypot, E-Mail an Gmail (Google), keine Cookies, kein Tracking, Schriften lokal, Links zu Instagram/Facebook, Login-Schutz. Bei Änderungen an der Seite (z. B. Analyse-Tool, eingebettete Videos) den Text anpassen.

```bash
ddev wp eval-file scripts/legal-content.php   # lokal, nur die zwei Rechtsseiten
```

Produktion: Datei hochladen und auf dem Server `wp eval-file` ausführen (siehe Deployment).

## Deployment (Mittwald)

Produktion: https://thierrybill.ch, Mittwald-Projekt «thierrybill», App «thierrybill-wp» (`a-es3wr7`), Projekt-User `p-b6amzs`, Pfad `/home/p-b6amzs/html/thierrybill-wp` (WordPress direkt im Ordner, nicht in `web/`). SSH: `ssh-qsz9am@a-es3wr7@ssh.isenstedt.project.host` mit `~/.ssh/id_ed25519`. Mittwald-IP: `185.215.158.240`.

```bash
scripts/deploy.sh
```

Überträgt alle im Git erfassten Dateien unter `web/` (ohne `wp-config.php`, `.htaccess`) per rsync und leert danach Cache und Rewrite-Regeln über WP-CLI. Datenbank und Uploads werden nicht deployt; Inhalte werden in Produktion gepflegt.

Ersteinrichtung (einmalig erledigt am 16.10.2026): lokale Datenbank **ohne** `wp_users`/`wp_usermeta` importiert, `wp search-replace https://thierrybill.ddev.site https://thierrybill.ch --all-tables-with-prefix`, Uploads per rsync. Sicherung des Ausgangszustands in `~/backup/` auf dem Server. Domain `thierrybill.ch` und `www` als Virtual Host in mStudio, verifiziert per TXT-Eintrag (bis zur Umstellung noch in der Hetzner-Zone), SSL-Zertifikat von Let's Encrypt ist ausgestellt.

**Domain/DNS:** registriert bei METANET AG, DNS-Zone bis zum Launch bei Hetzner (`ns1.your-server.de`, alte Seite auf `78.46.130.16`). Launch = bei METANET die Nameserver auf `ns01.agenturserver.de`, `ns01.agenturserver.it`, `ns01.agenturserver.co` (Mittwald) umstellen. Vorher die Seite unter Mittwald testen, z. B. mit `curl --resolve thierrybill.ch:443:185.215.158.240 https://thierrybill.ch/`.

## Vor dem Launch

- Mehr Tattoo-Fotos und echte Grafik-Arbeiten in die Bildfolgen aufnehmen (Grafik zeigt bisher nur das Fotoshooting-Bild)
- Formular-Empfänger prüfen (aktuell Admin-E-Mail), WP Mail SMTP einrichten
- Admin-Passwort ändern
- Suchmaschinen freigeben (Produktion, nach dem Nameserver-Wechsel): `wp option update blog_public 1` (sonst bleibt die Seite `noindex`)
- Domain: bisher lief die Seite unter `https://thierrybill.ch` (ohne www) – dabei bleiben, damit die 301-Weiterleitungen und bestehende Links greifen
- Google Search Console: Domain bestätigen, `https://thierrybill.ch/sitemap.xml` einreichen
- Instagram- und Facebook-Profil auf `https://thierrybill.ch` verlinken (stärkt die Verbindung Person ↔ Website)
- Nach dem Launch: Hetzner-Hosting (Server dedi3600, Account 809q) kündigen und die dort sichtbar gewordenen Passwörter nicht mehr verwenden
