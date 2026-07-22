<?php
/**
 * Layout preview switcher (MELD-125).
 * Expects: $profileLayout, $profileLayoutLinks (key => url)
 */
$labels = array(
    'a' => 'A · Zwei Spuren',
    'b' => 'B · Schritte',
    'c' => 'C · Kompakt',
);
?>
<nav class="profile-layout-switch" aria-label="Layout-Vorschau">
  <span class="profile-layout-switch-label">Layout:</span>
<?php foreach($labels as $key => $label) {
    $url = isset($profileLayoutLinks[$key]) ? $profileLayoutLinks[$key] : '#';
    $active = ($profileLayout === $key);
?>
  <a class="profile-layout-chip<?php echo $active ? ' is-active' : ''; ?>" href="<?php echo htmlspecialchars($url, ENT_QUOTES, 'UTF-8'); ?>"<?php echo $active ? ' aria-current="page"' : ''; ?>><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></a>
<?php } ?>
</nav>
