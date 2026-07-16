<?php
session_start();
$_SESSION['page']='log';
$_SESSION['adminpage']=true;
include "common/header.php";
if(!requirePermission("perm_showLog")) die();

$chunk = listChunkLog(0, 50);
?>
<div id="header" class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
<h2>Log</h2>
</div>
<input class="w3-input w3-border w3-padding" type="text" placeholder="Log durchsuchen..." id="filterString" onkeyup="filterLog()">
<div id="Liste">
<?php echo $chunk['html']; ?>
<div<?php echo listChunkRenderSentinelAttrs('log', $chunk['nextCursor'], $chunk['hasMore'], 'filterLog'); ?>></div>
</div>
<script>
    Element.prototype.appendAfter = function (element) {
	element.parentNode.insertBefore(this, element.nextSibling);
    }, false;

function getLog() {
    if (window.XMLHttpRequest) {
	xmlhttp=new XMLHttpRequest();
    }
    else {
	xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange=function() {
	if (xmlhttp.readyState==4 && xmlhttp.status==200 && xmlhttp.responseText) {
            var parent = document.getElementById("Liste");
            var NewElement = document.createElement("div");
	    var first = parent.firstElementChild;
            if(!first || first.id === 'listSentinel') return;
	    first.parentNode.insertBefore(NewElement, first);
            let doc = new DOMParser().parseFromString(xmlhttp.responseText, 'text/html');
            let div = doc.body.firstChild;
            NewElement.parentNode.replaceChild(div, NewElement);
	}
    }
    var parent = document.getElementById("Liste");
    var first = parent.firstElementChild;
    if(!first || first.id === 'listSentinel') return;
    var maxIndex = parseInt(first.id);
    if(maxIndex > 0) {
        var str = "getLog.php?id="+<?php echo "\"".$GLOBALS['cronID']."\""; ?>+"&maxIndex="+maxIndex;
        xmlhttp.open("GET", str, true);
        xmlhttp.send();
    }
}
var interval = setInterval(getLog, 1000);

</script>

<script src="js/filterLog.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/infiniteScroll.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>

<?php
include "common/footer.php";
?>
