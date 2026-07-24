<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
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
<?php
adminListPageBegin('System', 'Log');
adminListSearchField('Log durchsuchen…', array('onkeyup' => 'filterLog()'));
?>
<div id="Liste" style="clear:both;">
<?php echo $chunk['html']; ?>
<?php echo listChunkRenderSentinel('log', $chunk['nextCursor'], $chunk['hasMore'], 'filterLog'); ?>
</div>
<?php adminListPageEnd(); ?>
<script>
function getLogTopRow() {
    var parent = document.getElementById("Liste");
    if(!parent) return null;
    return parent.querySelector(":scope > div[id]:not(#listSentinel)");
}

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

function getLogTopTimestamp() {
    var top = getLogTopRow();
    if(!top) return '';
    return top.getAttribute('data-timestamp') || '';
}

function getLog() {
    var maxIndex = getLogMaxIndex();
    if(!(maxIndex > 0)) return;
    var topTimestamp = getLogTopTimestamp();

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
            var existing = document.getElementById(div.id);
            if(existing) {
                // Deduped log: same Index, newer Timestamp — refresh in place (MELD-160)
                existing.parentNode.replaceChild(div, existing);
                return;
            }
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
    var body = "maxIndex="+encodeURIComponent(maxIndex)
        +"&topTimestamp="+encodeURIComponent(topTimestamp);
    xmlhttp.open("POST", "getLog.php", true);
    xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xmlhttp.send(body);
}
var interval = setInterval(getLog, 1000);
</script>

<script src="js/filterLog.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/infiniteScroll.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>

<?php
include "common/footer.php";
?>
