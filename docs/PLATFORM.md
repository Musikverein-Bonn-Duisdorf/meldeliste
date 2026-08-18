# Vereinsplattform — Modulgrenzen

Verkaufbare / installierbare Module (Sibling-Apps, **eine gemeinsame MySQL**):

| Modul | Repo | DB-Prefix (Ziel) | Identity |
|-------|------|------------------|----------|
| Meldeliste | meldeliste | `meldeliste_` (später `melde_`) | Owner von `User` + `Permissions` + SSO-Issuer |
| Notenarchiv | notenarchiv | `archiv_` | liest Melde-`User` / Permissions; SSO-Redeem |
| Mitgliederverwaltung | mitgliederverwaltung | `mit_` | Hub für Profil/Mitgliedschaft/SEPA; liest Melde-`User` |

Dieses Dokument ist die **kanonische** Plattform-Quelle. Kopien in Archiv/MIT sollen hierher verweisen.

## Reihenfolge (MELD-156 / MELD-108)

1. **Phase 1 — Notenarchiv andocken** (Epic ARCHIV-4: Identity → SSO → Permissions → Security, dann Domain ARCHIV-9).
2. **Phase 2 — Mitgliederverwaltung-Hub** (Epic MIT-1 / MIT-15: `mit_MemberProfile` auf Melde-User, Fördernde als Melde-User ausgeblendet in Listen, Anschrift/Bank/SEPA).

Archiv braucht **kein** zweites Personenmodell — nur `meldeliste_User` + SSO. MIT blockiert Archiv nicht.

## Identity (MELD-110, Stand 2026-08-15)

- **Gemeinsame MySQL-DB:** ja — Module teilen die Instanz, Tabellen über Prefix getrennt.
- **Kanonische User-Tabelle:** `{identityPrefix}User` (Singular), Standard `meldeliste_User` (+ `Permissions`).
- **Legacy:** `{prefix}Users` (Plural) ist abzuschaffen; Notenarchiv nutzt `$identityPrefix = "meldeliste_"` und liest `User`.
- **Live-Check (aktuell erreichbare DB):** vorhanden `meldeliste_User`, `meldeliste_UserVoice` — **kein** `meldeliste_Users`.
- **Personen-ID:** `User.Index` bleibt die gemeinsame Login-ID; heikle Zusatzdaten in MIT (`mit_MemberProfile`), keine `mit_Person`.
- **Geburtstag:** nur in `mit_MemberProfile` (Melde-Spalte entfernt).
- **Mitglied-Flag:** nur in `mit_Membership` (`Type=aktiv`, `Status=active`); Melde-Spalte `Mitglied` entfernt.
- **Fördernde:** Melde-User-Zeile existiert; in Melde-Listen/Audience ausgeblendet außer explizite User-Chips.
- **DSGVO:** keine separate User-DB — logische Trennung, Modul-ACLs; IBAN/Anschrift in MIT.

Siehe auch Notenarchiv `docs/IDENTITY.md`. Ticket: MELD-110 / Epic MELD-108 / ARCHIV-4 / MIT-15 / MELD-192.

## Ownership-Matrix (MELD-157)

Sibling-Apps: Integration nur über **DB + SSO**. Kein Melde-Host für Archiv-/MIT-Config, Backup oder Update (kein PHP-Include, kein Iframe-Admin).

### A — Nur Melde (nicht kopieren, nicht aus Addons aufrufen)

| Thema | Begründung |
|-------|------------|
| `User` / `Permissions` / Gruppen | Identity-Owner |
| SSO **Issue** (`sso.php`, Ticket-Tabelle schreiben) | Melde ist Issuer |
| Termine, Meldungen, Inventar, Mail, App-API | Melde-Domain |
| `backup.php`, `updater.php` als Melde-Ops-UI | Decken Melde-Artefakte; Sibling-Apps haben eigene Ops-UIs für ihren Prefix (Archiv: ARCHIV-2) |
| App-Token / Push / MVDApp | Melde-Client |

### B — Pro Modul behalten (jetzt kopieren / parallel pflegen)

| Thema | Regel |
|-------|--------|
| Config-UI (`config-menu` + Modul-`config`) | Eigenes Modul-Config; gleiche UX-Muster, keine Melde-Config schreiben |
| Schema / Update (`update.php`, SchemaManager) | Nur eigener Prefix (`archiv_*` / `mit_*`) |
| Install / Log / Domain-Seiten | Modul-eigen |
| Backup-UI (`backup.php` / `cron.php`) | Nur eigener Prefix; Identity/`meldeliste_*` nie anfassen. Archiv-Dateien (`data/`) bleiben Hosting/rsync |
| UI-Shell-Assets (`custom.css`, FA6, `app-nav.js`, Shell-Helfer) | Vorerst Copy; später Kit-Kandidat — **Vertrag:** [UI-SHELL.md](UI-SHELL.md) |
| SSO **Redeem**, Session, Identity-Read | Copy bis Kit |

**Hinweis:** Melde-`backup.php` deckt Melde-Artefakte ab (DB-Prefix plus `uploads/`); Archiv hat eine **eigene** Backup-UI nur für `archiv_*` (ARCHIV-2). Archiv-Dateien (`data/`) bleiben Hosting/rsync. Gemeinsames Hosting-Dump der ganzen DB bleibt optional parallel.

### C — Später Kit `mvd-platform` (MELD-158, kein Blocker für ARCHIV-4)

Extrahieren, wenn Melde+Archiv(+MIT) dieselben Dateien zum dritten Mal driftieren:

- SSO-Redeem (+ Protokoll-Doku)
- `sessionBootstrap` / Cookie-Defaults
- `SQLtable` / dünne DB-Basis
- `assetUrl`, `adminList*`, Modal-Host-Kern, ggf. Shell-CSS-Slice

**Vertragsdokument (Master):** [UI-SHELL.md](UI-SHELL.md) — Filter/Listen, Logs, Modals/Popups, Chips/Styles, Asset-Versionierung. Archiv/MIT verlinken nur (keine Kopie).

**Nicht** ins Kit: Domain-Libs, Config-Inhalte, SchemaVersion, Backup-UI, SSO-**Issue**.

## Betriebsmodelle

- **Self-Host** und **gehostet**: gleiche Artefakte; Unterschied nur Betrieb/Config.
- Single-Tenant zuerst (eine Installation = ein Verein).

## Constraints

- Keine Cross-App-PHP-Includes; Integration über gemeinsame MySQL-DB + SSO.
- White-Label: Vereinsname/URLs/Branding in Config, nicht hardcoded.
- Feature-Flags / Lizenz-Hook später andockbar (`modules.enabled` o. Ä.).
- Melde-Eingriffe in der Parallelphase minimal (SSO-Hook, UserVoice).
