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

MailGroup::ensureSchema();

$msg = '';
$err = '';
$g = new MailGroup();
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
$catalog = AudienceSpec::buildCatalog(array(
    'forMail' => false,
    'includeMailGroups' => false,
));

include 'common/header.php';
$inputCls = $GLOBALS['optionsDB']['colorInputBackground'];
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2><?php echo (int)$g->Index ? 'Gruppe bearbeiten' : 'Neue Gruppe'; ?></h2>
</div>
<?php if($msg) { ?><div class="w3-panel w3-green w3-padding"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>
<?php if($err) { ?><div class="w3-panel w3-red w3-padding"><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>

<div class="w3-container w3-padding">
  <a href="groups.php">← Zur Übersicht</a>
</div>

<form method="post" class="w3-container w3-padding">
  <?php if((int)$g->Index) { ?>
  <input type="hidden" name="Index" value="<?php echo (int)$g->Index; ?>" />
  <?php } ?>
  <label>Name</label>
  <input class="w3-input w3-border w3-margin-bottom <?php echo $inputCls; ?>" type="text" name="Name" required
    value="<?php echo htmlspecialchars((string)$g->Name, ENT_QUOTES, 'UTF-8'); ?>"
    placeholder="z.B. Egerländerbesetzung" />

  <label>Mitglieder</label>
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
include 'common/footer.php';
?>
