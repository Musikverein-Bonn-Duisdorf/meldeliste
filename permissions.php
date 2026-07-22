<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page'] = 'permissions';
$_SESSION['adminpage'] = true;
include "common/header.php";
if(!requirePermission("perm_editPermissions")) {
    denyAccess();
}

$permCatalog = Permissions::permissionCatalog();
$permKeys = array();
foreach($permCatalog as $item) {
    $permKeys[] = $item['key'];
}
$permLabels = Permissions::permissionLabels();
$sessionUserId = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;

$sql = sprintf(
    'SELECT `Index` FROM `%sUser` WHERE `Deleted` != 1 ORDER BY `Nachname`, `Vorname`;',
    $GLOBALS['dbprefix']
);
$dbr = mysqli_query($conn, $sql);
sqlerror();

$rows = array();
while($row = mysqli_fetch_array($dbr)) {
    $user = new User;
    $user->load_by_id($row['Index']);
    if(!$user->Index) {
        continue;
    }
    $perm = new Permissions;
    $perm->load_by_user($user->Index);
    $rows[] = array(
        'user' => $user,
        'perm' => $perm,
        'name' => $user->getName(),
        'hasAny' => $perm->hasAnyPermission(),
    );
}
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Berechtigungen bearbeiten</h2>
</div>
<div class="w3-container w3-padding">
  <div class="w3-row w3-margin-bottom">
    <div class="w3-col m6 s12 w3-padding-small">
      <input type="search" id="permFilter" class="w3-input w3-border" placeholder="Name filtern…" autocomplete="off">
    </div>
    <div class="w3-col m6 s12 w3-padding-small w3-padding-16">
      <label class="w3-margin-right">
        <input type="checkbox" id="permOnlyActive"> nur mit mindestens einem Recht
      </label>
      <span id="permSaveStatus" class="w3-small" aria-live="polite"></span>
    </div>
  </div>
</div>
<div class="perm-matrix-wrap w3-margin-bottom">
  <table class="perm-matrix" id="permMatrix">
    <thead>
      <tr>
        <th class="perm-user-col">Benutzer</th>
<?php foreach($permKeys as $key) {
    $meta = $permLabels[$key];
    $gid = Permissions::groupIdForPermission($key);
    $gid = preg_replace('/[^a-z0-9_-]/i', '', (string)$gid);
    if($gid === '') {
        $gid = 'system';
    }
    $labelParts = preg_split('/\s+/u', trim($meta['label']), -1, PREG_SPLIT_NO_EMPTY);
    $labelHtml = htmlspecialchars(implode("\n", $labelParts), ENT_QUOTES, 'UTF-8');
    $labelHtml = nl2br($labelHtml, false);
?>
        <th class="perm-col perm-group perm-group--<?php echo htmlspecialchars($gid, ENT_QUOTES, 'UTF-8'); ?>">
          <span class="perm-col-label"><?php echo $labelHtml; ?></span>
        </th>
<?php } ?>
      </tr>
    </thead>
    <tbody>
<?php foreach($rows as $entry) {
    $uid = (int)$entry['user']->Index;
    $name = (string)$entry['name'];
    $nameAttr = htmlspecialchars(mb_strtolower($name, 'UTF-8'), ENT_QUOTES, 'UTF-8');
?>
      <tr data-name="<?php echo $nameAttr; ?>" data-has-perms="<?php echo $entry['hasAny'] ? '1' : '0'; ?>">
        <td class="perm-user-col"><?php echo htmlspecialchars($name); ?></td>
<?php foreach($permKeys as $key) {
    $on = (bool)$entry['perm']->$key;
    $locked = ($uid === $sessionUserId && $key === 'perm_editPermissions');
    $title = htmlspecialchars($permLabels[$key]['label'], ENT_QUOTES, 'UTF-8');
    $gid = Permissions::groupIdForPermission($key);
    $gid = preg_replace('/[^a-z0-9_-]/i', '', (string)$gid);
    if($gid === '') {
        $gid = 'system';
    }
?>
        <td class="perm-cell perm-group perm-group--<?php echo htmlspecialchars($gid, ENT_QUOTES, 'UTF-8'); ?> <?php echo $on ? 'perm-on' : 'perm-off'; ?>" title="<?php echo $title; ?>">
          <input type="checkbox"
                 class="perm-toggle"
                 data-user="<?php echo $uid; ?>"
                 data-perm="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"
                 <?php echo $on ? 'checked' : ''; ?>
                 <?php echo $locked ? 'disabled title="Eigenes Recht „Berechtigungen bearbeiten“ kann nicht entfernt werden"' : ''; ?>>
        </td>
<?php } ?>
      </tr>
<?php } ?>
    </tbody>
  </table>
</div>
<script>
(function() {
  var matrix = document.getElementById('permMatrix');
  var filterInput = document.getElementById('permFilter');
  var onlyActive = document.getElementById('permOnlyActive');
  var statusEl = document.getElementById('permSaveStatus');
  var statusTimer = null;

  function setStatus(text, ok) {
    if(!statusEl) return;
    statusEl.textContent = text || '';
    statusEl.className = 'w3-small ' + (ok === true ? 'w3-text-green' : (ok === false ? 'w3-text-red' : ''));
    if(statusTimer) clearTimeout(statusTimer);
    if(text) {
      statusTimer = setTimeout(function() {
        statusEl.textContent = '';
        statusEl.className = 'w3-small';
      }, 2500);
    }
  }

  function applyFilter() {
    var q = (filterInput.value || '').toLowerCase().trim();
    var only = onlyActive.checked;
    var rows = matrix.tBodies[0].rows;
    for(var i = 0; i < rows.length; i++) {
      var row = rows[i];
      var name = row.getAttribute('data-name') || '';
      var has = row.getAttribute('data-has-perms') === '1';
      var matchName = !q || name.indexOf(q) !== -1;
      var matchActive = !only || has;
      row.style.display = (matchName && matchActive) ? '' : 'none';
    }
  }

  function refreshRowHasPerms(row) {
    var boxes = row.querySelectorAll('.perm-toggle');
    var any = false;
    for(var i = 0; i < boxes.length; i++) {
      if(boxes[i].checked) { any = true; break; }
    }
    row.setAttribute('data-has-perms', any ? '1' : '0');
  }

  function saveToggle(cb) {
    var cell = cb.parentNode;
    var previous = !cb.checked;
    cell.classList.remove('perm-error');
    cell.classList.add('perm-saving');
    setStatus('Speichern…', null);

    var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    xhr.onreadystatechange = function() {
      if(xhr.readyState !== 4) return;
      cell.classList.remove('perm-saving');
      var ok = false;
      var err = 'Speichern fehlgeschlagen';
      try {
        var data = JSON.parse(xhr.responseText || '{}');
        ok = xhr.status >= 200 && xhr.status < 300 && data && data.ok;
        if(data && data.error === 'cannot_remove_own_edit') {
          err = 'Eigenes Recht „Berechtigungen bearbeiten“ kann nicht entfernt werden';
        }
      } catch(e) {}
      if(!ok) {
        cb.checked = previous;
        cell.classList.toggle('perm-on', previous);
        cell.classList.toggle('perm-off', !previous);
        cell.classList.add('perm-error');
        setStatus(err, false);
        return;
      }
      cell.classList.toggle('perm-on', cb.checked);
      cell.classList.toggle('perm-off', !cb.checked);
      refreshRowHasPerms(cell.parentNode);
      applyFilter();
      setStatus('Gespeichert', true);
    };
    xhr.open('POST', 'savePermission.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.send(
      'user=' + encodeURIComponent(cb.getAttribute('data-user')) +
      '&perm=' + encodeURIComponent(cb.getAttribute('data-perm')) +
      '&value=' + (cb.checked ? '1' : '0')
    );
  }

  if(filterInput) filterInput.addEventListener('input', applyFilter);
  if(onlyActive) onlyActive.addEventListener('change', applyFilter);

  matrix.addEventListener('change', function(ev) {
    var t = ev.target;
    if(!t || !t.classList || !t.classList.contains('perm-toggle')) return;
    if(t.disabled) return;
    t.parentNode.classList.toggle('perm-on', t.checked);
    t.parentNode.classList.toggle('perm-off', !t.checked);
    saveToggle(t);
  });
})();
</script>
<?php
include "common/footer.php";
?>
