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
    if(wert === 1) return {color: '#4CAF50', opacity: '1', label: 'Zusage', textColor: null};
    // Deckend, aber weich wie früher 50% #f42316 über Weiß (kein Register-Durchscheinen)
    if(wert === 2) return {color: '#fa918a', opacity: '1', label: 'Absage', textColor: null};
    if(wert === 3) return {color: '#2196F3', opacity: '0.6', label: 'unsicher', textColor: null};
    // Deckend weiß, damit Register-Bänder nicht durchscheinen (MELD-98)
    return {color: '#ffffff', opacity: '1', label: 'nicht gemeldet', textColor: null};
}

/** Match PHP hexContrastText(): black or white against background luminance. */
function hexContrastText(hex) {
    if(!hex || typeof hex !== 'string') return '#000000';
    var h = hex.trim().replace(/^#/, '');
    if(h.length === 3) {
        h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
    }
    if(!/^[0-9a-fA-F]{6}$/.test(h)) return '#000000';
    var r = parseInt(h.slice(0, 2), 16);
    var g = parseInt(h.slice(2, 4), 16);
    var b = parseInt(h.slice(4, 6), 16);
    var luma = (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255;
    return luma > 0.55 ? '#000000' : '#FFFFFF';
}

/** Match PHP hexContrastTextOnFill(): account for translucent seat fills over white. */
function hexContrastTextOnFill(hex, opacity, bgHex) {
    opacity = parseFloat(opacity);
    if(isNaN(opacity)) opacity = 1;
    if(opacity >= 0.999) return hexContrastText(hex);
    if(!hex || typeof hex !== 'string') return '#000000';
    var h = hex.trim().replace(/^#/, '');
    if(h.length === 3) {
        h = h[0] + h[0] + h[1] + h[1] + h[2] + h[2];
    }
    if(!/^[0-9a-fA-F]{6}$/.test(h)) return '#000000';
    var bg = (bgHex && typeof bgHex === 'string') ? bgHex.trim().replace(/^#/, '') : 'FFFFFF';
    if(bg.length === 3) {
        bg = bg[0] + bg[0] + bg[1] + bg[1] + bg[2] + bg[2];
    }
    if(!/^[0-9a-fA-F]{6}$/.test(bg)) bg = 'FFFFFF';
    opacity = Math.max(0, Math.min(1, opacity));
    var fr = parseInt(h.slice(0, 2), 16);
    var fg = parseInt(h.slice(2, 4), 16);
    var fb = parseInt(h.slice(4, 6), 16);
    var br = parseInt(bg.slice(0, 2), 16);
    var bgc = parseInt(bg.slice(2, 4), 16);
    var bb = parseInt(bg.slice(4, 6), 16);
    var r = Math.round(fr * opacity + br * (1 - opacity));
    var g = Math.round(fg * opacity + bgc * (1 - opacity));
    var b = Math.round(fb * opacity + bb * (1 - opacity));
    var luma = (0.2126 * r + 0.7152 * g + 0.0722 * b) / 255;
    return luma > 0.55 ? '#000000' : '#FFFFFF';
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
        text.setAttribute('fill', visual.textColor || hexContrastTextOnFill(visual.color, visual.opacity));
        text.setAttribute('opacity', '1');
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

function replaceElementWithHtml(el, html) {
    if(!el || !el.parentNode || !html) return null;
    var wrap = document.createElement('div');
    wrap.innerHTML = html;
    var replacement = wrap.firstElementChild;
    if(!replacement) return null;
    el.parentNode.replaceChild(replacement, el);
    return replacement;
}

var mainPageTerminRefreshTimer = null;
var mainPageTerminRefreshSeq = 0;

function scheduleRefreshMainPageTerminEntries(terminId) {
    terminId = parseInt(terminId, 10) || 0;
    if(!terminId) return;
    if(mainPageTerminRefreshTimer) {
        clearTimeout(mainPageTerminRefreshTimer);
    }
    mainPageTerminRefreshTimer = setTimeout(function() {
        mainPageTerminRefreshTimer = null;
        refreshMainPageTerminEntries(terminId);
    }, 200);
}

function refreshMainPageTerminEntries(terminId) {
    terminId = parseInt(terminId, 10) || 0;
    if(!terminId) return;

    var seq = ++mainPageTerminRefreshSeq;

    function getXhr() {
        if(window.XMLHttpRequest) return new XMLHttpRequest();
        return new ActiveXObject('Microsoft.XMLHTTP');
    }

    // Rückmeldungs-Karten (meldungen.php / mein-register.php)
    var responseEl = document.getElementById('responseLine' + terminId);
    if(responseEl) {
        (function(el) {
            var register = el.getAttribute('data-register') || '0';
            var xhr = getXhr();
            xhr.onreadystatechange = function() {
                if(xhr.readyState !== 4) return;
                if(seq !== mainPageTerminRefreshSeq) return;
                if(xhr.status < 200 || xhr.status >= 300 || !xhr.responseText) return;
                replaceElementWithHtml(el, xhr.responseText);
            };
            xhr.open('GET', 'melde.php?cmd=responseLine&termin=' + encodeURIComponent(terminId)
                + '&register=' + encodeURIComponent(register), true);
            xhr.send();
        })(responseEl);
    }

    // Persönliche Melde-Karten (index.php / termine-archiv)
    var nodes = document.querySelectorAll('[id^="entry"]');
    for(var i = 0; i < nodes.length; i++) {
        (function(el) {
            var m = /^entry(\d+)_user(\d+)$/.exec(el.id);
            if(!m) return;
            if(parseInt(m[1], 10) !== terminId) return;
            var userId = m[2];
            var xhr = getXhr();
            xhr.onreadystatechange = function() {
                if(xhr.readyState !== 4) return;
                if(seq !== mainPageTerminRefreshSeq) return;
                if(xhr.status < 200 || xhr.status >= 300 || !xhr.responseText) return;
                replaceElementWithHtml(el, xhr.responseText);
            };
            xhr.open('GET', 'melde.php?cmd=reload&user=' + encodeURIComponent(userId)
                + '&termin=' + encodeURIComponent(terminId), true);
            xhr.send();
        })(nodes[i]);
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

function getOpenTerminResponseContext() {
    var host = document.getElementById('ajaxModalHost');
    var content = document.getElementById('ajaxModalContent');
    if(!host || !content || host.style.display === 'none') return null;
    var root = content.querySelector('.termin-response-modal');
    if(!root) return null;
    var terminId = parseInt(root.getAttribute('data-termin-id'), 10) || 0;
    if(!terminId) return null;
    var register = parseInt(root.getAttribute('data-register'), 10) || 0;
    var activeCb = content.querySelector('.orchestra-panel-toggle input[type="checkbox"]');
    return {
        terminId: terminId,
        register: register,
        activeOnly: !!(activeCb && activeCb.checked)
    };
}

var terminResponseRefreshTimer = null;
var terminResponseRefreshSeq = 0;
var terminResponseRefreshXhr = null;

function scheduleRefreshOpenTerminResponseModal(terminId) {
    terminId = parseInt(terminId, 10) || 0;
    if(!terminId) return;
    invalidateTerminResponseModalCache(terminId);
    if(terminResponseRefreshTimer) {
        clearTimeout(terminResponseRefreshTimer);
    }
    terminResponseRefreshTimer = setTimeout(function() {
        terminResponseRefreshTimer = null;
        refreshOpenTerminResponseModal(terminId);
    }, 280);
}

function refreshOpenTerminResponseModal(terminId) {
    terminId = parseInt(terminId, 10) || 0;
    var ctx = getOpenTerminResponseContext();
    if(!ctx) return;
    if(terminId && ctx.terminId !== terminId) return;

    var host = document.getElementById('ajaxModalHost');
    var content = document.getElementById('ajaxModalContent');
    if(!host || !content) return;

    var scrollTop = host.scrollTop || 0;
    var activeOnly = ctx.activeOnly;
    var register = ctx.register;
    var key = 'terminResponse:' + ctx.terminId;
    if(register) key += ':' + register;

    if(terminResponseRefreshXhr && terminResponseRefreshXhr.abort) {
        try { terminResponseRefreshXhr.abort(); } catch(e) {}
    }
    var seq = ++terminResponseRefreshSeq;
    if(typeof closeOrchestraSeatSheet === 'function') {
        closeOrchestraSeatSheet();
    }

    var xhr;
    if(window.XMLHttpRequest) {
        xhr = new XMLHttpRequest();
    }
    else {
        xhr = new ActiveXObject('Microsoft.XMLHTTP');
    }
    terminResponseRefreshXhr = xhr;
    xhr.onreadystatechange = function() {
        if(xhr.readyState !== 4) return;
        if(seq !== terminResponseRefreshSeq) return;
        terminResponseRefreshXhr = null;
        if(xhr.status < 200 || xhr.status >= 300 || !xhr.responseText) return;

        // Modal inzwischen geschlossen oder anderer Termin?
        var still = getOpenTerminResponseContext();
        if(!still || still.terminId !== ctx.terminId) return;

        modalCache[key] = xhr.responseText;
        content.innerHTML = xhr.responseText;
        if(activeOnly) {
            var cb = content.querySelector('.orchestra-panel-toggle input[type="checkbox"]');
            if(cb) {
                cb.checked = true;
                toggleActiveOrchestra(cb);
            }
        }
        host.scrollTop = scrollTop;
    };
    var url = 'getModal.php?type=terminResponse&id=' + encodeURIComponent(ctx.terminId);
    if(register) url += '&register=' + encodeURIComponent(register);
    xhr.open('GET', url, true);
    xhr.send();
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
            var openCtx = getOpenTerminResponseContext();
            if(openCtx && openCtx.terminId === termin) {
                scheduleRefreshOpenTerminResponseModal(termin);
            }
            else {
                invalidateTerminResponseModalCache(termin);
                refreshOrchestraSeatSheetIfOpen(seat);
            }
            scheduleRefreshMainPageTerminEntries(termin);
        }
        else {
            applyOrchestraSeatWert(seat, wert);
            refreshOrchestraSeatSheetIfOpen(seat);
        }
    };

    var body = 'cmd=save&user='+user+'&termin='+termin+'&wert='+next
        +'&Children='+children+'&Guests='+guests;
    xhr.open('POST', 'melde.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.send(body);
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
        + '<div class="orchestra-seat-sheet-actions" hidden>'
        +   '<button type="button" class="w3-btn w3-border w3-border-black w3-center orchestra-seat-sheet-btn" data-wert="1" title="Zusage">&#10004;</button>'
        +   '<button type="button" class="w3-btn w3-border w3-border-black w3-center orchestra-seat-sheet-btn" data-wert="2" title="Absage">&#10008;</button>'
        +   '<button type="button" class="w3-btn w3-border w3-border-black w3-center orchestra-seat-sheet-btn" data-wert="3" title="unsicher"><b>?</b></button>'
        + '</div>';

    var host = document.getElementById('ajaxModalHost');
    if(host) {
        host.appendChild(sheet);
    }
    else {
        document.body.appendChild(sheet);
    }

    function onClose(ev) {
        ev.preventDefault();
        ev.stopPropagation();
        closeOrchestraSeatSheet();
    }
    function onAction(ev) {
        ev.preventDefault();
        ev.stopPropagation();
        var btn = ev.currentTarget;
        if(!orchestraSheetSeat) return;
        var w = parseInt(btn.getAttribute('data-wert'), 10);
        saveOrchestraSeatWert(orchestraSheetSeat, w);
    }
    sheet.querySelector('.orchestra-seat-sheet-close').addEventListener('click', onClose);
    sheet.querySelectorAll('.orchestra-seat-sheet-btn').forEach(function(btn) {
        btn.addEventListener('click', onAction);
    });
    return sheet;
}

function getOrchestraMeldeButtonStyles() {
    var panel = document.querySelector('#ajaxModalContent .orchestra-panel')
        || document.querySelector('.orchestra-panel');
    return {
        yes: (panel && panel.getAttribute('data-color-yes')) || 'w3-green',
        no: (panel && panel.getAttribute('data-color-no')) || 'w3-red',
        maybe: (panel && panel.getAttribute('data-color-maybe')) || 'w3-blue',
        disabled: (panel && panel.getAttribute('data-color-disabled')) || 'w3-light-grey'
    };
}

function addOrchestraClassTokens(el, classStr) {
    String(classStr || '').split(/\s+/).forEach(function(token) {
        if(token) el.classList.add(token);
    });
}

function styleOrchestraSeatSheetButtons(sheet, currentWert) {
    var styles = getOrchestraMeldeButtonStyles();
    var colorByWert = {1: styles.yes, 2: styles.no, 3: styles.maybe};
    currentWert = parseInt(currentWert, 10) || 0;
    sheet.querySelectorAll('.orchestra-seat-sheet-btn').forEach(function(btn) {
        var w = parseInt(btn.getAttribute('data-wert'), 10);
        btn.className = 'w3-btn w3-border w3-border-black w3-center orchestra-seat-sheet-btn';
        // Wie Termin-Seite: aktuelle Auswahl bzw. alle bei keiner Meldung in Farbe,
        // sonst disabled.
        if(currentWert && currentWert !== w) {
            addOrchestraClassTokens(btn, styles.disabled);
        }
        else {
            addOrchestraClassTokens(btn, colorByWert[w] || styles.disabled);
        }
    });
}

function getOrchestraSeatInfo(seat) {
    var name = seat.getAttribute('data-name') || '';
    var instrument = seat.getAttribute('data-instrument') || '';
    var wertRaw = seat.getAttribute('data-wert');
    var wert = wertRaw === null ? -1 : parseInt(wertRaw, 10);
    if(isNaN(wert)) wert = -1;
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
    var actions = sheet.querySelector('.orchestra-seat-sheet-actions');
    if(info.editable) {
        actions.removeAttribute('hidden');
        styleOrchestraSeatSheetButtons(sheet, info.wert);
    }
    else {
        actions.setAttribute('hidden', 'hidden');
    }
    sheet.removeAttribute('hidden');
    orchestraSuppressClick = false;
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
    orchestraSuppressClick = false;
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
var orchestraLongPressOpened = false;

document.addEventListener('touchstart', function(ev) {
    var seat = findOrchestraSeat(ev.target);
    if(!seat) return;
    clearOrchestraLongPress();
    orchestraLongPressOpened = false;
    var touch = ev.touches && ev.touches[0];
    orchestraLongPressStart = touch ? {x: touch.clientX, y: touch.clientY} : null;
    orchestraLongPressSeat = seat;
    orchestraLongPressTimer = setTimeout(function() {
        orchestraLongPressTimer = null;
        if(orchestraLongPressSeat !== seat) return;
        orchestraLongPressOpened = true;
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
    // Nur den Ghost-Click nach Long-Press unterdrücken, Flag danach zurücksetzen
    if(orchestraLongPressOpened || orchestraSuppressClick) {
        ev.preventDefault();
        ev.stopPropagation();
        orchestraLongPressOpened = false;
        // Kurz gesetzt lassen, falls noch ein click folgt — dann in click-Handler löschen
        setTimeout(function() {
            orchestraSuppressClick = false;
        }, 50);
    }
    clearOrchestraLongPress();
}, {passive: false});

document.addEventListener('touchcancel', function() {
    orchestraLongPressOpened = false;
    orchestraSuppressClick = false;
    clearOrchestraLongPress();
}, {passive: true});

document.addEventListener('contextmenu', function(ev) {
    if(findOrchestraSeat(ev.target)) {
        ev.preventDefault();
    }
});
