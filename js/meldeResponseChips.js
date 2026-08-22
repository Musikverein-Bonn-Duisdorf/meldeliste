/**
 * MELD-175: chip add/remove for termin response modal (Ja / Nein / Unsicher).
 */
(function(global) {
    'use strict';

    function parseCatalog(root) {
        var el = root.querySelector('#meldeResponseUserCatalog');
        if(!el) return [];
        try {
            var c = JSON.parse(el.textContent || '[]');
            if(!Array.isArray(c)) return [];
            return c.map(function(row) {
                return {
                    id: Number(row.id),
                    label: String(row.label || '').trim(),
                    meta: String(row.meta || '').trim()
                };
            }).filter(function(row) {
                return row.label !== '' && !isNaN(row.id) && row.id > 0;
            });
        } catch(e) {
            return [];
        }
    }

    function getTerminId(root) {
        var modal = root.querySelector('.termin-response-modal');
        if(!modal) return 0;
        return parseInt(modal.getAttribute('data-termin-id'), 10) || 0;
    }

    function assignedUserIds(root) {
        var ids = {};
        root.querySelectorAll('.melde-response-editable-chip[data-user-id]').forEach(function(chip) {
            var id = parseInt(chip.getAttribute('data-user-id'), 10) || 0;
            if(id > 0) ids[id] = true;
        });
        return ids;
    }

    function postMelde(body, onOk, onFail) {
        var xhr;
        if(global.XMLHttpRequest) {
            xhr = new XMLHttpRequest();
        } else {
            xhr = new ActiveXObject('Microsoft.XMLHTTP');
        }
        xhr.onreadystatechange = function() {
            if(xhr.readyState !== 4) return;
            if(xhr.status >= 200 && xhr.status < 300) {
                if(onOk) onOk();
            } else if(onFail) {
                onFail();
            }
        };
        xhr.open('POST', 'melde.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send(body);
    }

    function afterMutation(terminId) {
        if(typeof global.scheduleRefreshOpenTerminResponseModal === 'function') {
            global.scheduleRefreshOpenTerminResponseModal(terminId);
        }
        if(typeof global.scheduleRefreshMainPageTerminEntries === 'function') {
            global.scheduleRefreshMainPageTerminEntries(terminId);
        }
        if(typeof global.invalidateTerminResponseModalCache === 'function') {
            global.invalidateTerminResponseModalCache(terminId);
        }
    }

    function bindSection(root, sectionEl, catalog, terminId) {
        var wert = parseInt(sectionEl.getAttribute('data-melde-wert'), 10) || 0;
        if(wert < 1 || wert > 3) return;

        var input = sectionEl.querySelector('.melde-response-add-input');
        var suggest = sectionEl.querySelector('.melde-response-add-suggest');
        if(!input || !suggest) return;

        var activeIndex = -1;

        function hideSuggest() {
            suggest.hidden = true;
            suggest.innerHTML = '';
            activeIndex = -1;
        }

        function showSuggestions(items) {
            suggest.innerHTML = '';
            if(!items.length) {
                hideSuggest();
                return;
            }
            items.forEach(function(item, idx) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'mail-recipient-suggest-item';
                btn.setAttribute('data-index', String(idx));
                btn.textContent = item.meta ? item.label + ' · ' + item.meta : item.label;
                btn.addEventListener('mousedown', function(ev) {
                    ev.preventDefault();
                });
                btn.addEventListener('click', function(ev) {
                    ev.preventDefault();
                    pickUser(item.id);
                });
                suggest.appendChild(btn);
            });
            suggest.hidden = false;
            activeIndex = 0;
            var first = suggest.querySelector('.mail-recipient-suggest-item');
            if(first) first.classList.add('is-active');
        }

        function filterCatalog(q) {
            q = String(q || '').toLowerCase().trim();
            var taken = assignedUserIds(root);
            var out = [];
            catalog.forEach(function(item) {
                if(taken[item.id]) return;
                if(q === '') return;
                var hay = (item.label + ' ' + item.meta).toLowerCase();
                if(hay.indexOf(q) === -1) return;
                out.push(item);
                if(out.length >= 12) return;
            });
            return out;
        }

        function pickUser(userId) {
            userId = parseInt(userId, 10) || 0;
            if(!(userId > 0) || !terminId) return;
            input.value = '';
            hideSuggest();
            var body = 'cmd=save&ajax=1&user=' + encodeURIComponent(String(userId))
                + '&termin=' + encodeURIComponent(String(terminId))
                + '&wert=' + encodeURIComponent(String(wert))
                + '&Children=0&Guests=0';
            postMelde(body, function() {
                afterMutation(terminId);
            });
        }

        input.addEventListener('input', function() {
            showSuggestions(filterCatalog(input.value));
        });
        input.addEventListener('keydown', function(ev) {
            var items = suggest.querySelectorAll('.mail-recipient-suggest-item');
            if(ev.key === 'Escape') {
                hideSuggest();
                return;
            }
            if(ev.key === 'ArrowDown' && items.length) {
                ev.preventDefault();
                activeIndex = Math.min(activeIndex + 1, items.length - 1);
                items.forEach(function(el, i) {
                    el.classList.toggle('is-active', i === activeIndex);
                });
                return;
            }
            if(ev.key === 'ArrowUp' && items.length) {
                ev.preventDefault();
                activeIndex = Math.max(activeIndex - 1, 0);
                items.forEach(function(el, i) {
                    el.classList.toggle('is-active', i === activeIndex);
                });
                return;
            }
            if(ev.key === 'Enter') {
                if(!items.length) return;
                ev.preventDefault();
                var pick = items[activeIndex] || items[0];
                var idx = parseInt(pick.getAttribute('data-index'), 10) || 0;
                var list = filterCatalog(input.value);
                if(list[idx]) pickUser(list[idx].id);
            }
        });
        input.addEventListener('blur', function() {
            setTimeout(hideSuggest, 150);
        });

        sectionEl.querySelectorAll('.melde-response-editable-chip .mail-recipient-chip-remove').forEach(function(btn) {
            btn.addEventListener('click', function(ev) {
                ev.preventDefault();
                ev.stopPropagation();
                var chip = btn.closest('.melde-response-editable-chip');
                if(!chip) return;
                var userId = parseInt(chip.getAttribute('data-user-id'), 10) || 0;
                if(!(userId > 0) || !terminId) return;
                var body = 'cmd=delete&ajax=1&user=' + encodeURIComponent(String(userId))
                    + '&termin=' + encodeURIComponent(String(terminId));
                postMelde(body, function() {
                    afterMutation(terminId);
                });
            });
        });
    }

    function initIn(root) {
        root = root || document;
        var modal = root.querySelector ? root.querySelector('.termin-response-modal[data-melde-editable="1"]') : null;
        if(!modal || modal.getAttribute('data-melde-chips-init') === '1') return;
        modal.setAttribute('data-melde-chips-init', '1');

        var terminId = getTerminId(root);
        if(!terminId) return;
        var catalog = parseCatalog(root);
        root.querySelectorAll('.melde-response-editable-section[data-melde-wert]').forEach(function(sectionEl) {
            bindSection(root, sectionEl, catalog, terminId);
        });
    }

    global.MeldeResponseChips = { initIn: initIn };
})(window);
