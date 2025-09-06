# Homeoffice Tracker

Ein leichtgewichtiges PHP/SQLite Tool zur Erfassung von Arbeitstagen (Homeoffice, Büro, Urlaub, Krank, Schulung), mit Wochenbuchung, Monatskalender, Auswertung, PDF/CSV-Export und Ziel-Quote.

## Features
- Wochenansicht mit Schnellaktionen: Woche komplett auf Homeoffice, Büro, Keine Angabe, Urlaub, Krank, Schulung setzen
- Zusätzliche Typen: Urlaub, Krank, Schulung (inkl. Icons, Farben, Export, Auswertung)
- Monatskalender mit Badges, direktem Sprung zur Buchungswoche
- Auswertung je Quartal mit Charts (Chart.js), Verteilung, Monatsentwicklung, Quote vs. Zielquote
- PDF- und CSV-Export (Dompdf)
- Einstellbare Homeoffice-Quote
- Login/Registrierung (einfach), Dark-/Light-Theme Toggle

## Voraussetzungen
- PHP 8.x mit SQLite3-Extension
- Webserver oder PHP Built-in Server
- Optional: Composer, falls `vendor/` nicht mitgeliefert ist

## Installation
1. Projekt herunterladen/klonen
2. Abhängigkeiten installieren (falls nötig):
   - Mitgeliefertes `vendor/` verwenden oder `composer install` ausführen
3. Schreibrechte sicherstellen: Die Anwendung legt die SQLite-Datei `homeoffice.db` im Projektverzeichnis an

## Starten (Entwicklung)
```bash
php -S localhost:8000
```
Danach im Browser `http://localhost:8000/login.php` aufrufen.

Beim ersten Start einen Benutzer registrieren (falls noch keiner existiert). Danach stehen Dashboard, Buchung, Kalender, Auswertung und Einstellungen zur Verfügung.

## Datenbank
- SQLite (`homeoffice.db` im Projektverzeichnis)
- Beim Start werden Tabellen erstellt; einfache Migrationen (z. B. erweiterte Typen) laufen automatisch

## Wichtige Dateien
- `booking.php` – Wochenbuchung mit Schnellaktionen
- `calendar.php` – Monatskalender mit Typ-Badges und Link zur Buchungswoche
- `evaluation.php` – Auswertung, Charts und Exporte
- `export_pdf.php`, `export_csv.php` – Exporte
- `settings.php` – Zielquote einstellen
- `database.php` – DB-Access, Migration, Validierung
- `templates/` – Header/Footer/Layout
- `assets/` – JS/CSS





