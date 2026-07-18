<header class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
<span onclick="closeModal()" class="w3-button w3-display-topright">&times;</span>
<h2><?php echo $terminName; ?></h2>
</header>
<div>
<?php if($showOrchestra) { ?>
<div class="orchestra-panel w3-container w3-margin-top">
  <div class="orchestra-panel-header w3-row">
    <div class="w3-col s12 m6 l6"><b>Besetzung</b></div>
    <div class="w3-col s12 m6 l6 orchestra-panel-toggle">
      <label class="w3-small">
        <input type="checkbox" class="w3-check" onchange="toggleActiveOrchestra(this)">
        Nur aktive Besetzung
      </label>
    </div>
  </div>
  <div class="orchestra-layout orchestra-layout--full">
    <div class="orchestra-svg-wrap">
<?php echo $orchestraFull; ?>
    </div>
  </div>
  <div class="orchestra-layout orchestra-layout--active" hidden>
    <div class="orchestra-svg-wrap">
<?php echo $orchestraActive; ?>
    </div>
  </div>
</div>
<?php } ?>
</div>
<div class="w3-container w3-margin-top"><div class="w3-row"><div class="w3-col l<?php echo $colsize[0]; ?> m<?php echo $colsize[0]; ?> s<?php echo $colsize[0]; ?>"><b>Zusagen</b></div>
<div class="w3-col l<?php echo $colsize[1]; ?> m<?php echo $colsize[1]; ?> s<?php echo $colsize[1]; ?>">&nbsp;</div><?php
$actcol = 2;
if($showChildrenHeader) {
    echo '<div class="w3-col l'.$colsize[$actcol].' m'.$colsize[$actcol].' s'.$colsize[$actcol].'">Kinder</div>';
    $actcol++;
}
if($showGuestsHeader) {
    echo '<div class="w3-col l'.$colsize[$actcol].' m'.$colsize[$actcol].' s'.$colsize[$actcol].'">G&auml;ste</div>';
}
?>
</div>
</div>
<div class="w3-container">
<?php echo $whoYesHtml; ?>
</div>
<div class="w3-container w3-margin-top"><b>unsicher</b></div>
<div class="w3-container">
<?php echo $whoMaybeHtml; ?>
</div>
<div class="w3-container w3-margin-top"><b>Absagen</b></div>
<div class="w3-container">
<?php echo $whoNoHtml; ?>
</div>
<?php if($canEditResponse) { ?>
<div class="w3-container w3-margin-top"><b>noch nicht gemeldet</b></div>
<form class="w3-container w3-row" action="index.php" method="POST">
<?php foreach($missingUsers as $missing) { ?>
<button class="w3-btn w3-border w3-margin-top w3-border-black w3-col s12 l4 m6 <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?>" type="submit" name="proxy" value="<?php echo $missing['id']; ?>"><?php echo $missing['name']; ?></button>
<?php } ?>
</form>
<?php } ?>
<div class="w3-container w3-margin-bottom"><br />
</div>
