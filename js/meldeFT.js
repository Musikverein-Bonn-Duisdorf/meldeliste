function meldeFT(cronID, user, termin) {
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
            var newel = document.createElement('div');
            newel.innerHTML = xmlhttp.responseText;
            oldel.parentNode.replaceChild(newel, oldel);
	}
    }
    var FreeText = document.getElementById("FreeText"+termin);
    FreeText = FreeText.value;
    if(user == 0) return;
    if(isNaN(wert) || wert > 3 || wert < 0) return;
    if(termin == 0) return;
    var str = "melde.php?cmd=freetext&id="+cronID+"&user="+user+"&termin="+termin+"&freeText="+FreeText;
    xmlhttp.open("GET",str,true);
    xmlhttp.send();
}
