/**
 * Profile: QR for App-Login, group chip picker, live automatic membership (MELD-125).
 */
(function () {
  function renderQr(root) {
    var el = (root || document).querySelector('.profile-qr[data-alink-url]');
    if (!el || el.getAttribute('data-qr-ready') === '1' || typeof QRCode === 'undefined') return;
    var url = el.getAttribute('data-alink-url') || '';
    if (!url) return;
    el.innerHTML = '';
    new QRCode(el, { text: url, width: 192, height: 192, correctLevel: QRCode.CorrectLevel.M });
    el.setAttribute('data-qr-ready', '1');
  }

  function initQr() {
    var details = document.getElementById('profile-app-login');
    if (!details) return;
    function maybeQr() {
      if (details.open) renderQr(details);
    }
    maybeQr();
    details.addEventListener('toggle', maybeQr);
  }

  function normalize(str) {
    return (str || '').toLowerCase().replace(/\s+/g, ' ').trim();
  }

  function parseJsonAttr(el, name, fallback) {
    try {
      return JSON.parse(el.getAttribute(name) || '');
    } catch (e) {
      return fallback;
    }
  }

  function initGroupChips() {
    var wrap = document.getElementById('profile-groups-wrap');
    if (!wrap) return;
    var chipsEl = document.getElementById('profile-group-chips');
    var inputEl = document.getElementById('profile-group-input');
    var suggestEl = document.getElementById('profile-group-suggest');
    var hiddensEl = document.getElementById('profile-group-hiddens');
    if (!chipsEl || !inputEl || !suggestEl || !hiddensEl) return;

    var catalog = parseJsonAttr(wrap, 'data-group-catalog', { mailGroups: [] });
    var mailGroups = Array.isArray(catalog.mailGroups) ? catalog.mailGroups : [];
    var selected = parseJsonAttr(wrap, 'data-selected-groups', []);
    if (!Array.isArray(selected)) selected = [];
    selected = selected.map(Number).filter(function (id) { return id > 0; });
    var activeIndex = -1;
    var readonly = !!inputEl.disabled;

    function labelFor(id) {
      for (var i = 0; i < mailGroups.length; i++) {
        if (Number(mailGroups[i].id) === Number(id)) return mailGroups[i].label || ('#' + id);
      }
      return 'Gruppe #' + id;
    }

    function syncHiddens() {
      hiddensEl.innerHTML = '';
      selected.forEach(function (id) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'userMailGroups[]';
        input.value = String(id);
        hiddensEl.appendChild(input);
      });
    }

    function render() {
      chipsEl.innerHTML = '';
      selected.forEach(function (id) {
        var chip = document.createElement('span');
        chip.className = 'mail-recipient-chip mail-recipient-chip--mailGroup';
        chip.setAttribute('data-id', String(id));
        var text = document.createElement('span');
        text.textContent = labelFor(id);
        chip.appendChild(text);
        if (!readonly) {
          var btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'mail-recipient-chip-remove';
          btn.setAttribute('aria-label', 'Entfernen');
          btn.innerHTML = '&times;';
          btn.addEventListener('click', function () {
            selected = selected.filter(function (x) { return x !== Number(id); });
            render();
          });
          chip.appendChild(btn);
        }
        chipsEl.appendChild(chip);
      });
      syncHiddens();
    }

    function filteredSuggestions() {
      var q = normalize(inputEl.value);
      var items = [];
      mailGroups.forEach(function (g) {
        var id = Number(g.id);
        if (selected.indexOf(id) !== -1) return;
        if (q === '' || normalize(g.label).indexOf(q) !== -1) {
          items.push(g);
        }
      });
      return items;
    }

    function hideSuggest() {
      suggestEl.hidden = true;
      suggestEl.innerHTML = '';
      activeIndex = -1;
    }

    function showSuggest() {
      if (readonly) return;
      var items = filteredSuggestions();
      suggestEl.innerHTML = '';
      if (!items.length) {
        hideSuggest();
        return;
      }
      items.forEach(function (g, idx) {
        var row = document.createElement('button');
        row.type = 'button';
        row.className = 'mail-recipient-suggest-item' + (idx === activeIndex ? ' mail-recipient-suggest-item--active' : '');
        row.setAttribute('data-index', String(idx));
        var label = document.createElement('span');
        label.textContent = g.label || '';
        row.appendChild(label);
        var meta = document.createElement('span');
        meta.className = 'mail-recipient-suggest-meta';
        meta.textContent = 'Gruppe';
        row.appendChild(meta);
        row.addEventListener('mousedown', function (e) {
          e.preventDefault();
          addGroup(Number(g.id));
        });
        suggestEl.appendChild(row);
      });
      suggestEl.hidden = false;
    }

    function addGroup(id) {
      id = Number(id);
      if (!(id > 0) || selected.indexOf(id) !== -1) return;
      selected.push(id);
      inputEl.value = '';
      hideSuggest();
      render();
      inputEl.focus();
    }

    if (!readonly) {
      inputEl.addEventListener('input', function () {
        activeIndex = -1;
        showSuggest();
      });
      inputEl.addEventListener('focus', showSuggest);
      inputEl.addEventListener('blur', function () {
        window.setTimeout(hideSuggest, 150);
      });
      inputEl.addEventListener('keydown', function (e) {
        var items = filteredSuggestions();
        if (e.key === 'ArrowDown') {
          e.preventDefault();
          if (!items.length) return;
          activeIndex = (activeIndex + 1) % items.length;
          showSuggest();
        } else if (e.key === 'ArrowUp') {
          e.preventDefault();
          if (!items.length) return;
          activeIndex = activeIndex <= 0 ? items.length - 1 : activeIndex - 1;
          showSuggest();
        } else if (e.key === 'Enter') {
          if (activeIndex >= 0 && items[activeIndex]) {
            e.preventDefault();
            addGroup(Number(items[activeIndex].id));
          }
        } else if (e.key === 'Backspace' && !inputEl.value && selected.length) {
          selected.pop();
          render();
        } else if (e.key === 'Escape') {
          hideSuggest();
        }
      });
    }

    render();
  }

  function initPermissionChips() {
    var wrap = document.getElementById('profile-perms-wrap');
    if (!wrap) return;
    var chipsEl = document.getElementById('profile-perm-chips');
    var inputEl = document.getElementById('profile-perm-input');
    var suggestEl = document.getElementById('profile-perm-suggest');
    var hiddensEl = document.getElementById('profile-perm-hiddens');
    if (!chipsEl || !inputEl || !suggestEl || !hiddensEl) return;

    var catalog = parseJsonAttr(wrap, 'data-perm-catalog', { permissions: [] });
    var permissions = Array.isArray(catalog.permissions) ? catalog.permissions : [];
    var selected = parseJsonAttr(wrap, 'data-selected-perms', []);
    if (!Array.isArray(selected)) selected = [];
    selected = selected.map(String).filter(function (k) { return !!k; });
    var lockedKey = String(wrap.getAttribute('data-locked-perm') || '');
    var activeIndex = -1;

    function metaFor(key) {
      for (var i = 0; i < permissions.length; i++) {
        if (String(permissions[i].key) === String(key)) return permissions[i];
      }
      return { key: key, label: key, group: '', groupId: 'sonst' };
    }

    function sortSelected() {
      var order = {};
      permissions.forEach(function (p, idx) {
        order[String(p.key)] = idx;
      });
      selected.sort(function (a, b) {
        var ia = order.hasOwnProperty(a) ? order[a] : 999;
        var ib = order.hasOwnProperty(b) ? order[b] : 999;
        return ia - ib;
      });
    }

    function syncHiddens() {
      hiddensEl.innerHTML = '';
      selected.forEach(function (key) {
        var input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'userPermissions[]';
        input.value = String(key);
        hiddensEl.appendChild(input);
      });
    }

    function render() {
      if (lockedKey && selected.indexOf(lockedKey) === -1) {
        selected.push(lockedKey);
      }
      sortSelected();
      chipsEl.innerHTML = '';
      selected.forEach(function (key) {
        var meta = metaFor(key);
        var gid = String(meta.groupId || 'sonst').replace(/[^a-z0-9_-]/gi, '');
        var chip = document.createElement('span');
        chip.className = 'profile-perm-tile profile-perm-tile--' + gid;
        chip.setAttribute('data-key', String(key));
        var text = document.createElement('span');
        text.textContent = meta.label || key;
        chip.appendChild(text);
        if (key !== lockedKey) {
          var btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'mail-recipient-chip-remove';
          btn.setAttribute('aria-label', 'Entfernen');
          btn.innerHTML = '&times;';
          btn.addEventListener('click', function () {
            selected = selected.filter(function (x) { return x !== key; });
            render();
          });
          chip.appendChild(btn);
        }
        chipsEl.appendChild(chip);
      });
      syncHiddens();
    }

    function filteredSuggestions() {
      var q = normalize(inputEl.value);
      var items = [];
      permissions.forEach(function (p) {
        var key = String(p.key);
        if (selected.indexOf(key) !== -1) return;
        if (q === '' || normalize(p.label).indexOf(q) !== -1 || normalize(p.group).indexOf(q) !== -1) {
          items.push(p);
        }
      });
      return items;
    }

    function hideSuggest() {
      suggestEl.hidden = true;
      suggestEl.innerHTML = '';
      activeIndex = -1;
    }

    function showSuggest() {
      var items = filteredSuggestions();
      suggestEl.innerHTML = '';
      if (!items.length) {
        hideSuggest();
        return;
      }
      items.forEach(function (p, idx) {
        var row = document.createElement('button');
        row.type = 'button';
        row.className = 'mail-recipient-suggest-item' + (idx === activeIndex ? ' mail-recipient-suggest-item--active' : '');
        row.setAttribute('data-index', String(idx));
        var label = document.createElement('span');
        label.textContent = p.label || '';
        row.appendChild(label);
        var meta = document.createElement('span');
        meta.className = 'mail-recipient-suggest-meta';
        meta.textContent = p.group || '';
        row.appendChild(meta);
        row.addEventListener('mousedown', function (e) {
          e.preventDefault();
          addPerm(String(p.key));
        });
        suggestEl.appendChild(row);
      });
      suggestEl.hidden = false;
    }

    function addPerm(key) {
      key = String(key);
      if (!key || selected.indexOf(key) !== -1) return;
      selected.push(key);
      inputEl.value = '';
      hideSuggest();
      render();
      inputEl.focus();
    }

    inputEl.addEventListener('input', function () {
      activeIndex = -1;
      showSuggest();
    });
    inputEl.addEventListener('focus', showSuggest);
    inputEl.addEventListener('blur', function () {
      window.setTimeout(hideSuggest, 150);
    });
    inputEl.addEventListener('keydown', function (e) {
      var items = filteredSuggestions();
      if (e.key === 'ArrowDown') {
        e.preventDefault();
        if (!items.length) return;
        activeIndex = (activeIndex + 1) % items.length;
        showSuggest();
      } else if (e.key === 'ArrowUp') {
        e.preventDefault();
        if (!items.length) return;
        activeIndex = activeIndex <= 0 ? items.length - 1 : activeIndex - 1;
        showSuggest();
      } else if (e.key === 'Enter') {
        if (activeIndex >= 0 && items[activeIndex]) {
          e.preventDefault();
          addPerm(String(items[activeIndex].key));
        }
      } else if (e.key === 'Backspace' && !inputEl.value && selected.length) {
        var last = selected[selected.length - 1];
        if (last !== lockedKey) {
          selected.pop();
          render();
        }
      } else if (e.key === 'Escape') {
        hideSuggest();
      }
    });

    render();
  }

  function parseCatalog(wrap) {
    try {
      return JSON.parse(wrap.getAttribute('data-membership-catalog') || '{}');
    } catch (e) {
      return null;
    }
  }

  function roleMatches(audience, mitglied) {
    if (audience === 'users' || audience === 'musicians') return true;
    if (audience === 'members') return !!mitglied;
    if (audience === 'nonmembers') return !mitglied;
    return false;
  }

  function attrsMatchSpec(attrs, groups, registers) {
    groups = Array.isArray(groups) ? groups.slice() : [];
    registers = (registers || []).map(function (id) { return Number(id); }).filter(function (id) { return id > 0; });
    if (!groups.length && !registers.length) return false;
    if (!groups.length && registers.length) groups = ['musicians'];
    var i;
    for (i = 0; i < groups.length; i++) {
      if (!roleMatches(groups[i], attrs.mitglied)) continue;
      if (registers.length && registers.indexOf(attrs.registerId) === -1) continue;
      return true;
    }
    return false;
  }

  function buildChips(catalog, attrs) {
    var chips = [];
    var labels = catalog.groupLabels || {};
    var groupIds = catalog.groupIds || Object.keys(labels);
    var i;
    for (i = 0; i < groupIds.length; i++) {
      var gid = groupIds[i];
      if (attrsMatchSpec(attrs, [gid], [])) {
        chips.push({ type: 'group', label: labels[gid] || gid });
      }
    }
    var regName = (attrs.registerName || '').trim();
    if (regName && regName.toLowerCase() !== 'keins') {
      chips.push({ type: 'register', label: 'Register: ' + regName });
    }
    var mailGroups = catalog.mailGroups || [];
    for (i = 0; i < mailGroups.length; i++) {
      var g = mailGroups[i];
      if (attrsMatchSpec(attrs, g.groups || [], g.registers || [])) {
        chips.push({ type: 'mailGroup', label: 'Gruppe: ' + (g.name || '') });
      }
    }
    return chips;
  }

  function readAttrs(form, catalog) {
    var instrumentEl = form.querySelector('#profile-instrument') || form.querySelector('[name="Instrument"]');
    var mitgliedEl = form.querySelector('#pref-Mitglied') || form.querySelector('[name="Mitglied"][type="checkbox"]');
    var instrumentId = instrumentEl ? Number(instrumentEl.value || 0) : 0;
    var info = (catalog.instruments && catalog.instruments[String(instrumentId)]) || null;
    return {
      mitglied: !!(mitgliedEl && mitgliedEl.checked),
      registerId: info ? Number(info.registerId || 0) : 0,
      registerName: info ? String(info.registerName || '') : ''
    };
  }

  function renderAutoChips(container, chips) {
    container.innerHTML = '';
    if (!chips.length) {
      var empty = document.createElement('span');
      empty.className = 'profile-auto-empty';
      empty.textContent = '—';
      container.appendChild(empty);
      return;
    }
    chips.forEach(function (chip) {
      var span = document.createElement('span');
      span.className = 'mail-recipient-chip mail-recipient-chip--' + chip.type;
      span.textContent = chip.label;
      container.appendChild(span);
    });
  }

  function initMembershipPreview() {
    var wrap = document.getElementById('profile-auto-membership-wrap');
    var container = document.getElementById('profile-auto-membership');
    if (!wrap || !container) return;
    var catalog = parseCatalog(wrap);
    if (!catalog) return;
    var form = wrap.closest('form');
    if (!form) return;

    function refresh() {
      renderAutoChips(container, buildChips(catalog, readAttrs(form, catalog)));
    }

    var instrumentEl = form.querySelector('#profile-instrument') || form.querySelector('[name="Instrument"]');
    var mitgliedEl = form.querySelector('#pref-Mitglied') || form.querySelector('[name="Mitglied"][type="checkbox"]');
    if (instrumentEl) instrumentEl.addEventListener('change', refresh);
    if (mitgliedEl) mitgliedEl.addEventListener('change', refresh);
    refresh();
  }

  function init() {
    initQr();
    initGroupChips();
    initPermissionChips();
    initMembershipPreview();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
