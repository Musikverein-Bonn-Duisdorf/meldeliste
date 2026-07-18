<?php
/**
 * Render orchestra seating SVG.
 *
 * Always uses logical coordinates with viewBox so CSS can scale to the container
 * ($scale is ignored for output size; kept for call-site compatibility).
 *
 * @param int  $tid        Termin id (0 = static register colors, no responses)
 * @param float $scale     Unused for sizing (compat)
 * @param bool $activeOnly Packed layout: only ja/vielleicht
 */
function printOrchestra($tid, $scale = 1, $activeOnly = false) {
    unset($scale); // sizing is CSS/viewBox based
    $baseWidth = 1000;
    $baseHeight = 600;
    $rowdistance = 60;
    $minrowdistance = 150;
    $seatR = 18;

    $editable = false;
    if($tid) {
        $editable = requirePermission('perm_editResponse');
    }

    $svgClass = 'orchestra-svg';
    if($editable) {
        $svgClass .= ' orchestra-svg--editable';
    }

    $aMeldungen = array();
    $aAushilfen = array();
    $aInstrument = array();
    $aUser = array();
    if($tid) {
        $sql = sprintf("SELECT * FROM `%sMeldungen` INNER JOIN (SELECT `Index` AS `uIndex`, `Vorname`, `Nachname`, `Instrument` AS `uInstrument` FROM `%sUser`) `%sUser` ON `User` = `uIndex` WHERE `Termin` = %d ORDER BY `Instrument`, `Nachname`, `Vorname`;",
                       $GLOBALS['dbprefix'],
                       $GLOBALS['dbprefix'],
                       $GLOBALS['dbprefix'],
                       $tid
        );
        $dbMeldungen = mysqli_query($GLOBALS['conn'], $sql);
        while($row = mysqli_fetch_array($dbMeldungen)) {
            $aMeldungen[] = $row;
        }

        $sql = sprintf("SELECT * FROM `%sAushilfen` WHERE `Termin` = %d;",
        $GLOBALS['dbprefix'],
        $tid
        );
        $dbAushilfe = mysqli_query($GLOBALS['conn'], $sql);
        while($row = mysqli_fetch_array($dbAushilfe)) {
            $aAushilfen[] = $row;
        }
    }
    $sql = sprintf("SELECT * FROM `%sUser` WHERE `Deleted` = 0 ORDER BY `Nachname`, `Vorname`;",
                   $GLOBALS['dbprefix']
    );
    $dbUser = mysqli_query($GLOBALS['conn'], $sql);
    while($row = mysqli_fetch_array($dbUser)) {
        $aUser[] = $row;
    }
    $sql = sprintf("SELECT * FROM `%sInstrument`;",
                   $GLOBALS['dbprefix']
    );
    $dbInstrument = mysqli_query($GLOBALS['conn'], $sql);
    while($row = mysqli_fetch_array($dbInstrument)) {
        $aInstrument[] = $row;
    }

    $meldungByUser = array();
    foreach($aMeldungen as $meldung) {
        $meldungByUser[(int)$meldung['User']] = $meldung;
    }

    // Build musician roster once (not per register).
    $allMusikerBase = array();
    foreach($aUser as $user) {
        $uid = (int)$user['Index'];
        $short = getShort($user['Vorname'], $user['Nachname']);
        $wert = -1;
        $instr = $user['Instrument'];
        $children = 0;
        $guests = 0;
        $fullName = trim($user['Vorname'].' '.$user['Nachname']);
        if(isset($meldungByUser[$uid])) {
            $meldung = $meldungByUser[$uid];
            if($meldung['Instrument'] != $user['Instrument'] && $meldung['Instrument'] > 0) {
                $instr = $meldung['Instrument'];
            }
            $wert = $meldung['Wert'];
            $children = (int)$meldung['Children'];
            $guests = (int)$meldung['Guests'];
        }
        $allMusikerBase[] = array(
            'userId' => $uid,
            'aushilfe' => false,
            'short' => $short,
            'name' => $fullName,
            'Instrument' => $instr,
            'homeInstrument' => $user['Instrument'],
            'Wert' => $wert,
            'Children' => $children,
            'Guests' => $guests,
        );
    }
    if($tid) {
        foreach($aAushilfen as $user) {
            $short = getShortAushilfe($user['Name']);
            $allMusikerBase[] = array(
                'userId' => 0,
                'aushilfe' => true,
                'short' => $short,
                'name' => (string)$user['Name'],
                'Instrument' => $user['Instrument'],
                'homeInstrument' => $user['Instrument'],
                'Wert' => 1,
                'Children' => 0,
                'Guests' => 0,
            );
        }
    }

    $sql = sprintf('SELECT * FROM `%sRegister` ORDER BY `Row`;',
                   $GLOBALS['dbprefix']
    );
    $dbregister = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    $k = 0;
    $j = 0;
    $lastrow = 0;
    $lmaxradius = array();
    $rmaxradius = array();
    $radius = 0;
    array_push($lmaxradius, 0);
    array_push($rmaxradius, 0);

    $seatsHtml = '';
    $minX = null;
    $minY = null;
    $maxX = null;
    $maxY = null;

    while($register = mysqli_fetch_array($dbregister)) {
        if($lastrow != $register['Row']) {
            array_push($lmaxradius, $lmaxradius[count($lmaxradius)-1]+$rowdistance);
            array_push($rmaxradius, $rmaxradius[count($rmaxradius)-1]+$rowdistance);
        }
        $lastrow = $register['Row'];
        if($register['Row'] > 0) {
            if($register['ArcMin'] < 90) {
                $radius = $lmaxradius[$register['Row']-1]+$rowdistance;
            }
            else {
                $radius = $rmaxradius[$register['Row']-1]+$rowdistance;
            }
        }
        if($radius < $minrowdistance) {
            $radius = $minrowdistance;
        }

        $registerInstruments = array();
        $sorting = array();
        for($idx = 0; $idx < count($aInstrument); $idx++) {
            if($aInstrument[$idx]['Register'] == $register['Index']) {
                $registerInstruments[] = $idx;
                $sorting[] = (int)$aInstrument[$idx]['Sortierung'];
            }
        }
        asort($sorting);
        $sortedInstruments = array();
        $keys = array_keys($sorting);
        for($idx = 0; $idx < count($registerInstruments); $idx++) {
            $sortedInstruments[] = $aInstrument[$registerInstruments[$keys[$idx]]]['Index'];
        }

        $allMusiker = $allMusikerBase;

        $allSorted = array();
        foreach($allMusiker as $m) {
            if($m['Wert'] != 1) {
                $allSorted[] = $m;
            }
        }
        foreach($allMusiker as $m) {
            if($m['Wert'] == 1) {
                $allSorted[] = $m;
            }
        }
        $allMusiker = $allSorted;

        if($tid && $activeOnly) {
            $activeMusiker = array();
            foreach($allMusiker as $m) {
                $w = (int)$m['Wert'];
                if($w === 1 || $w === 3) {
                    $activeMusiker[] = $m;
                }
            }
            $allMusiker = $activeMusiker;
        }

        $hasActiveDirigent = false;
        if($tid && (int)$register['Row'] === 0) {
            foreach($allMusiker as $m) {
                if(!in_array($m['Instrument'], $sortedInstruments)) continue;
                $w = (int)$m['Wert'];
                if($w === 1 || $w === 3) {
                    $hasActiveDirigent = true;
                    break;
                }
            }
        }

        foreach($sortedInstruments as $instrument) {
            foreach($allMusiker as $user) {
                if($user['Instrument'] != $instrument) continue;
                if($tid && (int)$register['Row'] === 0) {
                    $w = (int)$user['Wert'];
                    if($hasActiveDirigent) {
                        if($w !== 1 && $w !== 3) {
                            continue;
                        }
                    }
                    elseif(!in_array($user['homeInstrument'], $sortedInstruments)) {
                        continue;
                    }
                }

                $short = $user['short'];
                $match = isset($user['Wert']) ? $user['Wert'] : -1;
                if($register['Row'] == 0) {
                    $radius = 0;
                    $arc = 0;
                }
                else {
                    $arc = $register['ArcMin']+$k*($register['ArcMax']-$register['ArcMin'])/abs($register['ArcMax']-$register['ArcMin'])*40/(2*pi()*$radius)*360;
                    if($register['ArcMin'] < $register['ArcMax']) {
                        if($arc+20/(2*pi()*$radius)*360 >= $register['ArcMax']) {
                            $j++;
                            $radius += 40;
                            $k = 0;
                        }
                    }
                    elseif($register['ArcMin'] > $register['ArcMax']) {
                        if($arc-20/(2*pi()*$radius)*360 <= $register['ArcMax']) {
                            $j++;
                            $radius += 40;
                            $k = 0;
                        }
                    }
                    if($register['ArcMin'] < 90) {
                        if($radius > $lmaxradius[$register['Row']]) {
                            $lmaxradius[$register['Row']] = $radius;
                        }
                    }
                    else {
                        if($radius > $rmaxradius[$register['Row']]) {
                            $rmaxradius[$register['Row']] = $radius;
                        }
                    }
                    $arc = $register['ArcMin']+$k*($register['ArcMax']-$register['ArcMin'])/abs($register['ArcMax']-$register['ArcMin'])*40/(2*pi()*$radius)*360;
                }
                $x = $baseWidth/2-$radius*cos($arc/180*pi());
                $y = 40+$radius*sin($arc/180*pi());

                if($minX === null) {
                    $minX = $x - $seatR;
                    $maxX = $x + $seatR;
                    $minY = $y - $seatR;
                    $maxY = $y + $seatR;
                }
                else {
                    $minX = min($minX, $x - $seatR);
                    $maxX = max($maxX, $x + $seatR);
                    $minY = min($minY, $y - $seatR);
                    $maxY = max($maxY, $y + $seatR);
                }

                $safeShort = htmlspecialchars((string)$short, ENT_QUOTES, 'UTF-8');
                if($tid) {
                    $style = orchestraSeatVisual($match);
                    $wertAttr = (int)$match;
                    if($wertAttr < 0) {
                        $wertAttr = 0;
                    }
                    $titleText = htmlspecialchars(
                        $user['name'].' — '.$style['label'],
                        ENT_QUOTES,
                        'UTF-8'
                    );
                    $seatEditable = $editable && !$user['aushilfe'] && (int)$user['userId'] > 0;
                    $seatsHtml .= '<g class="orchestra-seat"'
                        .' data-wert="'.$wertAttr.'"'
                        .' data-user="'.(int)$user['userId'].'"'
                        .' data-termin="'.(int)$tid.'"'
                        .' data-children="'.(int)$user['Children'].'"'
                        .' data-guests="'.(int)$user['Guests'].'"'
                        .' data-editable="'.($seatEditable ? '1' : '0').'"'
                        .($seatEditable ? ' style="cursor:pointer"' : '')
                        .">\n";
                    $seatsHtml .= '<title>'.$titleText."</title>\n";
                    $seatsHtml .= '<circle opacity="'.$style['opacity'].'" cx="'.$x.'" cy="'.$y.'" r="'.$seatR.'" stroke="black" stroke-width="2" fill="'.$style['color']."\" />\n";
                    $seatsHtml .= '<text opacity="'.$style['opacity'].'" text-anchor="middle" alignment-baseline="central" fill="#000000" font-size="10" x="'.$x.'" y="'.$y.'">'.$safeShort."</text>\n";
                    $seatsHtml .= "</g>\n";
                }
                else {
                    $seatsHtml .= '<circle cx="'.$x.'" cy="'.$y.'" r="'.$seatR.'" stroke="black" stroke-width="2" fill="'.$register['Color']."\" />\n";
                    $seatsHtml .= '<text text-anchor="middle" alignment-baseline="central" fill="#000000" font-size="10" x="'.$x.'" y="'.$y.'">'.$safeShort."</text>\n";
                }

                $k++;
            }
        }
        $k = 0;
        $j = 0;
    }

    $pad = 24;
    if($minX === null) {
        $vbX = 0;
        $vbY = 0;
        $vbW = $baseWidth;
        $vbH = $baseHeight;
    }
    else {
        // Never zoom in for sparse seating: keep at least the full stage.
        // Only expand the viewBox when seats overflow the base canvas.
        $contentMinX = $minX - $pad;
        $contentMinY = $minY - $pad;
        $contentMaxX = $maxX + $pad;
        $contentMaxY = $maxY + $pad;
        $vbX = min(0, $contentMinX);
        $vbY = min(0, $contentMinY);
        $vbW = max($baseWidth, $contentMaxX - $vbX);
        $vbH = max($baseHeight, $contentMaxY - $vbY);
    }

    $str = '<svg class="'.$svgClass.'" viewBox="'
        .htmlspecialchars(sprintf('%.2f %.2f %.2f %.2f', $vbX, $vbY, $vbW, $vbH), ENT_QUOTES, 'UTF-8')
        .'" width="100%" height="auto" preserveAspectRatio="xMidYMid meet" role="img" aria-label="Orchesterbesetzung"';
    if($tid && !empty($GLOBALS['cronID'])) {
        $str .= ' data-cron-id="'.htmlspecialchars((string)$GLOBALS['cronID'], ENT_QUOTES, 'UTF-8').'"';
    }
    $str .= '>';
    $str .= $seatsHtml;
    $str .= '</svg>';
    return $str;
}

function orchestraSeatVisual($wert) {
    switch((int)$wert) {
    case 1:
        return array('color' => '#4CAF50', 'opacity' => 1, 'label' => 'Zusage');
    case 2:
        return array('color' => '#f42316', 'opacity' => 0.5, 'label' => 'Absage');
    case 3:
        return array('color' => '#2196F3', 'opacity' => 0.6, 'label' => 'unsicher');
    default:
        return array('color' => '#ffffff', 'opacity' => 0.5, 'label' => 'nicht gemeldet');
    }
}
