/**
 * Lazy-load list detail modals into a shared host.
 */
var modalCache = {};
var modalLoadingKey = null;

function closeModal() {
    var host = document.getElementById('ajaxModalHost');
    if(host) host.style.display = 'none';
    if(typeof closeOrchestraSeatSheet === 'function') closeOrchestraSeatSheet();
}

function openModal(type, id, register) {
    var host = document.getElementById('ajaxModalHost');
    var content = document.getElementById('ajaxModalContent');
    if(!host || !content) return;

    var key = type + ':' + id;
    if(register) key += ':' + register;
    if(modalCache[key]) {
        content.innerHTML = modalCache[key];
        host.style.display = 'block';
        return;
    }

    content.innerHTML = '<div class="w3-container w3-padding w3-center"><p>Lade…</p></div>';
    host.style.display = 'block';
    modalLoadingKey = key;

    var xhr;
    if(window.XMLHttpRequest) {
        xhr = new XMLHttpRequest();
    }
    else {
        xhr = new ActiveXObject('Microsoft.XMLHTTP');
    }
    xhr.onreadystatechange = function() {
        if(xhr.readyState !== 4) return;
        if(modalLoadingKey !== key) return;
        if(xhr.status >= 200 && xhr.status < 300 && xhr.responseText) {
            modalCache[key] = xhr.responseText;
            content.innerHTML = xhr.responseText;
        }
        else if(xhr.responseText) {
            content.innerHTML = xhr.responseText;
        }
        else {
            content.innerHTML = '<div class="w3-container w3-padding"><header class="w3-container"><span onclick="closeModal()" class="w3-button w3-display-topright">&times;</span><h2>Fehler</h2></header><p>Modal konnte nicht geladen werden (HTTP '+xhr.status+').</p></div>';
        }
    };
    var url = 'getModal.php?type=' + encodeURIComponent(type) + '&id=' + encodeURIComponent(id);
    if(register) url += '&register=' + encodeURIComponent(register);
    xhr.open('GET', url, true);
    xhr.send();
}

document.addEventListener('keydown', function(e) {
    if(e.key === 'Escape') closeModal();
});

/**
 * Toggle between full seating plan and packed active-only (ja/vielleicht) layout.
 */
function toggleActiveOrchestra(checkbox) {
    var panel = checkbox;
    while(panel && !(panel.classList && panel.classList.contains('orchestra-panel'))) {
        panel = panel.parentNode;
    }
    if(!panel) return;
    var full = panel.querySelector('.orchestra-layout--full');
    var active = panel.querySelector('.orchestra-layout--active');
    if(!full || !active) return;
    if(checkbox.checked) {
        full.setAttribute('hidden', 'hidden');
        active.removeAttribute('hidden');
    }
    else {
        active.setAttribute('hidden', 'hidden');
        full.removeAttribute('hidden');
    }
}

/** Cycle: keine Meldung → Ja → Nein → vielleicht → Ja … (MELD-64). */
function orchestraNextWert(wert) {
    wert = parseInt(wert, 10) || 0;
    if(wert === 1) return 2;
    if(wert === 2) return 3;
    if(wert === 3) return 1;
    return 1;
}

function orchestraSeatVisual(wert) {
    wert = parseInt(wert, 10) || 0;
    if(wert === 1) return {color: '#4CAF50', opacity: '1', label: 'Zusage'};
    if(wert === 2) return {color: '#f42316', opacity: '0.5', label: 'Absage'};
    if(wert === 3) return {color: '#2196F3', opacity: '0.6', label: 'unsicher'};
    return {color: '#ffffff', opacity: '0.5', label: 'nicht gemeldet'};
}

function applyOrchestraSeatWert(seat, wert) {
    wert = parseInt(wert, 10) || 0;
    seat.setAttribute('data-wert', String(wert));
    var visual = orchestraSeatVisual(wert);
    var circle = seat.querySelector('circle');
    var text = seat.querySelector('text');
    var title = seat.querySelector('title');
    if(circle) {
        circle.setAttribute('fill', visual.color);
        circle.setAttribute('opacity', visual.opacity);
    }
    if(text) {
        text.setAttribute('opacity', visual.opacity);
    }
    if(title) {
        var instrument = seat.getAttribute('data-instrument') || '';
        var raw = title.textContent || '';
        var parts = raw.split(/\n| — /);
        var name = (parts[0] || '').trim() || raw;
        if(!instrument && parts.length >= 3) {
            instrument = (parts[1] || '').trim();
        }
        var lines = [name];
        if(instrument) {
            lines.push(instrument);
        }
        lines.push(visual.label);
        title.textContent = lines.join('\n');
    }
}

function invalidateTerminResponseModalCache(terminId) {
    var prefix = 'terminResponse:' + terminId;
    Object.keys(modalCache).forEach(function(key) {
        if(key === prefix || key.indexOf(prefix + ':') === 0) {
            delete modalCache[key];
        }
    });
}

function syncOrchestraSeatsForUser(terminId, userId, wert) {
    var seats = document.querySelectorAll(
        '.orchestra-seat[data-termin="'+terminId+'"][data-user="'+userId+'"]'
    );
    for(var i = 0; i < seats.length; i++) {
        applyOrchestraSeatWert(seats[i], wert);
        var inActive = seats[i].closest && seats[i].closest('.orchestra-layout--active');
        if(inActive && wert !== 1 && wert !== 3) {
            seats[i].style.display = 'none';
        }
        else if(inActive) {
            seats[i].style.display = '';
        }
    }
}

function getOrchestraCronId(seat) {
    var svg = seat.ownerSVGElement;
    if(svg && svg.getAttribute('data-cron-id')) {
        return svg.getAttribute('data-cron-id');
    }
    var meta = document.querySelector('meta[name="cron-id"]');
    if(meta) return meta.getAttribute('content');
    if(typeof window.cronID !== 'undefined' && window.cronID) return window.cronID;
    return null;
}

function cycleOrchestraSeat(seat) {
    if(!seat || seat.getAttribute('data-editable') !== '1') return;
    var wert = parseInt(seat.getAttribute('data-wert'), 10) || 0;
    saveOrchestraSeatWert(seat, orchestraNextWert(wert));
}

function saveOrchestraSeatWert(seat, next) {
    if(!seat || seat.getAttribute('data-editable') !== '1') return;
    if(seat.getAttribute('data-busy') === '1') return;

    var user = parseInt(seat.getAttribute('data-user'), 10) || 0;
    var termin = parseInt(seat.getAttribute('data-termin'), 10) || 0;
    var wert = parseInt(seat.getAttribute('data-wert'), 10) || 0;
    var children = parseInt(seat.getAttribute('data-children'), 10) || 0;
    var guests = parseInt(seat.getAttribute('data-guests'), 10) || 0;
    next = parseInt(next, 10) || 0;
    if(!user || !termin || next < 1 || next > 3) return;

    var cronID = getOrchestraCronId(seat);
    if(!cronID) return;

    seat.setAttribute('data-busy', '1');
    applyOrchestraSeatWert(seat, next);

    var xhr;
    if(window.XMLHttpRequest) {
        xhr = new XMLHttpRequest();
    }
    else {
        xhr = new ActiveXObject('Microsoft.XMLHTTP');
    }
    xhr.onreadystatechange = function() {
        if(xhr.readyState !== 4) return;
        seat.removeAttribute('data-busy');
        if(xhr.status >= 200 && xhr.status < 300) {
            syncOrchestraSeatsForUser(termin, user, next);
            invalidateTerminResponseModalCache(termin);
            refreshOrchestraSeatSheetIfOpen(seat);
            var oldel = document.getElementById('entry'+termin+'_user'+user);
            if(oldel && xhr.responseText) {
                var wrap = document.createElement('div');
                wrap.innerHTML = xhr.responseText;
                var replacement = wrap.firstElementChild || wrap.firstChild;
                if(replacement && oldel.parentNode) {
                    oldel.parentNode.replaceChild(replacement, oldel);
                }
            }
        }
        else {
            applyOrchestraSeatWert(seat, wert);
            refreshOrchestraSeatSheetIfOpen(seat);
        }
    };

    var str = 'melde.php?cmd=save&id='+encodeURIComponent(cronID)
        +'&user='+user+'&termin='+termin+'&wert='+next
        +'&Children='+children+'&Guests='+guests;
    xhr.open('GET', str, true);
    xhr.send();
}

var orchestraSheetSeat = null;
var orchestraLongPressTimer = null;
var orchestraLongPressSeat = null;
var orchestraSuppressClick = false;
var ORCHESTRA_LONG_PRESS_MS = 450;

function ensureOrchestraSeatSheet() {
    var sheet = document.getElementById('orchestraSeatSheet');
    if(sheet) return sheet;

    sheet = document.createElement('div');
    sheet.id = 'orchestraSeatSheet';
    sheet.className = 'orchestra-seat-sheet';
    sheet.setAttribute('hidden', 'hidden');
    sheet.innerHTML =
        '<button type="button" class="orchestra-seat-sheet-close" aria-label="Schließen">&times;</button>'
        + '<div class="orchestra-seat-sheet-name"></div>'
        + '<div class="orchestra-seat-sheet-instrument"></div>'
        + '<div class="orchestra-seat-sheet-status"></div>'
        + '<div class="orchestra-seat-sheet-actions" hidden>'
        +   '<button type="button" class="w3-btn w3-border orchestra-seat-sheet-btn" data-wert="1">Ja</button>'
        +   '<button type="button" class="w3-btn w3-border orchestra-seat-sheet-btn" data-wert="2">Nein</button>'
        +   '<button type="button" class="w3-btn w3-border orchestra-seat-sheet-btn" data-wert="3">Vielleicht</button>'
        + '</div>';
    document.body.appendChild(sheet);

    sheet.querySelector('.orchestra-seat-sheet-close').addEventListener('click', function(ev) {
        ev.preventDefault();
        closeOrchestraSeatSheet();
    });
    sheet.querySelectorAll('.orchestra-seat-sheet-btn').forEach(function(btn) {
        btn.addEventListener('click', function(ev) {
            ev.preventDefault();
            ev.stopPropagation();
            if(!orchestraSheetSeat) return;
            var w = parseInt(btn.getAttribute('data-wert'), 10);
            saveOrchestraSeatWert(orchestraSheetSeat, w);
        });
    });
    return sheet;
}

function getOrchestraSeatInfo(seat) {
    var name = seat.getAttribute('data-name') || '';
    var instrument = seat.getAttribute('data-instrument') || '';
    var wert = parseInt(seat.getAttribute('data-wert'), 10);
    if(isNaN(wert)) wert = -1;
    var visual = orchestraSeatVisual(wert);
    if(!name) {
        var title = seat.querySelector('title');
        if(title) {
            var parts = (title.textContent || '').split(/\n/);
            name = (parts[0] || '').trim();
            if(!instrument && parts.length >= 2) instrument = (parts[1] || '').trim();
        }
    }
    return {
        name: name,
        instrument: instrument,
        wert: wert,
        statusLabel: visual.label,
        editable: seat.getAttribute('data-editable') === '1'
    };
}

function openOrchestraSeatSheet(seat) {
    if(!seat) return;
    var sheet = ensureOrchestraSeatSheet();
    var info = getOrchestraSeatInfo(seat);
    orchestraSheetSeat = seat;
    sheet.querySelector('.orchestra-seat-sheet-name').textContent = info.name || 'Musiker';
    var instrEl = sheet.querySelector('.orchestra-seat-sheet-instrument');
    if(info.instrument) {
        instrEl.textContent = info.instrument;
        instrEl.removeAttribute('hidden');
    }
    else {
        instrEl.textContent = '';
        instrEl.setAttribute('hidden', 'hidden');
    }
    var statusEl = sheet.querySelector('.orchestra-seat-sheet-status');
    if(seat.getAttribute('data-wert') !== null && seat.hasAttribute('data-wert')) {
        statusEl.textContent = info.statusLabel;
        statusEl.removeAttribute('hidden');
    }
    else {
        statusEl.textContent = '';
        statusEl.setAttribute('hidden', 'hidden');
    }
    var actions = sheet.querySelector('.orchestra-seat-sheet-actions');
    if(info.editable) {
        actions.removeAttribute('hidden');
        actions.querySelectorAll('.orchestra-seat-sheet-btn').forEach(function(btn) {
            var w = parseInt(btn.getAttribute('data-wert'), 10);
            if(w === info.wert) {
                btn.classList.add('orchestra-seat-sheet-btn--active');
            }
            else {
                btn.classList.remove('orchestra-seat-sheet-btn--active');
            }
        });
    }
    else {
        actions.setAttribute('hidden', 'hidden');
    }
    sheet.removeAttribute('hidden');
}

function refreshOrchestraSeatSheetIfOpen(seat) {
    if(!orchestraSheetSeat || !seat) return;
    if(orchestraSheetSeat === seat
        || (orchestraSheetSeat.getAttribute('data-user') === seat.getAttribute('data-user')
            && orchestraSheetSeat.getAttribute('data-termin') === seat.getAttribute('data-termin'))) {
        openOrchestraSeatSheet(seat);
    }
}

function closeOrchestraSeatSheet() {
    var sheet = document.getElementById('orchestraSeatSheet');
    if(sheet) sheet.setAttribute('hidden', 'hidden');
    orchestraSheetSeat = null;
}

function clearOrchestraLongPress() {
    if(orchestraLongPressTimer) {
        clearTimeout(orchestraLongPressTimer);
        orchestraLongPressTimer = null;
    }
    orchestraLongPressSeat = null;
    orchestraLongPressStart = null;
}

function findOrchestraSeat(el) {
    while(el && el !== document) {
        if(el.classList && el.classList.contains('orchestra-seat')) {
            return el;
        }
        el = el.parentNode || el.parentElement;
    }
    return null;
}

function clearDomTextSelection() {
    var sel = window.getSelection ? window.getSelection() : null;
    if(sel && sel.removeAllRanges) sel.removeAllRanges();
}

document.addEventListener('click', function(ev) {
    var t = ev.target;
    if(!t) return;

    var sheet = document.getElementById('orchestraSeatSheet');
    if(sheet && !sheet.hasAttribute('hidden')) {
        if(sheet.contains(t)) return;
        if(!findOrchestraSeat(t)) {
            closeOrchestraSeatSheet();
        }
    }

    var seat = findOrchestraSeat(t);
    if(!seat) return;

    if(orchestraSuppressClick) {
        orchestraSuppressClick = false;
        ev.preventDefault();
        ev.stopPropagation();
        return;
    }

    // Klick/Tipp: Meldung wechseln (wenn erlaubt)
    if(seat.getAttribute('data-editable') === '1') {
        ev.preventDefault();
        cycleOrchestraSeat(seat);
    }
});

var orchestraLongPressStart = null;

document.addEventListener('touchstart', function(ev) {
    var seat = findOrchestraSeat(ev.target);
    if(!seat) return;
    clearOrchestraLongPress();
    var touch = ev.touches && ev.touches[0];
    orchestraLongPressStart = touch ? {x: touch.clientX, y: touch.clientY} : null;
    orchestraLongPressSeat = seat;
    orchestraLongPressTimer = setTimeout(function() {
        orchestraLongPressTimer = null;
        if(orchestraLongPressSeat !== seat) return;
        orchestraSuppressClick = true;
        clearDomTextSelection();
        openOrchestraSeatSheet(seat);
        if(navigator.vibrate) {
            try { navigator.vibrate(20); } catch(e) {}
        }
    }, ORCHESTRA_LONG_PRESS_MS);
}, {passive: true});

document.addEventListener('touchmove', function(ev) {
    if(!orchestraLongPressSeat || !orchestraLongPressStart) return;
    var touch = ev.touches && ev.touches[0];
    if(!touch) {
        clearOrchestraLongPress();
        return;
    }
    var dx = touch.clientX - orchestraLongPressStart.x;
    var dy = touch.clientY - orchestraLongPressStart.y;
    if((dx * dx + dy * dy) > 100) { // ~10px
        clearOrchestraLongPress();
    }
}, {passive: true});

document.addEventListener('touchend', function(ev) {
    if(orchestraSuppressClick) {
        ev.preventDefault();
        ev.stopPropagation();
    }
    clearOrchestraLongPress();
}, {passive: false});

document.addEventListener('touchcancel', function() {
    clearOrchestraLongPress();
}, {passive: true});

document.addEventListener('contextmenu', function(ev) {
    if(findOrchestraSeat(ev.target)) {
        ev.preventDefault();
    }
});
