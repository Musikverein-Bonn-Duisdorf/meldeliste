<?php
/**
 * Archiv piece rows (Archiv Composition::printLine parity).
 * Expects: $items (list of piece arrays with coverHtml/meta).
 */
$h = function ($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
$items = isset($items) && is_array($items) ? $items : array();
if(count($items)) {
foreach($items as $item) {
    $title = isset($item['title']) ? trim((string)$item['title']) : '';
    if($title === '') {
        $title = '—';
    }
    $composer = isset($item['composer']) ? trim((string)$item['composer']) : '';
    $arranger = isset($item['arranger']) ? trim((string)$item['arranger']) : '';
    $publisher = isset($item['publisher']) ? trim((string)$item['publisher']) : '';
    $publisherHref = isset($item['publisherHref']) ? trim((string)$item['publisherHref']) : '';
    $publisherLabel = isset($item['publisherLabel']) ? trim((string)$item['publisherLabel']) : '';
    if($publisherLabel === '') {
        $publisherLabel = $publisher !== '' ? $publisher : $publisherHref;
    }
    $year = isset($item['year']) ? trim((string)$item['year']) : '';
    $coverHtml = isset($item['coverHtml']) ? (string)$item['coverHtml'] : '';
?>
      <div class="piece-row list-row">
        <div class="piece-rail" aria-hidden="true"></div>
        <div class="piece-main">
<?php if($coverHtml !== '') {
    echo $coverHtml;
} ?>
          <div class="piece-text">
            <div class="piece-title"><?php echo $h($title); ?></div>
            <div class="piece-meta-line">
<?php if($composer !== '') { ?>
              <span class="piece-meta-item"><span class="piece-meta-k">Komponist</span> <?php echo $h($composer); ?></span>
<?php } ?>
<?php if($arranger !== '') { ?>
              <span class="piece-meta-item"><span class="piece-meta-k">Arrangeur</span> <?php echo $h($arranger); ?></span>
<?php } ?>
<?php if($publisherLabel !== '') { ?>
              <span class="piece-meta-item"><span class="piece-meta-k">Verlag</span> <?php
    if($publisherHref !== '') {
        echo '<a href="'.$h($publisherHref).'" target="_blank" rel="noopener noreferrer">'.$h($publisherLabel).'</a>';
    }
    else {
        echo $h($publisherLabel);
    }
?></span>
<?php } ?>
<?php if($year !== '') { ?>
              <span class="piece-meta-item"><span class="piece-meta-k">Jahr</span> <?php echo $h($year); ?></span>
<?php } ?>
            </div>
          </div>
        </div>
      </div>
<?php
}
} ?>
