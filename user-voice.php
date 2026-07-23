<?php
ob_start();
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page'] = 'user-voice';
$_SESSION['adminpage'] = true;

include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

if(empty($GLOBALS['optionsDB']['urlNotenarchiv'])) {
    denyAccess();
}

$userId = isset($_REQUEST['user']) ? (int)$_REQUEST['user'] : 0;
if($userId < 1) {
    $userId = (int)$_SESSION['userid'];
}

$canEditUsers = requirePermission('perm_editUsers');
$isSelf = ($userId === (int)$_SESSION['userid']);
if(!$canEditUsers && !$isSelf) {
    denyAccess();
}

$target = new User();
$target->load_by_id($userId);
if(!$target->Index || (int)$target->Deleted === 1) {
    die('<div class="w3-panel w3-red w3-padding"><b>Benutzer nicht gefunden.</b></div>');
}

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_voice'])) {
    $primaryInstrument = isset($_POST['primary_instrument']) ? (int)$_POST['primary_instrument'] : 0;
    $primaryVoice = isset($_POST['primary_voice']) ? trim((string)$_POST['primary_voice']) : '';
    $fallbacks = array();
    if(isset($_POST['fb_instrument']) && is_array($_POST['fb_instrument'])) {
        foreach($_POST['fb_instrument'] as $i => $instr) {
            $voice = isset($_POST['fb_voice'][$i]) ? trim((string)$_POST['fb_voice'][$i]) : '';
            $fallbacks[] = array(
                'instrument' => (int)$instr,
                'voice' => $voice,
            );
        }
    }
    UserVoice::savePrimaryAndFallbacks($userId, $primaryInstrument, $primaryVoice, $fallbacks);
    setFlash('success', 'Stimmen gespeichert.');
    redirectAfterPost('user-voice.php?user='.$userId);
}

$voices = UserVoice::listByUser($userId);
$primaryInstrument = (int)$target->Instrument;
$primaryVoice = '1';
$fallbackRows = array();
foreach($voices as $uv) {
    if((int)$uv->Priority === 0) {
        $primaryInstrument = (int)$uv->Instrument;
        $primaryVoice = (string)$uv->VoiceLabel;
    }
    else {
        $fallbackRows[] = $uv;
    }
}

include 'common/header.php';
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Stimme / Fallbacks — <?php echo htmlspecialchars($target->Vorname.' '.$target->Nachname, ENT_QUOTES, 'UTF-8'); ?></h2>
</div>
<div class="w3-panel w3-mobile w3-center w3-col s3 l4"></div>
<div class="w3-card <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-mobile w3-center w3-border w3-padding w3-col s6 l4">
<?php echo renderFlashHtml(); ?>
  <form method="POST" class="w3-left-align">
    <input type="hidden" name="save_voice" value="1">
    <h4>Primär</h4>
    <label>Instrument</label>
    <select class="w3-input w3-border w3-margin-bottom" name="primary_instrument">
      <?php echo instrumentOption($primaryInstrument); ?>
    </select>
    <label>Stimmenbezeichnung</label>
    <input class="w3-input w3-border w3-margin-bottom" type="text" name="primary_voice" value="<?php echo htmlspecialchars($primaryVoice, ENT_QUOTES, 'UTF-8'); ?>" placeholder="z.&nbsp;B. 1, Solo, Alt">

    <h4>Fallbacks</h4>
    <div id="fallback-rows">
<?php
$fbCount = max(count($fallbackRows), 1);
for($i = 0; $i < $fbCount; $i++) {
    $fbInstr = isset($fallbackRows[$i]) ? (int)$fallbackRows[$i]->Instrument : 0;
    $fbVoice = isset($fallbackRows[$i]) ? (string)$fallbackRows[$i]->VoiceLabel : '';
?>
      <div class="w3-row w3-margin-bottom fallback-row">
        <div class="w3-col s5">
          <select class="w3-input w3-border" name="fb_instrument[]">
            <?php echo instrumentOption($fbInstr); ?>
          </select>
        </div>
        <div class="w3-col s5 w3-padding-small">
          <input class="w3-input w3-border" type="text" name="fb_voice[]" value="<?php echo htmlspecialchars($fbVoice, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Stimme">
        </div>
      </div>
<?php } ?>
    </div>
    <button type="button" class="w3-btn w3-small w3-margin-bottom" onclick="addFallbackRow()">+ Fallback</button>
    <br>
    <button class="w3-btn <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-margin-top" type="submit">Speichern</button>
<?php if($canEditUsers) { ?>
    <a class="w3-btn w3-margin-top" href="new-musiker.php?id=<?php echo (int)$userId; ?>">Zurück zum Profil</a>
<?php } ?>
  </form>
</div>
<script>
function addFallbackRow() {
  var container = document.getElementById('fallback-rows');
  var template = container.querySelector('.fallback-row');
  if(!template) return;
  var clone = template.cloneNode(true);
  var voice = clone.querySelector('input[name="fb_voice[]"]');
  if(voice) voice.value = '';
  container.appendChild(clone);
}
</script>
<?php include 'common/footer.php'; ?>
