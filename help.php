<?php
session_start();
$_SESSION['page']='help';
$_SESSION['adminpage']=false;
include "common/header.php";
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar'] ;?>">
<h2>Hilfe</h2>
</div>
<div class="w3-container w3-margin-top">
Release: <?php echo "<b>".$GLOBALS['version']['String']."</b> (".$GLOBALS['version']['Date'].")"; ?>
</div>
<div class="w3-container w3-margin-top">
<a href="mailto:<?php echo $GLOBALS['optionsDB']['AdminEmail']; ?>">Nachricht an Admin</a>
</div>

<div class="w3-container w3-margin-top"><p><b>Kurzanleitung:</b></p>

<ul>
	<li>
		<p><i class="fas fa-home"></i> - Home</p>
		<p>Startseite der Meldeliste - hier werden die n&auml;chsten Termine angezeigt.<br>
		Mit folgenden Buttons können die Meldungen gesetzt werden:</p>
		<table>
		<tr><td class="s3 m3 l3 w3-margin-left w3-border w3-border-black w3-center w3-green" style="width:100px"><b>&#10004;</b></td><td>Komme</td></tr>
		<tr><td class="s3 m3 l3 w3-margin-left w3-border w3-border-black w3-center w3-red" style="width:100px"><b>&#10008;</b></td><td>Komme nicht</td></tr>
		<tr><td class="s3 m3 l3 w3-margin-left w3-border w3-border-black w3-center w3-blue" style="width:100px"><b>?&nbsp;</b></td><td>Bin noch unsicher</td></tr>
		</table>
	</li>
	<p>Eine große Planungshilfe wäre es, wenn Zu- und Absagen wie auch "unsicher"-Meldungen möglichst <b>vollständig</b> eingetragen werden.</p>
<?php if($GLOBALS['optionsDB']['showAppmntPage']) {
    ?>
	<li>
		<p><i class="far fa-calendar-alt"></i> - Termine</p>
		<p>Hier werde alle zuk&uuml;nftigen Termine angezeigt.<br />(Auch hier können die Meldungen gesetzt werden.)</p>
		<ul>
			<li>
				<p><i class="fa fa-info-circle"></i></p></li>
				<p>Details des Termins anzeigen</p>
			<li>
				<p><i class="fa fa-calendar-plus"></i></p>
				<p>Den einzelnen Termin dem persönlichen Kalender hinzufügen (z.B. Google Calendar oder Outlook).
				<br />Auf das Kalendersymbol mit dem Plus-Zeichen neben der Terminbezeichnung klicken, die Datei öffnen und importieren</p>
			</li>
		</ul>
	</li>
    <?php
}
?>
	<li>
		<p><i class="fas fa-users"></i> - Mein Register</p>
		<p>Anzeige der R&uuml;ckmeldungen des eigenen Registers:</p>
		<table>
		<tr><td class="s3 m3 l3 w3-margin-left w3-border w3-border-black <?php echo $GLOBALS['optionsDB']['colorAppmntYes']; ?>" style="width:200px">Komme</td></tr>
		<tr><td class="s3 m3 l3 w3-margin-left w3-border w3-border-black <?php echo $GLOBALS['optionsDB']['colorAppmntNo']; ?>" style="width:200px">Komme nicht</td></tr>
		<tr><td class="s3 m3 l3 w3-margin-left w3-border w3-border-black <?php echo $GLOBALS['optionsDB']['colorAppmntMaybe']; ?>" style="width:200px">Bin noch unsicher</td></tr>
		</table>
	</li>
	<li>
		<p><i class="fas fa-user"></i> - Mein Profil</p>
		<p>Anzeige der eigenen Profildaten</p>
	</li>
	<li>
		<p>Vereinshomepage</p>
		<p>&Ouml;ffnet die Vereinshomepage in einem eigenen Tab</p>
	</li>
	<li>
		<p><i class="fas fa-info"></i> - Info/Hilfe</p>
		<p>Diese Seite</p>
	</li>
	<li>
		<p><i class="fas fa-sign-out-alt"></i> - Ausloggen</p>
		<p>Beendet die aktuelle Sitzung</p>
	</li>
</ul>

</div>

<?php
include "common/footer.php";
?>
