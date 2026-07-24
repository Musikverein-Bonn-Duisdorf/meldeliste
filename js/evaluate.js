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
          return String(a.name || '').localeCompare(String(b.name || ''));
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

  function bindSortableTable(tableId, getRows, renderRow, defaultKey, defaultDir) {
    var table = document.getElementById(tableId);
    if (!table) {
      return null;
    }
    var tbody = table.querySelector('tbody');
    var state = { key: defaultKey, dir: defaultDir, type: 'number' };

    function paint() {
      var rows = typeof getRows === 'function' ? getRows() : [];
      var sorted = sortRows(rows, state.key, state.type, state.dir);
      tbody.innerHTML = '';
      if (!sorted.length) {
        var empty = document.createElement('tr');
        var td = document.createElement('td');
        td.colSpan = table.querySelectorAll('thead th').length;
        td.textContent = 'Keine Einträge';
        td.className = 'w3-text-gray';
        empty.appendChild(td);
        tbody.appendChild(empty);
        return;
      }
      sorted.forEach(function (row) {
        tbody.appendChild(renderRow(row));
      });
      Array.prototype.forEach.call(table.querySelectorAll('th.eval-sort'), function (th) {
        var label = th.getAttribute('data-label') || th.textContent.replace(/\s*[▲▼]\s*$/, '');
        th.setAttribute('data-label', label);
        if (th.getAttribute('data-sort') === state.key) {
          th.textContent = label + (state.dir === 'asc' ? ' ▲' : ' ▼');
        } else {
          th.textContent = label;
        }
      });
    }

    Array.prototype.forEach.call(table.querySelectorAll('th.eval-sort'), function (th) {
      th.addEventListener('click', function () {
        var key = th.getAttribute('data-sort');
        var type = th.getAttribute('data-type') || 'string';
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

    var defaultTh = table.querySelector('th.eval-sort[data-sort="' + defaultKey + '"]');
    if (defaultTh) {
      state.type = defaultTh.getAttribute('data-type') || 'number';
    }
    return { paint: paint };
  }

  function cellsWithLabels(tr, pairs) {
    pairs.forEach(function (pair) {
      var td = document.createElement('td');
      td.setAttribute('data-label', pair[0]);
      td.textContent = pair[1] == null ? '' : String(pair[1]);
      tr.appendChild(td);
    });
    return tr;
  }

  function rankingRow(row) {
    return cellsWithLabels(document.createElement('tr'), [
      ['Name', row.name],
      ['Ja', row.yes],
      ['Nein', row.no],
      ['Vielleicht', row.maybe],
      ['Termine', row.termine],
      ['Quote', formatQuote(row.quote)]
    ]);
  }

  function inactiveRow(row) {
    return cellsWithLabels(document.createElement('tr'), [
      ['Name', row.name],
      ['Letzter Login', formatDate(row.lastLogin)],
      ['Letzte Teilnahme', formatDate(row.lastAttend)],
      ['Meldequote', formatQuote(row.quote)]
    ]);
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
  rankingCtl = bindSortableTable('evalRanking', function () { return filteredRows(rankingAll); }, rankingRow, 'quote', 'desc');
  inactiveCtl = bindSortableTable('evalInactive', function () { return filteredRows(inactiveAll); }, inactiveRow, 'lastLogin', 'asc');
  bindPersonFilter();
  repaintTables();
})();
