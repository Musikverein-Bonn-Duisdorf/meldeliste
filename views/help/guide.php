<?php
/**
 * Help guide sections (permission-filtered).
 * Keep this in sync when user-facing workflows change (see ticket workflow / makeVersion reminder).
 *
 * Expected vars: $helpUser (User), $optionsDB
 */

$sections = array();
$yesColor = htmlspecialchars($optionsDB['colorAppmntYes'], ENT_QUOTES, 'UTF-8');
$noColor = htmlspecialchars($optionsDB['colorAppmntNo'], ENT_QUOTES, 'UTF-8');
$maybeColor = htmlspecialchars($optionsDB['colorAppmntMaybe'], ENT_QUOTES, 'UTF-8');

$meldeButtons = '
<table class="help-legend">
<tr><td class="w3-border w3-border-black w3-center w3-green help-legend-swatch"><b>&#10004;</b></td><td>Komme (ja)</td></tr>
<tr><td class="w3-border w3-border-black w3-center w3-red help-legend-swatch"><b>&#10008;</b></td><td>Komme nicht (nein)</td></tr>
<tr><td class="w3-border w3-border-black w3-center w3-blue help-legend-swatch"><b>?</b></td><td>Noch unsicher (vielleicht)</td></tr>
</table>';

$registerLegend = '
<table class="help-legend">
<tr><td class="w3-border w3-border-black '.$yesColor.' help-legend-swatch-wide">Komme</td></tr>
<tr><td class="w3-border w3-border-black '.$noColor.' help-legend-swatch-wide">Komme nicht</td></tr>
<tr><td class="w3-border w3-border-black '.$maybeColor.' help-legend-swatch-wide">Bin noch unsicher</td></tr>
</table>';

$sections[] = array(
    'id' => 'einfuehrung',
    'title' => 'Einführung',
    'body' => '
<p>Die Meldeliste ist die zentrale Plattform für Termine, Rückmeldungen und (je nach Rechten) Verwaltung im Verein.</p>
<p>Über die Navigation erreichst du die Bereiche, die für dich freigeschaltet sind: auf dem Desktop links mit Text, auf Tablet und Smartphone unten als Leiste (weitere Einträge und Admin unter <b>Mehr</b>). Diese Hilfe zeigt nur Abschnitte, die zu deinen aktuellen Rechten passen.</p>
<p>Bitte melde dich möglichst vollständig zu Terminen an (ja / nein / vielleicht) – das erleichtert die Planung enorm.</p>
'
);

$sections[] = array(
    'id' => 'navigation',
    'title' => 'Navigation',
    'body' => '
<p>Auf dem Desktop steht die Navigation links (Icons mit Beschriftung). Auf Tablet und Smartphone unten; unter <b>Mehr</b> findest du weitere Einträge (u.&nbsp;a. Mein Profil), Admin und Ausloggen.</p>
<ul class="help-list">
<li><i class="far fa-calendar-alt"></i> <b>Termine</b> – bevorstehende Termine und schnelles Melden</li>
<li><i class="fas fa-calendar"></i> <b>Kalender</b> – Monatsübersicht der für dich sichtbaren Termine (Farbe = deine Meldung; ausgegraut wie in der Übersicht, wenn du nicht zur Zielgruppe gehörst; bei Schichten erscheinen die einzelnen Schichten mit ihren Zeiten; Klick öffnet Meldeabfrage, „Weitere Optionen“ die Details; bei vielen Einträgen am selben Tag öffnet <b>+N</b> die Tagesauswahl); Info-Button für Abo-Link, Drucken für alle kommenden Termine als Tabelle</li>
<li><i class="fas fa-envelope"></i> <b>Meine Nachrichten</b> – empfangene Mails aus der Meldeliste (Badge bei ungelesenen)</li>
<li><i class="fas fa-users"></i> <b>Mein Register</b> – Rückmeldungen deines Registers (auf Tablet/Smartphone dauerhaft in der unteren Leiste)</li>
'.($helpUser->hasInventories() ? '<li><i class="fas fa-shirt"></i> <b>Mein Inventar</b> – dir gehörendes oder an dich ausgeliehenes Inventar</li>' : '').'
<li><i class="fas fa-user"></i> <b>Mein Profil</b> – eigene Stammdaten und Einstellungen (Desktop in der Seitenleiste, Tablet/Smartphone unter <b>Mehr</b>)</li>
<li><i class="fas fa-photo-film"></i> <b>Medien</b> – Links zu Aufnahmen und Social Media (konfigurierbar)</li>
'.((!empty($optionsDB['urlNotenarchiv']) && requirePermission('perm_accessNotenarchiv')) ? '<li><i class="fas fa-book"></i> <b>Notenarchiv</b> – SSO-Link zum Notenarchiv</li>' : '').'
'.((!empty($optionsDB['urlMitgliederverwaltung']) && requirePermission('perm_accessMitgliederverwaltung')) ? '<li><i class="fas fa-id-card"></i> <b>Mitglieder</b> – SSO-Link zur Mitgliederverwaltung</li>' : '').'
<li>Logo oben rechts – öffnet die <b>Vereinshomepage</b> in einem neuen Tab</li>
<li><i class="fas fa-circle-question"></i> <b>Hilfe</b> – diese Seite inkl. Changelog</li>
'.(isAdmin() ? '<li><i class="fas fa-wrench"></i> <b>Admin</b> – Verwaltungsmenü in der Reihenfolge Personen → Termine → Meldungen → Kommunikation → Inventar → Register → System; Einträge sind in denselben Farben wie die Rechte-Chips eingefärbt (Desktop links unten, Tablet/Smartphone unter Mehr)</li>' : '').'
<li><i class="fas fa-sign-out-alt"></i> <b>Ausloggen</b> – Sitzung beenden</li>
</ul>
'
);

$sections[] = array(
    'id' => 'melden',
    'title' => 'Zu Terminen melden',
    'body' => '
<p>Unter <b>Termine</b> (Startseite) kannst du dich zu Terminen eintragen:</p>
<ul>
<li>Über die Suchzeile findest du Termine nach Titel, Ort, Datum oder Beschreibung (auch im Termin-Archiv).</li>
<li>Unter <b>Kalender</b> siehst du dieselben Termine als Monatsraster (ausgegraut wie in der Übersicht, wenn du nicht zur Zielgruppe gehörst); bei Schichten die einzelnen Schichten mit ihren Zeiten. Klick öffnet zuerst die Meldeabfrage (ja / nein / vielleicht). Über <b>Weitere Optionen</b> erreichst du die Termin-Details. Passt nicht alles in die Zelle, öffnet <b>+N</b> eine Liste aller Einträge dieses Tages. Über dem Monat: Info öffnet das Abo-Fenster, Drucken listet alle kommenden Termine (nicht nur den aktuellen Monat).</li>
</ul>
'.$meldeButtons.'
<p>Tippe auf den gewünschten Status. Die Farbe am Termin zeigt deinen aktuellen Stand. Eine erneute Auswahl ändert die Meldung.</p>
<p><b>Tipp:</b> Auch „vielleicht“ oder „nein“ sind wertvoll – offene Einträge erschweren die Planung.</p>
<p>Bei Terminen mit <b>Besetzung</b> kannst du im Termin-Detail ggf. das <b>Instrument für diesen Termin</b> anpassen (z.&nbsp;B. Dirigat übernehmen). Speichern mit dem Speicher-Button neben der Auswahl.</p>
<p>Ein Klick auf Titel, Beschreibung oder Ort öffnet die Termin-Details (Uhrzeit, Orchesterübersicht, …).</p>
<p>Über <i class="fa fa-calendar-plus"></i> kannst du einen Termin als ICS-Datei in deinen Kalender (Google, Outlook, …) importieren. Bei Terminen mit Schichten steht der Button an jeder Schichtzeile und übernimmt Schichtname und -zeit.</p>
'
);

$sections[] = array(
    'id' => 'mein-register',
    'title' => 'Mein Register',
    'body' => '
<p>Unter <b>Mein Register</b> siehst du, wie sich die Musikerinnen und Musiker deines Registers zu Terminen gemeldet haben. Die Übersicht nutzt dieselbe Zeilenoptik wie die Startseite (Datum, Titel, Status-Chips); Tippen/Klick öffnet die Namensliste im Detail – Personenzeilen in Registerfarbe, gruppiert nach Zusage / Unsicher / Absage.</p>
<ul>
<li>Über die Suchzeile findest du Termine nach Titel, Ort, Datum oder Beschreibung.</li>
</ul>
'.$registerLegend.'
<p>So erkennst du schnell Lücken in der Besetzung deines Registers.</p>
'
);

$sections[] = array(
    'id' => 'nachrichten',
    'title' => 'Meine Nachrichten',
    'body' => '
<p>Über das Brief-Symbol öffnest du deinen Posteingang in der Meldeliste. Dort erscheinen Nachrichten, die über die App an dich verschickt wurden.</p>
<p>Ungelesene Nachrichten werden in der Navigation als Badge angezeigt. Öffne eine Nachricht, um sie zu lesen; der Status aktualisiert sich entsprechend. Lange Listen werden beim Scrollen nachgeladen; die Suchleiste filtert die bereits geladenen Einträge nach Betreff oder Absender.</p>
'
);

if($helpUser->hasInventories()) {
    $sections[] = array(
        'id' => 'mein-inventar',
        'title' => 'Mein Inventar',
        'body' => '
<p>Wenn dir Inventar gehört <b>oder an dich ausgeliehen</b> ist (aktive Leihe), erscheint <b>Mein Inventar</b> in der Navigation.</p>
<p>Dort siehst du zuerst verliehene Stücke (Chip „verliehen“ und unter Ausleihe die Person), danach eigenes Eigentum – als Liste bzw. auf dem Handy als Karten mit Nr., Typ, Hersteller und Modell. Sind Fotos hinterlegt, erscheint ein Thumbnail; ohne Stückfoto das Default-Bild des Inventar-Typs, falls hinterlegt. Ein Tippen/Klick öffnet die Details im Modal; dort kannst du durch die Fotos blättern, Klick aufs Bild vergrößert, und unter <b>Dokumente</b> hinterlegte Dateien öffnen. Bearbeiten, Hochladen und Löschen nur mit den entsprechenden Rechten.</p>
<p>Liegt ein Leihvertrag oder Rückgabeprotokoll zur Unterschrift, erscheint es oben unter <b>Zur Unterschrift</b>. Ein Rückgabeprotokoll startest du nicht selbst — das macht die Inventar-Verwaltung; danach kannst du unterschreiben. Du unterschreibest im Popup (nur dein Feld; Ort, Datum und Uhrzeit werden automatisch ergänzt). Ist der Vertrag schon vom Verein unterschrieben, kannst du ihn auch vor Ort auf dem geöffneten Formular des Inventar-Admins unterschreiben. Fertig ist das Formular erst mit beiden Unterschriften; dann wird das fertige Formular wie ein Scan an der Leihe gespeichert und geht zusätzlich per Mail an dich. Die Inventar-Verwaltung kann dir den Vertrag zur Unterschrift senden (Nachricht und E-Mail mit Login-Link direkt zum Formular). Bis dahin kannst du deine eigene Unterschrift wieder löschen. Drucken und Scannen bleiben daneben möglich. Das ist keine qualifizierte elektronische Signatur.</p>
'
    );
}

$sections[] = array(
    'id' => 'profil',
    'title' => 'Mein Profil',
    'body' => '
<p>Unter <b>Mein Profil</b> pflegst du deine Kontaktdaten, E-Mail-Adressen und weitere Angaben für den Melde-Betrieb.</p>
<p>Halte insbesondere E-Mail und Instrument aktuell – davon hängen Benachrichtigungen und die Orchesterdarstellung ab. <b>Geburtstag, Telefon, Anschrift und Vereinsmitgliedschaft</b> pflegst du in der <b>Mitgliederverwaltung</b> (vollständige Stammdaten). Ohne das Recht <b>Benutzer bearbeiten</b> sind Name, Instrument, Gruppen und Rechte hier nur lesbar bzw. nicht änderbar; mit diesem Recht kannst du dein Melde-Subset unter <b>Mein Profil</b> genauso bearbeiten wie in der User-Verwaltung.</p>
<p><b>Benachrichtigungen</b> (unabhängig wählbar):</p>
<ul>
<li><b>E-Mail</b> – Nachrichten per E-Mail (Mailverteiler / SMTP)</li>
<li><b>Nachrichten</b> – Eintrag unter „Meine Nachrichten“ in der Meldeliste</li>
<li><b>App: …</b> – lokale Hinweise in der Android-App (Poll, kein Push-Dienst). Pro Ereignisart: neue Nachricht, neuer Termin, Termin geändert, Termin bald (nächste Tage; standardmäßig aus)</li>
</ul>
<p>Unter <b>Gruppen</b> siehst du, welchen Rollen du zugeordnet bist – relevant für Mail und Termin-Sichtbarkeit:</p>
<ul>
<li><b>Alle User</b> – Konto vorhanden</li>
<li><b>Alle Musiker</b> – Haken <b>aktiv</b> und Instrument zugeordnet</li>
<li><b>Alle Aktiven</b> / <b>Alle Fördernden</b> – offene Mitgliedschaft dieses Typs in der Mitgliederverwaltung</li>
<li><b>Alle Mitglieder</b> – Aktive oder Fördernde</li>
<li><b>Alle Nicht-Mitglieder</b> – keine offene Vereinsmitgliedschaft</li>
</ul>
<p>Den <b>Mitglied</b>-Status setzt die Mitgliederverwaltung; benannte Gruppen ändert nur, wer <b>Benutzer bearbeiten</b> hat (unter Musiker anlegen/bearbeiten oder im eigenen Profil). <b>Automatisch</b> zeigt dem Admin live die daraus folgenden Rollen/Register/regelbasierten Gruppen. Änderungen an Benachrichtigungen und Profilfeldern werden im Anwendungsprotokoll festgehalten.</p>
<p>Falls du ein Einmal-Passwort erhalten hast, wirst du nach dem Login zum Ändern des Passworts aufgefordert.</p>
<p>Die Android-App speichert nach dem Login ein Gerätetoken und meldet dich beim nächsten Öffnen automatisch an. Abmelden in der App widerruft dieses Token.</p>
<p>Unter <b>Persönlichen Kalender abonnieren</b> findest du deinen persönlichen ICS-Link für Google, Apple oder Outlook (siehe auch <a href="#help-kalender-abo">Persönlichen Kalender abonnieren</a>).</p>
'
);

$sections[] = array(
    'id' => 'kalender-abo',
    'title' => 'Persönlichen Kalender abonnieren',
    'body' => '
<p>Du kannst deine sichtbaren Meldeliste-Termine in deinen privaten Kalender (Google, Apple, Outlook, …) <b>abonnieren</b>. Der Link steht unter <b>Mein Profil</b> und auf der Seite <b>Kalender</b> im Info-Dialog (runde Buttons über der Monatsauswahl).</p>
<p><b>Einweg:</b> Termine und dein Melde-Status (zugesagt / vielleicht / ohne) werden in den Kalender übernommen. Bei Schichten erscheinen die einzelnen Schichten (Name und Zeit), nicht der Eltern-Termin. Zu- und Absagen änderst du weiterhin in der Meldeliste — nicht in der Kalender-App.</p>
<p><b>Aktualisierung:</b> Wie oft der Feed neu geladen wird, steuert dein Kalender-Anbieter (oft erst nach einigen Stunden). Darauf hat die Meldeliste keinen Einfluss.</p>
<ul>
<li><b>Google Kalender</b> (am PC): Weitere Kalender → <b>Über URL hinzufügen</b> → HTTPS-Link einfügen.</li>
<li><b>Apple</b> (iOS): Einstellungen → Kalender → Accounts → Account hinzufügen → Andere → <b>Kalenderabonnement</b>; oder in der Kalender-App auf dem Mac: Ablage → Neues Kalenderabonnement.</li>
<li><b>Outlook</b>: Kalender hinzufügen → Aus dem Internet / Abonnieren → HTTPS- oder webcal-Link.</li>
</ul>
<p>Abgesagte Termine (Nein) erscheinen nicht im Abo. Ausgegraute Termine (außerhalb deiner Zielgruppe, aber für dich sichtbar) sind im Abo als frei hinterlegt. Der Link ist persönlich — nicht weitergeben.</p>
'
);

$sections[] = array(
    'id' => 'medien',
    'title' => 'Medien &amp; Vereinshomepage',
    'body' => '
<p>Unter <b>Medien</b> (Icon <i class="fas fa-photo-film"></i>) findest du die konfigurierten Links zu Discord, YouTube, Instagram, Facebook sowie Fotos, Videos und Audio – leere Einträge in der Konfiguration werden ausgeblendet.</p>
<p>Das <b>Logo</b> oben rechts öffnet die Vereinshomepage in einem neuen Tab (kein eigener Nav-Button mehr).</p>
'
);

$sections[] = array(
    'id' => 'admin-personen',
    'title' => 'Admin: Personen',
    'visible' => isAdmin() && requirePermission('perm_showUsers'),
    'body' => '
<ul class="help-list">
<li><b>Personenliste</b> – alle Nutzer an einem Ort; die Liste lädt beim Scrollen nach, Sortier-Chips sortieren serverseitig (Standard: Register-Prio), Suche (mehrere Wörter = UND) und Filter-Chips (Aktive/Gäste, Mitglieder/Nicht-Mitglieder, Register) filtern die bereits geladenen Einträge (bei aktiver Filterung wird weiter nachgeladen). Klick öffnet Details im Modal. Zeilen in Registerfarbe; Instrument, Gruppen und Rechte als Chips (Rechte über Gruppe gestrichelt); im Modal bei Inventar-Recht auch Eigentum und aktive Leihen als Inventar-Chips. Orchestergrafik ist aufklappbar (standardmäßig offen)</li>
'.(requirePermission('perm_editUsers') ? '<li><b>Musiker anlegen</b> – Person anlegen inkl. Benachrichtigungen, Haken <b>aktiv</b> (aus = Gastmusiker), Instrument, Gruppen-Chips und Rechte (persönlich editierbar; über Gruppen vererbte Rechte erscheinen mit gestricheltem Rahmen und sind hier nicht entfernbar); Mitgliedschaft wird in der Mitgliederverwaltung gepflegt; beim Bearbeiten bei Inventar-Recht (oder eigenes Profil) Eigentum und aktive Leihen als Inventar-Chips; <b>Deaktivieren</b> setzt Gastmusiker; <b>Löschen</b> prüft zuerst Inventar (Eigentum/aktive Ausleihe blockiert das Löschen mit Hinweis), entfernt danach zukünftige Meldungen/Schichtmeldungen und soft-löscht die Person – zurückliegende Meldungen bleiben für Statistik/Archiv; <b>Automatisch</b> zeigt die abgeleitete Zugehörigkeit</li>' : '').'
'.(requirePermission('perm_editUsers') && !empty($optionsDB['urlNotenarchiv']) ? '<li><b>Stimme / Fallbacks</b> – primäre Stimme und Fallback-Instrumente für das Notenarchiv (Stimmsatz); Priorität zuerst Primär, dann Fallbacks in Reihenfolge; im Profil verlinkt oder <code>user-voice.php</code></li>' : '').'
</ul>
'
);

$sections[] = array(
    'id' => 'admin-termine',
    'title' => 'Admin: Termine',
    'visible' => isAdmin() && requirePermission('perm_editAppmnts'),
    'body' => '
<p>Unter Admin → <b>Termin erstellen</b> legst du neue Termine an. Das Formular ist in Abschnitte gegliedert (Was, Wann, Wo, Optionen): auf dem Smartphone untereinander, auf dem Tablet zweispaltig, am PC als vier Spalten nebeneinander.</p>
<p>Ist ein <b>Notenarchiv</b> angeschlossen, kannst du dem Termin unter <b>Programm</b> eine oder mehrere Archiv-Sammlungen zuordnen (Chip-Eingabe). In der Terminübersicht erscheint der Chip <b>Programm</b>; Klick öffnet die Stückliste wie im Archiv (Cover, Komponist, Arrangeur, Verlag, …).</p>
<p>Im <b>Kalender</b> kannst du auf eine freie Tagesfläche klicken: Nach Bestätigung öffnet sich das Anlege-Formular mit vorausgefülltem Datum.</p>
<p>Nach Speichern/Löschen von Terminen oder Schichten &amp; Aufgaben erfolgt ein Redirect (kein erneutes Absenden beim Aktualisieren); Rücksprungziele können über Session-Token (<code>return_token</code>) geführt werden. Beginn- und Endzeit einer Schicht/Aufgabe sind optional.</p>
<p>Das Flag <b>Besetzung</b> steuert, ob Registeraufschlüsselung und Orchesterdarstellung greifen – für Proben und Auftritte. Veranstaltungen ohne Besetzung (z.&nbsp;B. Grillfest, Radtour) brauchen das nicht (nur Manpower).</p>
<p>Mit dem Chip-Feld <b>sichtbar für</b> steuerst du den Kreis. Der Standard für neue Termine kommt aus der Konfiguration (<code>defaultTerminVisibility</code>, Auslieferung: <b>Alle Musiker</b>). Ohne Chips = versteckt – nur User mit Recht <b>Versteckte Termine anzeigen</b>. Mit Chips nur der gewählte Kreis (Rollen, Gruppen, Register, Personen); Admins mit dem genannten Recht sehen weiterhin alles. Personen ohne Haken <b>aktiv</b> (Gastmusiker) kannst du hier wie andere Personen auswählen – sie gehören dann zu Sichtbarkeit und Besetzung dieses Termins.</p>
<p>Die Checkbox <b>Discord</b> ist vorausgewählt, wenn die Sichtbarkeit den Config-Default-Gruppen entspricht; sie lässt sich abwählen. Posts erfolgen nur bei gesetzter Checkbox und konfiguriertem Webhook.</p>
<p>Im <b>Archiv: Termine</b> findest du vergangene Termine (ebenfalls durchsuchbar).</p>
'
);

$sections[] = array(
    'id' => 'auftrag',
    'title' => 'Melden im Auftrag',
    'visible' => isAdmin() && requirePermission('perm_editResponse'),
    'body' => '
<p>Mit <b>im Auftrag melden</b> (Admin → Meldungen) kannst du für andere Personen melden – z.&nbsp;B. wenn jemand telefonisch absagt.</p>
<ol>
<li>Person auswählen (Proxy)</li>
<li>Termine wie gewohnt melden</li>
<li>Instrument für den Termin ggf. für diese Person setzen</li>
</ol>
<p>Solange du im Auftrag arbeitest, beziehen sich Meldungen und Instrument-Änderungen auf die ausgewählte Person, nicht auf dich. Die Terminliste aktualisiert den Status der Proxy-Person sofort nach dem Melden.</p>
'
);

$sections[] = array(
    'id' => 'admin-meldungen',
    'title' => 'Admin: Meldungen',
    'visible' => isAdmin() && requirePermission('perm_showResponse'),
    'body' => '
<p>Unter Admin → <b>Meldungen</b> siehst du Rückmeldungen übergreifend; im <b>Archiv</b> vergangene Termine. Beide Listen haben eine Suchzeile (Titel, Ort, Datum, Beschreibung) und dieselben kompakten Terminzeilen wie auf der Startseite (Status-Chips, Register-Zusammenfassung).</p>
<p>In Termin- und Register-Ansichten kannst du Rückmeldungs-Modals öffnen – Namenslisten nach Status gruppiert, Personenzeilen in Registerfarbe; Namen als Chip öffnen das Personen-Modal. Die Orchesterübersicht skaliert auf die Fensterbreite und zeigt die Besetzung farbig nach Meldestatus (Hover zeigt Name und Status). Mit <b>Nur aktive Besetzung</b> siehst du einen Sitzplan nur mit Zusagen und Unsicheren – ohne Lücken durch Absagen oder fehlende Meldungen.</p>
'.(requirePermission('perm_editResponse') ? '<p>Mit Recht <b>Rückmeldungen bearbeiten</b> kannst du im Orchesterplan per Klick auf einen Kreis den Status durchschalten: (keine Meldung →) Zusage → Absage → unsicher → Zusage …</p>' : '').'
'
);

$sections[] = array(
    'id' => 'admin-mail',
    'title' => 'Admin: E-Mails',
    'visible' => isAdmin() && requirePermission('perm_sendEmail'),
    'body' => '
<p>Unter Admin → <b>Email versenden</b> erstellst du Nachrichten an Verteiler oder einzelne Empfänger.</p>
<p>Unter Admin → <b>Gruppen</b> legst du wiederverwendbare Gruppen an. <b>Mitglieder</b> sind die Union aus Rollen, Registern und einzelnen Personen (z.&nbsp;B. Posaunen + Schlagwerk + Klarinetten + einzelne Personen). Diese Gruppen kannst du beim Mailversand und bei der Termin-Sichtbarkeit als Chip auswählen. Unter <b>Vererbte Rechte</b> kannst du einer Gruppe Rechte setzen (z.&nbsp;B. „Versteckte Termine“ für den Vorstand) – alle Mitglieder erhalten diese zusätzlich zu ihren persönlichen. Einzelne Personen kannst du den Gruppen auch direkt im Profil (Anlegen/Bearbeiten) zuordnen.</p>
<p>Beim Mailversand kannst du Chips für Rollen, Gruppen, Register, Personen und <b>Teilnehmer</b> (ja/vielleicht) zukünftiger Termine wählen. Über <b>Email an Teilnehmer</b> am Termin wird der passende Teilnehmer-Chip vorausgewählt. Mails werden in einer Warteschlange verarbeitet; den Versandstatus siehst du in der Admin-Ansicht (<b>Versendet</b> nur bei erfolgreichem SMTP; Fehler und teilweise fehlgeschlagene Jobs werden dort mitgezählt). Bei versendeten Mails siehst du den gewählten <b>Verteiler</b> sowie die Liste der einzelnen Empfänger. Empfänger finden die Nachricht unter <b>Meine Nachrichten</b> (auch wenn der E-Mail-Versand fehlgeschlagen ist, sofern die Inbox aktiv war). Die Übersicht lädt lange Listen beim Scrollen nach; die Suchleiste filtert nach Betreff, Absender, Status oder ID.</p>
<p>Falls Discord angebunden ist, kann der Versand optional auch dort veröffentlicht werden (nur bei konfiguriertem Webhook).</p>
'
);

$sections[] = array(
    'id' => 'admin-inventar',
    'title' => 'Admin: Inventar',
    'visible' => isAdmin() && (requirePermission('perm_showInventories') || requirePermission('perm_editInventories')),
    'body' => '
<ul class="help-list">
'.(requirePermission('perm_showInventories') ? '
<li><b>Inventar</b> – Vereinsbesitz (Bestände, Details und Ausleihen); die Liste lädt beim Scrollen nach, Sortier-Chips sortieren serverseitig, Suche (mehrere Wörter = UND, z. B. <code>marsch Ralf</code>) und Chip <b>Versichert</b> filtern die bereits geladenen Einträge (bei aktiver Filterung wird weiter nachgeladen); Klick auf die Zeile öffnet Details; hinterlegte Fotos erscheinen als Thumbnail, sonst das Default-Bild des Typs; im Modal blätterbar, Klick vergrößert; aktive Ausleihe: Chip „verliehen“ plus Person und Datum; Eigentümer-/Ausleihe-Chips öffnen die Person; „Übersicht für Versicherung“ öffnet eine druck-/PDF-fähige Tabelle (Spalten per Checkbox wählen, dann kopieren oder als PDF speichern)</li>
' : '').'
'.(requirePermission('perm_editInventories') ? '
<li><b>Inventar anlegen</b> – neue Stücke über die eigene Seite (Plus in der Inventarliste oder Admin → Inventar anlegen)</li>
<li><b>Inventar-Typen</b> – Prefix bestimmt den Nummernkreis (z.&nbsp;B. <code>MARSCH-001</code>, <code>INSTR-42</code>); die Beschriftung erscheint in Listen und Formularen; optionale <b>Vorschau</b> (JPEG/PNG/GIF/WebP) gilt als Default-Thumbnail, solange das Stück kein eigenes Foto hat</li>
<li>Bearbeiten, Löschen und Ausleihen nur mit Schreibrechten; im Inventar-Modal Fotos hinzufügen oder löschen, <b>Vorschau</b> setzt das angezeigte Foto als Listen-Thumbnail; unter <b>Dokumente</b> Dateien (PDF oder Bild) mit Notiz ablegen, im neuen Tab öffnen oder löschen; unter <b>Leihen</b> Person per Chip-Suche wählen und <b>Leihvertrag</b> (übrige Angaben optional bzw. im Formular) — der Vertrag öffnet sich; offene Leihen mit Datum und <b>Rückgabe</b> beenden (öffnet das Rückgabeprotokoll); beendete Leihen sind zugeklappt; Scans einzeln löschen oder den ganzen Leiheintrag entfernen</li>
<li><b>Leihvertrag / Rückgabe</b> – aus der Leihhistorie druckbare Formulare (alle Inventartypen); Klauseltexte unter Konfiguration (<code>loanText</code>, <code>loanTextExtern</code>, <code>loanReturnText</code>); Leihbeginn, Leihende, Kaution und Leihgebühr im Formular änderbar und speicherbar; Kaution und Leihgebühr im Druck nur wenn &gt; 0 €; Entleiher immer so bezeichnet; Adresse und zusätzliche Vereinbarungen/Bemerkungen optional und jederzeit änderbar (bei angeschlossener Mitgliederverwaltung wird die MIT-Anschrift vorausgefüllt, sofern noch leer); Rückgabe-Checkliste online abhakbar und speicherbar. <b>Digitale Unterschrift:</b> zwei Felder (Verleiher und Entleiher) im Popup; Verleiher mit <b>vertreten durch Name</b> des unterzeichnenden Admins nur unter der Unterschrift; Ort, Datum und Uhrzeit automatisch; Vereinsanschrift beim Verleiher (Konfiguration). Vor Ort beide nacheinander auf dem Gerät, oder nur Vereinsunterschrift und daneben <b>Zur Unterschrift senden</b> (Nachricht und E-Mail mit Login-Link zum Formular). Beim Entleiher erscheint das Formular unter Mein Inventar zur Unterschrift (ohne Inventar-Recht). Nicht-Mitglieder erhalten denselben Titel <b>Leihvertrag</b> mit Zusatzklauseln (Ausgabe im Anhang und unverzügliche Herausgabe). Vor Abschluss einzelne Unterschriften löschbar (eigenes Feld bzw. als Admin beide). Fertig erst mit beiden Unterschriften: fertiges Formular im Drucklayout wird wie ein Scan an der Leihe gespeichert (Text eingefroren) und geht zusätzlich per Mail an den Entleiher (keine qualifizierte Signatur). Angaben nach Abschluss sind gesperrt; vorheriges Speichern verwirft vorhandene Unterschriften und das gespeicherte Formular. Daneben Scan (PDF, JPEG oder PNG) ablegen, im neuen Tab öffnen oder löschen. Der Verein kann die Leihsache insbesondere bei Ende der Mitgliedschaft zurückfordern</li>
' : '').'
</ul>
'
);

$sections[] = array(
    'id' => 'admin-register',
    'title' => 'Admin: Register',
    'visible' => isAdmin() && requirePermission('perm_editRegisters'),
    'body' => '
<ul class="help-list">
<li><b>Register</b> – anlegen, sortieren und einfärben (Sitzplan und Gruppenbildung). <b>Reihe</b> = Abstand vom Dirigenten (0 = Dirigent); <b>ArcMin/ArcMax</b> = Winkelbereich (0° links, 90° vorne, 180° rechts). Nach dem Speichern aktualisiert sich die Vorschau</li>
<li><b>Instrument-Typen</b> – Instrumente (z.&nbsp;B. Flöte, Trompete) den Registern zuordnen, sortieren und Spielbarkeit setzen; Farbe in der Typen-Übersicht, Register-Farben steuern die Orchesterdarstellung</li>
<li>Beide Seiten brauchen das Recht <b>Register bearbeiten</b></li>
</ul>
'
);

$sections[] = array(
    'id' => 'admin-system',
    'title' => 'Admin: System',
    'visible' => isAdmin() && (requirePermission('perm_editConfig') || requirePermission('perm_showLog') || requirePermission('perm_editPermissions')),
    'body' => '
<ul class="help-list">
'.(requirePermission('perm_editPermissions') ? '
<li><b>Berechtigungen</b> – Matrix aller User (Autosave); persönliche Rechte sind editierbar; Haken mit gestricheltem Rahmen kommen nur über eine Gruppe und lassen sich hier nicht entfernen; Klick auf den Namen öffnet das User-Modal; Rechte auch beim Anlegen/Bearbeiten unter Musiker</li>
' : '').'
'.(requirePermission('perm_editConfig') ? '
<li><b>Konfiguration</b> – Farben, Texte, Feature-Schalter, Webhooks, Default-Sichtbarkeit neuer Termine (<code>defaultTerminVisibility</code>), Leih- und Rückgabetexte (<code>loanText</code>, <code>loanTextExtern</code>, <code>loanReturnText</code>; Leerzeile = Absatz; Platzhalter <code>{org}</code> <code>{start}</code> <code>{duration}</code> <code>{fee}</code> <code>{kaution}</code>), …; Änderungen erscheinen im Log</li>
<li><b>Plattform / SSO</b> – <code>urlNotenarchiv</code> und <code>urlMitgliederverwaltung</code> setzen die Modul-Ziele (Hosts daraus sind für SSO automatisch erlaubt); <code>ssoRedirectAllowlist</code> nur für Extra-Hosts. Nav-Links erscheinen bei gesetzter URL und dem Recht <b>Notenarchiv</b> bzw. <b>Mitglieder</b></li>
' : '').'
'.(requirePermission('perm_showLog') ? '
<li><b>Statistik</b> – Auswertungen; auf breiten Bildschirmen Diagramme und Listen zweispaltig. Zeitraum in Tagen frei wählen, Teilnahme-/Log-Charts, Ranking und Inaktive (ohne Login/Teilnahme im Schwellwert <code>inactiveUsersDays</code>). Ranking und Inaktive teilen sich denselben Chip-Filter wie die Personenliste (Aktive/Gäste/Mitglieder, Register, Gruppen) und nutzen denselben Zeilen-Stil inkl. Sortier-Chips</li>
<li><b>Log</b> – Anwendungsprotokoll (Suche serverseitig; mehrere Wörter = UND, z. B. <code>ERROR Meier</code>; Live-Aktualisierung); Akteure und referenzierte User/Termine/Inventar/Emails/Schichten in der Nachricht als Chip öffnen das jeweilige Modal; Chunk-Größe für Scroll und Live-Nachladen über <code>logListChunkSize</code></li>
' : '').'
'.(requirePermission('perm_editConfig') ? '
<li><b>Backup</b> – Datenbank-ZIP herunterladen (inkl. Versionsinfo) oder wieder einspielen; im Browser über <code>Backup</code>, per CLI mit <code>php cron.php CRONID backup</code>; automatisiert remote nur mit eigenem <code>$backupToken</code> in <code>config.php</code> (mind. 32 Zeichen) über <code>cron.php?id=…&amp;cmd=backup</code> — nicht mit dem allgemeinen Cron-ID. Erfolgreiche Downloads erscheinen im <b>Log</b> als Info, fehlgeschlagene als Fehler</li>
<li><b>Updater</b> – Software-Update und Datenbank-Prüfung/Reparatur; der Bericht listet nur Änderungen und Probleme (keine „ok“-Zeilen)</li>
' : '').'
</ul>
'
);

$sections[] = array(
    'id' => 'kontakt',
    'title' => 'Kontakt',
    'body' => '
<p><a href="mailto:'.htmlspecialchars($optionsDB['AdminEmail'], ENT_QUOTES, 'UTF-8').'">Nachricht an Admin</a></p>
<p>Die installierte Version ist im Changelog markiert (rechts bzw. darunter).</p>
'
);

$visible = array();
foreach($sections as $section) {
    if(isset($section['visible']) && !$section['visible']) {
        continue;
    }
    $visible[] = $section;
}
?>
<nav class="help-toc w3-card w3-padding w3-margin-bottom" aria-label="Inhalt">
  <h3 class="w3-margin-top">Inhalt</h3>
  <ol class="help-toc-list">
<?php foreach($visible as $section) { ?>
    <li><a href="#help-<?php echo htmlspecialchars($section['id'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo $section['title']; ?></a></li>
<?php } ?>
    <li class="w3-hide-large"><a href="#help-changelog">Changelog</a></li>
  </ol>
</nav>

<?php foreach($visible as $section) { ?>
<section class="help-section w3-margin-bottom" id="help-<?php echo htmlspecialchars($section['id'], ENT_QUOTES, 'UTF-8'); ?>">
  <h3><?php echo $section['title']; ?></h3>
  <div class="help-section-body">
    <?php echo $section['body']; ?>
  </div>
</section>
<?php } ?>
