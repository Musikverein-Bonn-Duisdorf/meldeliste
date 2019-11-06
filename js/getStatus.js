function getStatus(cronID, user, termin) {
    if (window.XMLHttpRequest) {
	// AJAX nutzen mit IE7+, Chrome, Firefox, Safari, Opera
	xmlhttp=new XMLHttpRequest();
    }
    else {
	// AJAX mit IE6, IE5
	xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
    }
    var str = "melde.php?cmd=status&id="+cronID+"&user="+user+"&termin="+termin;
    xmlhttp.open("GET",str,true);
    xmlhttp.send();    
}
