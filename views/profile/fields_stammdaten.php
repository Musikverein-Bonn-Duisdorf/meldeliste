<?php
/**
 * Shared profile field: Stammdaten.
 * Expects: $n, $fill, $edit, $disabled, $canEditUsers, $inputBg
 */
?>
<label>Vorname</label>
<input class="w3-input w3-border <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?> w3-margin-bottom w3-mobile" name="Vorname" type="text" placeholder="Vorname" <?php if($fill) echo 'value="'.htmlspecialchars((string)$n->Vorname, ENT_QUOTES, 'UTF-8').'"'; ?> <?php echo $disabled; ?>>
<label>Nachname</label>
<input class="w3-input w3-border <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?> w3-margin-bottom w3-mobile" name="Nachname" type="text" placeholder="Nachname" <?php if($fill) echo 'value="'.htmlspecialchars((string)$n->Nachname, ENT_QUOTES, 'UTF-8').'"'; ?> <?php echo $disabled; ?>>
<?php if($canEditUsers) { ?>
<label>Mitglieds-Nr. (optional)</label>
<input class="w3-input w3-border <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?> w3-margin-bottom w3-mobile" name="RefID" type="number" placeholder="Vereins-Nr." <?php if($fill) echo 'value="'.htmlspecialchars((string)$n->RefID, ENT_QUOTES, 'UTF-8').'"'; ?> <?php echo $disabled; ?>>
<?php } elseif($fill) { ?>
<input type="hidden" name="Nachname" value="<?php echo htmlspecialchars((string)$n->Nachname, ENT_QUOTES, 'UTF-8'); ?>">
<input type="hidden" name="Vorname" value="<?php echo htmlspecialchars((string)$n->Vorname, ENT_QUOTES, 'UTF-8'); ?>">
<?php } ?>
<label>Emailadressen</label>
<input class="w3-input w3-border <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?> w3-margin-bottom w3-mobile" name="Email" type="email" placeholder="Email" <?php if($fill) echo 'value="'.htmlspecialchars((string)$n->Email, ENT_QUOTES, 'UTF-8').'"'; ?>>
<input class="w3-input w3-border <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?> w3-margin-bottom w3-mobile" name="Email2" type="email" placeholder="Email 2 (optional)" <?php if($fill) echo 'value="'.htmlspecialchars((string)$n->Email2, ENT_QUOTES, 'UTF-8').'"'; ?>>
<?php if($edit != 2 || !empty($canEditUsers)) { ?>
<label class="w3-text-gray">Loginname (optional)</label>
<input class="w3-input w3-border <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?> w3-margin-bottom w3-mobile" name="login" type="text" placeholder="Loginname" <?php if($fill) echo 'value="'.htmlspecialchars((string)$n->login, ENT_QUOTES, 'UTF-8').'"'; ?> <?php echo $disabled; ?>>
<?php } ?>
<?php if($fill && ($n->login || $edit == 3 || !empty($canEditUsers))) { ?>
<label class="w3-text-gray">neues Passwort (optional)</label>
<input class="w3-input w3-border <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?> w3-margin-bottom w3-mobile" name="pw1" type="password" placeholder="*****" autocomplete="new-password">
<label class="w3-text-gray">neues Passwort wiederholen (optional)</label>
<input class="w3-input w3-border <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?> w3-margin-bottom w3-mobile" name="pw2" type="password" placeholder="*****" autocomplete="new-password">
<?php } ?>
<label>Instrument</label>
<select class="w3-input w3-border <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?> w3-margin-bottom w3-mobile" name="Instrument" <?php echo $disabled; ?>>
<?php
if($fill) {
    echo instrumentOption($n->Instrument);
}
else {
    echo instrumentOption(0);
}
?>
</select>
<?php if($fill && (int)$n->Index > 0 && !empty($GLOBALS['optionsDB']['urlNotenarchiv'])) { ?>
<p class="w3-small"><a href="user-voice.php?user=<?php echo (int)$n->Index; ?>">Stimme / Fallbacks (Notenarchiv)</a></p>
<?php } ?>
<?php
if($fill && (int)$n->Index > 0) {
    $membership = AudienceSpec::membershipForUser((int)$n->Index);
    if(count($membership)) {
?>
<label>Gruppenzugehörigkeit</label>
<div class="mail-recipient-chips w3-margin-bottom" aria-label="Gruppenzugehörigkeit">
<?php
        foreach($membership as $chip) {
            $type = htmlspecialchars((string)$chip['type'], ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars((string)$chip['label'], ENT_QUOTES, 'UTF-8');
            echo '<span class="mail-recipient-chip mail-recipient-chip--'.$type.'">'.$label.'</span>';
        }
?>
</div>
<?php
    }
}
?>
