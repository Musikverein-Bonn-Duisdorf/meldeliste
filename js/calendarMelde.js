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

function calendarOfferNewTermin(dateIso) {
    if(!dateIso) return;
    var label = calendarFormatDeDate(dateIso);
    if(!window.confirm('Neuen Termin am ' + label + ' anlegen?')) {
        return;
    }
    window.location.href = 'new-termin.php?Datum=' + encodeURIComponent(dateIso);
}

document.addEventListener('DOMContentLoaded', function() {
    var wrap = document.querySelector('.meld-cal-wrap');
    if(!wrap || wrap.getAttribute('data-can-create') !== '1') return;
    wrap.addEventListener('click', function(e) {
        if(e.target.closest('.meld-cal-chip, .meld-cal-more, button, a, select, input')) {
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
