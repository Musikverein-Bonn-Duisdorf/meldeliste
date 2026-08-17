/**
 * Calendar melde modal helpers (MELD-126).
 * Close intermediate modal + update chip colors after melde().
 */
function invalidateCalendarMeldeModalCache(terminId) {
    if(typeof modalCache === 'undefined') return;
    var prefix = 'calendarMelde:' + terminId;
    Object.keys(modalCache).forEach(function(k) {
        if(k === prefix || k.indexOf(prefix + ':') === 0) {
            delete modalCache[k];
        }
    });
}

function refreshOpenCalendarMeldeModal(terminId) {
    var root = document.querySelector('.calendar-melde-modal[data-termin-id="' + terminId + '"]');
    if(!root) return;
    invalidateCalendarMeldeModalCache(terminId);
    if(typeof closeModal === 'function') {
        closeModal();
    }
}

function calendarChipColorClass(wert) {
    var wrap = document.querySelector('.meld-cal-wrap');
    if(!wrap) return '';
    var w = parseInt(wert, 10);
    if(w === 1) return wrap.getAttribute('data-color-yes') || '';
    if(w === 2) return wrap.getAttribute('data-color-no') || '';
    if(w === 3) return wrap.getAttribute('data-color-maybe') || '';
    return wrap.getAttribute('data-color-none') || '';
}

function updateCalendarChipsForTermin(terminId, wert) {
    var chips = document.querySelectorAll('.meld-cal-chip[data-termin-id="' + terminId + '"]');
    if(!chips.length) return;
    var wrap = document.querySelector('.meld-cal-wrap');
    var palette = [];
    if(wrap) {
        palette = [
            wrap.getAttribute('data-color-yes') || '',
            wrap.getAttribute('data-color-no') || '',
            wrap.getAttribute('data-color-maybe') || '',
            wrap.getAttribute('data-color-none') || ''
        ];
    }
    var next = calendarChipColorClass(wert);
    chips.forEach(function(chip) {
        palette.forEach(function(cls) {
            if(cls) chip.classList.remove(cls);
        });
        if(next) chip.classList.add(next);
        chip.setAttribute('data-melde-wert', (wert === null || wert === undefined || wert === '') ? '' : String(wert));
    });
}

function calendarFormatDeDate(iso) {
    var m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso || '');
    if(!m) return iso || '';
    return m[3] + '.' + m[2] + '.' + m[1];
}

/**
 * Day overflow "+N": list all events for that day, then open calendarMelde.
 */
function openCalendarDayEventsPicker(dateIso, events) {
    var host = document.getElementById('ajaxModalHost');
    var content = document.getElementById('ajaxModalContent');
    if(!host || !content || !events || !events.length) return;

    var esc = function(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    };
    var titleDate = calendarFormatDeDate(dateIso);
    var items = '';
    events.forEach(function(ev) {
        var id = parseInt(ev.id, 10);
        if(!(id > 0)) return;
        var color = esc(ev.color || '');
        var label = esc(ev.label || ('Termin #' + id));
        var wert = (ev.wert === null || ev.wert === undefined) ? '' : String(ev.wert);
        var wrap = document.querySelector('.meld-cal-wrap');
        var unpubCls = wrap ? (wrap.getAttribute('data-style-unpublished') || 'w3-opacity') : 'w3-opacity';
        var unpub = ev.unpublished ? (' ' + esc(unpubCls)) : '';
        items += '<button type="button" class="meld-cal-day-pick-item ' + color + unpub + '"'
            + ' data-termin-id="' + id + '"'
            + ' data-melde-wert="' + esc(wert) + '">'
            + label
            + '</button>';
    });
    if(!items) return;

    content.innerHTML =
        '<div class="profile-shell modal-shell calendar-day-events-modal" data-date="' + esc(dateIso) + '">'
        + '<header class="profile-hero">'
        + '<div class="profile-hero-text">'
        + '<p class="profile-kicker">Kalender</p>'
        + '<h2 class="profile-title">' + esc(titleDate) + '</h2>'
        + '</div>'
        + '<div class="profile-hero-actions">'
        + '<button type="button" class="modal-close w3-button" onclick="closeModal()" aria-label="Schließen">&times;</button>'
        + '</div>'
        + '</header>'
        + '<div class="profile-grid">'
        + '<section class="profile-col">'
        + '<div class="meld-cal-day-pick-list">' + items + '</div>'
        + '</section>'
        + '</div>'
        + '</div>';
    host.style.display = 'block';
}

function calendarOfferNewTermin(dateIso) {
    if(!dateIso) return;
    var label = calendarFormatDeDate(dateIso);
    var go = function() {
        window.location.href = 'new-termin.php?Datum=' + encodeURIComponent(dateIso);
    };
    if(typeof window.appConfirm === 'function') {
        window.appConfirm('Neuen Termin am ' + label + ' anlegen?', {
            title: 'Termin',
            okLabel: 'Anlegen'
        }).then(function(ok) {
            if(ok) go();
        });
        return;
    }
    go();
}

document.addEventListener('click', function(e) {
    var more = e.target.closest ? e.target.closest('.meld-cal-more') : null;
    if(more) {
        e.preventDefault();
        e.stopPropagation();
        var raw = more.getAttribute('data-cal-day-events') || '[]';
        var events = [];
        try {
            events = JSON.parse(raw);
        }
        catch(err) {
            events = [];
        }
        openCalendarDayEventsPicker(more.getAttribute('data-date') || '', events);
        return;
    }
    var pick = e.target.closest ? e.target.closest('.meld-cal-day-pick-item') : null;
    if(pick) {
        e.preventDefault();
        e.stopPropagation();
        var tid = parseInt(pick.getAttribute('data-termin-id'), 10);
        if(tid > 0 && typeof openModal === 'function') {
            openModal('calendarMelde', tid);
        }
    }
}, true);

document.addEventListener('DOMContentLoaded', function() {
    var wrap = document.querySelector('.meld-cal-wrap');
    if(!wrap || wrap.getAttribute('data-can-create') !== '1') return;
    wrap.addEventListener('click', function(e) {
        if(e.target.closest('.meld-cal-chip, .meld-cal-more, .meld-cal-day-pick-item, button, a, select, input')) {
            return;
        }
        var cell = e.target.closest('.meld-cal-cell');
        if(!cell || !wrap.contains(cell)) return;
        var dateIso = cell.getAttribute('data-date');
        if(dateIso) {
            calendarOfferNewTermin(dateIso);
        }
    });
});
