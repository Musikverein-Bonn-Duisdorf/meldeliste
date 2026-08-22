/**
 * Update list page title count when client-side filters change (Inventar, Personen, …).
 * Expects adminListPageBegin(..., array('listCount' => N)) → data-list-title-base / data-list-total.
 */
(function (global) {
    'use strict';

    function getTitleEl() {
        var shell = document.querySelector('.admin-list-shell');
        if (!shell) return null;
        return shell.querySelector('.profile-title[data-list-title-base]');
    }

    function getListEl() {
        return document.getElementById('Liste');
    }

    function adminListFilterActive() {
        var input = document.getElementById('filterString');
        if (input && String(input.value || '').trim() !== '') {
            return true;
        }
        var insured = document.getElementById('filterInsured');
        if (insured && insured.classList.contains('is-active')) {
            return true;
        }
        var personenChips = document.querySelectorAll('[data-personen-filter]');
        var i;
        for (i = 0; i < personenChips.length; i++) {
            if (!personenChips[i].classList.contains('is-active')) {
                return true;
            }
        }
        if (document.querySelector('[data-register-filter].is-active')) {
            return true;
        }
        return false;
    }

    function isRowVisible(el) {
        if (!el || el.nodeType !== 1) return false;
        if (el.classList && el.classList.contains('list-filtered-out')) return false;
        if (el.style && el.style.display === 'none') return false;
        return true;
    }

    function isListRow(el, list) {
        if (!el || el.nodeType !== 1 || !list) return false;
        if (el.id === 'listSentinel') return false;
        if (el.parentNode !== list) return false;
        if (el.classList && el.classList.contains('mail-list-header')) return false;
        if (el.classList && el.classList.contains('inv-list-empty')) return false;
        if (el.classList && (el.classList.contains('w3-modal') || el.classList.contains('w3-modal-content'))) {
            return false;
        }
        if (el.classList && el.classList.contains('mail-list-item')) return true;
        if (el.classList && (el.classList.contains('list-row') || el.classList.contains('inv-row'))) {
            return true;
        }
        if (el.tagName === 'DIV' && el.id) return true;
        return false;
    }

    function countVisibleRows(list) {
        var n = 0;
        var i;
        for (i = 0; i < list.children.length; i++) {
            var el = list.children[i];
            if (!isListRow(el, list)) continue;
            if (isRowVisible(el)) n++;
        }
        return n;
    }

    function updateAdminListTitleCount() {
        var title = getTitleEl();
        var list = getListEl();
        if (!title || !list) return;

        var base = title.getAttribute('data-list-title-base') || '';
        if (base === '') return;

        var totalRaw = title.getAttribute('data-list-total');
        var total = totalRaw !== null && totalRaw !== '' ? parseInt(totalRaw, 10) : NaN;
        var count;

        if (adminListFilterActive()) {
            count = countVisibleRows(list);
        } else if (!isNaN(total)) {
            count = total;
        } else {
            count = countVisibleRows(list);
        }

        title.textContent = base + ' (' + count + ')';
    }

    global.adminListFilterActive = adminListFilterActive;
    global.updateAdminListTitleCount = updateAdminListTitleCount;
})(window);
