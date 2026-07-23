<?php
/**
 * Kontakt / Zugang.
 * Expects: $n, $fill, $edit, $disabled, $inputBg
 */
?>
<div class="profile-field">
  <label class="profile-label" for="profile-email">E-Mail</label>
  <input id="profile-email" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" name="Email" type="email" placeholder="primär" <?php if($fill) echo 'value="'.htmlspecialchars((string)$n->Email, ENT_QUOTES, 'UTF-8').'"'; ?>>
</div>
<div class="profile-field">
  <label class="profile-label" for="profile-email2">E-Mail 2</label>
  <input id="profile-email2" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" name="Email2" type="email" placeholder="optional" <?php if($fill) echo 'value="'.htmlspecialchars((string)$n->Email2, ENT_QUOTES, 'UTF-8').'"'; ?>>
</div>
<?php if($edit != 2 || !empty($adminUserEdit)) { ?>
<div class="profile-field">
  <label class="profile-label" for="profile-login">Login</label>
  <input id="profile-login" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" name="login" type="text" placeholder="optional" <?php if($fill) echo 'value="'.htmlspecialchars((string)$n->login, ENT_QUOTES, 'UTF-8').'"'; ?> <?php echo $disabled; ?>>
</div>
<?php } elseif($fill) { ?>
<div class="profile-field">
  <span class="profile-label">Login</span>
  <div class="profile-value"><?php echo htmlspecialchars((string)$n->login, ENT_QUOTES, 'UTF-8') !== '' ? htmlspecialchars((string)$n->login, ENT_QUOTES, 'UTF-8') : '—'; ?></div>
</div>
<?php } ?>
<?php if($fill && ($n->login || $edit == 3 || !empty($adminUserEdit))) { ?>
<div class="profile-field">
  <label class="profile-label" for="profile-pw1">Neues Passwort</label>
  <input id="profile-pw1" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" name="pw1" type="password" placeholder="*****" autocomplete="new-password">
</div>
<div class="profile-field">
  <label class="profile-label" for="profile-pw2">Wiederholen</label>
  <input id="profile-pw2" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" name="pw2" type="password" placeholder="*****" autocomplete="new-password">
</div>
<?php } ?>
<?php include __DIR__.'/fields_meta.php'; ?>
