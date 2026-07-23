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
$inputBg = $GLOBALS['optionsDB']['colorInputBackground'];

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
    $inherited = Group::inheritedPermissionSources((int)$user->Index);
    $hasAny = $perm->hasAnyPermission() || count($inherited) > 0;
    $rows[] = array(
        'user' => $user,
        'perm' => $perm,
        'inherited' => $inherited,
        'name' => $user->getName(),
        'hasAny' => $hasAny,
    );
}
?>
<?php
adminListPageBegin('System', 'Berechtigungen', array(
    'shellClass' => 'perm-page-shell',
    'actionsHtml' => '<span id="permSaveStatus" class="profile-label perm-save-status" aria-live="polite"></span>',
));
?>
    <div class="perm-toolbar">
      <div class="profile-field perm-toolbar-search">
        <input type="search" id="permFilter" class="w3-input w3-border profile-control <?php echo htmlspecialchars($inputBg, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Name tippen…" aria-label="Name tippen…" autocomplete="off">
      </div>
      <div class="profile-field perm-toolbar-filter">
        <label class="profile-pref" for="permOnlyActive">
          <input type="checkbox" id="permOnlyActive">
          <span>nur mit mindestens einem Recht</span>
        </label>
      </div>
    </div>
<?php adminListChromeClose(); ?>

    <div class="perm-matrix-wrap">
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
            <td class="perm-user-col perm-user-link"
                role="button"
                tabindex="0"
                title="Benutzer öffnen"
                onclick="openModal('user', <?php echo $uid; ?>)"
                onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();openModal('user', <?php echo $uid; ?>);}">
              <?php echo htmlspecialchars($name); ?>
            </td>
<?php foreach($permKeys as $key) {
    $personalOn = (bool)$entry['perm']->$key;
    $groupNames = isset($entry['inherited'][$key]) ? $entry['inherited'][$key] : array();
    $inheritedOnly = !$personalOn && count($groupNames) > 0;
    $on = $personalOn || count($groupNames) > 0;
    $locked = ($uid === $sessionUserId && $key === 'perm_editPermissions');
    $title = $permLabels[$key]['label'];
    if(count($groupNames)) {
        $title .= ' — Gruppe: '.implode(', ', $groupNames);
    }
    $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $gid = Permissions::groupIdForPermission($key);
    $gid = preg_replace('/[^a-z0-9_-]/i', '', (string)$gid);
    if($gid === '') {
        $gid = 'system';
    }
    $cellExtra = $inheritedOnly ? ' perm-inherited' : '';
    $disabled = $locked || $inheritedOnly;
    $disabledTitle = '';
    if($locked) {
        $disabledTitle = ' title="Eigenes Recht „Berechtigungen bearbeiten“ kann nicht entfernt werden"';
    }
    elseif($inheritedOnly) {
        $disabledTitle = ' title="'.htmlspecialchars('Nur über Gruppe: '.implode(', ', $groupNames), ENT_QUOTES, 'UTF-8').'"';
    }
?>
            <td class="perm-cell perm-group perm-group--<?php echo htmlspecialchars($gid, ENT_QUOTES, 'UTF-8'); ?> <?php echo $on ? 'perm-on' : 'perm-off'; ?><?php echo $cellExtra; ?>" title="<?php echo $title; ?>">
              <input type="checkbox"
                     class="perm-toggle"
                     data-user="<?php echo $uid; ?>"
                     data-perm="<?php echo htmlspecialchars($key, ENT_QUOTES, 'UTF-8'); ?>"
                     <?php echo $on ? 'checked' : ''; ?>
                     <?php echo $disabled ? 'disabled'.$disabledTitle : ''; ?>>
            </td>
<?php } ?>
          </tr>
<?php } ?>
        </tbody>
      </table>
    </div>
<?php adminListPageEnd(); ?>
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
    statusEl.className = 'profile-label perm-save-status' + (ok === true ? ' perm-save-ok' : (ok === false ? ' perm-save-err' : ''));
    if(statusTimer) clearTimeout(statusTimer);
    if(text) {
      statusTimer = setTimeout(function() {
        statusEl.textContent = '';
        statusEl.className = 'profile-label perm-save-status';
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

  function applyInheritedState(cb, cell, data) {
    var inherited = data && data.inherited;
    var groups = (data && data.inheritedGroups) ? data.inheritedGroups : [];
    if(inherited && !cb.checked) {
      cb.checked = true;
      cb.disabled = true;
      cell.classList.add('perm-inherited');
      cell.classList.add('perm-on');
      cell.classList.remove('perm-off');
      var tip = groups.length ? ('Nur über Gruppe: ' + groups.join(', ')) : 'Nur über Gruppe';
      cb.setAttribute('title', tip);
      cell.setAttribute('title', tip);
    } else if(!inherited) {
      cell.classList.remove('perm-inherited');
      if(!(cb.disabled && cb.getAttribute('data-perm') === 'perm_editPermissions')) {
        cb.disabled = false;
        cb.removeAttribute('title');
      }
    }
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
      var data = null;
      try {
        data = JSON.parse(xhr.responseText || '{}');
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
      applyInheritedState(cb, cell, data);
      refreshRowHasPerms(cell.parentNode);
      applyFilter();
      setStatus('Gespeichert', true);
      if(typeof modalCache !== 'undefined') {
        delete modalCache['user:' + cb.getAttribute('data-user')];
      }
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
