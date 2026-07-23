<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page'] = 'groups';
$_SESSION['adminpage'] = true;

include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));

if(!loggedIn()) {
    header('Location: login.php');
    exit;
}
if(!empty($_SESSION['singleUsePW'])) {
    header('Location: changePW.php');
    exit;
}
if(!requirePermission('perm_sendEmail')) {
    denyAccess();
}

Group::ensureSchema();

$msg = '';
$err = '';
$g = new Group();
$editId = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['Index']) ? (int)$_POST['Index'] : 0);
if($editId > 0) {
    $g->load_by_id($editId);
}

// Speichern vor HTML-Ausgabe (Redirect)
if(isset($_POST['save'])) {
    $g->Name = isset($_POST['Name']) ? $_POST['Name'] : '';
    $specRaw = isset($_POST['memberSpec']) ? $_POST['memberSpec'] : '{}';
    $decoded = json_decode($specRaw, true);
    if(!is_array($decoded)) {
        $decoded = AudienceSpec::emptySpec();
    }
    $g->setMemberSpecArray($decoded);
    $permPosted = array();
    if(isset($_POST['groupPermissions']) && is_array($_POST['groupPermissions'])) {
        $permPosted = $_POST['groupPermissions'];
    }
    $g->setPermissionSpecArray($permPosted);
    if(!(int)$g->Index) {
        $g->CreatedBy = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
    }
    if(!$g->is_valid()) {
        $err = 'Name ist Pflicht.';
    }
    elseif($g->save()) {
        header('Location: group-edit.php?id='.(int)$g->Index.'&saved=1');
        exit;
    }
    else {
        $err = 'Speichern fehlgeschlagen.';
    }
}

if(isset($_GET['saved'])) {
    $msg = 'Gruppe gespeichert.';
}

$memberSpec = $g->Index ? $g->getMemberSpecArray() : AudienceSpec::emptySpec();
$groupPerms = $g->Index ? $g->getPermissionSpecArray() : array();
$groupPermFlip = array_flip($groupPerms);
$catalog = AudienceSpec::buildCatalog(array(
    'forMail' => false,
    'includeNamedGroups' => false,
));

include 'common/header.php';
$inputCls = $GLOBALS['optionsDB']['colorInputBackground'];
$groupTitle = (int)$g->Index ? 'Gruppe bearbeiten' : 'Neue Gruppe';
$backLink = '<a class="w3-button w3-border" href="groups.php">Zur Übersicht</a>';
adminListPageBegin('Kommunikation', $groupTitle, array('actionsHtml' => $backLink));
?>
<?php if($msg) { ?><div class="w3-panel w3-green w3-padding"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>
<?php if($err) { ?><div class="w3-panel w3-red w3-padding"><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>

<form method="post" class="profile-form">
  <?php if((int)$g->Index) { ?>
  <input type="hidden" name="Index" value="<?php echo (int)$g->Index; ?>" />
  <?php } ?>
  <div class="profile-field">
    <label class="profile-label" for="groupName">Name</label>
    <input id="groupName" class="w3-input w3-border profile-control <?php echo $inputCls; ?>" type="text" name="Name" required
      value="<?php echo htmlspecialchars((string)$g->Name, ENT_QUOTES, 'UTF-8'); ?>"
      placeholder="z.B. Egerländerbesetzung" />
  </div>

  <div class="profile-field">
    <label class="profile-label">Mitglieder</label>
    <p class="w3-small w3-text-gray">Rollen, Register und Personen (Union). Beispiel: Posaunen + Schlagwerk + Klarinetten + einzelne Personen.</p>
    <div class="w3-mobile w3-margin-bottom w3-padding w3-border <?php echo $inputCls; ?>">
      <div id="mailRecipientChips" class="mail-recipient-chips" aria-live="polite"></div>
      <input type="text" id="mailRecipientInput" class="w3-input w3-border <?php echo $inputCls; ?>" placeholder="Rolle, Register oder Person tippen…" autocomplete="off" />
      <div id="mailRecipientSuggest" class="mail-recipient-suggest" hidden></div>
      <input type="hidden" name="memberSpec" id="mailRecipientSpec" value="<?php echo htmlspecialchars(json_encode($memberSpec), ENT_QUOTES, 'UTF-8'); ?>" />
      <p class="w3-small w3-margin-top mail-recipient-count-line">
        <span id="mailRecipientCount" class="mail-recipient-count" aria-live="polite">…</span>
      </p>
    </div>
  </div>

  <div class="profile-field">
    <label class="profile-label">Vererbte Rechte</label>
    <p class="w3-small w3-text-gray">Mitglieder dieser Gruppe erhalten die gesetzten Rechte zusätzlich zu ihren persönlichen Rechten.</p>
    <div class="profile-perm-tiles w3-padding w3-border <?php echo htmlspecialchars($inputCls, ENT_QUOTES, 'UTF-8'); ?>">
<?php foreach(Permissions::permissionCatalog() as $item) {
    $key = $item['key'];
    $gid = preg_replace('/[^a-z0-9_-]/i', '', (string)$item['groupId']);
    if($gid === '') {
        $gid = 'system';
    }
    $checked = isset($groupPermFlip[$key]);
    $id = 'groupPerm_'.$key;
?>
      <label class="profile-pref perm-group perm-group--<?php echo htmlspecialchars($gid, ENT_QUOTES, 'UTF-8'); ?>" for="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="checkbox" id="<?php echo htmlspecialchars($id, ENT_QUOTES, 'UTF-8'); ?>"
          name="groupPermissions[]" value="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"
          <?php echo $checked ? 'checked' : ''; ?>>
        <span><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
      </label>
<?php } ?>
    </div>
  </div>

  <button class="w3-button <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?>" type="submit" name="save" value="1">Speichern</button>
</form>

<script type="application/json" id="mailRecipientCatalog"><?php echo json_encode($catalog, JSON_UNESCAPED_UNICODE); ?></script>
<script src="js/mailRecipients.js?<?php echo isset($GLOBALS['version']['Hash']) ? $GLOBALS['version']['Hash'] : '0'; ?>-<?php echo @filemtime(__DIR__.'/js/mailRecipients.js'); ?>"></script>
<script>
(function() {
  if(typeof MailRecipientChips === 'undefined') return;
  MailRecipientChips.init({
    catalogEl: document.getElementById('mailRecipientCatalog'),
    chipsEl: document.getElementById('mailRecipientChips'),
    inputEl: document.getElementById('mailRecipientInput'),
    suggestEl: document.getElementById('mailRecipientSuggest'),
    hiddenEl: document.getElementById('mailRecipientSpec'),
    countEl: document.getElementById('mailRecipientCount'),
    countUrl: 'mailRecipientCount.php',
    countLabel: 'Mitglieder',
    allowEmpty: true,
    defaultGroups: [],
    jobId: 0
  });
})();
</script>
<?php
adminListPageEnd();
include 'common/footer.php';
?>
