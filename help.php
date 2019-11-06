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
<table>
<tbody>
<row>
<td>
<ul>
	<li>
		<p><i class="fas fa-home"></i> - Home</p>
		<p>Startseite der Meldeliste - hier werden die n&auml;chsten Termine angezeigt.</p>
	</li>
<?php
if($GLOBALS['optionsDB']['showAppmntPage']) {
    ?>
	<li>
		<p><i class="far fa-calendar-alt"></i> - Termine</p>
		<p>Hier werde alle zuk&uuml;nftigen Termine angezeigt.</p>
	</li>
    <?php
}
?>
	<li>
		<p><i class="fas fa-users"></i> - Mein Register</p>
		<p>Anzeige der R&uuml;ckmeldungen des eigenen Registers:</p>
		<ul>
			<li><div class="w3-highway-green">Gr&uuml;n:&nbsp;Komme</div></li>
			<li><div class="w3-highway-red">Rot:&nbsp;&nbsp;Komme nicht</div></li>
			<li><div class="w3-highway-blue">Blau:&nbsp;Noch unsicher</div></li>
		</ul>
	</li>
	<li>
		<p><i class="fas fa-user"></i> - Mein Profil</p>
		<p>Anzeige der eigenen Profildaten</p>
	</li>
<?php if($_SESSION['admin']) {?>
	<li>
		<p><i class="fas fa-wrench"></i> - Adminpage</p>
		<p>Einstellungen &auml;ndern</p>
	</li>
<?php } ?>
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
</td><td></td>
</row>
</tbody>
</table>
</div>
<?php if($_SESSION['admin']) {?>
<?php } ?>
<?php
include "common/footer.php";
?>
