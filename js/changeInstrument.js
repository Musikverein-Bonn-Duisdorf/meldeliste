function changeInstrument(user, termin) {
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
    var body = "cmd=instrument&user="+encodeURIComponent(user)
        +"&termin="+encodeURIComponent(termin)
        +"&instrument="+encodeURIComponent(instrument);
    xmlhttp.open("POST", "melde.php", true);
    xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xmlhttp.send(body);
}
