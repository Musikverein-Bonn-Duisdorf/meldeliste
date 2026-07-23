<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page'] = 'groups';
$_SESSION['adminpage'] = true;
include 'common/header.php';
if(!requirePermission('perm_sendEmail')) {
    denyAccess();
}

Group::ensureSchema();

$msg = '';
$err = '';

if(isset($_POST['delete']) && isset($_POST['Index'])) {
    $g = new Group();
    $g->load_by_id((int)$_POST['Index']);
    if((int)$g->Index && $g->delete()) {
        $msg = 'Gruppe gelöscht.';
    }
    else {
        $err = 'Löschen fehlgeschlagen.';
    }
}

$groups = Group::listAll();
$actions = '<a class="w3-button '.$GLOBALS['optionsDB']['colorBtnSubmit'].'" href="group-edit.php">Neue Gruppe</a>'
    .' <a class="w3-button w3-border" href="mail.php">Email versenden</a>';
adminListPageBegin('Kommunikation', 'Gruppen', array('actionsHtml' => $actions));
?>
<?php if($msg) { ?><div class="w3-panel w3-green w3-padding"><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>
<?php if($err) { ?><div class="w3-panel w3-red w3-padding"><?php echo htmlspecialchars($err, ENT_QUOTES, 'UTF-8'); ?></div><?php } ?>

  <div class="mail-list">
    <div class="mail-list-header <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
      <div>Name</div>
      <div>Mitglieder</div>
      <div>Aktion</div>
    </div>
<?php
if(!count($groups)) {
    echo '<div class="mail-list-item"><div class="mail-list-primary">Noch keine Gruppen angelegt.</div></div>';
}
foreach($groups as $g) {
    $id = (int)$g->Index;
    $count = (int)$g->memberCount(false);
    $countMail = (int)$g->memberCount(true);
    $permKeys = $g->getPermissionSpecArray();
    $permKeySet = array_fill_keys($permKeys, true);
    $permTiles = array();
    if(count($permKeys)) {
        foreach(Permissions::permissionCatalog() as $item) {
            if(empty($permKeySet[$item['key']])) {
                continue;
            }
            $permTiles[] = $item;
        }
    }
?>
    <div class="mail-list-item">
      <div class="mail-list-primary">
        <?php echo htmlspecialchars((string)$g->Name, ENT_QUOTES, 'UTF-8'); ?>
<?php if(count($permTiles)) { ?>
        <div class="profile-perm-tiles mail-list-perm-tiles" aria-label="Vererbte Rechte">
<?php
            foreach($permTiles as $item) {
                $gid = preg_replace('/[^a-z0-9_-]/i', '', (string)$item['groupId']);
                echo '<span class="profile-perm-tile profile-perm-tile--'.$gid.'">'
                    .htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8')
                    .'</span>';
            }
?>
        </div>
<?php } ?>
      </div>
      <div class="mail-list-status"><?php echo $count; ?> <span class="w3-small w3-text-gray">(<?php echo $countMail; ?> mit Mail)</span></div>
      <div class="mail-list-actions">
        <a class="w3-button w3-small w3-blue" href="group-edit.php?id=<?php echo $id; ?>">Bearbeiten</a>
        <form method="post" style="display:inline;" onsubmit="return confirm('Gruppe wirklich löschen?');">
          <input type="hidden" name="Index" value="<?php echo $id; ?>" />
          <button class="w3-button w3-small w3-red" type="submit" name="delete" value="1">Löschen</button>
        </form>
      </div>
    </div>
<?php } ?>
  </div>
<?php
adminListPageEnd();
include 'common/footer.php';
?>
