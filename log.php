<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page']='log';
$_SESSION['adminpage']=true;
include "common/header.php";
if(!requirePermission("perm_showLog")) {
    denyAccess();
}

$logChunkLimit = listChunkLogConfiguredLimit();
$logActiveTypes = listChunkLogPreferredTypes();
$logActiveSet = array_fill_keys($logActiveTypes, true);
$logLevelsAttr = implode(',', $logActiveTypes);

$logLevelDefs = array(
    array(0, 'FATAL', 'fatal'),
    array(1, 'ERROR', 'error'),
    array(2, 'WARNING', 'warning'),
    array(3, 'DB DELETE', 'db-delete'),
    array(4, 'DB INSERT', 'db-insert'),
    array(5, 'DB UPDATE', 'db-update'),
    array(6, 'EMAIL', 'email'),
    array(7, 'INFO', 'info'),
    array(8, 'DEBUG', 'debug'),
);

// Buffer chunk build so any echo cannot appear above the search field
ob_start();
$chunk = listChunkLog(0, $logChunkLimit, '', $logActiveTypes);
$leak = ob_get_clean();
if($leak !== false && $leak !== '') {
    $chunk['html'] = $leak.$chunk['html'];
}
?>
<?php
ob_start();
?>
<div id="logLevelFilters" class="inv-sort-bar log-level-filters" role="group" aria-label="Log-Level">
  <div class="inv-sort-bar-filters">
<?php foreach($logLevelDefs as $def) {
    list($id, $label, $mod) = $def;
    $on = isset($logActiveSet[$id]);
    $cls = 'inv-sort-chip inv-filter-chip log-level-chip log-type-chip log-type-chip--'.$mod
        .($on ? ' is-active' : '');
?>
    <button type="button"
            class="<?php echo htmlspecialchars($cls, ENT_QUOTES, 'UTF-8'); ?>"
            data-log-level="<?php echo (int)$id; ?>"
            aria-pressed="<?php echo $on ? 'true' : 'false'; ?>"><?php
        echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8');
    ?></button>
<?php } ?>
  </div>
</div>
<?php
$logLevelFiltersHtml = ob_get_clean();
adminListPageBegin('System', 'Log');
adminListSearchField('Log durchsuchen…', array(
    'afterSearchHtml' => $logLevelFiltersHtml,
));
?>
<div id="Liste" style="clear:both;">
<?php echo $chunk['html']; ?>
<?php echo listChunkRenderSentinel(
    'log',
    $chunk['nextCursor'],
    $chunk['hasMore'],
    'filterLog',
    ' data-limit="'.(int)$logChunkLimit.'" data-server-q="1" data-levels="'.htmlspecialchars($logLevelsAttr, ENT_QUOTES, 'UTF-8').'"'
); ?>
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

function getLogPollLimit() {
    var sentinel = document.getElementById("listSentinel");
    if(!sentinel) return 0;
    var n = parseInt(sentinel.getAttribute("data-limit") || "0", 10);
    return n > 0 ? n : 0;
}

function getLogPollLevels() {
    var sentinel = document.getElementById("listSentinel");
    if(sentinel && sentinel.hasAttribute("data-levels")) {
        return sentinel.getAttribute("data-levels") || "";
    }
    if(typeof window.logLevelFilterSelected === "function") {
        return window.logLevelFilterSelected().join(",");
    }
    return "0,1,2,3,4,5,6";
}

function getLog() {
    var maxIndex = getLogMaxIndex();
    if(!(maxIndex > 0)) return;
    var topTimestamp = getLogTopTimestamp();
    var limit = getLogPollLimit();
    var levels = getLogPollLevels();

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
            var nodes = [];
            for(var c = doc.body.firstElementChild; c; c = c.nextElementSibling) {
                if(c.id) nodes.push(c);
            }
            if(!nodes.length) return;

            var frag = document.createDocumentFragment();
            var hasNew = false;
            for(var i = 0; i < nodes.length; i++) {
                var div = nodes[i];
                var existing = document.getElementById(div.id);
                if(existing) {
                    // Deduped log: same Index, newer Timestamp — refresh in place (MELD-160)
                    existing.parentNode.replaceChild(div, existing);
                }
                else {
                    frag.appendChild(div);
                    hasNew = true;
                }
            }
            if(hasNew) {
                // MELD-165: batch prepend (HTML is newest-first)
                var first = parent.querySelector(":scope > div[id]:not(#listSentinel)");
                if(first) {
                    parent.insertBefore(frag, first);
                }
                else {
                    var sentinel = document.getElementById("listSentinel");
                    if(sentinel) parent.insertBefore(frag, sentinel);
                    else parent.appendChild(frag);
                }
            }
            // MELD-164: live prepend/refresh must respect active filter
            var filterInput = document.getElementById("filterString");
            if(filterInput && filterInput.value && typeof filterLog === "function") {
                filterLog();
            }
	}
    }
    var body = "maxIndex="+encodeURIComponent(maxIndex)
        +"&topTimestamp="+encodeURIComponent(topTimestamp)
        +"&limit="+encodeURIComponent(limit)
        +"&levels="+encodeURIComponent(levels);
    xmlhttp.open("POST", "getLog.php", true);
    xmlhttp.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xmlhttp.send(body);
}
var interval = setInterval(getLog, 1000);
</script>

<script src="js/filterLog.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/logLevelFilter.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>
<script src="js/infiniteScroll.js?<?php echo $GLOBALS['version']['Hash']; ?>"></script>

<?php
include "common/footer.php";
?>
