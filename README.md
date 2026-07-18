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
| `MELD-<n>-…` | Feature-Branches |

## Tests

Automatisierte Integrationstests liegen im privaten Sibling-Repository [meldeliste-tests](https://github.com/Musikverein-Bonn-Duisdorf/meldeliste-tests) (Checkout neben `meldeliste/`). Setup und Ausführung sind dort dokumentiert.

## Changelog

Siehe [CHANGELOG.md](CHANGELOG.md).

## License

Proprietär – siehe [LICENSE](LICENSE).

Copyright (c) 2026 Manuel Schedler. All rights reserved.

Enthaltene Drittkomponenten (z. B. TinyMCE, Font Awesome, PHPMailer) unterliegen jeweils eigenen Lizenzen.
