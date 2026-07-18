/**
 * Sortable list headers for w3-row lists (MELD-96).
 *
 * Client mode (full load):
 *   bindListSort({ headerId: 'listHeader', listId: 'Liste' });
 *   Rows: .list-row with data-sort-{key}
 *   Header cells: .list-sort data-sort="key" data-type="string|number|date"
 *
 * Server mode (infinite scroll):
 *   bindListSort({ headerId: 'listHeader', mode: 'server' });
 *   Uses window.listInfiniteReload(sort, dir) from infiniteScroll.js
 */
(function (global) {
    'use strict';

    function compareValues(av, bv, type, dir) {
        var mul = dir === 'asc' ? 1 : -1;
        if (type === 'number') {
            av = Number(av);
            bv = Number(bv);
            if (!isFinite(av)) av = 0;
            if (!isFinite(bv)) bv = 0;
            if (av === bv) return 0;
            return (av < bv ? -1 : 1) * mul;
        }
        if (type === 'date') {
            av = av ? String(av) : '';
            bv = bv ? String(bv) : '';
            if (av === bv) return 0;
            return (av < bv ? -1 : 1) * mul;
        }
        av = av == null || av === '' ? '' : String(av);
        bv = bv == null || bv === '' ? '' : String(bv);
        return av.localeCompare(bv, 'de', { sensitivity: 'base', numeric: true }) * mul;
    }

    function paintHeaders(header, state) {
        var cells = header.querySelectorAll('.list-sort');
        Array.prototype.forEach.call(cells, function (cell) {
            var label = cell.getAttribute('data-label');
            if (!label) {
                label = (cell.textContent || '').replace(/\s*[▲▼]\s*$/, '').trim();
                cell.setAttribute('data-label', label);
            }
            if (cell.getAttribute('data-sort') === state.key) {
                cell.textContent = label + (state.dir === 'asc' ? ' ▲' : ' ▼');
            } else {
                cell.textContent = label;
            }
        });
    }

    function sortClient(list, key, type, dir) {
        var rows = [];
        var children = list.children;
        var i;
        for (i = 0; i < children.length; i++) {
            var el = children[i];
            if (el.id === 'listSentinel') continue;
            if (el.nodeType !== 1) continue;
            if (!el.classList || !el.classList.contains('list-row')) continue;
            rows.push(el);
        }
        rows.sort(function (a, b) {
            var av = a.getAttribute('data-sort-' + key);
            var bv = b.getAttribute('data-sort-' + key);
            return compareValues(av, bv, type, dir);
        });
        var sentinel = document.getElementById('listSentinel');
        for (i = 0; i < rows.length; i++) {
            if (sentinel && sentinel.parentNode === list) {
                list.insertBefore(rows[i], sentinel);
            } else {
                list.appendChild(rows[i]);
            }
        }
    }

    function bindListSort(opts) {
        opts = opts || {};
        var headerId = opts.headerId || 'listHeader';
        var listId = opts.listId || 'Liste';
        var mode = opts.mode || 'client';
        var header = document.getElementById(headerId);
        if (!header) return;

        var state = {
            key: opts.defaultKey || '',
            dir: opts.defaultDir || 'asc',
            type: opts.defaultType || 'string'
        };

        Array.prototype.forEach.call(header.querySelectorAll('.list-sort'), function (cell) {
            if (!cell.getAttribute('data-label')) {
                cell.setAttribute('data-label', (cell.textContent || '').replace(/\s*[▲▼]\s*$/, '').trim());
            }
            cell.addEventListener('click', function () {
                var key = cell.getAttribute('data-sort');
                if (!key) return;
                var type = cell.getAttribute('data-type') || 'string';
                if (state.key === key) {
                    state.dir = state.dir === 'asc' ? 'desc' : 'asc';
                } else {
                    state.key = key;
                    state.type = type;
                    state.dir = type === 'number' ? 'desc' : 'asc';
                }
                paintHeaders(header, state);
                if (mode === 'server') {
                    if (typeof global.listInfiniteReload === 'function') {
                        global.listInfiniteReload(state.key, state.dir);
                    }
                } else {
                    var list = document.getElementById(listId);
                    if (list) sortClient(list, state.key, state.type, state.dir);
                }
            });
        });

        if (state.key) {
            paintHeaders(header, state);
            if (mode === 'client') {
                var list = document.getElementById(listId);
                if (list) sortClient(list, state.key, state.type, state.dir);
            }
        }
    }

    global.bindListSort = bindListSort;
})(window);
