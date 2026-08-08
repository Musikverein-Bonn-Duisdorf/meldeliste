(function () {
  'use strict';

  var dataEl = document.getElementById('evaluate-data');
  if (!dataEl) {
    return;
  }

  var data;
  try {
    data = JSON.parse(dataEl.textContent);
  } catch (e) {
    return;
  }

  var attendance = data.attendance || { labels: [], yes: [], no: [], maybe: [], rate: [] };
  var log = data.log || { labels: [], series: {} };
  var logLabels = data.logLabels || [];
  var rankingAll = data.ranking || [];
  var inactiveAll = data.inactive || [];
  var rankingCtl = null;
  var inactiveCtl = null;

  function drawAttendance() {
    var canvas = document.getElementById('chartAttendance');
    if (!canvas || typeof Chart === 'undefined') {
      return;
    }
    new Chart(canvas, {
      data: {
        labels: attendance.labels,
        datasets: [
          {
            type: 'bar',
            label: 'Ja',
            data: attendance.yes,
            backgroundColor: 'rgba(76, 175, 80, 0.75)',
            stack: 'meld'
          },
          {
            type: 'bar',
            label: 'Nein',
            data: attendance.no,
            backgroundColor: 'rgba(244, 67, 54, 0.75)',
            stack: 'meld'
          },
          {
            type: 'bar',
            label: 'Vielleicht',
            data: attendance.maybe,
            backgroundColor: 'rgba(33, 150, 243, 0.75)',
            stack: 'meld'
          },
          {
            type: 'line',
            label: 'Ja-Quote %',
            data: attendance.rate,
            yAxisID: 'yRate',
            borderColor: 'rgba(255, 152, 0, 1)',
            backgroundColor: 'rgba(255, 152, 0, 0.2)',
            tension: 0.2,
            pointRadius: 3
          }
        ]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        interaction: { mode: 'index', intersect: false },
        scales: {
          x: { stacked: true },
          y: { stacked: true, beginAtZero: true, title: { display: true, text: 'Meldungen' } },
          yRate: {
            position: 'right',
            beginAtZero: true,
            max: 100,
            grid: { drawOnChartArea: false },
            title: { display: true, text: 'Ja-Quote %' }
          }
        }
      }
    });
  }

  function drawLog() {
    var canvas = document.getElementById('chartLog');
    if (!canvas || typeof Chart === 'undefined') {
      return;
    }
    var colors = [
      '#b71c1c', '#e53935', '#fb8c00', '#6d4c41',
      '#43a047', '#1e88e5', '#8e24aa', '#546e7a'
    ];
    var datasets = [];
    for (var i = 0; i <= 7; i++) {
      datasets.push({
        label: logLabels[i] || ('Type ' + i),
        data: (log.series && log.series[i]) ? log.series[i] : [],
        backgroundColor: colors[i],
        stack: 'log'
      });
    }
    new Chart(canvas, {
      type: 'bar',
      data: {
        labels: log.labels || [],
        datasets: datasets
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          x: { stacked: true },
          y: { stacked: true, beginAtZero: true, title: { display: true, text: 'Anzahl' } }
        }
      }
    });
  }

  function formatQuote(q) {
    var n = Number(q);
    if (!isFinite(n)) {
      return '—';
    }
    return (n * 100).toFixed(1) + ' %';
  }

  function formatDate(v) {
    return v ? String(v) : '—';
  }

  function sortRows(rows, key, type, dir) {
    var mul = dir === 'asc' ? 1 : -1;
    return rows.slice().sort(function (a, b) {
      var av = a[key];
      var bv = b[key];
      if (type === 'number') {
        av = Number(av) || 0;
        bv = Number(bv) || 0;
        if (av === bv) {
          return String(a.name || '').localeCompare(String(b.name || ''), 'de');
        }
        return (av < bv ? -1 : 1) * mul;
      }
      av = av == null || av === '' ? '' : String(av);
      bv = bv == null || bv === '' ? '' : String(bv);
      return av.localeCompare(bv, 'de') * mul;
    });
  }

  function readPersonFilter() {
    var root = document.getElementById('evalPersonFilter');
    var filter = {
      showAktive: true,
      showGaeste: true,
      showMitglied: true,
      showNoMitglied: true,
      registers: [],
      groups: []
    };
    if (!root) {
      return filter;
    }
    var aktiveBtn = root.querySelector('[data-eval-filter="aktive"]');
    var gaesteBtn = root.querySelector('[data-eval-filter="gaeste"]');
    var mitgliedBtn = root.querySelector('[data-eval-filter="mitglied"]');
    var noMitgliedBtn = root.querySelector('[data-eval-filter="nomitglied"]');
    if (aktiveBtn) filter.showAktive = aktiveBtn.classList.contains('is-active');
    if (gaesteBtn) filter.showGaeste = gaesteBtn.classList.contains('is-active');
    if (mitgliedBtn) filter.showMitglied = mitgliedBtn.classList.contains('is-active');
    if (noMitgliedBtn) filter.showNoMitglied = noMitgliedBtn.classList.contains('is-active');

    Array.prototype.forEach.call(root.querySelectorAll('[data-register-filter]'), function (btn) {
      if (btn.classList.contains('is-active')) {
        filter.registers.push(String(btn.getAttribute('data-register-filter') || '0'));
      }
    });
    Array.prototype.forEach.call(root.querySelectorAll('[data-group-filter]'), function (btn) {
      if (btn.classList.contains('is-active')) {
        filter.groups.push(String(btn.getAttribute('data-group-filter') || '0'));
      }
    });
    return filter;
  }

  function rowMatchesPersonFilter(row, filter) {
    var active = Number(row.active) === 1;
    var mitglied = Number(row.mitglied) === 1;
    var regId = String(row.registerId == null ? 0 : row.registerId);
    var groupIds = Array.isArray(row.groupIds) ? row.groupIds.map(function (g) { return String(g); }) : [];

    if (active && !filter.showAktive) return false;
    if (!active && !filter.showGaeste) return false;
    if (mitglied && !filter.showMitglied) return false;
    if (!mitglied && !filter.showNoMitglied) return false;
    if (filter.registers.length && filter.registers.indexOf(regId) === -1) return false;
    if (filter.groups.length) {
      var hit = false;
      for (var i = 0; i < groupIds.length; i++) {
        if (filter.groups.indexOf(groupIds[i]) !== -1) {
          hit = true;
          break;
        }
      }
      if (!hit) return false;
    }
    return true;
  }

  function paintSortHeaders(header, state) {
    if (!header) return;
    Array.prototype.forEach.call(header.querySelectorAll('.list-sort'), function (btn) {
      var label = btn.getAttribute('data-label');
      if (!label) {
        label = (btn.textContent || '').replace(/\s*[▲▼]\s*$/, '').trim();
        btn.setAttribute('data-label', label);
      }
      if (btn.getAttribute('data-sort') === state.key) {
        btn.textContent = label + (state.dir === 'asc' ? ' ▲' : ' ▼');
      } else {
        btn.textContent = label;
      }
    });
  }

  function appendMetaItem(line, label, value) {
    var item = document.createElement('span');
    item.className = 'user-meta-item';
    var k = document.createElement('span');
    k.className = 'user-meta-k';
    k.textContent = label;
    item.appendChild(k);
    item.appendChild(document.createTextNode(' ' + String(value)));
    line.appendChild(item);
  }

  function buildUserRow(row, metaPairs) {
    var el = document.createElement('div');
    var classes = ['user-row', 'list-row'];
    var active = Number(row.active) === 1;
    var mitglied = Number(row.mitglied) === 1;
    var regId = Number(row.registerId) || 0;
    var instrument = row.instrument ? String(row.instrument) : '';
    var color = row.registerColor ? String(row.registerColor) : '';

    if (mitglied) {
      classes.push('user-row--member');
    } else {
      classes.push('user-row--nomember');
    }
    if (!active) {
      classes.push('user-row--inactive');
    }
    if (regId <= 0) {
      classes.push('user-row--no-register');
    }
    if (color) {
      classes.push('user-row--register-color');
      el.style.setProperty('--user-register-color', color);
    }
    el.className = classes.join(' ');
    el.setAttribute('data-active', active ? '1' : '0');
    el.setAttribute('data-mitglied', mitglied ? '1' : '0');
    el.setAttribute('data-register-id', String(regId));
    el.setAttribute('role', 'button');
    el.tabIndex = 0;

    var uid = Number(row.id) || 0;
    el.addEventListener('click', function () {
      if (uid > 0 && typeof openModal === 'function') {
        openModal('user', uid);
      }
    });
    el.addEventListener('keydown', function (ev) {
      if (ev.key === 'Enter' || ev.key === ' ') {
        ev.preventDefault();
        if (uid > 0 && typeof openModal === 'function') {
          openModal('user', uid);
        }
      }
    });

    var idCol = document.createElement('div');
    idCol.className = 'user-id';
    var idNum = document.createElement('div');
    idNum.className = 'user-id-num';
    var idK = document.createElement('span');
    idK.className = 'user-id-k';
    idK.textContent = 'User-ID';
    idNum.appendChild(idK);
    idNum.appendChild(document.createTextNode(' ' + String(uid)));
    idCol.appendChild(idNum);

    var chips = document.createElement('div');
    chips.className = 'user-id-chips';
    chips.setAttribute('aria-label', 'Instrument und Mitgliedschaft');
    if (instrument) {
      var instLine = document.createElement('div');
      instLine.className = 'user-id-chip-line mail-recipient-chips';
      var instChip = document.createElement('span');
      instChip.className = 'mail-recipient-chip mail-recipient-chip--instrument';
      instChip.textContent = instrument;
      instLine.appendChild(instChip);
      chips.appendChild(instLine);
    }
    var statusLine = document.createElement('div');
    statusLine.className = 'user-id-chip-line mail-recipient-chips';
    var statusChip = document.createElement('span');
    if (!active) {
      statusChip.className = 'mail-recipient-chip mail-recipient-chip--guestMusician';
      statusChip.textContent = 'Gast';
    } else if (mitglied) {
      statusChip.className = 'mail-recipient-chip mail-recipient-chip--member';
      statusChip.textContent = 'Mitglied';
    } else {
      statusChip.className = 'mail-recipient-chip mail-recipient-chip--nomember';
      statusChip.textContent = 'kein Mitglied';
    }
    statusLine.appendChild(statusChip);
    chips.appendChild(statusLine);
    idCol.appendChild(chips);
    el.appendChild(idCol);

    var rail = document.createElement('div');
    rail.className = 'user-rail';
    rail.setAttribute('aria-hidden', 'true');
    el.appendChild(rail);

    var main = document.createElement('div');
    main.className = 'user-main';
    var nameEl = document.createElement('div');
    nameEl.className = 'user-name';
    nameEl.textContent = row.name ? String(row.name) : '';
    main.appendChild(nameEl);

    var meta = document.createElement('div');
    meta.className = 'user-meta-line';
    metaPairs.forEach(function (pair) {
      appendMetaItem(meta, pair[0], pair[1]);
    });
    main.appendChild(meta);
    el.appendChild(main);

    return el;
  }

  function rankingRow(row) {
    return buildUserRow(row, [
      ['Ja', row.yes],
      ['Nein', row.no],
      ['Vielleicht', row.maybe],
      ['Termine', row.termine],
      ['Quote', formatQuote(row.quote)]
    ]);
  }

  function inactiveRow(row) {
    return buildUserRow(row, [
      ['Login', formatDate(row.lastLogin)],
      ['Teilnahme', formatDate(row.lastAttend)],
      ['Quote', formatQuote(row.quote)]
    ]);
  }

  function bindSortableList(listId, headerId, getRows, renderRow, defaultKey, defaultDir) {
    var list = document.getElementById(listId);
    var header = document.getElementById(headerId);
    if (!list) {
      return null;
    }
    var state = { key: defaultKey, dir: defaultDir, type: 'number' };

    function paint() {
      var rows = typeof getRows === 'function' ? getRows() : [];
      var sorted = sortRows(rows, state.key, state.type, state.dir);
      list.innerHTML = '';
      if (!sorted.length) {
        var empty = document.createElement('div');
        empty.className = 'inv-list-empty w3-text-gray';
        empty.textContent = 'Keine Einträge';
        list.appendChild(empty);
        paintSortHeaders(header, state);
        return;
      }
      sorted.forEach(function (row) {
        list.appendChild(renderRow(row));
      });
      paintSortHeaders(header, state);
    }

    if (header) {
      Array.prototype.forEach.call(header.querySelectorAll('.list-sort'), function (btn) {
        if (!btn.getAttribute('data-label')) {
          btn.setAttribute('data-label', (btn.textContent || '').replace(/\s*[▲▼]\s*$/, '').trim());
        }
        btn.addEventListener('click', function () {
          var key = btn.getAttribute('data-sort');
          var type = btn.getAttribute('data-type') || 'string';
          if (!key) return;
          if (state.key === key) {
            state.dir = state.dir === 'asc' ? 'desc' : 'asc';
          } else {
            state.key = key;
            state.type = type;
            state.dir = type === 'number' ? 'desc' : 'asc';
          }
          paint();
        });
      });
      var defaultBtn = header.querySelector('.list-sort[data-sort="' + defaultKey + '"]');
      if (defaultBtn) {
        state.type = defaultBtn.getAttribute('data-type') || 'number';
      }
    }

    return { paint: paint };
  }

  function filteredRows(all) {
    var filter = readPersonFilter();
    return all.filter(function (row) {
      return rowMatchesPersonFilter(row, filter);
    });
  }

  function repaintTables() {
    if (rankingCtl) rankingCtl.paint();
    if (inactiveCtl) inactiveCtl.paint();
  }

  function bindPersonFilter() {
    var root = document.getElementById('evalPersonFilter');
    if (!root) return;
    root.addEventListener('click', function (ev) {
      var btn = ev.target.closest('[data-eval-filter], [data-register-filter], [data-group-filter]');
      if (!btn || !root.contains(btn)) return;
      ev.preventDefault();
      var on = !btn.classList.contains('is-active');
      btn.classList.toggle('is-active', on);
      btn.setAttribute('aria-pressed', on ? 'true' : 'false');
      repaintTables();
    });
  }

  drawAttendance();
  drawLog();
  rankingCtl = bindSortableList('evalRanking', 'evalRankingSort', function () { return filteredRows(rankingAll); }, rankingRow, 'quote', 'desc');
  inactiveCtl = bindSortableList('evalInactive', 'evalInactiveSort', function () { return filteredRows(inactiveAll); }, inactiveRow, 'lastLogin', 'asc');
  bindPersonFilter();
  repaintTables();
})();
