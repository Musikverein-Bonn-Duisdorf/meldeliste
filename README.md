# Meldeliste

Zentrale Plattform für Termine, Rückmeldungen und Verwaltung im Musikverein Bonn-Duisdorf – u. a. Meldelisten, Register, Inventar, Nachrichten und Auswertung.

## Voraussetzungen

- PHP (mit MySQLi)
- MySQL oder MariaDB
- Webserver (Apache/nginx o. Ä.) mit Document Root auf dem Projektverzeichnis

## Installation

1. Repository klonen und `common/config_sample.php` nach `common/config.php` kopieren.
2. In `common/config.php` MySQL-Zugangsdaten, SMTP und weitere Platzhalter eintragen.
3. Im Browser `install.php` öffnen (ohne Login, solange noch keine Admin-Benutzer existieren) und Schema anlegen bzw. prüfen.

Details zum Schema und zur Erstinstallation liefert die Install-Seite selbst.

## Branches

| Branch | Verwendung |
|--------|------------|
| `master` | Produktion |
| `dev` | Staging / Vorschau |
| `MELD-<n>-…` / `feature/MELD-<n>-…` | Feature-Branches |

## Mobile App (Android)

Die WebView-App **MVDApp** braucht schlanke JSON-Endpunkte für Token-Login und Notify-Poll (MELD-49).

→ Spezifikation: [docs/app-api.md](docs/app-api.md)

## Backup

Datenbank-Backup als ZIP (`manifest.json` mit Software-/Schema-Version + `database.sql`).

- Admin-UI: **System → Backup** (Recht Konfiguration)
- Remote-Abruf (Cron auf einem anderen Server):

```bash
curl -fsS "https://HOST/cron.php?id=CRONID&cmd=backup" -o "backup-$(date +%F).zip"
```

CLI-Restore (destruktiv, nur mit `--yes`):

```bash
php scripts/restoreBackup.php /path/to/backup.zip --yes
```

## Tests

Automatisierte Integrationstests liegen im privaten Sibling-Repository [meldeliste-tests](https://github.com/Musikverein-Bonn-Duisdorf/meldeliste-tests) (Checkout neben `meldeliste/`). Setup und Ausführung sind dort dokumentiert.

## Changelog

Siehe [CHANGELOG.md](CHANGELOG.md).

## License

Proprietär – siehe [LICENSE](LICENSE).

Copyright (c) 2026 Manuel Schedler. All rights reserved.

Enthaltene Drittkomponenten (z. B. TinyMCE, Font Awesome, PHPMailer) unterliegen jeweils eigenen Lizenzen.
