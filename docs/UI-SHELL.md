# UI-Shell-Parität — Filter, Listen, Logs, Modals, Styles, Versionierung

**Master / kanonisch:** dieses Dokument in der Meldeliste. Notenarchiv und Mitgliederverwaltung **verlinken** nur hierher — Inhalt nicht kopieren, nicht forken.

Sibling-Apps **implementieren** die Muster per Copy der Shell-Artefakte (kein PHP-Include über Modulgrenzen — siehe [PLATFORM.md](PLATFORM.md)). Später Kit-Kandidat `mvd-platform` (MELD-158).

| Thema | Melde-Referenz (Einstieg) |
|-------|---------------------------|
| Listen-Chrome / Suche | `libs/helpers.php` (`adminList*`) |
| Chunks / Sentinel | `libs/listChunk.php`, `getList.php`, `js/infiniteScroll.js` |
| Sortierung | `js/sortList.js`, `#listHeader.inv-sort-bar`, `data-sort-*` an Zeilen |
| Client-Suche | `js/listRowSearch.js` + listen-spezifische `filter*.js` |
| Log | `log.php`, `getLog.php`, `libs/log.php` |
| Modals / Entity-Chips | `js/modal.js`, `getModal.php`, `entityOpenHtml()` |
| Confirm/Alert | `js/appDialog.js`, `#appConfirmModal` |
| Chips / Farben | `styles/custom.css`, `renderPermissionGroupColorCss()` |
| Assets / Release | `assetUrl()`, `makeVersion.sh`, `VERSION` / `HASH` |

Agent-Regeln (Melde): `.cursor/rules/ui-chips.mdc`, `ui-modals-overlays.mdc`, `short-ui-labels.mdc`, `merge-makeversion.mdc`.

**Verweise aus Sibling-Repos:** `notenarchiv/docs/UI-SHELL.md`, `mitgliederverwaltung/docs/UI-SHELL.md` (Pointer only).

---

## 1. Listen-Chrome und Filter

### 1.1 Seiten-Shell (Markup-Vertrag)

```
adminListPageBegin($kicker, $title, $options?)
  → .profile-page
       .profile-shell.admin-list-shell
         .app-page-chrome
           header.profile-hero.admin-list-hero.admin-list-hero--{group}
             .profile-kicker / .profile-title
             optional .profile-hero-actions
         [optional Toolbar via adminListSearchField]
         .admin-list-body
           #Liste … Zeilen …
           #listSentinel …
adminListPageEnd()
```

PHP-APIs (`libs/helpers.php`):

| Funktion | Zweck |
|----------|--------|
| `adminListPageBegin(string $kicker, string $title, array $options = [])` | Start; Options: `actionsHtml`, `shellClass`, `groupId`, `permKey` |
| `adminListSearchField(string $placeholder, array $options = [])` | Suche + schließt Chrome / öffnet Body |
| `adminListChromeClose(bool $captureToBody = false)` | Chrome schließen ohne Search |
| `adminListPageEnd()` | Shell schließen |

`adminListSearchField`-Options:

- `id` — Default `#filterString` (**behalten**, viele Filter-JS erwarten das)
- `onkeyup` — z. B. `filterTermine()`
- `extraHtml` — Chip-Toolbar (Versichert, Personen-Filter, …)
- `ariaLabel` — Default = Placeholder

**UX-Regeln (Pflicht):**

- Kein sichtbares Label „Suchen“ — nur Placeholder (+ `aria-label`).
- Keine Anleitungsprosa neben dem Feld.
- Hero-Kicker/Titel kurz; Hilfe gehört in die Help-Guide, nicht in die Toolbar.

### 1.2 Zwei Filter-Modi

| Modus | Wann | Verhalten |
|-------|------|-----------|
| **Client** | Termine, Personen, Inventar, Mail, … | `#filterString` + Chips; Zeilen per CSS/`list-filtered-out` ausblenden; Tokens **UND** über `listRowMatchesQuery` |
| **Server-`q`** | Log (`data-server-q="1"`) | Suche an `getList.php?q=`; Reload ab Cursor `0`; kein Full-Table-Scan im Browser |

Gemeinsame Client-Helfer (`js/listRowSearch.js`):

- `listRowSearchText(el)` — Text + `data-search` / `data-sort-*`
- `listRowMatchesQuery(haystack, query)` — Whitespace-Tokens, AND

Zeilen sollen `data-search` (und ggf. Sort-Attribute) tragen, damit versteckte Spalten mitgesucht werden.

### 1.3 Filter-Chips (Client)

- Basis-Klassen: `inv-sort-chip inv-filter-chip`, aktiv: `is-active`
- Inventar: `#filterInsured` (`?versichert=1`)
- Personen: `[data-personen-filter=…]`, `[data-register-filter=…]`
- Chips in `adminListSearchField(…, ['extraHtml' => …])` legen

`js/infiniteScroll.js` erkennt aktive Client-Filter (Text, Versichert, Personen-Chips) und **lädt automatisch weiter**, solange Treffer dünn sind (`shouldAutoChainFilter`). Bei `data-server-q="1"` **kein** Auto-Chain über die ganze Tabelle.

### 1.4 Infinite Scroll

**Endpoint:** `getList.php`

| Query | Bedeutung |
|-------|-----------|
| `type` | `log`, `termine`, `termineArchiv`, `meldungen`, `archiv`, `musiker`/`users`/…, `mailJobs`, `meineMails`, `inventories`, … |
| `cursor` | typspezifisch (`0` / Index / `datum\|id` / Offset) |
| `limit` | Seite; Default ~50, Log bis Config |
| `q` | nur Server-Suche (Log) |
| `sort` / `dir` | z. B. User-Listen |
| `user` | Termine für User |

**Antwort:** HTML-Fragment (Zeilen).  
**Header:** `X-Has-More: 0|1`, `X-Next-Cursor: …`

**Sentinel** (`listChunkRenderSentinel`):

```html
<div id="listSentinel"
     data-list-type="…"
     data-cursor="…"
     data-has-more="0|1"
     data-filter-fn="filterTermine"   <!-- optional: globaler Name -->
     data-server-q="1"                <!-- optional: Log -->
     data-limit="100"                 <!-- optional -->
     data-sort="" data-dir=""         <!-- optional -->
     data-extra="user=123">           <!-- optional Query-Fragment -->
</div>
```

JS: `js/infiniteScroll.js` — erwartet `#Liste` + `#listSentinel`.  
API: `window.listInfiniteReload(sort, dir)` setzt Cursor zurück und lädt neu.

PHP-Chunk-Helfer (`libs/listChunk.php`): `listChunkLog`, `listChunkTermine`, `listChunkUsers`, `listChunkInventories`, `listChunkMailJobs`, … — Rückgabe `{ html, nextCursor, hasMore }`.

Sibling-Apps: **eigene** `type`-Werte und Chunk-Funktionen für die eigene Domäne; Sentinel-/JS-Vertrag identisch halten.

### 1.5 Tabellen-Sortierung

Lange Katalog-/Admin-Listen sortieren über **Sortier-Chips** in `#listHeader.inv-sort-bar` (nicht über klassische Tabellenköpfe).

**Markup:**

```html
<div id="listHeader" class="inv-sort-bar">
  <!-- optional: .inv-sort-bar-filters mit Filter-Chips -->
  <div class="inv-sort-bar-sorts" role="toolbar" aria-label="Sortierung">
    <button type="button" class="inv-sort-chip list-sort"
            data-sort="nr" data-type="number">Nr.</button>
    <button type="button" class="inv-sort-chip list-sort"
            data-sort="title" data-type="string">Titel</button>
  </div>
</div>
<div id="Liste">…</div>
```

**Zeilen:** `.list-row` mit `data-sort-{key}` (gleicher Key wie `data-sort` am Chip). Zusätzlich `data-search` für die Client-Suche.

**JS:** `js/sortList.js` → `bindListSort({ … })`

| Modus | Wann | Verhalten |
|-------|------|-----------|
| **`mode: 'server'`** | Infinite-Scroll-Listen (Personen, Inventar, Archiv-Katalog, …) | Klick → `listInfiniteReload(sort, dir)`; Sentinel trägt `data-sort` / `data-dir`; `getList.php?sort=&dir=` + Chunk-`ORDER BY` |
| **`mode: 'client'`** (Default) | Volle Liste ohne Chunks | Sortiert vorhandene `.list-row` im DOM (Sentinel bleibt am Ende) |

**Defaults:** `defaultKey` / `defaultDir` / `defaultType` müssen zum **ersten** Server-Chunk und zu den Sentinel-`data-sort`/`data-dir` passen (sonst Drift bis zum ersten Chip-Klick).

**Typ:** `data-type="string|number|date"` — erster Klick auf eine neue Spalte: `number` → `desc`, sonst `asc`; erneuter Klick toggelt die Richtung. Aktive Spalte zeigt `▲`/`▼`.

**UX:** kurze Chip-Labels (1–2 Wörter); keine Anleitungsprosa neben der Sortierleiste. Hilfe nur in der Help-Guide.

**Sibling-Apps:** `sortList.js` + Sort-Bar + `data-sort-*` kopieren; eigene Chunk-Sort-Whitelist in `listChunk*` / `getList.php`.

---

## 2. Logs

Melde-Log ist **Melde-Domäne**. Archiv/MIT haben jeweils eine **eigene** `{prefix}Log`-Tabelle und UI — Muster kopieren, nicht Melde-`getLog.php` aufrufen.

### 2.1 Seitenaufbau

1. `adminListPageBegin` / `adminListSearchField`
2. Erster Chunk: `listChunkLog(0, listChunkLogConfiguredLimit())`
3. Sentinel mit `data-server-q="1"`, `data-limit`, `data-filter-fn="filterLog"`
4. Live-Poll (Melde: 1 s) → `getLog.php`

Config: `logListChunkSize` (Default 100, Clamp 1–500) — gilt für Scroll **und** Poll-Batch.

### 2.2 Zeilen-Markup

```html
<div id="{Index}" class="log-row list-row …" data-timestamp="{Timestamp}">
  <div class="log-id">… <span class="w3-tag log-type-chip log-type-chip--{info|error|…}">TYPE</span></div>
  <div class="log-rail"></div>
  <div class="log-main">
    <div class="log-user">{entityOpenHtml('user', …) | SYSTEM}</div>
    <div class="log-message">{logMessageLinkEntities(Message)}</div>
  </div>
</div>
```

Typen (Melde): 0 FATAL … 7 INFO — CSS `.log-type-chip--*`.

### 2.3 Live-Poll

- **POST** `getLog.php`: `maxIndex`, `topTimestamp`, `limit`
- Auth: eingeloggt + Log-Recht (Melde: `perm_showLog`)
- `logPollNextHtml($maxIndex, $topTimestamp, $limit)` — neue Zeilen `Index > maxIndex` (newest-first prepend) oder Update derselben Top-Zeile bei Timestamp-Änderung
- Client: Prefetch/Replace per `id`, danach bei aktiver Suche `filterLog()` erneut

### 2.4 Server-Suche (Log)

- Tokens: Whitespace, max. 8, **AND**
- Treffer in Message, Vorname/Nachname, `SYSTEM` (User=0), Typ-Labels (`ERROR`, `INFO`, …)
- Bei Eingabeänderung: Liste leeren, Cursor `0`, `getList.php?type=log&q=…`

### 2.5 Entity-Links in Log-Texten

`logMessageLinkEntities(string $html): string` — **nur Anzeige**, Speicherung unverändert. Keine doppelte Verlinkung, wenn bereits `.entity-open` vorhanden.

**Kanonische Schreibformate** (neue Log-Zeilen):

| Fragment | Beispiel |
|----------|----------|
| User | `User: (id) <b>Name</b>` |
| Termin | `Termin: (id) <b>Titel</b>` |
| Inventar | `Inventory: (id) <b>…</b>` |
| E-Mail | `Email: (id) <b>…</b>` oder `Email-ID: <b>N</b>` |
| Schicht | `Schicht/Aufgabe: (id) <b>…</b>` |

Legacy: `User: <b>Name</b>` wird über Melde-ID/Namensauflösung versucht — neue Logs immer mit `(id)`.

---

## 3. Modals, Popups, Overlays

### 3.1 Platzierung (Pflicht)

`.app-main` hat Overflow + `z-index: 1` → feste Overlays darin kollidieren mit Titlebar/Nav.

| Art | Platzierung |
|-----|-------------|
| AJAX-Dialoge | nur `#ajaxModalHost` (Footer, **außerhalb** `.app-shell`) |
| Seiten-lokale `.w3-modal` | `deferPageModalHtml($html)` puffern; Footer gibt sie neben dem Host aus |
| Sheets / Bottom-Bars | an `document.body`, **nicht** in `#ajaxModalHost` |
| Safety-Net | `liftPageModalsOutOfAppMain()` in `modal.js` — trotzdem PHP korrekt deferren |

`.w3-modal` global `z-index: 2000` (über Titlebar ~1100 / Nav ~1000).

Host-Markup:

```html
<div id="ajaxModalHost" class="w3-modal" onclick="if(event.target===this)closeModal();">
  <div id="ajaxModalContent" class="w3-modal-content"></div>
</div>
```

### 3.2 Modal-Shell (neue Dialoge)

Nicht das alte `w3-container` + farbiger TitleBar-Karten-Header.

```
.profile-shell.modal-shell
  header.profile-hero
    .profile-kicker / .profile-title
    .profile-hero-actions … .modal-close.w3-button
  .termin-grid | .profile-grid
    .profile-col / .profile-field
```

Referenz: `views/termin/calendar_melde_modal.php`.  
Lösch-Bestätigung: gleiche Shell; Primäraktion klar („Löschen“), Abbrechen sekundär.

### 3.3 AJAX-API

- `openModal(type, id, register?)` → `GET getModal.php?type=&id=&register=`
- Cache-Key: `type:id[:register]`
- `closeModal()`; Escape schließt
- Kein Prefetch beim Listen-Rendern; kein Inline-`onclick` pro Zeile für Entities

Melde-`getModal.php`-Typen (Domäne Melde): `termin`, `calendarMelde`, `terminResponse`, `shift`/`shiftResponse`, `user`, `inventar`/`inventory`, `mail`.  
Sibling-Apps: **eigene** Typen + Endpoint; JS-Vertrag (`openModal` / Host) gleich halten.

### 3.4 Entity-Open-Chips (MELD-167)

PHP: `entityOpenHtml($type, $id, $label, $chipMod = '')`.

```html
<span class="mail-recipient-chip mail-recipient-chip--{mod} entity-open"
      role="button" tabindex="0"
      data-entity-type="{user|termin|inventar|mail|shift}"
      data-entity-id="{n}">Label</span>
```

Default-Modifier: `user`→`--user`, `termin`→`--termin`, `inventar`→`--instrument`, `mail`→`--mailGroup`, `shift`→`--termin`.  
Optional 4. Parameter überschreibt den Modifier (z. B. `guestMusician`).

JS: Capture-Delegation auf `.entity-open` → `openModal` (async). Mapping: `shift` → `shiftResponse`, `inventory` → `inventar`.

**Nicht:** unterstrichene Textlinks statt Chips; Modal-HTML in Listen/Log-Chunks einbetten; neue Chip-Farbfamilien erfinden, wenn ein Modifier passt.

### 3.5 Confirm / Alert (WebView-sicher)

Native `window.confirm` / `alert` in WebViews problematisch.

- Markup: `#appConfirmModal` (Footer)
- JS: `window.appConfirm(message, opts)`, `window.appAlert(message, opts)` → Promise
- Forms: `data-confirm="…"` (siehe `appDialog.js`)

---

## 4. Styles: Chips, Farben, Nav

### 4.1 App-Shell

```
body.app-layout
  .app-titlebar.{colorTitle}
  [.app-banner …]
  .app-shell
    nav.app-nav.{colorNav}
      .app-nav-primary > a.app-nav-item
      .app-nav-more-…
    .app-main
```

Assets: `styles/custom.css`, lokale Font Awesome 6, `js/app-nav.js`.  
Branding-Farben in Modul-Config: `colorTitle`, `colorTitleBar`, `colorNav`, `colorNavAdmin`, `colorBackground`, … (White-Label, nicht hardcoded).

Safe-Area / aktive Tabs: Nav-Hintergrund und Tab-Hintergründe getrennt halten (siehe MELD-184) — aktiver Tab braucht sichtbare `colorTitleBar`, Parent nicht als grauer `colorNav`-Streifen in der Safe-Area.

### 4.2 Chip-System (MELD-60)

| Klasse | Rolle |
|--------|--------|
| `.mail-recipient-chips` / `.profile-perm-tiles` | Container (flex, wrap, gap) |
| `.mail-recipient-chip` / `.profile-perm-tile` | Chip-Basis |
| `--user`, `--termin`, `--namedGroup`/`--mailGroup`, `--register`, `--instrument`, `--insured`/`--loaned`, `--guestMusician`/`--member`/`--nomember`/`--audience`/`--group` | Typ-Modifier |

Keine neuen Farbfamilien ohne Not — bestehende Modifier nutzen.

### 4.3 Rechte-Gruppen → Akzentfarben

Melde: `Permissions::permissionGroups()` liefert `id`, `title`, `color`, `keys`.  
`renderPermissionGroupColorCss()` erzeugt u. a.:

- `.admin-nav-perm--{id}`
- `.profile-perm-tile--{id}`
- `.admin-list-hero--{id}` + `--page-title-accent` auf der Shell
- Matrix-Gruppen `.perm-group--{id}`

Listen-Hero: `adminHeroClass(['kicker'|`groupId`|`permKey`])`.  
Log-/Listen-Rails: `var(--page-title-accent, …)`.

Archiv/MIT ohne Melde-Rechte-Matrix: gleiche CSS-Variablen-Pipeline für **eigene** Nav-/Hero-Gruppen oder feste Modul-Akzente — Klassen-Namen und Custom-Properties kompatibel halten, wenn Shell-CSS geteilt wird.

### 4.4 Was nicht tun

- Flache Single-Color-Seiten ohne bestehende Shell-Atmosphäre aufbrechen
- Neue Modal-/Chip-Parallelwelten neben `#ajaxModalHost` / `.mail-recipient-chip`
- Cards im Hero nur „zum Schmuck“

---

## 5. Versionierung und Cache-Bust

### 5.1 Release-Artefakte

| Datei | Inhalt |
|-------|--------|
| `VERSION` | `YYYY-MM-DD-{last5Hash}` |
| `HASH` | SHA1 über Workspace-Dateien |
| `common/version.php` | `$version['String'|'Date'|'Hash']` |

`makeVersion.sh`: Hash berechnen → Dateien schreiben → Changelog pending → Commit `release $VERSION`.  
Nach Merge nach `master`: immer ausführen (`merge-makeversion` / `git-flow.sh release-master`). Sibling-Apps: analoges Script für **eigenes** Repo.

### 5.2 `assetUrl($rel)`

```
{rel}?v={VERSION}&h={HASH}-{mtime}
```

HTML-escaped Rückgabe. **Alle** CSS/JS-Includes darüber (Header, Footer, Seiten) — sonst halten WebViews alte Assets (MELD-184).

Melde: `libs/helpers.php`. Archiv: gleiche Signatur in der UI-Shell-Lib.

### 5.3 Schema-Version (getrennt)

`SchemaVersion` / `ArchivSchemaVersion` sind **DB-Schema**, nicht UI-Asset-Version. Nie vermischen.

---

## 6. Copy-Checkliste für Archiv / Mitgliederverwaltung

Beim Portieren oder Nachziehen:

1. [ ] `adminListPageBegin` / `SearchField` / `PageEnd` + `#filterString`
2. [ ] `#Liste` + `#listSentinel` mit denselben `data-*`-Attributen
3. [ ] `infiniteScroll.js` + `listRowSearch.js` (Verhalten Client vs `data-server-q`)
4. [ ] Sortier-Chips: `#listHeader.inv-sort-bar` + `sortList.js` (`mode: 'server'` bei Chunks) + `data-sort-*` an Zeilen
5. [ ] Log: Chunk + Server-`q` + Poll-Endpoint + Chip-Types
6. [ ] `#ajaxModalHost` außerhalb `.app-main`; `deferPageModalHtml` für Seiten-Modals
7. [ ] Neue Dialoge: `profile-shell modal-shell`
8. [ ] Entity-Chips: `.entity-open` + `data-entity-type` / `data-entity-id`
9. [ ] `appConfirm` / `appAlert` statt native Dialoge
10. [ ] Chip-Modifier aus dem bestehenden Satz
11. [ ] `assetUrl()` auf allen statischen Assets; `makeVersion` bei Release
12. [ ] Keine Cross-App-PHP-Includes; Identity/SSO-Issue bleiben Melde ([PLATFORM.md](PLATFORM.md))

### Ownership (Kurz)

| Muster | Copy in Archiv/MIT | Nur Melde |
|--------|--------------------|-----------|
| Listen-Chrome, `assetUrl`, Modal-Host, Chip-CSS-Slice, Nav-Shell, Session-Bootstrap | ja | — |
| Infinite-Scroll-JS-Vertrag | ja (eigene `type`s) | — |
| Sortier-Chips / `sortList.js` | ja (eigene Sort-Keys) | — |
| Log-UI | eigene Prefix-Tabelle | Melde-Log-Inhalt |
| Entity-Modals | eigene Entities | Melde-`getModal`-Typen |
| User / Permissions / SSO-**Issue** | lesen / Redeem | Owner |

---

## 7. Änderungspflege

1. Verhaltensänderung an Listen/Log/Modal/Version in Melde → dieses Dokument im **selben** Ticket/PR aktualisieren.
2. Archiv/MIT: PR-Beschreibung mit Verweis auf Abschnitt + Melde-Commit/Version.
3. Drift nur bewusst und hier dokumentieren (kurz „Abweichung:“).
