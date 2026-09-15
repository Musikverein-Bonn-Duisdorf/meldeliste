<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page'] = 'sammlungen';
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
if(!requirePermission('perm_editAppmnts') || !archivFeatureEnabled()) {
    denyAccess();
}

CollectionGrant::ensureSchema();

if(isset($_POST['move']) && isset($_POST['Collection'])) {
    $dir = (string)$_POST['move'];
    CollectionGrant::move((int)$_POST['Collection'], $dir);
    header('Location: sammlungen.php');
    exit;
}

include 'common/header.php';

$rows = CollectionGrant::listAllWithMeta();
$n = count($rows);
$inputCls = $GLOBALS['optionsDB']['colorInputBackground'];
$catalog = AudienceSpec::buildCatalog(array(
    'forMail' => false,
    'includeNamedGroups' => true,
    'includeTermine' => true,
));
// Bereits gewählte Termine (auch vergangene) im Katalog halten
foreach($rows as $row) {
    $grant = isset($row['grant']) ? $row['grant'] : null;
    if(!$grant || !(int)$grant->Index) {
        continue;
    }
    $spec = $grant->getAccessSpecArray();
    if(empty($spec['termine']) || !is_array($spec['termine'])) {
        continue;
    }
    $known = array();
    foreach($catalog['termine'] as $trow) {
        $known[(int)$trow['id']] = true;
    }
    foreach($spec['termine'] as $tid) {
        $tid = (int)$tid;
        if($tid <= 0 || isset($known[$tid])) {
            continue;
        }
        $catalog['termine'][] = array(
            'id' => $tid,
            'label' => AudienceSpec::terminParticipantLabel($tid),
            'meta' => 'Termin',
        );
        $known[$tid] = true;
    }
}
adminListPageBegin('', 'Sammlungen', array('hideKicker' => true));
?>

<?php if(!$n) { ?>
  <div class="mail-list-item"><div class="mail-list-primary">Keine Sammlungen.</div></div>
<?php } else { ?>
  <div class="sammlung-fold-list">
<?php
foreach($rows as $i => $row) {
    $id = (int)$row['id'];
    $grant = $row['grant'];
    $accessSpec = ($grant && $grant->Index) ? $grant->getAccessSpecArray() : AudienceSpec::emptySpec();
    $specJson = htmlspecialchars(json_encode($accessSpec), ENT_QUOTES, 'UTF-8');

    $toolbar = '<form method="post" class="sammlung-fold-sort">';
    $toolbar .= '<input type="hidden" name="Collection" value="'.$id.'" />';
    $toolbar .= '<button class="w3-button w3-small w3-border" type="submit" name="move" value="up"'.($i === 0 ? ' disabled' : '').' title="Hoch" aria-label="Hoch"><i class="fas fa-arrow-up" aria-hidden="true"></i></button>';
    $toolbar .= '<button class="w3-button w3-small w3-border" type="submit" name="move" value="down"'.($i === $n - 1 ? ' disabled' : '').' title="Runter" aria-label="Runter"><i class="fas fa-arrow-down" aria-hidden="true"></i></button>';
    $toolbar .= '</form>';

    $grantHtml = '<div class="sammlung-grant-chips w3-padding w3-border '.$inputCls.'" data-collection-grant="'.$id.'">';
    $grantHtml .= '<div class="mail-recipient-chips" id="grantChips'.$id.'" aria-live="polite"></div>';
    $grantHtml .= '<input type="text" id="grantInput'.$id.'" class="w3-input w3-border '.$inputCls.'" placeholder="Rolle, Register, Gruppe, Termin oder Person…" autocomplete="off" aria-label="Freigabe" />';
    $grantHtml .= '<div id="grantSuggest'.$id.'" class="mail-recipient-suggest" hidden></div>';
    $grantHtml .= '<input type="hidden" id="grantSpec'.$id.'" value="'.$specJson.'" />';
    $grantHtml .= '</div>';

    echo sammlungFoldHtml($id, $row['name'], array(
        'toolbarHtml' => $toolbar,
        'grantHtml' => $grantHtml,
    ));
}
?>
  </div>
  <script type="application/json" id="sammlungGrantCatalog"><?php echo json_encode($catalog, JSON_UNESCAPED_UNICODE); ?></script>
  <script src="js/mailRecipients.js?<?php echo isset($GLOBALS['version']['Hash']) ? $GLOBALS['version']['Hash'] : '0'; ?>-<?php echo @filemtime(__DIR__.'/js/mailRecipients.js'); ?>"></script>
  <script>
(function() {
  if(typeof MailRecipientChips === 'undefined' || !MailRecipientChips.create) return;
  var catalogEl = document.getElementById('sammlungGrantCatalog');
  var saveUrl = 'saveCollectionGrant.php';
  var saveTimers = {};

  function saveGrant(collectionId, hiddenEl) {
    if(!hiddenEl) return;
    var body = 'collection=' + encodeURIComponent(String(collectionId))
      + '&accessSpec=' + encodeURIComponent(hiddenEl.value || '{}');
    var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    xhr.open('POST', saveUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.send(body);
  }

  document.querySelectorAll('[data-collection-grant]').forEach(function(wrap) {
    var id = Number(wrap.getAttribute('data-collection-grant'));
    if(!(id > 0)) return;
    var hiddenEl = document.getElementById('grantSpec' + id);
    MailRecipientChips.create({
      catalogEl: catalogEl,
      chipsEl: document.getElementById('grantChips' + id),
      inputEl: document.getElementById('grantInput' + id),
      suggestEl: document.getElementById('grantSuggest' + id),
      hiddenEl: hiddenEl,
      countEl: null,
      allowEmpty: true,
      defaultGroups: [],
      jobId: 0,
      onChange: function() {
        if(saveTimers[id]) clearTimeout(saveTimers[id]);
        saveTimers[id] = setTimeout(function() {
          saveGrant(id, hiddenEl);
        }, 280);
      }
    });
  });
})();
  </script>
<?php } ?>
<?php
adminListPageEnd();
include 'common/footer.php';
?>
