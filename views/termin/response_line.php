<?php
$col = $colsize;
$actcol = 2;
?>
<div class="w3-row <?php echo $entry['colorClass']; ?>"><div class="w3-col l<?php echo $col[0]; ?> m<?php echo $col[0]; ?> s<?php echo $col[0]; ?>"><?php echo $entry['name']; ?></div>
<div class="w3-col l<?php echo $col[1]; ?> m<?php echo $col[1]; ?> s<?php echo $col[1]; ?>"><?php echo $entry['instrument'] !== '' ? $entry['instrument'] : '&nbsp;'; ?></div><?php
if($entry['children'] !== null) {
    echo '<div class="w3-col l'.$col[$actcol].' m'.$col[$actcol].' s'.$col[$actcol].'">';
    if($entry['children'] === false) {
        // empty placeholder for Absage
    }
    elseif($entry['children'] > 0) {
        echo '+ '.$entry['children'];
    }
    else {
        echo '&nbsp;';
    }
    echo '</div>';
    $actcol++;
}
if($entry['guests'] !== null) {
    echo '<div class="w3-col l'.$col[$actcol].' m'.$col[$actcol].' s'.$col[$actcol].'">';
    if($entry['guests'] === false) {
        // empty placeholder for Absage
    }
    elseif($entry['guests'] > 0) {
        echo '+ '.$entry['guests'];
    }
    else {
        echo '&nbsp;';
    }
    echo '</div>';
    $actcol++;
}
if($entry['freeText'] !== null && $entry['freeText'] !== '') {
    echo '<div class="w3-col l'.$col[$actcol].' m'.$col[$actcol].' s'.$col[$actcol].'">'.$entry['freeText'].'</div>';
}
?>
</div>
