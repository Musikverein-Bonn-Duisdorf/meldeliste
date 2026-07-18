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

    $showRegisterBands = !empty($GLOBALS['optionsDB']['showOrchestraRegisterBands']);

    $editable = false;
    if($tid) {
        $editable = requirePermission('perm_editResponse');
    }

    $svgClass = 'orchestra-svg';
    if($editable) {
        $svgClass .= ' orchestra-svg--editable';
    }
    if($showRegisterBands) {
        $svgClass .= ' orchestra-svg--register-bands';
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

    $instrumentNameById = array();
    foreach($aInstrument as $instRow) {
        $instrumentNameById[(int)$instRow['Index']] = html_entity_decode((string)$instRow['Name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
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
        $instrId = (int)$instr;
        $allMusikerBase[] = array(
            'userId' => $uid,
            'aushilfe' => false,
            'short' => $short,
            'name' => $fullName,
            'Instrument' => $instr,
            'instrumentName' => isset($instrumentNameById[$instrId]) ? $instrumentNameById[$instrId] : '',
            'homeInstrument' => $user['Instrument'],
            'Wert' => $wert,
            'Children' => $children,
            'Guests' => $guests,
        );
    }
    if($tid) {
        foreach($aAushilfen as $user) {
            $short = getShortAushilfe($user['Name']);
            $instrId = (int)$user['Instrument'];
            $allMusikerBase[] = array(
                'userId' => 0,
                'aushilfe' => true,
                'short' => $short,
                'name' => (string)$user['Name'],
                'Instrument' => $user['Instrument'],
                'instrumentName' => isset($instrumentNameById[$instrId]) ? $instrumentNameById[$instrId] : '',
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
    $bandsHtml = '';
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

        // Reihenfolge stabil halten (Instrument-Sortierung + Name), nicht nach Meldewert umsortieren.

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

        $regSeatPoints = array();

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

                $regSeatPoints[] = array($x, $y);

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
                $instrLabel = isset($user['instrumentName']) ? trim((string)$user['instrumentName']) : '';
                if($tid) {
                    $style = orchestraSeatVisual($match);
                    $wertAttr = (int)$match;
                    if($wertAttr < 0) {
                        $wertAttr = 0;
                    }
                    $titleParts = array($user['name']);
                    if($instrLabel !== '') {
                        $titleParts[] = $instrLabel;
                    }
                    $titleParts[] = $style['label'];
                    $titleText = htmlspecialchars(implode("\n", $titleParts), ENT_QUOTES, 'UTF-8');
                    $seatEditable = $editable && !$user['aushilfe'] && (int)$user['userId'] > 0;
                    $seatsHtml .= '<g class="orchestra-seat"'
                        .' data-wert="'.$wertAttr.'"'
                        .' data-user="'.(int)$user['userId'].'"'
                        .' data-termin="'.(int)$tid.'"'
                        .' data-children="'.(int)$user['Children'].'"'
                        .' data-guests="'.(int)$user['Guests'].'"'
                        .' data-name="'.htmlspecialchars((string)$user['name'], ENT_QUOTES, 'UTF-8').'"'
                        .' data-editable="'.($seatEditable ? '1' : '0').'"'
                        .($instrLabel !== '' ? ' data-instrument="'.htmlspecialchars($instrLabel, ENT_QUOTES, 'UTF-8').'"' : '')
                        .($seatEditable ? ' style="cursor:pointer"' : '')
                        .">\n";
                    $seatsHtml .= '<title>'.$titleText."</title>\n";
                    $seatsHtml .= '<circle opacity="'.$style['opacity'].'" cx="'.$x.'" cy="'.$y.'" r="'.$seatR.'" stroke="black" stroke-width="2" fill="'.$style['color']."\" />\n";
                    $seatsHtml .= '<text opacity="'.$style['opacity'].'" text-anchor="middle" dominant-baseline="middle" fill="#000000" font-size="10" x="'.$x.'" y="'.$y.'">'.$safeShort."</text>\n";
                    $seatsHtml .= "</g>\n";
                }
                else {
                    $titleParts = array($user['name']);
                    if($instrLabel !== '') {
                        $titleParts[] = $instrLabel;
                    }
                    $titleText = htmlspecialchars(implode("\n", $titleParts), ENT_QUOTES, 'UTF-8');
                    $seatsHtml .= '<g class="orchestra-seat"'
                        .' data-name="'.htmlspecialchars((string)$user['name'], ENT_QUOTES, 'UTF-8').'"'
                        .($instrLabel !== '' ? ' data-instrument="'.htmlspecialchars($instrLabel, ENT_QUOTES, 'UTF-8').'"' : '')
                        .'>'."\n";
                    $seatsHtml .= '<title>'.$titleText."</title>\n";
                    $seatsHtml .= '<circle cx="'.$x.'" cy="'.$y.'" r="'.$seatR.'" stroke="black" stroke-width="2" fill="'.$register['Color']."\" />\n";
                    $seatsHtml .= '<text text-anchor="middle" dominant-baseline="middle" fill="#000000" font-size="10" x="'.$x.'" y="'.$y.'">'.$safeShort."</text>\n";
                    $seatsHtml .= "</g>\n";
                }

                $k++;
            }
        }

        if($showRegisterBands && count($regSeatPoints) > 0) {
            $bandColor = isset($register['Color']) ? (string)$register['Color'] : '#cccccc';
            $safeName = htmlspecialchars(html_entity_decode((string)$register['Name'], ENT_QUOTES | ENT_HTML5, 'UTF-8'), ENT_QUOTES, 'UTF-8');
            $bandsHtml .= orchestraRegisterRibbonSvg($regSeatPoints, $seatR + 8, $bandColor, $safeName);
        }

        $k = 0;
        $j = 0;
    }

    $pad = 12;
    if($minX === null) {
        $vbX = 0;
        $vbY = 0;
        $vbW = $baseWidth;
        $vbH = $baseHeight;
    }
    else {
        $contentW = ($maxX - $minX) + 2 * $pad;
        $contentH = ($maxY - $minY) + 2 * $pad;
        $cx = ($minX + $maxX) / 2;
        $cy = ($minY + $maxY) / 2;

        if($tid) {
            // Meldungs-Modal: eng an Sitze
            $minViewW = 560;
            $minViewH = 340;
        }
        else {
            // Register/Musiker: Mittelding zwischen Vollbühne und Modal-Zoom
            $minViewW = 820;
            $minViewH = 480;
        }

        $vbW = max($contentW, $minViewW);
        $vbH = max($contentH, $minViewH);
        $vbX = $cx - $vbW / 2;
        $vbY = $cy - $vbH / 2;
    }

    $str = '<svg class="'.$svgClass.'" viewBox="'
        .htmlspecialchars(sprintf('%.2f %.2f %.2f %.2f', $vbX, $vbY, $vbW, $vbH), ENT_QUOTES, 'UTF-8')
        .'" preserveAspectRatio="xMidYMin meet" role="img" aria-label="Orchesterbesetzung"';
    if($tid && !empty($GLOBALS['cronID'])) {
        $str .= ' data-cron-id="'.htmlspecialchars((string)$GLOBALS['cronID'], ENT_QUOTES, 'UTF-8').'"';
    }
    $str .= '>';
    if($bandsHtml !== '') {
        $str .= '<g class="orchestra-register-bands" pointer-events="none">'.$bandsHtml.'</g>';
    }
    $str .= $seatsHtml;
    $str .= '</svg>';
    return $str;
}

/**
 * Register highlight: round ribbon along seat placement order only.
 */
function orchestraRegisterRibbonSvg($points, $haloR, $color, $title = '') {
    $n = count($points);
    if($n < 1) {
        return '';
    }

    $fill = htmlspecialchars($color, ENT_QUOTES, 'UTF-8');
    $html = '<g class="orchestra-register-band">';
    if($title !== '') {
        $html .= '<title>'.$title.'</title>';
    }

    if($n === 1) {
        // Einzelplatz: kleiner Punkt statt Halo-Kreis um den Sitz
        $html .= '<circle cx="'.sprintf('%.2f', $points[0][0]).'" cy="'.sprintf('%.2f', $points[0][1]).'" r="'
            .sprintf('%.2f', $haloR * 0.55).'" fill="'.$fill.'" opacity="0.4"/>';
    }
    else {
        $ptsAttr = '';
        foreach($points as $p) {
            $ptsAttr .= sprintf('%.2f,%.2f ', $p[0], $p[1]);
        }
        $html .= '<polyline points="'.trim($ptsAttr).'" fill="none" stroke="'.$fill
            .'" stroke-opacity="0.45" stroke-width="'.sprintf('%.2f', $haloR * 1.7)
            .'" stroke-linecap="round" stroke-linejoin="round"/>';
    }

    $html .= '</g>'."\n";
    return $html;
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
