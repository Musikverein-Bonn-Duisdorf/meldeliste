function meldeShift(cronID, user, shift, termin, wert) {
    if (window.XMLHttpRequest) {
	// AJAX nutzen mit IE7+, Chrome, Firefox, Safari, Opera
	xmlhttp=new XMLHttpRequest();
    }
    else {
	// AJAX mit IE6, IE5
	xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange=function() {
	if (xmlhttp.readyState==4 && xmlhttp.status==200) {
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
            if(typeof scheduleRefreshMainPageTerminEntries === 'function') {
                scheduleRefreshMainPageTerminEntries(termin, cronID);
            }
	}
    }
    if(shift == 0) return;
    if(user == 0) return;
    var str = "meldeshift.php?cmd=save&id="+cronID+"&user="+user+"&termin="+termin+"&wert="+wert+"&shift="+shift;
    xmlhttp.open("GET",str,true);
    xmlhttp.send();
}
