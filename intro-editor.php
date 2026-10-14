<?php
/**
 * MELD-249: Admin editor for intro / changelog multipage content.
 */
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page'] = 'intro-editor';
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
if(!requirePermission('perm_editConfig')) {
    denyAccess('Keine Berechtigung für die Konfiguration.');
}

$kind = isset($_GET['kind']) ? (string)$_GET['kind'] : 'intro';
if($kind !== 'changelog') {
    $kind = 'intro';
}

$inputBg = (string)$GLOBALS['optionsDB']['colorInputBackground'];
$btnEdit = (string)$GLOBALS['optionsDB']['colorBtnEdit'];
$btnSubmit = (string)$GLOBALS['optionsDB']['colorBtnSubmit'];

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!csrf_verify(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
        setFlash('error', 'Ungültige Anfrage (CSRF).');
        header('Location: intro-editor.php?kind='.urlencode($kind));
        exit;
    }
    $kind = isset($_POST['kind']) && $_POST['kind'] === 'changelog' ? 'changelog' : 'intro';
    $titles = isset($_POST['pageTitle']) && is_array($_POST['pageTitle']) ? $_POST['pageTitle'] : array();
    $bodies = isset($_POST['pageHtml']) && is_array($_POST['pageHtml']) ? $_POST['pageHtml'] : array();
    $pages = array();
    $n = max(count($titles), count($bodies));
    for($i = 0; $i < $n; $i++) {
        $pages[] = array(
            'title' => isset($titles[$i]) ? (string)$titles[$i] : '',
            'html' => isset($bodies[$i]) ? (string)$bodies[$i] : '',
        );
    }
    $pages = Intro::normalizePages($pages);
    if($pages === array()) {
        setFlash('error', 'Mindestens eine Seite mit Inhalt nötig.');
        header('Location: intro-editor.php?kind='.urlencode($kind));
        exit;
    }
    $publish = isset($_POST['publish']);
    $version = Intro::savePages($kind, $pages, $publish);
    $GLOBALS['optionsDB'] = loadconfig();
    if($publish) {
        setFlash('success', ($kind === 'changelog' ? 'Changelog' : 'Einführung').' veröffentlicht (Version '.$version.').');
    }
    else {
        setFlash('success', 'Gespeichert (Version unverändert).');
    }
    header('Location: intro-editor.php?kind='.urlencode($kind));
    exit;
}

$pages = $kind === 'changelog' ? Intro::changelogPages() : Intro::introPages();
if($pages === array()) {
    $pages = array(array('title' => '', 'html' => ''));
}
$version = $kind === 'changelog' ? Intro::changelogVersion() : Intro::introVersion();

include 'common/header.php';
?>
<div class="w3-row">
<div class="w3-col s12 m1 l1 w3-hide-small">&nbsp;</div>
<div class="w3-panel w3-mobile w3-border w3-col s12 m10 l10" style="text-align:left;">
  <p class="w3-left-align"><b>Einführung / Changelog</b>
    <a class="w3-right w3-small" href="config-menu.php">Konfiguration</a>
  </p>
  <p class="w3-small">Version <?php echo (int)$version; ?>
    · <a href="intro-editor.php?kind=intro"<?php echo $kind === 'intro' ? ' class="w3-text-blue"' : ''; ?>>Einführung</a>
    · <a href="intro-editor.php?kind=changelog"<?php echo $kind === 'changelog' ? ' class="w3-text-blue"' : ''; ?>>Changelog</a>
  </p>

  <form id="introEditorForm" name="introEditorForm" class="w3-container w3-margin" action="intro-editor.php?kind=<?php echo htmlspecialchars($kind, ENT_QUOTES, 'UTF-8'); ?>" method="POST">
    <?php echo csrf_field(); ?>
    <input type="hidden" name="kind" value="<?php echo htmlspecialchars($kind, ENT_QUOTES, 'UTF-8'); ?>">
    <div id="intro-pages">
<?php foreach($pages as $i => $page) {
    $idx = (int)$i;
    $title = isset($page['title']) ? (string)$page['title'] : '';
    $html = isset($page['html']) ? (string)$page['html'] : '';
?>
      <div class="intro-editor-page w3-border w3-padding w3-margin-bottom" data-page-index="<?php echo $idx; ?>">
        <div class="w3-row w3-margin-bottom">
          <div class="w3-col s8">
            <label>Titel</label>
            <input class="w3-input w3-border <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>"
                   name="pageTitle[]" type="text"
                   value="<?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?>">
          </div>
          <div class="w3-col s4 w3-right-align" style="padding-top:1.5rem;">
            <button type="button" class="w3-button w3-small w3-border intro-page-remove">Entfernen</button>
          </div>
        </div>
        <label>Text</label>
        <textarea class="intro-page-html w3-input w3-border <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>"
                  name="pageHtml[]" rows="10"><?php echo htmlspecialchars($html, ENT_QUOTES, 'UTF-8'); ?></textarea>
      </div>
<?php } ?>
    </div>
    <div class="mail-compose-actions">
      <button type="button" class="w3-btn w3-border w3-margin-bottom w3-mobile" id="introPageAdd">Seite</button>
      <button class="w3-btn <?php echo htmlspecialchars($btnEdit, ENT_QUOTES, 'UTF-8'); ?> w3-margin-bottom w3-mobile" name="save" value="1">Speichern</button>
      <button class="w3-btn <?php echo htmlspecialchars($btnSubmit, ENT_QUOTES, 'UTF-8'); ?> w3-margin-bottom w3-mobile" name="publish" value="1">Veröffentlichen</button>
    </div>
  </form>
</div>
<div class="w3-col s12 m1 l1 w3-hide-small">&nbsp;</div>
</div>
<template id="intro-page-template">
  <div class="intro-editor-page w3-border w3-padding w3-margin-bottom">
    <div class="w3-row w3-margin-bottom">
      <div class="w3-col s8">
        <label>Titel</label>
        <input class="w3-input w3-border <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>"
               name="pageTitle[]" type="text" value="">
      </div>
      <div class="w3-col s4 w3-right-align" style="padding-top:1.5rem;">
        <button type="button" class="w3-button w3-small w3-border intro-page-remove">Entfernen</button>
      </div>
    </div>
    <label>Text</label>
    <textarea class="intro-page-html w3-input w3-border <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>"
              name="pageHtml[]" rows="10"></textarea>
  </div>
</template>
<script src="<?php echo assetUrl('js/tinymce/tinymce.min.js'); ?>"></script>
<script src="<?php echo assetUrl('js/introEditor.js'); ?>"></script>
<?php
include 'common/footer.php';
