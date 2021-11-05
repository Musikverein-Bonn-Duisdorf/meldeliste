<?php
session_start();
$_SESSION['page']='update';
$_SESSION['adminpage']=true;
include "common/header.php";
requireAdmin();
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar'] ;?>">
    <h2>Updater</h2>
</div>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar'] ;?>"></div>
<?php
$para=array(
    "styleAppmntUnpublished",
    'colorLogDefault', 
    'colorLogFatal', 
    'colorLogError', 
    'colorLogWarning', 
    'colorLogDBDelete', 
    'colorLogDBInsert', 
    'colorLogDBUpdate', 
    'colorLogEmail', 
    'colorLogInfo', 
    'colorUserMember', 
    'colorUserNoMember',
    'colorAppmntConcert',
    'colorAppmntNoConcert',
    'colorAppmntYes',
    'colorAppmntNo',
    'colorAppmntMaybe',
    'colorBtnYes',
    'colorBtnNo',
    'colorBtnMaybe',
    'colorDisabled',
    'colorBtnEdit',
    'HoverEffect',
    'colorSuccess',
    'colorNav',
    'colorNavAdmin',
    'SubjectReminder',
    'SubjectPW',
    'MailGreetings',
    'cronSendnewAppmnts',
    'cronSendnewAppmntsDays',
    'cronSendnewAppmntsTime',
    'cronSendnewAppmntsInMail',
    'cronSendnewAppmntsText',
    'cronSendTomorrow',
    'cronSendTomorrowTime',
    'AppmntAlwaysDecline',
    'showOrchestraView',
    'MessageOfTheDay',
    'MessageOfTheDayShort',
    'showAddToCalendarButton',
    'alwaysYesNewAppmnts',
);
$desc=array(
    "Stil f&uuml;r nicht ver&ouml;ffentlichte Termine",
    "Farbe von DEFAULT Logeintr&auml;gen",
    "Farbe von FATAL Logeintr&auml;gen",
    "Farbe von ERROR Logeintr&auml;gen",
    "Farbe von WARNING Logeintr&auml;gen",
    "Farbe von DB DELETE Logeintr&auml;gen",
    "Farbe von DB INSERT Logeintr&auml;gen",
    "Farbe von DB UPDATE Logeintr&auml;gen",
    "Farbe von EMAIL Logeintr&auml;gen",
    "Farbe von INFO Logeintr&auml;gen",
    "Farbe von Vereinsmitgliedern",
    "Farbe von Nicht-Vereinsmigliedern",
    "Farbe von Konzertauftritten",
    "Farbe von sonstigen Auftritten",
    "Farbe von zugesagten Terminen",
    "Farbe von abgesagten Terminen",
    "Farbe von unsicheren Terminen",
    "Farbe von Ja-Buttons",
    "Farbe von Nein-Buttons",
    "Farbe von Vielleicht-Buttons",
    "Farbe von deaktivierten Elementen",
    "Farbe von Edit-Buttons",
    "Stil des Hover-Effekts",
    "Farbe von Erfolgsmeldungen",
    "Farbe der Navigationsleiste",
    "Farbe der Admin-Navigationsleiste",
    "Betreff der Erinnerungsmail",
    "Betreff der Email mit neuem Passwort",
    "Gru&szlig;formel unter System-Emails",
    "Sende Nachricht, wenn neue Termine erstellt wurden.",
    "Sende Nachricht, wenn neue Termine erstellt wurden an folgenden Tagen.",
    "Sende Nachricht, wenn neue Termine erstellt zu folgender Uhrzeit.",
    "Zeige neue Termine in der Email.",
    "Text der Email mit neuen Terminen.",
    "Sende Nachricht, wenn Termin am folgenden Tag ansteht.",
    "Sende Nachricht, wenn Termin am folgenden Tag ansteht zu folgender Uhrzeit.",
    "Man kann sich auch von bereits geschlossenen Anmeldungen abmelden.",
    "Zeige einen Sitzplan des Orchesters in allen Meldungen",
    "Nachricht, die jedem Nutzen ein Einloggen angezeigt wird.",
    "Zusammenfassung der Nachricht, die jedem Nutzen ein Einloggen angezeigt wird.",
    "Zeige Button zum Erstellen von Kalendereinträgen",
    "Nutzer, die automatisch in neue Termine mit Ja eingetragen werden (userID durch Komma getrennt)",
);
$value=array(
    "w3-opacity",
    "w3-light-gray",
    "w3-red",
    "w3-deep-orange",
    "w3-blue",
    "w3-light-blue",
    "w3-lime",
    "w3-khaki",
    "w3-yellow",
    "w3-light-green",
    "w3-light-blue",
    "",
    "w3-light-blue",
    "",
    "w3-highway-green",
    "w3-highway-red",
    "w3-highway-blue",
    "w3-green",
    "w3-red",
    "w3-blue",
    "w3-gray",
    "w3-teal",
    "w3-hover-gray",
    "w3-green",
    "w3-teal",
    "w3-blue-gray",
    "Erinnerung",
    "neues Passwort",
    "Dein Musikverein Bonn-Duisdorf",
    0,
    "64",
    "14:00",
    1,
    "bitte folgende neue Termine vormerken",
    0,
    "14:00",
    1,
    1,
    "",
    "",
    0,
    "",
);
$type=array(
    "string",
    "color",
    "color",
    "color",
    "color",
    "color",
    "color",
    "color",
    "color",
    "color",
    "color",
    "color",
    "color",
    "color",
    "color",
    "color",
    "color",
    "color",
    "color",
    "color",
    "color",
    "color",
    "string",
    "color",
    "color",
    "color",
    "string",
    "string",
    "string",
    "bool",
    "days",
    "time",
    "bool",
    "text",
    "bool",
    "time",
    "bool",
    "bool",
    "text",
    "text",
    "bool",
    "string",
);

$N = count($para);
for($i=0; $i<$N; $i++) {
    $sql = sprintf("SELECT * FROM `%sconfig` WHERE `Parameter` = '%s';",
		   $GLOBALS['dbprefix'],
		   $para[$i]
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    $row = mysqli_fetch_array($dbr);
    if($row['Parameter'] != $para[$i]) {
        $sql = sprintf("INSERT INTO `%sconfig` (`Parameter`, `Value`, `Type`, `Description`) VALUES ('%s', '%s', '%s', '%s');",
                       $GLOBALS['dbprefix'],
                       $para[$i],
                       $value[$i],
                       $type[$i],
                       $desc[$i]
        );
        echo "<div class=\"w3-row w3-container w3-border w3-border-black w3-padding ".$GLOBALS['optionsDB']['colorLogDBInsert']."\"><div class=\"w3-col l2 m2 s2\"><b>INSERT</b></div><div class=\"w3-col l10 m10 s10\">".htmlspecialchars($sql)."</div></div>";
        mysqli_query($GLOBALS['conn'], $sql);
    }
    else {
        echo "<div class=\"w3-row w3-container w3-border w3-border-black w3-padding ".$GLOBALS['optionsDB']['colorLogInfo']."\"><div class=\"w3-col l2 m2 s2\">Skip</div><div class=\"w3-col l10 m10 s10\">".$para[$i]."</div></div>";
    }
}

$sql = sprintf("ALTER TABLE `%sUser` ADD `Email2` TEXT NOT NULL AFTER `Email`;",
$GLOBALS['dbprefix']
);
echo "<div class=\"w3-row w3-container w3-border w3-border-black w3-padding ".$GLOBALS['optionsDB']['colorLogDBInsert']."\"><div class=\"w3-col l2 m2 s2\"><b>INSERT</b></div><div class=\"w3-col l10 m10 s10\">".htmlspecialchars($sql)."</div></div>";
mysqli_query($GLOBALS['conn'], $sql);

?>

<?php
include "common/footer.php";
?>
