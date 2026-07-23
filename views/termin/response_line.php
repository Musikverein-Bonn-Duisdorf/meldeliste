<?php
/**
 * One person row in termin/shift response modals (MELD-149).
 * Expects $entry: registerColor, statusClass, name, instrument, children, guests, freeText
 */
$h = function ($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
$name = isset($entry['name']) ? (string)$entry['name'] : '';
$instrument = isset($entry['instrument']) ? (string)$entry['instrument'] : '';
$regHex = '';
if(!empty($entry['registerColor']) && function_exists('normalizeHexColor')) {
    $regHex = normalizeHexColor($entry['registerColor']);
}
$statusClass = isset($entry['statusClass']) ? (string)$entry['statusClass'] : '';
if($statusClass === '' && !empty($entry['colorClass'])) {
    $statusClass = (string)$entry['colorClass'];
}
$extras = array();
if(isset($entry['children']) && $entry['children'] !== null && $entry['children'] !== false && (int)$entry['children'] > 0) {
    $extras[] = '+'.(int)$entry['children'].' Kinder';
}
if(isset($entry['guests']) && $entry['guests'] !== null && $entry['guests'] !== false && (int)$entry['guests'] > 0) {
    $extras[] = '+'.(int)$entry['guests'].' Gäste';
}
if(isset($entry['freeText']) && $entry['freeText'] !== null && (string)$entry['freeText'] !== '') {
    $extras[] = (string)$entry['freeText'];
}
$cls = 'melde-response-person';
$style = '';
if($regHex !== '') {
    $cls .= ' melde-response-person--register';
    $fg = function_exists('hexContrastText') ? hexContrastText($regHex) : '#111';
    $style = ' style="--melde-person-reg:'.$h($regHex).';--melde-person-fg:'.$h($fg).'"';
}
elseif($statusClass !== '') {
    $cls .= ' '.$statusClass;
}
?>
<div class="<?php echo $h($cls); ?>"<?php echo $style; ?>>
  <div class="melde-response-person-main">
    <div class="melde-response-person-name"><?php echo $h($name); ?></div>
<?php if($instrument !== '') { ?>
    <div class="melde-response-person-instrument"><?php echo $h($instrument); ?></div>
<?php } ?>
  </div>
<?php if(count($extras)) { ?>
  <div class="melde-response-person-extras">
<?php foreach($extras as $ex) { ?>
    <span class="melde-response-person-extra"><?php echo $h($ex); ?></span>
<?php } ?>
  </div>
<?php } ?>
</div>
