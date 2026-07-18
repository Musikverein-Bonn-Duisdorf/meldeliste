<?php
session_start();
$_SESSION['page']='log';
$_SESSION['adminpage']=true;
include "common/header.php";
if(!requirePermission("perm_showLog")) {
    denyAccess();
}

// Buffer chunk build so any echo cannot appear above the search field
ob_start();
$chunk = listChunkLog(0, 50);
$leak = ob_get_clean();
if($leak !== false && $leak !== '') {
    $chunk['html'] = $leak.$chunk['html'];
}
?>
<div id="header" class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
<h2>Log</h2>
</div>
<div class="w3-container w3-padding-16" style="clear:both;">
<input class="w3-input w3-border w3-padding" type="text" placeholder="Log durchsuchen..." id="filterString" onkeyup="filterLog()">
</div>
<div id="Liste" style="clear:both;">
<?php echo $chunk['html']; ?>
<?php echo listChunkRenderSentinel('log', $chunk['nextCursor'], $chunk['hasMore'], 'filterLog'); ?>
</div>
<script>
function getLogMaxIndex() {
    var parent = document.getElementById("Liste");
    if(!parent) return 0;
    var rows = parent.querySelectorAll(":scope > div[id]:not(#listSentinel)");
    if(!rows.length) return 0;
    var max = 0;
    for(var i = 0; i < rows.length; i++) {
        var n = parseInt(rows[i].id, 10);
        if(n > max) max = n;
    }
    return max;
}

function getLog() {
    var maxIndex = getLogMaxIndex();
    if(!(maxIndex > 0)) return;

    var xmlhttp;
    if (window.XMLHttpRequest) {
	xmlhttp=new XMLHttpRequest();
    }
    else {
	xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
    }
    xmlhttp.onreadystatechange=function() {
	if (xmlhttp.readyState==4 && xmlhttp.status==200 && xmlhttp.responseText) {
            var parent = document.getElementById("Liste");
            if(!parent) return;
            var doc = new DOMParser().parseFromString(xmlhttp.responseText, 'text/html');
            var div = doc.body.firstElementChild;
            if(!div || !div.id) return;
            if(document.getElementById(div.id)) return;
            var first = parent.querySelector(":scope > div[id]:not(#listSentinel)");
            if(first) {
                parent.insertBefore(div, first);
            }
            else {
                var sentinel = document.getElementById("listSentinel");
                if(sentinel) parent.insertBefore(div, sentinel);
                else parent.appendChild(div);
            }
	}
    }
    var str = "getLog.php?id="+<?php echo "\"".$GLOBALS['cronID']."\""; ?>+"&maxIndex="+maxIndex;
    xmlhttp.open("GET", str, true);
    xmlhttp.send();
}
var interval = setInterval(getLog, 1000);
</script>

<script src="js/filterLog.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/infiniteScroll.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>

<?php
include "common/footer.php";
?>
