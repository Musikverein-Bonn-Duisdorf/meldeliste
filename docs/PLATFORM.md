# Vereinsplattform — Modulgrenzen

Verkaufbare / installierbare Module:

| Modul | Repo | DB-Prefix (Ziel) | Identity |
|-------|------|------------------|----------|
| Meldeliste | meldeliste | `meldeliste_` (später `melde_`) | Owner von `User` + `Permissions` |
| Notenarchiv | notenarchiv | `archiv_` | liest Melde-`User` |
| Mitgliederverwaltung | mitgliederverwaltung | `mit_` | liest Melde-`User` |

## Identity (MELD-110, Stand 2026-07-21)

- **Gemeinsame MySQL-DB:** ja — Module teilen die Instanz, Tabellen über Prefix getrennt.
- **Kanonische User-Tabelle:** `{identityPrefix}User` (Singular), Standard `meldeliste_User` (+ `Permissions`).
- **Legacy:** `{prefix}Users` (Plural) ist abzuschaffen; Notenarchiv nutzt `$identityPrefix = "meldeliste_"` und liest `User`.
- **Live-Check (aktuell erreichbare DB):** vorhanden `meldeliste_User`, `meldeliste_UserVoice` — **kein** `meldeliste_Users`.
- **Personen-ID:** `User.Index` bleibt die gemeinsame ID für Archiv/MIT/SSO.

Siehe auch Notenarchiv `docs/IDENTITY.md`. Ticket: MELD-110 / Epic MELD-108 / ARCHIV-4.

## Betriebsmodelle

- **Self-Host** und **gehostet**: gleiche Artefakte; Unterschied nur Betrieb/Config.
- Single-Tenant zuerst (eine Installation = ein Verein).

## Constraints

- Keine Cross-App-PHP-Includes; Integration über gemeinsame MySQL-DB + SSO.
- White-Label: Vereinsname/URLs/Branding in Config, nicht hardcoded.
- Feature-Flags / Lizenz-Hook später andockbar (`modules.enabled` o. Ä.).
- Melde-Eingriffe in der Parallelphase minimal (SSO-Hook, später UserVoice).
