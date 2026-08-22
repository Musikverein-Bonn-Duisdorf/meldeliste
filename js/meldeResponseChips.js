/**
 * MELD-175: chip add/remove for termin + shift response modals (Ja / Nein / Unsicher).
 */
(function(global) {
    'use strict';

    var focusWertAfterRefresh = 0;

    function requestFocusAfterRefresh(wert) {
        focusWertAfterRefresh = parseInt(wert, 10) || 0;
    }

    function restoreFocusIfRequested(root) {
        var wert = focusWertAfterRefresh;
        if(!wert) {
            return;
        }
        focusWertAfterRefresh = 0;
        root = root || document;
        if(!root.querySelector) {
            return;
        }
        var section = root.querySelector('.melde-response-editable-section[data-melde-wert="' + wert + '"]');
        var input = section && section.querySelector('.melde-response-add-input');
        if(!input) {
            return;
        }
        setTimeout(function() {
            try {
                input.focus({ preventScroll: true });
            } catch(e) {
                input.focus();
            }
        }, 0);
    }

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

    function getMeldeModalContext(root) {
        var modal = root.querySelector('.termin-response-modal');
        if(!modal) {
            return { terminId: 0, shiftId: 0 };
        }
        return {
            terminId: parseInt(modal.getAttribute('data-termin-id'), 10) || 0,
            shiftId: parseInt(modal.getAttribute('data-shift-id'), 10) || 0
        };
    }

    function assignedUserIdsInSection(sectionEl, wert) {
        var ids = {};
        if(!sectionEl) {
            return ids;
        }
        wert = parseInt(wert, 10) || 0;
        sectionEl.querySelectorAll('.melde-response-editable-chip[data-user-id]').forEach(function(chip) {
            var chipWert = parseInt(chip.getAttribute('data-melde-wert'), 10) || 0;
            if(wert > 0 && chipWert !== wert) {
                return;
            }
            var id = parseInt(chip.getAttribute('data-user-id'), 10) || 0;
            if(id > 0) {
                ids[id] = true;
            }
        });
        return ids;
    }

    function postMelde(url, body, onOk, onFail) {
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
        xhr.open('POST', url, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.send(body);
    }

    function afterMutation(ctx) {
        ctx = ctx || {};
        if(ctx.shiftId && typeof global.scheduleRefreshOpenShiftResponseModal === 'function') {
            global.scheduleRefreshOpenShiftResponseModal(ctx.shiftId);
        }
        if(ctx.terminId && typeof global.scheduleRefreshOpenTerminResponseModal === 'function') {
            global.scheduleRefreshOpenTerminResponseModal(ctx.terminId);
        }
        if(ctx.terminId && typeof global.scheduleRefreshMainPageTerminEntries === 'function') {
            global.scheduleRefreshMainPageTerminEntries(ctx.terminId);
        }
        if(ctx.shiftId && typeof global.invalidateShiftResponseModalCache === 'function') {
            global.invalidateShiftResponseModalCache(ctx.shiftId);
        }
        if(ctx.terminId && typeof global.invalidateTerminResponseModalCache === 'function') {
            global.invalidateTerminResponseModalCache(ctx.terminId);
        }
    }

    function bindSection(root, sectionEl, catalog, ctx) {
        var wert = parseInt(sectionEl.getAttribute('data-melde-wert'), 10) || 0;
        if(wert < 1 || wert > 3) return;

        var input = sectionEl.querySelector('.melde-response-add-input');
        var suggest = sectionEl.querySelector('.melde-response-add-suggest');
        if(!input || !suggest) return;

        var activeIndex = -1;
        var isShift = ctx.shiftId > 0;
        var postUrl = isShift ? 'meldeshift.php' : 'melde.php';

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
            var taken = assignedUserIdsInSection(sectionEl, wert);
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
            if(!(userId > 0)) return;
            if(isShift && !ctx.shiftId) return;
            if(!isShift && !ctx.terminId) return;
            input.value = '';
            hideSuggest();
            var body = 'cmd=save&ajax=1&user=' + encodeURIComponent(String(userId))
                + '&wert=' + encodeURIComponent(String(wert));
            if(isShift) {
                body += '&shift=' + encodeURIComponent(String(ctx.shiftId));
                if(ctx.terminId) {
                    body += '&termin=' + encodeURIComponent(String(ctx.terminId));
                }
            } else {
                body += '&termin=' + encodeURIComponent(String(ctx.terminId))
                    + '&Children=0&Guests=0';
            }
            postMelde(postUrl, body, function() {
                requestFocusAfterRefresh(wert);
                afterMutation(ctx);
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
                if(!(userId > 0)) return;
                if(isShift && !ctx.shiftId) return;
                if(!isShift && !ctx.terminId) return;
                var body = 'cmd=delete&ajax=1&user=' + encodeURIComponent(String(userId));
                if(isShift) {
                    body += '&shift=' + encodeURIComponent(String(ctx.shiftId));
                } else {
                    body += '&termin=' + encodeURIComponent(String(ctx.terminId));
                }
                postMelde(postUrl, body, function() {
                    afterMutation(ctx);
                });
            });
        });
    }

    function initIn(root) {
        root = root || document;
        var modal = root.querySelector ? root.querySelector('.termin-response-modal[data-melde-editable="1"]') : null;
        if(!modal || modal.getAttribute('data-melde-chips-init') === '1') return;
        modal.setAttribute('data-melde-chips-init', '1');

        var ctx = getMeldeModalContext(root);
        if(!ctx.terminId && !ctx.shiftId) return;
        var catalog = parseCatalog(root);
        root.querySelectorAll('.melde-response-editable-section[data-melde-wert]').forEach(function(sectionEl) {
            bindSection(root, sectionEl, catalog, ctx);
        });
        restoreFocusIfRequested(root);
    }

    global.MeldeResponseChips = { initIn: initIn, restoreFocusIfRequested: restoreFocusIfRequested };
})(window);
