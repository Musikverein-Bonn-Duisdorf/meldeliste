<?php
/**
 * Archiv-Sammlung detail modal (MELD-197).
 * Expects: $collectionId, $collectionName, $items.
 */
$h = function ($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
$name = trim((string)$collectionName);
if($name === '') {
    $name = 'Sammlung';
}
$items = isset($items) && is_array($items) ? $items : array();
$itemCount = count($items);
?>
<div class="profile-shell modal-shell archiv-piece-modal sammlung-modal" data-sammlung-id="<?php echo (int)$collectionId; ?>">
  <header class="profile-hero">
    <div class="profile-hero-text">
      <p class="profile-kicker">Sammlung</p>
      <h2 class="profile-title"><?php echo $h($name); ?></h2>
    </div>
    <div class="profile-hero-actions">
      <button type="button" class="modal-close w3-button" onclick="closeModal()" aria-label="Schließen">&times;</button>
    </div>
  </header>

  <div class="profile-grid">
    <section class="profile-col archiv-piece-modal-list" aria-labelledby="sammlung-modal-inhalt">
      <h3 id="sammlung-modal-inhalt" class="profile-col-title">Stücke</h3>
<?php if(!$itemCount) { ?>
      <div class="profile-field">
        <div class="profile-value">Keine Stücke in dieser Sammlung.</div>
      </div>
<?php } else {
    echo render('sammlung/piece_list', array('items' => $items));
} ?>
    </section>
  </div>
</div>
