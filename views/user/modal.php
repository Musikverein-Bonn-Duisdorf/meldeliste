<header class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <span onclick="closeModal()" class="w3-button w3-display-topright">&times;</span>
  <h2><?php echo $user->Vorname." ".$user->Nachname; ?></h2>
</header>
<div class="w3-container w3-row w3-margin">
  <div class="w3-col l6">userID:</div><div class="w3-col l6"><b><?php echo $user->Index; ?></b></div>
</div>
<div class="w3-container w3-row w3-margin">
  <div class="w3-col l6">Instrument:</div><div class="w3-col l6"><b><?php echo $user->iName; ?></b></div>
</div>
<div class="w3-container w3-row w3-margin">
  <div class="w3-col l6">Vereinsmitglied:</div><div class="w3-col l6"><b><?php echo bool2string($user->Mitglied); ?></b></div>
</div>
<div class="w3-container w3-row w3-margin">
  <div class="w3-col l6">E-Mail:</div><div class="w3-col l6"><b><?php echo bool2string($user->getMail); ?></b></div>
  <div class="w3-col l6">Nachrichten:</div><div class="w3-col l6"><b><?php echo bool2string($user->notifyInbox); ?></b></div>
</div>
<?php if($showUserDetails) { ?>
<div class="w3-container w3-row w3-margin">
  <div class="w3-col l6">Account erstellt:</div><div class="w3-col l6"><b><?php echo germanDate($user->Joined, 1); ?></b></div>
</div>
<div class="w3-container w3-row w3-margin">
  <div class="w3-col l6">Mitglieds-Nr.:</div><div class="w3-col l6"><b><?php echo $user->RefID; ?></b></div>
</div>
<div class="w3-container w3-margin">
  <div class="w3-col l6">Berechtigungen:</div>
  <?php echo $permissionsHtml; ?>
</div>
<?php } ?>
<div class="w3-container w3-row w3-margin">
  <div class="w3-col l6">Emailadresse:</div><div class="w3-col l6"><b><a href="mailto:<?php echo $user->Email; ?>"><?php echo $user->Email; ?></a></b></div>
</div>
<div class="w3-container w3-row w3-margin">
  <div class="w3-col l6">zweite Emailadresse:</div><div class="w3-col l6"><b><a href="mailto:<?php echo $user->Email2; ?>"><?php echo $user->Email2; ?></a></b></div>
</div>
<div class="w3-container w3-row w3-margin">
  <div class="w3-col l6">Loginname:</div><div class="w3-col l6"><b><?php echo $user->login; ?></b></div>
</div>
<div class="w3-container w3-row w3-margin">
  <div class="w3-col l6">Letzter Login:</div><div class="w3-col l6"><b><?php echo germanDate($user->LastLogin, 1); ?></b></div>
</div>
<div class="w3-container w3-row w3-margin">
  <div class="w3-col l6">Letzte Anwesenheit:</div><div class="w3-col l6"><b><?php echo germanDate($user->getLastVisit(), 1); ?></b></div>
</div>
<div class="w3-container w3-row w3-margin">
  <div class="w3-col l6">Meldequote:</div><div class="w3-col l6"><b><?php echo $user->getMeldeQuote()*100; ?> %</b></div>
</div>
<?php if($registerLeadName !== null) { ?>
<div class="w3-container w3-row w3-margin">
  <div class="w3-col l6">Registerführer:</div><div class="w3-col l6"><b><?php echo $registerLeadName; ?></b></div>
</div>
<?php } ?>
<?php if($showEditButton) { ?>
<form class="w3-center w3-bar w3-mobile" action="new-musiker.php" method="POST">
  <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8'); ?>">
  <input type="hidden" name="return_token" value="<?php echo htmlspecialchars(isset($returnToken) ? $returnToken : '', ENT_QUOTES, 'UTF-8'); ?>">
  <button class="w3-button w3-center w3-mobile w3-block <?php echo $GLOBALS['optionsDB']['colorBtnEdit']; ?>" type="submit" name="id" value="<?php echo $user->Index; ?>">bearbeiten</button>
</form>
<?php } ?>
