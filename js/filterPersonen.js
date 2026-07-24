/**
 * Personenliste: Suche + Aktive/Gäste/Mitglieder + Register-Filter (MELD-155).
 */
(function (global) {
    'use strict';

    function filterPersonen() {
        var input = document.getElementById('filterString');
        var filter = input && input.value ? input.value.toUpperCase() : '';
        var list = document.getElementById('Liste');
        if (!list) return;

        var showAktive = true;
        var showGaeste = true;
        var showMitglied = true;
        var showNoMitglied = true;
        var aktiveBtn = document.querySelector('[data-personen-filter="aktive"]');
        var gaesteBtn = document.querySelector('[data-personen-filter="gaeste"]');
        var mitgliedBtn = document.querySelector('[data-personen-filter="mitglied"]');
        var noMitgliedBtn = document.querySelector('[data-personen-filter="nomitglied"]');
        if (aktiveBtn) showAktive = aktiveBtn.classList.contains('is-active');
        if (gaesteBtn) showGaeste = gaesteBtn.classList.contains('is-active');
        if (mitgliedBtn) showMitglied = mitgliedBtn.classList.contains('is-active');
        if (noMitgliedBtn) showNoMitglied = noMitgliedBtn.classList.contains('is-active');

        var regButtons = document.querySelectorAll('[data-register-filter]');
        var activeRegs = [];
        Array.prototype.forEach.call(regButtons, function (btn) {
            if (btn.classList.contains('is-active')) {
                activeRegs.push(String(btn.getAttribute('data-register-filter') || '0'));
            }
        });
        var regFilterOn = activeRegs.length > 0;

        var children = list.children;
        var i;
        for (i = 0; i < children.length; i++) {
            var row = children[i];
            if (row.id === 'listSentinel') continue;
            if (!row.classList || !row.classList.contains('list-row')) continue;

            var active = row.getAttribute('data-active') === '1';
            var mitglied = row.getAttribute('data-mitglied') === '1';
            var regId = String(row.getAttribute('data-register-id') || '0');
            var visible = true;

            if (active && !showAktive) visible = false;
            if (!active && !showGaeste) visible = false;
            if (mitglied && !showMitglied) visible = false;
            if (!mitglied && !showNoMitglied) visible = false;
            if (visible && regFilterOn && activeRegs.indexOf(regId) === -1) {
                visible = false;
            }
            if (visible && filter) {
                var txt = typeof listRowSearchText === 'function'
                    ? listRowSearchText(row)
                    : (row.getAttribute('data-search') || row.textContent || '');
                if (String(txt).toUpperCase().indexOf(filter) === -1) {
                    visible = false;
                }
            }

            if (visible) {
                row.style.display = '';
                row.classList.remove('list-filtered-out');
            } else {
                row.style.display = 'none';
                row.classList.add('list-filtered-out');
            }
        }
    }

    function bindFilterChips() {
        var header = document.getElementById('listHeader');
        if (!header) return;
        header.addEventListener('click', function (ev) {
            var btn = ev.target.closest('[data-personen-filter], [data-register-filter]');
            if (!btn || !header.contains(btn)) return;
            ev.preventDefault();
            var on = !btn.classList.contains('is-active');
            btn.classList.toggle('is-active', on);
            btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            filterPersonen();
        });
    }

    global.filterPersonen = filterPersonen;
    // Back-compat for shared search helpers
    global.filterMusiker = filterPersonen;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            bindFilterChips();
            filterPersonen();
        });
    } else {
        bindFilterChips();
        filterPersonen();
    }
})(window);
