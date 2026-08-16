<?php
/**
 * Termin-Programm modal: all linked Archiv-Sammlungen + pieces (MELD-197).
 * Expects: $terminId, $terminName, $collections (list of {id,name,items}).
 */
$h = function ($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
$terminName = trim((string)$terminName);
if($terminName === '') {
    $terminName = 'Termin';
}
$collections = isset($collections) && is_array($collections) ? $collections : array();
?>
<div class="profile-shell modal-shell archiv-piece-modal programm-modal" data-termin-id="<?php echo (int)$terminId; ?>">
  <header class="profile-hero">
    <div class="profile-hero-text">
      <p class="profile-kicker">Programm</p>
      <h2 class="profile-title"><?php echo $h($terminName); ?></h2>
    </div>
    <div class="profile-hero-actions">
      <button type="button" class="modal-close w3-button" onclick="closeModal()" aria-label="Schließen">&times;</button>
    </div>
  </header>

  <div class="profile-grid">
<?php if(!count($collections)) { ?>
    <section class="profile-col archiv-piece-modal-list">
      <div class="profile-field">
        <div class="profile-value">Keine Sammlungen verknüpft.</div>
      </div>
    </section>
<?php } else {
    foreach($collections as $idx => $col) {
        $colName = isset($col['name']) ? trim((string)$col['name']) : '';
        if($colName === '') {
            $colName = 'Sammlung';
        }
        $items = isset($col['items']) && is_array($col['items']) ? $col['items'] : array();
        $itemCount = count($items);
        $headingId = 'programm-col-'.$idx;
?>
    <section class="profile-col archiv-piece-modal-list" aria-labelledby="<?php echo $h($headingId); ?>">
      <h3 id="<?php echo $h($headingId); ?>" class="profile-col-title"><?php echo $h($colName); ?></h3>
<?php   if(!$itemCount) { ?>
      <div class="profile-field">
        <div class="profile-value">Keine Stücke in dieser Sammlung.</div>
      </div>
<?php   } else {
            echo render('sammlung/piece_list', array('items' => $items));
        } ?>
    </section>
<?php
    }
} ?>
  </div>
</div>
