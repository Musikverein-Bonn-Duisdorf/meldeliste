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
            var newel = document.createElement('div');
            newel.innerHTML = xmlhttp.responseText;
            oldel.parentNode.replaceChild(newel, oldel);
	}
    }
    var str = "meldeshift.php?cmd=save&id="+cronID+"&user="+user+"&termin="+termin+"&wert="+wert+"&shift="+shift;
    xmlhttp.open("GET",str,true);
    xmlhttp.send();
}
