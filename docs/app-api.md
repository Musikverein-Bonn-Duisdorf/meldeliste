# Mobile App API (MELD-49)

Anforderungen der Android-App **MVDApp** an das Meldeliste-Backend.
Ziel: WebView-Shell mit persistentem Auto-Login und Benachrichtigungen per Pull.
Keine native Termine-/Melde-UI und keine große Domain-JSON-API im MVP.

**Jira:** [MELD-49](https://musikverein-bonn-duisdorf.atlassian.net/browse/MELD-49)  
**App-Repo:** `MVDApp` (Branch `feature/MELD-49-app-token-notify`)  
**Backend-Branch:** `MELD-49-App-API`

Nach Deploy: Schema-Repair/Updater ausführen (Schema-Version ≥ **14**).

---

## Architektur (MVP)

```
App (Login)  →  POST /api/auth/login.php     →  App-Token speichern + Session-Cookie
App (Start)  →  POST /api/auth/session.php   →  Token → PHP-Session → WebView
App (Poll)   →  GET  /api/notify/poll.php    →  lokale Notifications (WorkManager)
App (Logout) →  POST /api/auth/revoke.php    →  Token widerrufen
App (UI)     →  GET  /api/branding.php       →  Statusleisten-/Chrome-Farben (öffentlich)
```

Die Website bleibt die UI. Die App lädt `https://meldeliste.…` im WebView, sobald die Session steht.

**Später optional:** FCM Push (nicht Teil dieses MVP).

---

## Schema: `AppTokens`

Eintrag in `config/DBconfig.json` (physisch `{dbprefix}AppTokens`).  
`config/schema_version_number.php` auf **14** (oder höher) setzen, dann Repair.

| Spalte | Typ | Bedeutung |
|--------|-----|-----------|
| `Index` | int AUTO_INCREMENT | Primärschlüssel |
| `User` | int | FK → `User.Index` |
| `TokenHash` | text | SHA-256 des Raw-Tokens (nie Klartext speichern) |
| `DeviceLabel` | text NULL | z. B. Gerätename |
| `Created` | timestamp | Anlage |
| `LastUsed` | timestamp NULL | letzter erfolgreicher Exchange/Poll |
| `Expires` | timestamp NULL | optional; `NULL` = kein Ablauf |
| `Revoked` | tinyint Default 0 | Widerruf |

Token erzeugen: `bin2hex(random_bytes(32))`, nur Hash in der DB.

---

## Auth-Helfer

Session-Aufbau wie bei Passwort-/Link-Login in eine gemeinsame Funktion legen:

- Session-Keys: `userid`, `Vorname`, `Nachname`, `username`, `singleUsePW`, `permissions`, `admin`
- `recordLogin()` aufrufen

Referenz-Implementierung (Entwurf): `libs/appToken.php`.

---

## Endpunkte

Stil wie bestehende JSON-Scripts (`mailStatus.php`):

1. `meldeConfigureSession()` (über `api/_bootstrap.php`)
2. `common/include.php`
3. Header `Content-Type: application/json; charset=UTF-8`
4. `Cache-Control: no-store, no-cache, must-revalidate`

Pfade mit `.php` (kein URL-Rewrite nötig).
Token nur per `Authorization: Bearer` oder JSON/Form-Body — **nicht** per Query-String.

### `GET /api/branding.php`

Öffentliche Chrome-Farben aus der Site-Config (kein Auth).  
Für native Statusleiste / Splash; Quelle: DB-Config `colorTitle`, `colorTitleBar`, `colorNav`, `colorBackground`.

**Erfolg (200):**

```json
{
  "themeColor": "#FDF9E7",
  "themeColorOn": "#000000",
  "colorTitle": "#FDF9E7",
  "colorTitleBar": "#345A95",
  "colorNav": "#969696",
  "colorBackground": "#FDFFFC",
  "siteName": "Meldeliste"
}
```

| Feld | Bedeutung |
|------|-----------|
| `themeColor` | Statusleiste / `theme-color` (= `colorTitle`, Titelstreifen) |
| `themeColorOn` | Kontrastschrift zu `themeColor` (`#000000` / `#FFFFFF`) |
| `colorTitle` / `colorTitleBar` / `colorNav` / `colorBackground` | Roh-Hex aus Config |
| `siteName` | `WebSiteName` |

**Fehler:** `405` bei anderem als `GET`.

### `POST /api/auth/login.php`

**Body** (JSON oder Form):

| Feld | Pflicht | Beschreibung |
|------|---------|--------------|
| `login` | ja | Benutzername |
| `password` | ja | Passwort |
| `device` | nein | Gerätebezeichnung |

**Erfolg (200):** PHP-Session setzen (`Set-Cookie`) + neues App-Token anlegen.

```json
{
  "token": "<raw-token>",
  "user": {
    "id": 1,
    "name": "Max Mustermann",
    "singleUsePW": false
  },
  "expires": null
}
```

**Fehler:** `401` `{ "error": "invalid_credentials" }`

### `POST /api/auth/session.php`

Token → PHP-Session (Auto-Login beim App-Start).

**Auth:** `Authorization: Bearer <token>` **oder** JSON/Form-Feld `token`.

**Erfolg (200):**

```json
{
  "ok": true,
  "user": {
    "id": 1,
    "name": "Max Mustermann",
    "singleUsePW": false
  }
}
```

**Fehler:** `400` `{ "error": "missing_token" }`, `401` `{ "error": "invalid_token" }`

### `POST /api/auth/revoke.php`

Token widerrufen (`Revoked = 1`), optional Session zerstören.

**Auth:** wie bei `session.php`.

**Erfolg:** `{ "ok": true }` bzw. `{ "ok": false }`

### `GET /api/notify/poll.php?since=<ISO8601|MySQL-datetime>`

**Auth:** Bearer-Token **oder** bestehende PHP-Session.

**Quelle (MVP):** `MailOutbox` für den User:

- `DeletedByUser = 0`
- `Status IN ('pending','sending','sent')`
- bei gesetztem `since`: `Created > since`
- Limit sinnvoll (z. B. 50)

**Erfolg (200):**

```json
{
  "events": [
    {
      "id": "mail-123",
      "type": "mail",
      "title": "Betreff",
      "body": "Neue Nachricht in der Meldeliste",
      "created": "2026-07-19T10:00:00+02:00",
      "unread": true,
      "url": "meine-mails.php"
    }
  ],
  "serverTime": "2026-07-19T11:00:00+02:00",
  "unreadMail": 2
}
```

Die App speichert `serverTime` als nächsten `since`-Cursor und zeigt lokale Notifications.

**Fehler:** `401` `{ "error": "unauthorized" }`

---

## Explizit nicht im MVP

- Native Screens / parallele JSON-API für Termine und Melden
- FCM / Device-Register
- iOS-spezifische Backend-Extras

---

## Test-Checklist

- [ ] Schema-Repair → Tabelle `{prefix}AppTokens` existiert
- [ ] Login mit gültigen Credentials liefert Token + `Set-Cookie`
- [ ] Login mit ungültigen Credentials → `401`
- [ ] Session-Exchange mit Token → Browser/WebView lädt `index.php` eingeloggt
- [ ] Poll ohne/`since` liefert MailOutbox-Events
- [ ] Revoke → erneuter Exchange schlägt fehl (`401`)

---

## Dateien (Entwurf auf `feature/MELD-49-app-api`)

| Pfad | Rolle |
|------|--------|
| `config/DBconfig.json` | Tabelle `AppTokens` |
| `config/schema_version_number.php` | Version 13 |
| `libs/appToken.php` | Token-/Session-Helfer, Poll-Query |
| `libs/helpers.php` | Login nutzt gemeinsame Session-Funktion |
| `api/_bootstrap.php` | JSON-Bootstrap |
| `api/auth/login.php` | Login |
| `api/auth/session.php` | Session-Exchange |
| `api/auth/revoke.php` | Widerruf |
| `api/notify/poll.php` | Notify-Poll |
| `api/branding.php` | Öffentliche Chrome-Farben |
