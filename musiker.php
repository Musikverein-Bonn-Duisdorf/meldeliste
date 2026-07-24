<?php
ob_start();
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page'] = 'musiker';
$_SESSION['adminpage'] = true;

include_once 'common/include.php';
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($GLOBALS['conn']));
requireLoggedInOrRedirect();

if(!requirePermission('perm_showUsers')) {
    $logentry = new Log;
    $logentry->error(sprintf(
        'Zugriff auf musiker.php verweigert | User-ID: <b>%d</b>',
        isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0
    ));
    denyAccess('Keine Berechtigung. Eigenes Profil bitte über „Mein Profil“ speichern.');
}

include_once 'libs/form-response.php';
applyUserFormPostRedirect('musiker.php', array('allowNewUser' => true));

include 'common/header.php';

$sql = sprintf(
    'SELECT COUNT(`Index`) AS `Count` FROM `%sUser` WHERE `Deleted` != 1;',
    $GLOBALS['dbprefix']
);
$dbr = mysqli_query($conn, $sql);
sqlerror();
$row = mysqli_fetch_array($dbr);
$nPersonen = (int)$row['Count'];

$filterAktive = !isset($_GET['aktive']) || (string)$_GET['aktive'] !== '0';
$filterGaeste = !isset($_GET['gaeste']) || (string)$_GET['gaeste'] !== '0';
$filterMitglied = !isset($_GET['mitglied']) || (string)$_GET['mitglied'] !== '0';
$filterNoMitglied = !isset($_GET['nomitglied']) || (string)$_GET['nomitglied'] !== '0';

$registers = array();
$regSql = sprintf(
    'SELECT `Index`, `Name`, `Color`, `Sortierung` FROM `%sRegister` WHERE `Name` != "keins" ORDER BY `Sortierung`, `Name`;',
    $GLOBALS['dbprefix']
);
$regDbr = mysqli_query($conn, $regSql);
sqlerror();
if($regDbr) {
    while($regRow = mysqli_fetch_assoc($regDbr)) {
        $registers[] = $regRow;
    }
}
?>
<?php echo renderFlashHtml(); ?>
<?php adminListPageBegin('Personen', 'Personen ('.$nPersonen.')'); ?>
<?php adminListSearchField('Nach Person suchen…', array('onkeyup' => 'filterPersonen()')); ?>
<?php if(!empty($GLOBALS['optionsDB']['showOrchestraView'])) { ?>
<details class="orchestra-fold" id="orchestraFold" open>
  <summary class="orchestra-fold-summary">Orchestergrafik</summary>
  <div class="w3-center orchestra-svg-wrap">
<?php echo printOrchestra(0); ?>
  </div>
</details>
<?php } ?>
<div id="listHeader" class="inv-sort-bar">
  <div class="inv-sort-bar-filters" role="toolbar" aria-label="Filter">
    <button type="button" class="inv-sort-chip inv-filter-chip<?php echo $filterAktive ? ' is-active' : ''; ?>" data-personen-filter="aktive" aria-pressed="<?php echo $filterAktive ? 'true' : 'false'; ?>">Aktive</button>
    <button type="button" class="inv-sort-chip inv-filter-chip<?php echo $filterGaeste ? ' is-active' : ''; ?>" data-personen-filter="gaeste" aria-pressed="<?php echo $filterGaeste ? 'true' : 'false'; ?>">Gäste</button>
    <button type="button" class="inv-sort-chip inv-filter-chip<?php echo $filterMitglied ? ' is-active' : ''; ?>" data-personen-filter="mitglied" aria-pressed="<?php echo $filterMitglied ? 'true' : 'false'; ?>">Mitglieder</button>
    <button type="button" class="inv-sort-chip inv-filter-chip<?php echo $filterNoMitglied ? ' is-active' : ''; ?>" data-personen-filter="nomitglied" aria-pressed="<?php echo $filterNoMitglied ? 'true' : 'false'; ?>">Nicht-Mitglieder</button>
  </div>
  <div class="inv-sort-bar-filters inv-sort-bar-filters--registers" role="toolbar" aria-label="Register-Filter">
    <button type="button" class="inv-sort-chip inv-filter-chip" data-register-filter="0" aria-pressed="false">ohne Register</button>
<?php foreach($registers as $reg) {
    $hex = normalizeHexColor(isset($reg['Color']) ? $reg['Color'] : '');
    $style = $hex !== '' ? ' style="--reg-filter-color:'.$hex.'"' : '';
    $label = htmlspecialchars((string)$reg['Name'], ENT_QUOTES, 'UTF-8');
?>
    <button type="button" class="inv-sort-chip inv-filter-chip inv-filter-chip--register" data-register-filter="<?php echo (int)$reg['Index']; ?>" aria-pressed="false"<?php echo $style; ?>><?php echo $label; ?></button>
<?php } ?>
  </div>
  <div class="inv-sort-bar-sorts" role="toolbar" aria-label="Sortierung">
    <button type="button" class="inv-sort-chip list-sort" data-sort="register" data-type="string">Register</button>
    <button type="button" class="inv-sort-chip list-sort" data-sort="nachname" data-type="string">Name</button>
    <button type="button" class="inv-sort-chip list-sort" data-sort="instrument" data-type="string">Instrument</button>
    <button type="button" class="inv-sort-chip list-sort" data-sort="index" data-type="number">ID</button>
    <button type="button" class="inv-sort-chip list-sort" data-sort="email" data-type="string">E-Mail</button>
    <button type="button" class="inv-sort-chip list-sort" data-sort="lastlogin" data-type="date">Login</button>
    <button type="button" class="inv-sort-chip list-sort" data-sort="lastvisit" data-type="date">Teilnahme</button>
  </div>
</div>
<div id="Liste" class="user-list">
<?php
$listSql = sprintf(
    'SELECT u.`Index` FROM `%sUser` u
     LEFT JOIN `%sInstrument` i ON u.`Instrument` = i.`Index`
     LEFT JOIN `%sRegister` r ON i.`Register` = r.`Index`
     WHERE u.`Deleted` != 1
     ORDER BY COALESCE(r.`Sortierung`, 9999) ASC, u.`Nachname` ASC, u.`Vorname` ASC, u.`Index` ASC;',
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix'],
    $GLOBALS['dbprefix']
);
$listDbr = mysqli_query($conn, $listSql);
sqlerror();
if($listDbr) {
    while($listRow = mysqli_fetch_array($listDbr)) {
        $M = new User;
        $M->load_by_id((int)$listRow['Index']);
        $M->printTableLine();
    }
}
?>
</div>
<?php adminListPageEnd(); ?>
<script src="js/filterPersonen.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/sortList.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script>
bindListSort({ headerId: 'listHeader', listId: 'Liste', mode: 'client', defaultKey: 'register', defaultDir: 'asc', defaultType: 'string' });
(function () {
  var fold = document.getElementById('orchestraFold');
  if(!fold) return;
  try {
    if(localStorage.getItem('meldeOrchestraFold') === '0') {
      fold.removeAttribute('open');
    }
  } catch (e) {}
  fold.addEventListener('toggle', function () {
    try {
      localStorage.setItem('meldeOrchestraFold', fold.open ? '1' : '0');
    } catch (e2) {}
  });
})();
</script>

<?php
include 'common/footer.php';
?>
