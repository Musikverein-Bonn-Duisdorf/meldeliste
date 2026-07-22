<?php
/**
 * HTML report for DatabaseManager results (used by updater.php / install.php).
 */

/**
 * @param array $report
 * @param bool $notableOnly skip status "ok" (default true for repair/check UI)
 */
function DBRenderReport($report, $notableOnly = true) {
    if($notableOnly && is_array($report)) {
        $filtered = array();
        foreach($report as $entry) {
            $status = isset($entry['status']) ? $entry['status'] : '';
            if(class_exists('DatabaseManager') && DatabaseManager::isNotableStatus($status)) {
                $filtered[] = $entry;
            }
            elseif(!class_exists('DatabaseManager') && $status !== 'ok') {
                $filtered[] = $entry;
            }
        }
        $report = $filtered;
    }

    if(!is_array($report) || !count($report)) {
        echo "<div class=\"w3-panel w3-pale-green w3-padding\"><b>Keine Änderungen oder Probleme.</b> Schema und Daten passen zum Soll.</div>\n";
        return;
    }

    $str = '';
    $currentTable = null;

    foreach($report as $entry) {
        if($entry['level'] === 'table') {
            if($currentTable !== null) {
                $str .= "</div>\n";
            }
            $currentTable = $entry['target'];
            $color = DBStatusColor($entry['status']);
            $str .= "<div class=\"w3-container\">\n";
            $str .= "<div class=\"w3-padding ".$color."\"><b><i class=\"fa-solid fa-database\"></i> "
                .htmlspecialchars($entry['target'])."</b> "
                .htmlspecialchars($entry['message'])."</div>\n";
            continue;
        }

        if($entry['level'] === 'column') {
            $color = DBStatusColor($entry['status']);
            $str .= "<div class=\"w3-row\">";
            $str .= "<div class=\"w3-col l1 m1 s1 w3-hide-small w3-hide-medium\">&nbsp;</div>";
            $str .= "<div class=\"w3-col l11 m11 s12 w3-padding ".$color."\"><i class=\"fa-solid fa-table-columns\"></i> "
                .htmlspecialchars($entry['target'])." — ".htmlspecialchars($entry['message']);
            if(is_array($entry['detail'])) {
                $str .= " <small>".htmlspecialchars(json_encode($entry['detail']))."</small>";
            }
            elseif($entry['detail']) {
                $str .= " <small>".htmlspecialchars($entry['detail'])."</small>";
            }
            $str .= "</div></div>\n";
            continue;
        }

        if($entry['level'] === 'config') {
            $color = DBStatusColor($entry['status']);
            $str .= "<div class=\"w3-row\">";
            $str .= "<div class=\"w3-col l1 m1 s1 w3-hide-small w3-hide-medium\">&nbsp;</div>";
            $str .= "<div class=\"w3-col l11 m11 s12 w3-padding ".$color."\"><i class=\"fa-solid fa-gear\"></i> config."
                .htmlspecialchars($entry['target'])." — ".htmlspecialchars($entry['message'])."</div></div>\n";
            continue;
        }

        if($entry['level'] === 'user' || $entry['level'] === 'data') {
            $color = DBStatusColor($entry['status']);
            $icon = $entry['level'] === 'user' ? 'fa-user' : 'fa-database';
            $str .= "<div class=\"w3-row\">";
            $str .= "<div class=\"w3-col l12 m12 s12 w3-padding ".$color."\"><i class=\"fa-solid ".$icon."\"></i> "
                .htmlspecialchars($entry['target'])." — ".htmlspecialchars($entry['message'])."</div></div>\n";
        }
    }

    if($currentTable !== null) {
        $str .= "</div>\n";
    }

    echo $str;
}

function DBStatusColor($status) {
    switch($status) {
    case 'ok':
        return 'w3-green';
    case 'created':
    case 'fixed':
    case 'removed':
        return 'w3-yellow';
    case 'obsolete':
        return 'w3-orange';
    case 'missing':
    case 'mismatch':
    case 'error':
        return 'w3-red';
    default:
        return 'w3-light-grey';
    }
}

/**
 * @param string $mode check|create|repair
 * @deprecated prefer DatabaseManager directly; kept for updater.php compatibility
 */
function DBCheckIntegrity($mode = 'repair') {
    $manager = new DatabaseManager();
    switch($mode) {
    case 'check':
        $manager->check();
        break;
    case 'create':
        $manager->create();
        break;
    case 'repair':
    default:
        $manager->repair();
        break;
    }
    // Fresh create: show all notable actions (still skips pure "ok").
    DBRenderReport($manager->getReport(), true);
    return $manager;
}
?>
