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
<p>Oben in der Navigation erreichst du die Bereiche, die für dich freigeschaltet sind. Diese Hilfe zeigt nur Abschnitte, die zu deinen aktuellen Rechten passen.</p>
<p>Bitte melde dich möglichst vollständig zu Terminen an (ja / nein / vielleicht) – das erleichtert die Planung enorm.</p>
'
);

$sections[] = array(
    'id' => 'navigation',
    'title' => 'Navigation',
    'body' => '
<ul class="help-list">
<li><i class="far fa-calendar-alt"></i> <b>Termine</b> – bevorstehende Termine und schnelles Melden</li>
<li><i class="fas fa-envelope"></i> <b>Meine Nachrichten</b> – empfangene Mails aus der Meldeliste (Badge bei ungelesenen)</li>
<li><i class="fas fa-users"></i> <b>Mein Register</b> – Rückmeldungen deines Registers</li>
'.($helpUser->hasInventories() ? '<li><i class="fas fa-shirt"></i> <b>Mein Inventar</b> – dir zugeordnetes bzw. ausgeliehenes Inventar</li>' : '').'
<li><i class="fas fa-user"></i> <b>Mein Profil</b> – eigene Stammdaten und Einstellungen</li>
<li><i class="fas fa-photo-film"></i> <b>Medien</b> – Links zu Aufnahmen und Social Media (konfigurierbar)</li>
<li>Logo oben rechts – öffnet die <b>Vereinshomepage</b> in einem neuen Tab</li>
<li><i class="fas fa-circle-question"></i> <b>Hilfe</b> – diese Seite inkl. Changelog</li>
'.(isAdmin() ? '<li><i class="fas fa-wrench"></i> <b>Admin</b> – Verwaltungsmenü (nur mit Admin-Zugang und entsprechenden Rechten)</li>' : '').'
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
</ul>
'.$meldeButtons.'
<p>Tippe auf den gewünschten Status. Die Farbe am Termin zeigt deinen aktuellen Stand. Eine erneute Auswahl ändert die Meldung.</p>
<p><b>Tipp:</b> Auch „vielleicht“ oder „nein“ sind wertvoll – offene Einträge erschweren die Planung.</p>
<p>Bei Terminen mit <b>Besetzung</b> kannst du im Termin-Detail ggf. das <b>Instrument für diesen Termin</b> anpassen (z.&nbsp;B. Dirigat übernehmen). Speichern mit dem Speicher-Button neben der Auswahl.</p>
<p>Über das Info-Symbol <i class="fa fa-info-circle"></i> öffnest du die Termin-Details (Ort, Uhrzeit, Orchesterübersicht, …).</p>
<p>Über <i class="fa fa-calendar-plus"></i> kannst du einen Termin als ICS-Datei in deinen Kalender (Google, Outlook, …) importieren.</p>
'
);

$sections[] = array(
    'id' => 'mein-register',
    'title' => 'Mein Register',
    'body' => '
<p>Unter <b>Mein Register</b> siehst du, wie sich die Musikerinnen und Musiker deines Registers zu Terminen gemeldet haben:</p>
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
<p>Ungelesene Nachrichten werden in der Navigation als Badge angezeigt. Öffne eine Nachricht, um sie zu lesen; der Status aktualisiert sich entsprechend.</p>
'
);

if($helpUser->hasInventories()) {
    $sections[] = array(
        'id' => 'mein-inventar',
        'title' => 'Mein Inventar',
        'body' => '
<p>Wenn dir Inventar gehört oder an dich ausgeliehen ist, erscheint <b>Mein Inventar</b> in der Navigation.</p>
<p>Dort siehst du deine Stücke (Instrumente, Kleidung, …) und kannst Details im Modal öffnen. Bearbeiten ist nur möglich, wenn du die entsprechenden Rechte hast bzw. für dein eigenes Inventar freigegeben bist.</p>
'
    );
}

$sections[] = array(
    'id' => 'profil',
    'title' => 'Mein Profil',
    'body' => '
<p>Unter <b>Mein Profil</b> pflegst du deine Kontaktdaten, E-Mail-Adressen und weitere Angaben.</p>
<p>Halte insbesondere E-Mail und Instrument aktuell – davon hängen Benachrichtigungen und die Orchesterdarstellung ab.</p>
<p>Unter <b>Gruppenzugehörigkeit</b> siehst du, welchen Rollen (z.&nbsp;B. Alle Musiker), welchem Register und welchen benannten Gruppen du zugeordnet bist – relevant für Mail und Termin-Sichtbarkeit.</p>
<p>Falls du ein Einmal-Passwort erhalten hast, wirst du nach dem Login zum Ändern des Passworts aufgefordert.</p>
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
<p>Solange du im Auftrag arbeitest, beziehen sich Meldungen und Instrument-Änderungen auf die ausgewählte Person, nicht auf dich.</p>
'
);

$sections[] = array(
    'id' => 'admin-meldungen',
    'title' => 'Admin: Meldungen',
    'visible' => isAdmin() && requirePermission('perm_showResponse'),
    'body' => '
<p>Unter Admin → <b>Meldungen</b> siehst du Rückmeldungen übergreifend; im <b>Archiv</b> vergangene Termine. Beide Listen haben eine Suchzeile (Titel, Ort, Datum, Beschreibung).</p>
<p>In Termin- und Register-Ansichten kannst du Rückmeldungs-Modals öffnen. Die Orchesterübersicht skaliert auf die Fensterbreite und zeigt die Besetzung farbig nach Meldestatus (Hover zeigt Name und Status). Mit <b>Nur aktive Besetzung</b> siehst du einen Sitzplan nur mit Zusagen und Unsicheren – ohne Lücken durch Absagen oder fehlende Meldungen.</p>
'.(requirePermission('perm_editResponse') ? '<p>Mit Recht <b>Rückmeldungen bearbeiten</b> kannst du im Orchesterplan per Klick auf einen Kreis den Status durchschalten: (keine Meldung →) Zusage → Absage → unsicher → Zusage …</p>' : '').'
'
);

$sections[] = array(
    'id' => 'admin-personen',
    'title' => 'Admin: Personen',
    'visible' => isAdmin() && (requirePermission('perm_showUsers') || requirePermission('perm_editPermissions') || requirePermission('perm_editInstruments')),
    'body' => '
<ul class="help-list">
'.(requirePermission('perm_showUsers') ? '
<li><b>Registerübersicht / Musikerliste / Userliste</b> – Personen suchen, filtern und öffnen (Register-Überschrift in Registerfarbe); Spaltenköpfe sortieren die Liste</li>
'.(!empty($optionsDB['showMembers']) ? '<li><b>Mitgliederliste</b> – nur Vereinsmitglieder</li>' : '').'
'.(!empty($optionsDB['showNonMembers']) ? '<li><b>Nicht-Mitgliederliste</b></li>' : '').'
' : '').'
'.(requirePermission('perm_editUsers') ? '<li><b>Musiker anlegen</b> – neue Person anlegen und Rechte/Instrument setzen</li>' : '').'
'.(requirePermission('perm_editInstruments') ? '<li><b>Instrument-Typen / Register</b> – Typen und Register anlegen, sortieren und einfärben</li>' : '').'
'.(requirePermission('perm_editPermissions') ? '<li><b>Berechtigungen</b> – Matrix der Rechte pro User (Autosave)</li>' : '').'
</ul>
'
);

$sections[] = array(
    'id' => 'admin-termine',
    'title' => 'Admin: Termine',
    'visible' => isAdmin() && requirePermission('perm_editAppmnts'),
    'body' => '
<p>Unter Admin → <b>Termin erstellen</b> legst du neue Termine an. Das Formular ist in Abschnitte gegliedert (Was, Wann, Wo, Optionen): auf dem Smartphone untereinander, auf dem Tablet zweispaltig, am PC als vier Spalten nebeneinander.</p>
<p>Das Flag <b>Besetzung</b> steuert, ob Registeraufschlüsselung und Orchesterdarstellung greifen – für Proben und Auftritte. Veranstaltungen ohne Besetzung (z.&nbsp;B. Grillfest, Radtour) brauchen das nicht (nur Manpower).</p>
<p>Mit dem Chip-Feld <b>sichtbar für</b> steuerst du den Kreis (Standard: <b>Alle User</b>). Ohne Chips = versteckt – nur User mit Recht <b>Versteckte Termine anzeigen</b>. Mit Chips nur der gewählte Kreis (Rollen, Gruppen, Register, Personen); Admins mit dem genannten Recht sehen weiterhin alles.</p>
<p>Discord-Posts (bei konfiguriertem Webhook) erfolgen bei Sichtbarkeit <b>Alle User</b> automatisch, sonst nur mit der Checkbox <b>Auch auf Discord posten</b>.</p>
<p>Im <b>Archiv: Termine</b> findest du vergangene Termine (ebenfalls durchsuchbar). Aushilfen können am Termin ergänzt bzw. gelöscht werden, sofern freigegeben.</p>
'
);

$sections[] = array(
    'id' => 'admin-mail',
    'title' => 'Admin: E-Mails',
    'visible' => isAdmin() && requirePermission('perm_sendEmail'),
    'body' => '
<p>Unter Admin → <b>Email versenden</b> erstellst du Nachrichten an Verteiler oder einzelne Empfänger.</p>
<p>Unter Admin → <b>Gruppen</b> legst du wiederverwendbare Gruppen an (Rollen, Register, Personen). Diese Gruppen kannst du beim Mailversand und bei der Termin-Sichtbarkeit als Chip auswählen.</p>
<p>Mails werden in einer Warteschlange verarbeitet; den Versandstatus siehst du in der Admin-Ansicht. Bei versendeten Mails siehst du den gewählten <b>Verteiler</b> (Rollen, Gruppen, Register bzw. Termin-Teilnehmer) sowie die Liste der einzelnen Empfänger. Empfänger finden die Nachricht unter <b>Meine Nachrichten</b>.</p>
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
<li><b>Inventar</b> – Bestände anzeigen, Details und Ausleihen; Spaltenköpfe sortieren die Liste</li>
<li><b>Versicherung</b> – versicherte Stücke; Klick öffnet das Inventar-Modal; Spalten sortierbar; „Übersicht für Versicherung“ öffnet eine druck-/PDF-fähige Tabelle (Spalten per Checkbox wählen, dann kopieren oder als PDF speichern)</li>
' : '').'
'.(requirePermission('perm_editInventories') ? '
<li><b>Inventar-Typen</b> – Typen und Nummernkreise pflegen</li>
<li>Anlegen, Bearbeiten, Löschen und Ausleihen nur mit Schreibrechten</li>
' : '').'
</ul>
'
);

$sections[] = array(
    'id' => 'admin-system',
    'title' => 'Admin: System',
    'visible' => isAdmin() && (requirePermission('perm_editConfig') || requirePermission('perm_showLog')),
    'body' => '
<ul class="help-list">
'.(requirePermission('perm_editConfig') ? '
<li><b>Konfiguration</b> – Farben, Texte, Feature-Schalter, Webhooks, …</li>
<li><b>Plattform / SSO</b> – <code>ssoRedirectAllowlist</code>, <code>urlNotenarchiv</code> und <code>urlMitgliederverwaltung</code> für einmalige SSO-Tickets zu Schwester-Modulen (Nav-Links erscheinen bei gesetzter URL)</li>
<li><b>Updater</b> – Software-Update und Datenbank-Reparatur / Schema-Stand</li>
<li><b>Backup</b> – Datenbank-ZIP herunterladen (inkl. Versionsinfo) oder wieder einspielen; Remote-Abruf auch per <code>cron.php?cmd=backup</code></li>
' : '').'
'.(requirePermission('perm_showLog') ? '
<li><b>Log</b> – Anwendungsprotokoll (Filter, Live-Aktualisierung)</li>
<li><b>Statistik</b> – Auswertungen mit Abschnittsnavigation; auf breiten Bildschirmen Diagramme und Tabellen zweispaltig. Zeitraum in Tagen frei wählen, Teilnahme-/Log-Charts, Ranking und Inaktive (sortierbar)</li>
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
