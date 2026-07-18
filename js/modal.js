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
 * Toggle orchestra SVG: show only ja (1) / vielleicht (3) seats.
 */
function toggleActiveOrchestra(checkbox) {
    var panel = checkbox;
    while(panel && !(panel.classList && panel.classList.contains('orchestra-panel'))) {
        panel = panel.parentNode;
    }
    if(!panel) return;
    if(checkbox.checked) {
        panel.classList.add('orchestra-view--active-only');
    }
    else {
        panel.classList.remove('orchestra-view--active-only');
    }
}
