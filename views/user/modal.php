<?php
/**
 * User detail modal (profile-shell layout).
 * Expects: $user, $showUserDetails, $permissions, $registerLeadName,
 *          $showEditButton, $returnTo, $returnToken
 */
$name = trim((string)$user->Vorname.' '.(string)$user->Nachname);
$membership = AudienceSpec::membershipForUser((int)$user->Index);
$btnEdit = $GLOBALS['optionsDB']['colorBtnEdit'];
?>
<div class="profile-shell modal-shell user-modal">
  <header class="profile-hero">
    <div class="profile-hero-text">
      <p class="profile-kicker">Nutzer</p>
      <h2 class="profile-title"><?php echo htmlspecialchars($name !== '' ? $name : 'Profil', ENT_QUOTES, 'UTF-8'); ?></h2>
    </div>
    <div class="profile-hero-actions">
<?php if($showEditButton) { ?>
      <form class="profile-actions-primary" action="new-musiker.php" method="POST">
        <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($returnTo, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="return_token" value="<?php echo htmlspecialchars(isset($returnToken) ? $returnToken : '', ENT_QUOTES, 'UTF-8'); ?>">
        <button class="w3-btn profile-btn-primary <?php echo htmlspecialchars($btnEdit, ENT_QUOTES, 'UTF-8'); ?> w3-border w3-mobile" type="submit" name="id" value="<?php echo (int)$user->Index; ?>">Bearbeiten</button>
      </form>
<?php } ?>
      <button type="button" class="modal-close w3-button" onclick="closeModal()" aria-label="Schließen">&times;</button>
    </div>
  </header>

  <div class="profile-grid profile-grid--3">
    <section class="profile-col" aria-labelledby="user-modal-person">
      <h3 id="user-modal-person" class="profile-col-title">Person</h3>
      <div class="profile-field">
        <span class="profile-label">Instrument</span>
        <div class="profile-value"><?php echo htmlspecialchars((string)$user->iName, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
      <div class="profile-field">
        <span class="profile-label">aktiv</span>
        <div class="profile-value"><?php
          echo bool2string($user->Active);
          if((int)$user->Active === 0) {
              echo ' <span class="w3-text-grey">(Gastmusiker)</span>';
          }
        ?></div>
      </div>
      <div class="profile-field">
        <span class="profile-label">Mitglied</span>
        <div class="profile-value"><?php echo bool2string($user->Mitglied); ?></div>
      </div>
<?php if($registerLeadName !== null) { ?>
      <div class="profile-field">
        <span class="profile-label">Registerführer</span>
        <div class="profile-value"><?php echo htmlspecialchars((string)$registerLeadName, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
<?php } ?>
<?php if(count($membership)) { ?>
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
<?php } ?>
<?php if($showUserDetails) { ?>
      <div class="profile-field">
        <span class="profile-label">Mitglieds-Nr.</span>
        <div class="profile-value"><?php echo htmlspecialchars((string)$user->RefID, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
      <div class="profile-field">
        <span class="profile-label">User-ID</span>
        <div class="profile-value"><?php echo (int)$user->Index; ?></div>
      </div>
<?php } ?>
    </section>

    <section class="profile-col" aria-labelledby="user-modal-kontakt">
      <h3 id="user-modal-kontakt" class="profile-col-title">Kontakt</h3>
      <div class="profile-field">
        <span class="profile-label">E-Mail</span>
        <div class="profile-value"><?php if((string)$user->Email !== '') { ?><a href="mailto:<?php echo htmlspecialchars((string)$user->Email, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string)$user->Email, ENT_QUOTES, 'UTF-8'); ?></a><?php } else { echo '—'; } ?></div>
      </div>
      <div class="profile-field">
        <span class="profile-label">E-Mail 2</span>
        <div class="profile-value"><?php if((string)$user->Email2 !== '') { ?><a href="mailto:<?php echo htmlspecialchars((string)$user->Email2, ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars((string)$user->Email2, ENT_QUOTES, 'UTF-8'); ?></a><?php } else { echo '—'; } ?></div>
      </div>
<?php if($showUserDetails) { ?>
      <div class="profile-field">
        <span class="profile-label">Login</span>
        <div class="profile-value"><?php echo htmlspecialchars((string)$user->login, ENT_QUOTES, 'UTF-8'); ?></div>
      </div>
      <div class="profile-field">
        <span class="profile-label">Erstellt</span>
        <div class="profile-value"><?php echo germanDate($user->Joined, 1); ?></div>
      </div>
<?php } ?>
      <div class="profile-field">
        <span class="profile-label">Letzter Login</span>
        <div class="profile-value"><?php echo germanDate($user->LastLogin, 1); ?></div>
      </div>
<?php if(isAdmin()) { ?>
      <div class="profile-field">
        <span class="profile-label">Anwesenheit</span>
        <div class="profile-value"><?php echo germanDate($user->getLastVisit(), 1); ?></div>
      </div>
      <div class="profile-field">
        <span class="profile-label">Meldequote</span>
        <div class="profile-value"><?php echo (float)$user->getMeldeQuote()*100; ?> %</div>
      </div>
<?php } ?>
    </section>

    <section class="profile-col" aria-labelledby="user-modal-notify">
      <h3 id="user-modal-notify" class="profile-col-title">Benachrichtigungen</h3>
      <div class="profile-pref-group">
        <h4 class="profile-subhead">Kanäle</h4>
        <div class="profile-prefs profile-prefs--grid">
          <div class="profile-field">
            <span class="profile-label">E-Mail</span>
            <div class="profile-value"><?php echo bool2string($user->getMail); ?></div>
          </div>
          <div class="profile-field">
            <span class="profile-label">Nachrichten</span>
            <div class="profile-value"><?php echo bool2string($user->notifyInbox); ?></div>
          </div>
        </div>
      </div>
      <div class="profile-pref-group">
        <h4 class="profile-subhead">App-Hinweise</h4>
        <div class="profile-prefs profile-prefs--grid">
          <div class="profile-field">
            <span class="profile-label">Nachrichten</span>
            <div class="profile-value"><?php echo bool2string($user->notifyAppMail); ?></div>
          </div>
          <div class="profile-field">
            <span class="profile-label">Neuer Termin</span>
            <div class="profile-value"><?php echo bool2string($user->notifyAppTerminNew); ?></div>
          </div>
          <div class="profile-field">
            <span class="profile-label">Termin geändert</span>
            <div class="profile-value"><?php echo bool2string($user->notifyAppTerminChange); ?></div>
          </div>
          <div class="profile-field">
            <span class="profile-label">Termin bald</span>
            <div class="profile-value"><?php echo bool2string($user->notifyAppTerminSoon); ?></div>
          </div>
        </div>
      </div>
<?php
if($showUserDetails && !empty($permissions)) {
    $inherited = Group::inheritedPermissionSources((int)$user->Index);
    $activePerms = array();
    foreach(Permissions::permissionCatalog() as $item) {
        $key = $item['key'];
        $personal = ((int)$permissions->$key) === 1;
        $groups = isset($inherited[$key]) ? $inherited[$key] : array();
        if(!$personal && !count($groups)) {
            continue;
        }
        $item['personal'] = $personal;
        $item['groups'] = $groups;
        $activePerms[] = $item;
    }
    if(count($activePerms)) {
?>
      <div class="profile-pref-group">
        <h3 class="profile-col-title">Rechte</h3>
        <div class="profile-perm-tiles" aria-label="Aktive Rechte">
<?php
        foreach($activePerms as $item) {
            $gid = preg_replace('/[^a-z0-9_-]/i', '', (string)$item['groupId']);
            $inheritedOnly = empty($item['personal']) && count($item['groups']) > 0;
            $cls = 'profile-perm-tile profile-perm-tile--'.$gid;
            if($inheritedOnly) {
                $cls .= ' profile-perm-tile--inherited';
            }
            $title = '';
            if(count($item['groups'])) {
                $title = ($inheritedOnly ? 'Nur über Gruppe: ' : 'Auch Gruppe: ')
                    .implode(', ', $item['groups']);
            }
            echo '<span class="mail-recipient-chip '.$cls.'"'
                .($title !== '' ? ' title="'.htmlspecialchars($title, ENT_QUOTES, 'UTF-8').'"' : '')
                .'>'
                .htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8')
                .'</span>';
        }
?>
        </div>
      </div>
<?php
    }
}
?>
    </section>
  </div>
</div>
