function changeInstrument(cronID, user, termin) {
    if (window.XMLHttpRequest) {
	// AJAX nutzen mit IE7+, Chrome, Firefox, Safari, Opera
	xmlhttp=new XMLHttpRequest();
    }
    else {
	// AJAX mit IE6, IE5
	xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
    }
    var e = document.getElementById("iSelect"+termin);
    var instrument = e.options[e.selectedIndex].value;
    var str = "melde.php?cmd=instrument&id="+cronID+"&user="+user+"&termin="+termin+"&instrument="+instrument;
    xmlhttp.open("GET",str,true);
    xmlhttp.send();
}
