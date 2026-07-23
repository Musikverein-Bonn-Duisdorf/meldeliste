function melde(user, termin, wert, Children, Guests) {
    if (window.XMLHttpRequest) {
	// AJAX nutzen mit IE7+, Chrome, Firefox, Safari, Opera
	xmlhttp=new XMLHttpRequest();
    }
    else {
	// AJAX mit IE6, IE5
	xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange=function() {
	if (xmlhttp.readyState!=4) return;
	if (xmlhttp.status!=200) {
            if (typeof console !== 'undefined' && console.warn) {
                console.warn('Meldung fehlgeschlagen:', xmlhttp.status, xmlhttp.responseText);
            }
            return;
	}
            var oldel = document.getElementById("entry"+termin+"_user"+user);
            if(oldel && oldel.parentNode && xmlhttp.responseText) {
                if(typeof replaceElementWithHtml === 'function') {
                    replaceElementWithHtml(oldel, xmlhttp.responseText);
                }
                else {
                    var newel = document.createElement('div');
                    newel.innerHTML = xmlhttp.responseText;
                    var replacement = newel.firstElementChild || newel.firstChild;
                    if(replacement) {
                        oldel.parentNode.replaceChild(replacement, oldel);
                    }
                }
            }
            if(typeof scheduleRefreshOpenTerminResponseModal === 'function') {
                scheduleRefreshOpenTerminResponseModal(termin);
            }
            else if(typeof invalidateTerminResponseModalCache === 'function') {
                invalidateTerminResponseModalCache(termin);
            }
            if(typeof refreshOpenCalendarMeldeModal === 'function') {
                refreshOpenCalendarMeldeModal(termin);
            }
            else if(typeof invalidateCalendarMeldeModalCache === 'function') {
                invalidateCalendarMeldeModalCache(termin);
            }
            if(typeof updateCalendarChipsForTermin === 'function') {
                updateCalendarChipsForTermin(termin, wert);
            }
            // Weitere Einträge desselben Termins (z. B. Zähler) nachziehen
            if(typeof scheduleRefreshMainPageTerminEntries === 'function') {
                scheduleRefreshMainPageTerminEntries(termin);
            }
    }
    if(Children == -1) {
        var ChildInput = document.getElementById("Children"+termin);
        Children = ChildInput.value;
    }
    if(Guests == -1) {
        var GuestInput = document.getElementById("Guests"+termin);
        Guests = GuestInput.value;
    }
    if(user == 0) return;
    if(isNaN(wert) || wert > 3 || wert < 0) return;
    if(termin == 0) return;
    var body = "cmd=save&user="+encodeURIComponent(user)
        +"&termin="+encodeURIComponent(termin)
        +"&wert="+encodeURIComponent(wert)
        +"&Children="+encodeURIComponent(Children)
        +"&Guests="+encodeURIComponent(Guests);
    xmlhttp.open("POST", "melde.php", true);
    xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xmlhttp.send(body);
}
