<?php
/**
 * Benachrichtigungs-Prefs.
 * Expects: $checked
 * Optional: $idSuffix
 * Admin-Flags (Mitglied/RegisterLead) liegen in fields_person.php (nur Admin-Nutzerbearbeitung).
 */
$idSuffix = isset($idSuffix) ? (string)$idSuffix : '';
?>
<div class="profile-pref-groups">
  <div class="profile-pref-group">
    <h4 class="profile-subhead">Kanäle</h4>
    <div class="profile-prefs profile-prefs--grid">
      <label class="profile-pref">
        <input type="hidden" name="getMail" value="0">
        <input class="w3-check" type="checkbox" name="getMail" value="1" id="pref-getMail<?php echo htmlspecialchars($idSuffix, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $checked('getMail'); ?>>
        <span>E-Mail</span>
      </label>
      <label class="profile-pref">
        <input type="hidden" name="notifyInbox" value="0">
        <input class="w3-check" type="checkbox" name="notifyInbox" value="1" id="pref-notifyInbox<?php echo htmlspecialchars($idSuffix, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $checked('notifyInbox'); ?>>
        <span>Nachrichten</span>
      </label>
    </div>
  </div>
  <div class="profile-pref-group">
    <h4 class="profile-subhead">App-Hinweise</h4>
    <div class="profile-prefs profile-prefs--grid">
      <label class="profile-pref">
        <input type="hidden" name="notifyAppMail" value="0">
        <input class="w3-check" type="checkbox" name="notifyAppMail" value="1" id="pref-notifyAppMail<?php echo htmlspecialchars($idSuffix, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $checked('notifyAppMail'); ?>>
        <span>Nachrichten</span>
      </label>
      <label class="profile-pref">
        <input type="hidden" name="notifyAppTerminNew" value="0">
        <input class="w3-check" type="checkbox" name="notifyAppTerminNew" value="1" id="pref-notifyAppTerminNew<?php echo htmlspecialchars($idSuffix, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $checked('notifyAppTerminNew'); ?>>
        <span>Neuer Termin</span>
      </label>
      <label class="profile-pref">
        <input type="hidden" name="notifyAppTerminChange" value="0">
        <input class="w3-check" type="checkbox" name="notifyAppTerminChange" value="1" id="pref-notifyAppTerminChange<?php echo htmlspecialchars($idSuffix, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $checked('notifyAppTerminChange'); ?>>
        <span>Termin geändert</span>
      </label>
      <label class="profile-pref">
        <input type="hidden" name="notifyAppTerminSoon" value="0">
        <input class="w3-check" type="checkbox" name="notifyAppTerminSoon" value="1" id="pref-notifyAppTerminSoon<?php echo htmlspecialchars($idSuffix, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $checked('notifyAppTerminSoon'); ?>>
        <span>Termin bald</span>
      </label>
    </div>
  </div>
</div>
<p class="profile-help-link"><a href="help.php#help-profil">Hilfe zu Benachrichtigungen</a></p>
