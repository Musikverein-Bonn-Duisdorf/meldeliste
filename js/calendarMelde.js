/**
 * Calendar melde modal helpers (MELD-126).
 * Refresh intermediate modal + chip colors after melde().
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
    var host = document.getElementById('ajaxModalHost');
    if(!host || host.style.display === 'none') return;
    var root = document.querySelector('.calendar-melde-modal[data-termin-id="' + terminId + '"]');
    if(!root) return;
    invalidateCalendarMeldeModalCache(terminId);
    if(typeof openModal === 'function') {
        openModal('calendarMelde', terminId);
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
