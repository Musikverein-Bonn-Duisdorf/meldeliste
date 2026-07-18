function track(cronID, user, termin, wert, Children, Guests) {
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
	}
    }
    if(Children == -1) {
        var ChildInput = document.getElementById("Children"+user);
        Children = ChildInput.value;
    }
    if(Guests == -1) {
        var GuestInput = document.getElementById("Guests"+user);
        Guests = GuestInput.value;
    }
    var str = "track.php?cmd=save&id="+cronID+"&user="+user+"&termin="+termin+"&wert="+wert+"&Children="+Children+"&Guests="+Guests;
    xmlhttp.open("GET",str,true);
    xmlhttp.send();
}
