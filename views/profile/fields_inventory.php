<?php
/**
 * Read-only Inventar chips (Eigentum / aktive Leihen) on profile / Musiker-Form.
 * Expects: $n, $fill
 */
if(empty($fill) || !isset($n) || (int)$n->Index < 1) {
    return;
}
$inventoryChips = $n->getModalInventoryChips();
$ownedChips = isset($inventoryChips['owned']) && is_array($inventoryChips['owned'])
    ? $inventoryChips['owned'] : array();
$loanedChips = isset($inventoryChips['loaned']) && is_array($inventoryChips['loaned'])
    ? $inventoryChips['loaned'] : array();
if(!count($ownedChips) && !count($loanedChips)) {
    return;
}
?>
<?php if(count($ownedChips)) { ?>
<div class="profile-field">
  <span class="profile-label">Eigentum</span>
  <div class="mail-recipient-chips" aria-label="Inventar-Eigentum">
<?php echo implode('', $ownedChips); ?>
  </div>
</div>
<?php } ?>
<?php if(count($loanedChips)) { ?>
<div class="profile-field">
  <span class="profile-label">Leihen</span>
  <div class="mail-recipient-chips" aria-label="Aktive Leihen">
<?php echo implode('', $loanedChips); ?>
  </div>
</div>
<?php } ?>
