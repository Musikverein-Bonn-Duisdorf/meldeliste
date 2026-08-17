<?php
/**
 * Inventar-Modal: Dokumente (MELD-205), analog MIT Personen-Dokumente.
 * Expects: $inventoryId, $documents (InventoriesDocument[]), $canEdit
 */
$h = function ($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
$btnSubmit = isset($GLOBALS['optionsDB']['colorBtnSubmit']) ? $GLOBALS['optionsDB']['colorBtnSubmit'] : '';
$btnDelete = isset($GLOBALS['optionsDB']['colorBtnDelete']) ? $GLOBALS['optionsDB']['colorBtnDelete'] : '';
$inputBg = isset($GLOBALS['optionsDB']['colorInputBackground']) ? $GLOBALS['optionsDB']['colorInputBackground'] : '';
?>
<section class="inventory-docs profile-col" aria-labelledby="inv-sec-dokumente">
  <h3 id="inv-sec-dokumente" class="profile-col-title">Dokumente</h3>
<?php if(count($documents)) { ?>
  <ul class="inv-doc-list">
<?php foreach($documents as $doc) {
    $hasFile = $doc->absolutePath() !== null;
    $uploadedLabel = $doc->UploadedAt ? germanDate(substr((string)$doc->UploadedAt, 0, 10), 0) : '';
    $docId = (int)$doc->Index;
?>
    <li class="inv-doc-item">
      <div class="inv-doc-main">
        <span class="inv-doc-type"><?php echo $h((string)$doc->DocType); ?></span>
<?php if($hasFile) { ?>
        <a class="inv-doc-name" href="get-inventory-document.php?id=<?php echo $docId; ?>" target="_blank" rel="noopener noreferrer"><?php echo $h($doc->displayName()); ?></a>
<?php } else { ?>
        <span class="inv-doc-name"><?php echo $h($doc->displayName()); ?></span>
<?php } ?>
<?php if($uploadedLabel !== '') { ?>
        <span class="inv-doc-date"><?php echo $h($uploadedLabel); ?></span>
<?php } ?>
      </div>
<?php if($canEdit) { ?>
      <form method="post" action="inventory-document.php" class="inv-doc-delete" data-confirm="Dokument löschen?" data-confirm-ok="Löschen">
        <input type="hidden" name="inventory_id" value="<?php echo (int)$inventoryId; ?>">
        <input type="hidden" name="document_id" value="<?php echo $docId; ?>">
        <button type="submit" name="action" value="document_delete" class="w3-button w3-mobile <?php echo $h($btnDelete); ?>">Löschen</button>
      </form>
<?php } ?>
    </li>
<?php } ?>
  </ul>
<?php } else { ?>
  <p class="profile-value">—</p>
<?php } ?>
<?php if($canEdit) { ?>
  <form method="post" action="inventory-document.php" class="inv-doc-upload" enctype="multipart/form-data">
    <input type="hidden" name="inventory_id" value="<?php echo (int)$inventoryId; ?>">
    <input type="hidden" name="action" value="document_upload">
    <div class="inv-doc-upload-grid">
      <div class="profile-field">
        <label class="profile-label" for="inv-doc-type">Typ</label>
        <select id="inv-doc-type" class="w3-select w3-border profile-control <?php echo $h($inputBg); ?>" name="doc_type" required>
<?php foreach(InventoriesDocument::allowedTypes() as $t) { ?>
          <option value="<?php echo $h($t); ?>"<?php echo $t === InventoriesDocument::TYPE_RECHNUNG ? ' selected' : ''; ?>><?php echo $h($t); ?></option>
<?php } ?>
        </select>
      </div>
      <div class="profile-field inv-doc-upload-file">
        <label class="profile-label" for="inv-doc-file">Datei</label>
        <input id="inv-doc-file" class="w3-input w3-border profile-control <?php echo $h($inputBg); ?>" type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png" required>
      </div>
      <div class="profile-field">
        <label class="profile-label" for="inv-doc-note">Notiz</label>
        <input id="inv-doc-note" class="w3-input w3-border profile-control <?php echo $h($inputBg); ?>" type="text" name="doc_note">
      </div>
    </div>
    <div class="profile-actions">
      <button type="submit" class="w3-button profile-btn-primary w3-mobile <?php echo $h($btnSubmit); ?>">Hochladen</button>
    </div>
  </form>
<?php } ?>
</section>
