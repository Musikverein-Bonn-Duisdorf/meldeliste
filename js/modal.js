/**
 * Lazy-load list detail modals into a shared host.
 */
var modalCache = {};
var modalLoadingKey = null;

function closeModal() {
    var host = document.getElementById('ajaxModalHost');
    if(host) host.style.display = 'none';
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

/** Cycle: unsicher → Absage → Zusage → unsicher (MELD-64). Unset → unsicher. */
function orchestraNextWert(wert) {
    wert = parseInt(wert, 10) || 0;
    if(wert === 3) return 2;
    if(wert === 2) return 1;
    if(wert === 1) return 3;
    return 3;
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
        var name = title.textContent.split(' — ')[0] || title.textContent;
        title.textContent = name + ' — ' + visual.label;
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
    if(seat.getAttribute('data-busy') === '1') return;

    var user = parseInt(seat.getAttribute('data-user'), 10) || 0;
    var termin = parseInt(seat.getAttribute('data-termin'), 10) || 0;
    var wert = parseInt(seat.getAttribute('data-wert'), 10) || 0;
    var children = parseInt(seat.getAttribute('data-children'), 10) || 0;
    var guests = parseInt(seat.getAttribute('data-guests'), 10) || 0;
    if(!user || !termin) return;

    var cronID = getOrchestraCronId(seat);
    if(!cronID) return;

    var next = orchestraNextWert(wert);
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
        }
    };

    var str = 'melde.php?cmd=save&id='+encodeURIComponent(cronID)
        +'&user='+user+'&termin='+termin+'&wert='+next
        +'&Children='+children+'&Guests='+guests;
    xhr.open('GET', str, true);
    xhr.send();
}

document.addEventListener('click', function(ev) {
    var t = ev.target;
    if(!t || !t.closest) return;
    var seat = t.closest('.orchestra-seat');
    if(!seat) return;
    if(seat.getAttribute('data-editable') !== '1') return;
    ev.preventDefault();
    cycleOrchestraSeat(seat);
});
