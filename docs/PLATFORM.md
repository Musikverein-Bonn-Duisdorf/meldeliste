# Vereinsplattform — Modulgrenzen

Verkaufbare / installierbare Module (Sibling-Apps, **eine gemeinsame MySQL**):

| Modul | Repo | DB-Prefix (Ziel) | Identity |
|-------|------|------------------|----------|
| Meldeliste | meldeliste | `meldeliste_` (später `melde_`) | Owner von `User` + `Permissions` + SSO-Issuer |
| Notenarchiv | notenarchiv | `archiv_` | liest Melde-`User` / Permissions; SSO-Redeem |
| Mitgliederverwaltung | mitgliederverwaltung | `mit_` | später Mitgliedschafts-Hub; liest Melde-Login |

Dieses Dokument ist die **kanonische** Plattform-Quelle. Kopien in Archiv/MIT sollen hierher verweisen.

## Reihenfolge (MELD-156 / MELD-108)

1. **Phase 1 — Notenarchiv andocken** (Epic ARCHIV-4: Identity → SSO → Permissions → Security, dann Domain ARCHIV-9).
2. **Phase 2 — Mitgliederverwaltung-Hub** (Epic MIT-1: `mit_Person`, Fördernde ohne Melde-Konto, Anschrift/Bank).

Archiv braucht **kein** `mit_Person` — nur `meldeliste_User` + SSO. MIT blockiert Archiv nicht.

## Identity (MELD-110, Stand 2026-07-21)

- **Gemeinsame MySQL-DB:** ja — Module teilen die Instanz, Tabellen über Prefix getrennt.
- **Kanonische User-Tabelle:** `{identityPrefix}User` (Singular), Standard `meldeliste_User` (+ `Permissions`).
- **Legacy:** `{prefix}Users` (Plural) ist abzuschaffen; Notenarchiv nutzt `$identityPrefix = "meldeliste_"` und liest `User`.
- **Live-Check (aktuell erreichbare DB):** vorhanden `meldeliste_User`, `meldeliste_UserVoice` — **kein** `meldeliste_Users`.
- **Personen-ID:** `User.Index` bleibt die gemeinsame Login-ID für Archiv/SSO; Mitgliedschaftsstamm kommt später in MIT (`mit_Person`, optionaler Melde-Link).
- **DSGVO:** keine separate User-DB — logische Trennung, Modul-ACLs; IBAN/Anschrift erst in MIT (Phase 2).

Siehe auch Notenarchiv `docs/IDENTITY.md`. Ticket: MELD-110 / Epic MELD-108 / ARCHIV-4.

## Ownership-Matrix (MELD-157)

Sibling-Apps: Integration nur über **DB + SSO**. Kein Melde-Host für Archiv-/MIT-Config, Backup oder Update (kein PHP-Include, kein Iframe-Admin).

### A — Nur Melde (nicht kopieren, nicht aus Addons aufrufen)

| Thema | Begründung |
|-------|------------|
| `User` / `Permissions` / Gruppen | Identity-Owner |
| SSO **Issue** (`sso.php`, Ticket-Tabelle schreiben) | Melde ist Issuer |
| Termine, Meldungen, Inventar, Mail, App-API | Melde-Domain |
| `backup.php`, `updater.php` als Melde-Ops-UI | Eine DB → Backup/Deploy ist Hosting-Ops; Melde-UI deckt Melde-Artefakte ab |
| App-Token / Push / MVDApp | Melde-Client |

### B — Pro Modul behalten (jetzt kopieren / parallel pflegen)

| Thema | Regel |
|-------|--------|
| Config-UI (`config-menu` + Modul-`config`) | Eigenes Modul-Config; gleiche UX-Muster, keine Melde-Config schreiben |
| Schema / Update (`update.php`, SchemaManager) | Nur eigener Prefix (`archiv_*` / `mit_*`) |
| Install / Log / Domain-Seiten | Modul-eigen |
| UI-Shell-Assets (`custom.css`, FA6, `app-nav.js`, Shell-Helfer) | Vorerst Copy; später Kit-Kandidat |
| SSO **Redeem**, Session, Identity-Read | Copy bis Kit |

**Backup in Archiv/MIT:** keine eigene Backup-UI. Backup = mysqldump/Host der gemeinsamen DB (+ optional Dateien wie `data/`).

### C — Später Kit `mvd-platform` (MELD-158, kein Blocker für ARCHIV-4)

Extrahieren, wenn Melde+Archiv(+MIT) dieselben Dateien zum dritten Mal driftieren:

- SSO-Redeem (+ Protokoll-Doku)
- `sessionBootstrap` / Cookie-Defaults
- `SQLtable` / dünne DB-Basis
- `assetUrl`, `adminList*`, Modal-Host-Kern, ggf. Shell-CSS-Slice

**Nicht** ins Kit: Domain-Libs, Config-Inhalte, SchemaVersion, Backup-UI, SSO-**Issue**.

## Betriebsmodelle

- **Self-Host** und **gehostet**: gleiche Artefakte; Unterschied nur Betrieb/Config.
- Single-Tenant zuerst (eine Installation = ein Verein).

## Constraints

- Keine Cross-App-PHP-Includes; Integration über gemeinsame MySQL-DB + SSO.
- White-Label: Vereinsname/URLs/Branding in Config, nicht hardcoded.
- Feature-Flags / Lizenz-Hook später andockbar (`modules.enabled` o. Ä.).
- Melde-Eingriffe in der Parallelphase minimal (SSO-Hook, UserVoice).
