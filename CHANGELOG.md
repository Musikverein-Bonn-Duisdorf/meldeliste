# Changelog

Automatisch aus Git-Release-Commits erzeugt.

## 2026-07-18-47d71 (2026-07-18)

- MELD-79: Dynamischer Dirigent in Orchester-Übersicht

## 2026-07-18-62313 (2026-07-18)

- MELD-77: INSERT/UPDATE-Logs verschlanken; Bool-Vergleich Auftritt

## 2026-07-18-d2e13 (2026-07-18)

- myinventories.php: Inventar-Modal-Speichern übernehmen
- MELD-75/76: Versicherungsliste öffnet Inventar-Modal; Speichern bleibt auf aktuelle Seite

## 2026-07-18-7f431 (2026-07-18)

- MELD-74: Updater nur über perm_editConfig absichern
- MELD-74: Updater-Zugang über Berechtigungen statt User.Admin
- MELD-73: Insert-Log nur mit befüllten Feldern
- MELD-73/74: Config-gated Log-Felder und Updater mit Prüfen/Update

## 2026-07-18-5aebb (2026-07-18)

- MELD-72: Discord-Log Bezeichnung Bot statt Username
- MELD-72: Discord-Webhook-URL nicht mehr im App-Log
- MELD-72: Discord-Checkbox in mail.php nur bei konfiguriertem Webhook
- MELD-71/72: Discord-Skip nicht loggen, nur echte Fehler
- MELD-71/72: Discord ohne Webhook still überspringen, Fehler nur loggen

## 2026-07-18-2a4ed (2026-07-18)

- MELD-69: Inventar-Rechte absichern
- MELD-69: Inventar lesen/schreiben serverseitig absichern

## 2026-07-17-4e16c (2026-07-17)

- MELD-63: HTML/JS/PHP trennen (erster Slice)
- MELD-63: Views-Konvention, User- und Termin-Response-Modal

## 2026-07-17-6301e (2026-07-17)

- MELD-68: Rückmeldungs-Modal ohne perm_showResponse freigeben

## 2026-07-17-1ff0a (2026-07-17)

- MELD-56: Umlaute als UTF-8 speichern, SVG und Schema korrigieren

## 2026-07-17-432a0 (2026-07-17)

- MELD-59: Listenfilter wieder funktionsfähig machen
- MELD-59: Inventar- und Versicherungslisten mobil stapeln
- MELD-59: Email-Ansicht für mobile Screens nutzbar machen

## 2026-07-17-04d1a (2026-07-17)

- MELD-59: Mobile Admin-Nav als Akkordeon, Personenlisten stapeln

## 2026-07-17-ca03c (2026-07-17)

- MELD-44: Beim Email-Kopieren Grußformel nicht mitübernehmen
- MELD-44: Fett-Markierung **…** in Discord-Konverter nicht mehr entfernen
- MELD-44: HTML-Mails für Discord in Markdown/Klartext umwandeln
- MELD-44: Doppelte Grußformel im Discord-Post vermeiden
- MELD-44: Emails optional auf Discord posten

## 2026-07-17-4de70 (2026-07-17)

- MELD-65: Berechtigungen in editierbarer Matrix mit Autosave

## 2026-07-17-fc539 (2026-07-17)

- MELD-66: Ersten Mail-Chunk direkt nach Absenden starten

## 2026-07-17-aaa97 (2026-07-17)

- MELD-18: Aushilfen auf derselben Seite löschbar machen

## 2026-07-17-eeab9 (2026-07-17)

- MELD-51: Datenbank-Schemaversionierung

## 2026-07-17-291c4 (2026-07-17)

- MELD-15: Mein Profil bleibt auf Bearbeitungsseite
- MELD-15: Zufallspasswort-Generierung nach Redirect-Fix
- MELD-15: Form-Submit redirect zurück zur Ausgangsseite

## 2026-07-17-7d41a (2026-07-17)

- MELD-46: Anhang-Label ohne Email-ID-Hinweis
- MELD-46: Mail-Hinweise weiter kürzen
- MELD-46: Erklärende Hinweistexte in der Mail-UI entfernen
- MELD-46: Mail-Tabellen auf utf8mb4 für WYSIWYG-Sonderzeichen
- MELD-46: Editor um Tabelle, Zitat, Suche und Quellcode erweitern
- MELD-46: Email-Formular und Editor-Toolbar verbreitern
- MELD-46: WYSIWYG um Farbe, Schriftgröße und Ausrichtung erweitern
- MELD-46: TinyMCE-WYSIWYG für Email-Texte (self-hosted)

## 2026-07-17-df200 (2026-07-17)

- (keine weiteren Notizen)

## 2026-07-17-54f94 (2026-07-17)

- MELD-58: Nested-Admin-Nav Position, Breite und Hover fixen
- MELD-58: Admin-Nav in Untermenü-Gruppen gliedern

## 2026-07-17-fc3ce (2026-07-17)

- MELD-34: Doppelversand und Live-Status zuverlässig machen
- MELD-34: Einheitliche Zeitstempel in Admin- und Nutzer-Ansicht
- MELD-34: Doppelversand durch parallele Queue-Worker verhindern
- MELD-34: Live-Status für laufenden Mailversand in der Admin-Ansicht
- MELD-34: HTML in Mailvorschau und Detailansichten wieder rendern
- MELD-34: Gesendete Mails in der Admin-Ansicht lesbar
- MELD-34: Ausführlicheres Queue-Logging und Recovery für sending
- MELD-34: Nutzer-Posteingang, Abbrechen/Löschen und Queue-Logging
- MELD-34: Email-Übersicht mit Status und Entwurf-Kopie
- MELD-34: Doppel-Include von include.php verhindern
- MELD-34: Redirects in mail.php vor HTML-Ausgabe
- MELD-34: MailJob/MailOutbox-Schema bei Bedarf anlegen
- MELD-34: Email-Queue mit Entwürfen und Historie

## 2026-07-16-9d555 (2026-07-16)

- MELD-37: Konfigänderungen im App-Log sichtbar machen

## 2026-07-16-7323f (2026-07-16)

- MELD-29: Automatische Datenbankgenerierung und Update
- Meld 43 ausleihe von kleidung und sonstigem material tracken
- Meld 42 auf konzertaufnahmen und medien im allgemeinen verlinken
- merge master
- remerge master
- Meld 41 automatisch melden nur bei sichtbaren terminen

## 2025-01-02-59539 (2025-01-02)

- Meld 40 optionales freitext feld f r r ckmeldungen

## 2024-12-17-d47c6 (2024-12-17)

- (keine weiteren Notizen)

## 2024-12-16-3a6ae (2024-12-16)

- Meld 40 optionales freitext feld f r r ckmeldungen

## 2024-12-16-a8020 (2024-12-16)

- Meld 40 optionales freitext feld f r r ckmeldungen
- release 2024-11-20-6241c
- Meld 38 discord connector
- Meld 39 bei email an terminteilnehmer termin erw hnen

## 2024-11-20-6241c (2024-11-20)

- (keine weiteren Notizen)

## 2024-11-20-3f4f4 (2024-11-20)

- Meld 39 bei email an terminteilnehmer termin erw hnen

## 2024-11-12-a8e43 (2024-11-12)

- Meld 38 discord connector

## 2024-10-23-eadc2 (2024-10-23)

- Meld 35 always maybe

## 2024-10-23-e7ec2 (2024-10-23)

- Meld 36 plausibilit tspr fung von meldungen

## 2024-03-15-a299d (2024-03-15)

- (keine weiteren Notizen)

## 2024-03-14-0f334 (2024-03-14)

- Meld 31 geburtstagsbenachrichtigungen
- Meld 33 abonnierbarer individueller kalender

## 2024-02-15-47e81 (2024-02-15)

- Meld 33 abonnierbarer individueller kalender
- release 2024-02-02-31440
- hotfix aushilfen in Meldungen

## 2024-02-15-3bae5 (2024-02-15)

- (keine weiteren Notizen)

## 2024-02-15-3bae5 (2024-02-15)

- Meld 33 abonnierbarer individueller kalender
- release 2024-02-02-31440
- hotfix aushilfen in Meldungen

## 2024-02-02-31440 (2024-02-02)

- fixing missing aushilfen in capacity calculation
- Meld 29 automatische datenbankgenerierung und update
- MELD-28 self-hosted and updated fontawesome release
- MELD-27-Updater-Funktion-in-der-GUI

## 2023-10-13-0c562 (2023-10-13)

- MELD-27-Updater-Funktion-in-der-GUI

## 2023-10-13-5dbfd (2023-10-13)

- MELD-27-Updater-Funktion-in-der-GUI

## 2023-10-13-b8999 (2023-10-13)

- MELD-27-Updater-Funktion-in-der-GUI
- MELD-6 rename permission nav item
- MELD-26 fixing meldung and shiftmeldung

## 2023-10-06-9d2e2 (2023-10-06)

- MELD-26-Datumsformat-dd-MMM-yyy
- MELD-20-Letzten-Zeitstempel-anzeigen-f-r-Repeating-Log-Messages

## 2023-10-06-39480 (2023-10-06)

- MELD-25-Log-updated-nicht-mehr-automatisch

## 2023-10-06-d18d2 (2023-10-06)

- MELD-22-Informationen-beim-L-schen-von-Terminen-sind-nicht-dargestellt
- MELD-24 adding git icon

## 2023-10-06-39781 (2023-10-06)

- MELD-24 removing div if master branch is active
- MELD-24-Show-branch-name-if-not-master-in-header
- MELD-23-Deaktivierte-Musiker-tauchen-bei-Meldung-nicht-in-der-Orchestergrafik-auf
- MELD-21-Ausgabe-im-Log-uneinheitlich

## 2023-09-21-b46f8 (2023-09-21)

- MELD-19-Benutzer-sind-nicht-bearbeitbar
- MELD-2 MELD-3 Aushilfen und Musiker werden jetzt mit korrektem Instru…
- MELD-17-Aushilfe-From-in-Termin-Detail-hat-Layout-Fehler
- MELD-16-user.php-class-rufen-Termin-und-User-in-getChanges-auf

## 2023-09-21-ccb26 (2023-09-21)

- MELD-13-PHPMailer-als-Submodule-implementieren
- MELD-14-Log-Format-Terminupdate

## 2023-09-20-88279 (2023-09-20)

- MELD-11-Termin-ID-ist-null-bei-neuen-Terminen
- MELD-12-Filter-in-Userliste-ist-kaputt
- MELD-6-Permission-Matrix

## 2023-09-20-47d02 (2023-09-20)

- MELD-10-Review-Log-Layout

## 2023-09-18-dee3d (2023-09-18)

- MELD-7-Filterfunktion-f-r-Log
- MELD-8-Nachname-trailing-leading-spaces

## 2023-09-18-fcca0 (2023-09-18)

- MELD-5-Musiker-auf-inaktiv-setzen

## 2023-09-18-dddb6 (2023-09-18)

- (keine weiteren Notizen)

## 2023-09-15-21ea9 (2023-09-15)

- (keine weiteren Notizen)

## 2023-09-15-14e85 (2023-09-15)

- (keine weiteren Notizen)

## 2023-09-15-57ea4 (2023-09-15)

- (keine weiteren Notizen)

## 2023-08-04-8b500 (2023-08-04)

- (keine weiteren Notizen)

## 2023-05-08-b8b5e (2023-05-08)

- (keine weiteren Notizen)

## 2021-11-30-73ef8 (2021-11-30)

- (keine weiteren Notizen)

## 2021-11-30-4897d (2021-11-30)

- (keine weiteren Notizen)

## 2021-11-22-8f5ab (2021-11-22)

- (keine weiteren Notizen)

## 2021-11-19-f1d78 (2021-11-19)

- (keine weiteren Notizen)

## 2021-11-18-5fbd9 (2021-11-18)

- (keine weiteren Notizen)

## 2021-11-17-5c3e1 (2021-11-17)

- (keine weiteren Notizen)

## 2021-11-17-198c7 (2021-11-17)

- (keine weiteren Notizen)

## 2021-11-16-198c7 (2021-11-16)

- (keine weiteren Notizen)

## 2021-11-05-198c7 (2021-11-05)

- (keine weiteren Notizen)

## 2021-05-26-198c7 (2021-05-26)

- (keine weiteren Notizen)

## 2020-11-06-198c7 (2020-11-06)

- (keine weiteren Notizen)

## 2020-10-11-198c7 (2020-10-11)

- (keine weiteren Notizen)
