<div class="profile-shell modal-shell termin-response-modal"
     data-termin-id="<?php echo (int)$terminId; ?>"
     data-register="<?php echo (int)$filterRegister; ?>">
  <header class="profile-hero">
    <div class="profile-hero-text">
      <p class="profile-kicker">Meldungen</p>
      <h2 class="profile-title"><?php echo htmlspecialchars((string)$terminName, ENT_QUOTES, 'UTF-8'); ?></h2>
    </div>
    <div class="profile-hero-actions">
      <button type="button" class="modal-close w3-button" onclick="closeModal()" aria-label="Schließen">&times;</button>
    </div>
  </header>
<?php if($showOrchestra) { ?>
  <div class="orchestra-panel w3-margin-top"
       data-color-yes="<?php echo htmlspecialchars($GLOBALS['optionsDB']['colorBtnYes'], ENT_QUOTES, 'UTF-8'); ?>"
       data-color-no="<?php echo htmlspecialchars($GLOBALS['optionsDB']['colorBtnNo'], ENT_QUOTES, 'UTF-8'); ?>"
       data-color-maybe="<?php echo htmlspecialchars($GLOBALS['optionsDB']['colorBtnMaybe'], ENT_QUOTES, 'UTF-8'); ?>"
       data-color-disabled="<?php echo htmlspecialchars($GLOBALS['optionsDB']['colorDisabled'], ENT_QUOTES, 'UTF-8'); ?>">
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
  <div class="w3-container w3-margin-bottom"><br />
  </div>
</div>
