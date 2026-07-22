<?php
/**
 * Person / Orchester-Felder.
 * Expects: $n, $fill, $disabled, $canEditUsers, $adminUserEdit, $checked, $inputBg
 */
if(!isset($adminUserEdit)) {
    $adminUserEdit = !empty($canEditUsers);
}
?>
<div class="profile-field">
  <label class="profile-label" for="profile-vorname">Vorname</label>
  <input id="profile-vorname" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" name="Vorname" type="text" placeholder="Vorname" <?php if($fill) echo 'value="'.htmlspecialchars((string)$n->Vorname, ENT_QUOTES, 'UTF-8').'"'; ?> <?php echo $disabled; ?>>
</div>
<div class="profile-field">
  <label class="profile-label" for="profile-nachname">Nachname</label>
  <input id="profile-nachname" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" name="Nachname" type="text" placeholder="Nachname" <?php if($fill) echo 'value="'.htmlspecialchars((string)$n->Nachname, ENT_QUOTES, 'UTF-8').'"'; ?> <?php echo $disabled; ?>>
</div>
<?php if($adminUserEdit) { ?>
<div class="profile-field">
  <label class="profile-label" for="profile-refid">Mitglieds-Nr.</label>
  <input id="profile-refid" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" name="RefID" type="number" placeholder="optional" <?php if($fill) echo 'value="'.htmlspecialchars((string)$n->RefID, ENT_QUOTES, 'UTF-8').'"'; ?>>
</div>
<?php } elseif($fill) { ?>
<input type="hidden" name="Nachname" value="<?php echo htmlspecialchars((string)$n->Nachname, ENT_QUOTES, 'UTF-8'); ?>">
<input type="hidden" name="Vorname" value="<?php echo htmlspecialchars((string)$n->Vorname, ENT_QUOTES, 'UTF-8'); ?>">
<?php } ?>
<div class="profile-field">
  <label class="profile-label" for="profile-instrument">Instrument</label>
  <select id="profile-instrument" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" name="Instrument" <?php echo $disabled; ?>>
<?php
if($fill) {
    echo instrumentOption($n->Instrument);
}
else {
    echo instrumentOption(0);
}
?>
  </select>
</div>
<?php if($adminUserEdit) { ?>
<div class="profile-field">
  <span class="profile-label">Rolle</span>
  <div class="profile-prefs profile-prefs--grid">
    <label class="profile-pref">
      <input type="hidden" name="Active" value="0">
      <input class="w3-check" type="checkbox" name="Active" value="1" id="pref-Active" <?php echo $checked('Active'); ?>>
      <span>aktiv</span>
    </label>
    <label class="profile-pref">
      <input type="hidden" name="Mitglied" value="0">
      <input class="w3-check" type="checkbox" name="Mitglied" value="1" id="pref-Mitglied" <?php echo $checked('Mitglied'); ?>>
      <span>Mitglied</span>
    </label>
<?php   if(!empty($GLOBALS['optionsDB']['showRegisterLead'])) { ?>
    <label class="profile-pref">
      <input type="hidden" name="RegisterLead" value="0">
      <input class="w3-check" type="checkbox" name="RegisterLead" value="1" id="pref-RegisterLead" <?php echo $checked('RegisterLead'); ?>>
      <span>Registerführer</span>
    </label>
<?php   } ?>
  </div>
</div>
<?php } ?>
<?php if($fill && (int)$n->Index > 0 && $adminUserEdit && !empty($GLOBALS['optionsDB']['urlNotenarchiv'])) { ?>
<p class="profile-inline-link"><a href="user-voice.php?user=<?php echo (int)$n->Index; ?>">Stimme / Fallbacks</a></p>
<?php } ?>
<?php if($fill && (int)$n->Index > 0) { ?>
<div class="profile-field">
  <span class="profile-label">User-ID</span>
  <div class="profile-value"><?php echo (int)$n->Index; ?></div>
</div>
<?php } ?>
<?php
$mailGroups = MailGroup::listAll();
$userId = ($fill && (int)$n->Index > 0) ? (int)$n->Index : 0;
if($adminUserEdit && count($mailGroups)) {
    $selectedGroupIds = array();
    $groupCatalog = array();
    foreach($mailGroups as $g) {
        $gid = (int)$g->Index;
        $groupCatalog[] = array(
            'id' => $gid,
            'label' => (string)$g->Name,
        );
        if($userId > 0 && $g->hasExplicitUser($userId)) {
            $selectedGroupIds[] = $gid;
        }
    }
    $groupCatalogJson = json_encode(
        array('mailGroups' => $groupCatalog),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
    );
    $selectedJson = json_encode(
        array_values($selectedGroupIds),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    );
?>
<div class="profile-field" id="profile-groups-wrap"
     data-group-catalog="<?php echo htmlspecialchars((string)$groupCatalogJson, ENT_QUOTES, 'UTF-8'); ?>"
     data-selected-groups="<?php echo htmlspecialchars((string)$selectedJson, ENT_QUOTES, 'UTF-8'); ?>">
  <span class="profile-label">Gruppen</span>
  <input type="hidden" name="userMailGroupsPosted" value="1">
  <div class="profile-group-picker w3-border <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>">
    <div id="profile-group-chips" class="mail-recipient-chips" aria-live="polite"></div>
    <input type="text" id="profile-group-input" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Gruppe tippen…" autocomplete="off">
    <div id="profile-group-suggest" class="mail-recipient-suggest" hidden></div>
    <div id="profile-group-hiddens" hidden></div>
  </div>
</div>
<?php
}

if($adminUserEdit) {
    $previewCatalog = AudienceSpec::buildMembershipPreviewCatalog();
    $initialMitglied = $fill ? (bool)$n->Mitglied : false;
    $initialActive = $fill ? ((int)$n->Active !== 0) : true;
    $initialInstrument = $fill ? (int)$n->Instrument : 0;
    $initialRegisterId = 0;
    $initialRegisterName = '';
    if($initialInstrument > 0 && isset($previewCatalog['instruments'][(string)$initialInstrument])) {
        $initialRegisterId = (int)$previewCatalog['instruments'][(string)$initialInstrument]['registerId'];
        $initialRegisterName = (string)$previewCatalog['instruments'][(string)$initialInstrument]['registerName'];
    }
    $initialChips = AudienceSpec::previewDerivedMembership(array(
        'mitglied' => $initialMitglied,
        'active' => $initialActive,
        'registerId' => $initialRegisterId,
        'registerName' => $initialRegisterName,
        'userId' => $userId,
    ));
    $catalogJson = json_encode(
        $previewCatalog,
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE
    );
?>
<div class="profile-field" id="profile-auto-membership-wrap"
     data-membership-catalog="<?php echo htmlspecialchars((string)$catalogJson, ENT_QUOTES, 'UTF-8'); ?>">
  <span class="profile-label">Automatisch</span>
  <div class="mail-recipient-chips" id="profile-auto-membership" aria-live="polite" aria-label="Automatische Zugehörigkeit">
<?php
    if(count($initialChips)) {
        foreach($initialChips as $chip) {
            $type = htmlspecialchars((string)$chip['type'], ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars((string)$chip['label'], ENT_QUOTES, 'UTF-8');
            echo '<span class="mail-recipient-chip mail-recipient-chip--'.$type.'">'.$label.'</span>';
        }
    }
    else {
        echo '<span class="profile-auto-empty">—</span>';
    }
?>
  </div>
</div>
<?php
}
elseif($userId > 0) {
    $membership = AudienceSpec::membershipForUser($userId);
    if(count($membership)) {
?>
<div class="profile-field">
  <span class="profile-label">Gruppen</span>
  <div class="mail-recipient-chips" aria-label="Gruppenzugehörigkeit">
<?php
        foreach($membership as $chip) {
            $type = htmlspecialchars((string)$chip['type'], ENT_QUOTES, 'UTF-8');
            $label = htmlspecialchars((string)$chip['label'], ENT_QUOTES, 'UTF-8');
            echo '<span class="mail-recipient-chip mail-recipient-chip--'.$type.'">'.$label.'</span>';
        }
?>
  </div>
</div>
<?php
    }
}
?>
