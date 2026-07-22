<?php
/**
 * Inventar detail modal (profile-shell).
 * Expects: $inv (Inventories), $canEdit, $row (joined SQL), $loansHtml
 */
$btnSubmit = $GLOBALS['optionsDB']['colorBtnSubmit'];
$btnDelete = $GLOBALS['optionsDB']['colorBtnDelete'];
$inputBg = $GLOBALS['optionsDB']['colorInputBackground'];
$h = function ($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
$dash = '—';
$display = function ($text) use ($h, $dash) {
    $text = trim(html_entity_decode(strip_tags((string)$text)));
    return $text === '' ? $dash : $h($text);
};

$headerTitle = !empty($row['instName']) ? (string)$row['instName'] : (string)$row['iTyp'];
$regDisplay = RegNumber::displayInventory($inv->Inventory, $inv->RegNumber);
$isInstr = $inv->isInstrType() || (int)$inv->Instrument > 0;
$insured = !empty($row['Insurance']) || !empty($inv->Insurance);
?>
<div class="profile-shell modal-shell inventar-modal">
  <header class="profile-hero">
    <div class="profile-hero-text">
      <p class="profile-kicker">Inventar</p>
      <h2 class="profile-title"><?php echo $h($headerTitle !== '' ? $headerTitle : 'Inventar'); ?></h2>
    </div>
    <div class="profile-hero-actions">
<?php if($canEdit) { ?>
      <div class="profile-actions">
        <div class="profile-actions-primary">
          <button form="inventar-edit-form" class="w3-btn profile-btn-primary <?php echo $h($btnSubmit); ?> w3-border w3-mobile" type="submit" name="update" value="update">Speichern</button>
        </div>
        <details class="profile-actions-more">
          <summary>Weitere Aktionen</summary>
          <div class="profile-actions-secondary">
            <button type="button" class="w3-btn <?php echo $h($btnDelete); ?> w3-border w3-mobile" onclick="document.getElementById('del<?php echo (int)$inv->Index; ?>').style.display='block'">Löschen</button>
          </div>
        </details>
      </div>
<?php } ?>
      <button type="button" class="modal-close w3-button" onclick="closeModal()" aria-label="Schließen">&times;</button>
    </div>
  </header>

<?php echo $canEdit ? '<form id="inventar-edit-form" class="inventar-modal-form" action="" method="POST">' : '<div class="inventar-modal-form">'; ?>
<?php if($canEdit) { ?>
  <input type="hidden" name="InventoriesIndex" value="<?php echo (int)$inv->Index; ?>">
<?php } ?>

  <div class="profile-grid profile-grid--3">
    <section class="profile-col" aria-labelledby="inv-col-ident">
      <h3 id="inv-col-ident" class="profile-col-title">Stück</h3>
      <div class="profile-field">
        <label class="profile-label" for="inv-regnumber">Inventarnummer</label>
<?php if($canEdit) { ?>
        <input id="inv-regnumber" class="w3-input w3-border profile-control <?php echo $h($inputBg); ?>" type="number" name="RegNumber" value="<?php echo $h($inv->RegNumber); ?>">
        <div class="profile-value profile-value--hint"><?php echo $h($regDisplay); ?></div>
<?php } else { ?>
        <div class="profile-value"><?php echo $h($regDisplay); ?></div>
<?php } ?>
      </div>
<?php if($isInstr) { ?>
      <div class="profile-field">
        <label class="profile-label" for="inv-instrument">Instrument</label>
<?php   if($canEdit) { ?>
        <select id="inv-instrument" class="w3-select w3-border w3-input profile-control <?php echo $h($inputBg); ?>" name="Instrument"><?php echo instrumentOptionAll((int)$inv->Instrument); ?></select>
<?php   } else { ?>
        <div class="profile-value"><?php echo $display($inv->getInstrumentName()); ?></div>
<?php   } ?>
      </div>
<?php } ?>
      <div class="profile-field">
        <label class="profile-label" for="inv-description">Beschreibung</label>
<?php if($canEdit) { ?>
        <input id="inv-description" class="w3-input w3-border profile-control <?php echo $h($inputBg); ?>" type="text" name="Description" value="<?php echo $h($inv->Description); ?>">
<?php } else { ?>
        <div class="profile-value"><?php echo $display($inv->Description); ?></div>
<?php } ?>
      </div>
      <div class="profile-field">
        <span class="profile-label">Typ</span>
        <div class="profile-value"><?php echo $display($row['iTyp']); ?></div>
      </div>
      <div class="profile-field">
        <span class="profile-label">Inventar-ID</span>
        <div class="profile-value"><?php echo (int)$inv->Index; ?></div>
      </div>
    </section>

    <section class="profile-col" aria-labelledby="inv-col-details">
      <h3 id="inv-col-details" class="profile-col-title">Details</h3>
      <div class="profile-field">
        <label class="profile-label" for="inv-vendor">Hersteller</label>
<?php if($canEdit) { ?>
        <input id="inv-vendor" class="w3-input w3-border profile-control <?php echo $h($inputBg); ?>" type="text" name="Vendor" value="<?php echo $h($inv->Vendor); ?>">
<?php } else { ?>
        <div class="profile-value"><?php echo $display($inv->Vendor); ?></div>
<?php } ?>
      </div>
      <div class="profile-field">
        <label class="profile-label" for="inv-model">Modell</label>
<?php if($canEdit) { ?>
        <input id="inv-model" class="w3-input w3-border profile-control <?php echo $h($inputBg); ?>" type="text" name="Model" value="<?php echo $h($inv->Model); ?>">
<?php } else { ?>
        <div class="profile-value"><?php echo $display($inv->Model); ?></div>
<?php } ?>
      </div>
      <div class="profile-field">
        <label class="profile-label" for="inv-serial">Seriennummer</label>
<?php if($canEdit) { ?>
        <input id="inv-serial" class="w3-input w3-border profile-control <?php echo $h($inputBg); ?>" type="text" name="SerialNr" value="<?php echo $h($inv->SerialNr); ?>">
<?php } else { ?>
        <div class="profile-value"><?php echo $display($inv->SerialNr); ?></div>
<?php } ?>
      </div>
      <div class="profile-field">
        <label class="profile-label" for="inv-comment">Kommentar</label>
<?php if($canEdit) { ?>
        <input id="inv-comment" class="w3-input w3-border profile-control <?php echo $h($inputBg); ?>" type="text" name="Comment" value="<?php echo $h($inv->Comment); ?>">
<?php } else { ?>
        <div class="profile-value"><?php echo $display($inv->Comment); ?></div>
<?php } ?>
      </div>
    </section>

    <section class="profile-col" aria-labelledby="inv-col-ownership">
      <h3 id="inv-col-ownership" class="profile-col-title">Besitz</h3>
      <div class="profile-field">
        <label class="profile-label" for="inv-purchase-date">Kaufdatum</label>
<?php if($canEdit) { ?>
        <input id="inv-purchase-date" class="w3-input w3-border profile-control <?php echo $h($inputBg); ?>" type="date" name="PurchaseDate" value="<?php echo $h($inv->PurchaseDate); ?>">
<?php } else { ?>
        <div class="profile-value"><?php echo $display(germanDate($inv->PurchaseDate, 0)); ?></div>
<?php } ?>
      </div>
      <div class="profile-field">
        <label class="profile-label" for="inv-purchase-prize">Kaufpreis</label>
<?php if($canEdit) { ?>
        <input id="inv-purchase-prize" class="w3-input w3-border profile-control <?php echo $h($inputBg); ?>" type="number" step="0.01" name="PurchasePrize" value="<?php echo $h($inv->PurchasePrize); ?>">
<?php } else { ?>
        <div class="profile-value"><?php echo $display(mkPrize($inv->PurchasePrize)); ?></div>
<?php } ?>
      </div>
      <div class="profile-field">
        <label class="profile-label" for="inv-owner">Eigentümer</label>
<?php if($canEdit) { ?>
        <select id="inv-owner" class="w3-select w3-border w3-input profile-control <?php echo $h($inputBg); ?>" name="Owner"><?php echo UserOptionAll((int)$inv->Owner); ?></select>
<?php } else { ?>
        <div class="profile-value"><?php echo $display(getOwner((int)$inv->Owner)); ?></div>
<?php } ?>
      </div>
      <div class="profile-field">
        <span class="profile-label">Versichert</span>
<?php if($canEdit) { ?>
        <label class="profile-pref">
          <input type="hidden" name="Insurance" value="0">
          <input class="w3-check" type="checkbox" name="Insurance" value="1"<?php echo $insured ? ' checked' : ''; ?>>
          <span>ja</span>
        </label>
<?php } else { ?>
        <div class="profile-value"><?php echo bool2string($insured); ?></div>
<?php } ?>
      </div>
    </section>
  </div>
<?php echo $canEdit ? '</form>' : '</div>'; ?>

<?php if($canEdit) { ?>
  <form id="del<?php echo (int)$inv->Index; ?>" class="inventar-delete-confirm w3-padding w3-margin-top <?php echo $h($GLOBALS['optionsDB']['colorWarning']); ?>" action="" method="POST" style="display:none;">
    <p class="profile-value">Diesen Eintrag wirklich löschen?</p>
    <input type="hidden" name="InventoriesIndex" value="<?php echo (int)$inv->Index; ?>">
    <button class="w3-btn <?php echo $h($btnSubmit); ?> w3-border w3-mobile" type="submit" name="delete" value="delete">Ja</button>
    <button type="button" class="w3-btn w3-border w3-mobile" onclick="this.form.style.display='none'">Nein</button>
  </form>
<?php } ?>

  <div class="inventar-loans-block">
<?php echo $loansHtml; ?>
  </div>
</div>
