<?php
/**
 * Load shared orchestra datasets once (for full + activeOnly render).
 * @param int $tid
 * @return array{meldungen:array,aushilfen:array,users:array,instruments:array}
 */
function loadOrchestraData($tid) {
    $tid = (int)$tid;
    $aMeldungen = array();
    $aAushilfen = array();
    $aUser = array();
    $aInstrument = array();
    if($tid) {
        $sql = sprintf("SELECT * FROM `%sMeldungen` INNER JOIN (SELECT `Index` AS `uIndex`, `Vorname`, `Nachname`, `Instrument` AS `uInstrument` FROM `%sUser`) `%sUser` ON `User` = `uIndex` WHERE `Termin` = %d ORDER BY `Instrument`, `Nachname`, `Vorname`;",
                       $GLOBALS['dbprefix'],
                       $GLOBALS['dbprefix'],
                       $GLOBALS['dbprefix'],
                       $tid
        );
        $dbMeldungen = mysqli_query($GLOBALS['conn'], $sql);
        if($dbMeldungen) {
            while($row = mysqli_fetch_array($dbMeldungen)) {
                $aMeldungen[] = $row;
            }
        }

        $sql = sprintf("SELECT * FROM `%sAushilfen` WHERE `Termin` = %d;",
        $GLOBALS['dbprefix'],
        $tid
        );
        $dbAushilfe = mysqli_query($GLOBALS['conn'], $sql);
        if($dbAushilfe) {
            while($row = mysqli_fetch_array($dbAushilfe)) {
                $aAushilfen[] = $row;
            }
        }
    }
    $sql = sprintf("SELECT * FROM `%sUser` WHERE `Deleted` = 0 ORDER BY `Nachname`, `Vorname`;",
                   $GLOBALS['dbprefix']
    );
    $dbUser = mysqli_query($GLOBALS['conn'], $sql);
    if($dbUser) {
        while($row = mysqli_fetch_array($dbUser)) {
            $aUser[] = $row;
        }
    }
    $sql = sprintf("SELECT * FROM `%sInstrument`;",
                   $GLOBALS['dbprefix']
    );
    $dbInstrument = mysqli_query($GLOBALS['conn'], $sql);
    if($dbInstrument) {
        while($row = mysqli_fetch_array($dbInstrument)) {
            $aInstrument[] = $row;
        }
    }
    return array(
        'meldungen' => $aMeldungen,
        'aushilfen' => $aAushilfen,
        'users' => $aUser,
        'instruments' => $aInstrument,
    );
}

/**
 * Render orchestra seating SVG.
 *
 * Always uses logical coordinates with viewBox so CSS can scale to the container
 * ($scale is ignored for output size; kept for call-site compatibility).
 *
 * @param int  $tid        Termin id (0 = static register colors, no responses)
 * @param float $scale     Unused for sizing (compat)
 * @param bool $activeOnly Packed layout: only ja/vielleicht
 * @param array|null $data Optional preload from loadOrchestraData()
 */
function printOrchestra($tid, $scale = 1, $activeOnly = false, $data = null) {
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

    if(!is_array($data)) {
        $data = loadOrchestraData($tid);
    }
    $aMeldungen = isset($data['meldungen']) ? $data['meldungen'] : array();
    $aAushilfen = isset($data['aushilfen']) ? $data['aushilfen'] : array();
    $aUser = isset($data['users']) ? $data['users'] : array();
    $aInstrument = isset($data['instruments']) ? $data['instruments'] : array();

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
                    $fillColor = $style['color'];
                    $fillOpacity = (float)$style['opacity'];
                    $textFill = !empty($style['textColor'])
                        ? $style['textColor']
                        : hexContrastTextOnFill($fillColor, $fillOpacity);
                    $seatsHtml .= '<circle opacity="'.$style['opacity'].'" cx="'.$x.'" cy="'.$y.'" r="'.$seatR.'" stroke="black" stroke-width="2" fill="'.$fillColor."\" />\n";
                    $seatsHtml .= '<text text-anchor="middle" dominant-baseline="middle" fill="'.$textFill.'" font-size="10" x="'.$x.'" y="'.$y.'">'.$safeShort."</text>\n";
                    $seatsHtml .= "</g>\n";
                }
                else {
                    $titleParts = array($user['name']);
                    if($instrLabel !== '') {
                        $titleParts[] = $instrLabel;
                    }
                    $titleText = htmlspecialchars(implode("\n", $titleParts), ENT_QUOTES, 'UTF-8');
                    $fillColor = isset($register['Color']) ? (string)$register['Color'] : '#cccccc';
                    $textFill = hexContrastText($fillColor);
                    $seatsHtml .= '<g class="orchestra-seat"'
                        .' data-name="'.htmlspecialchars((string)$user['name'], ENT_QUOTES, 'UTF-8').'"'
                        .($instrLabel !== '' ? ' data-instrument="'.htmlspecialchars($instrLabel, ENT_QUOTES, 'UTF-8').'"' : '')
                        .'>'."\n";
                    $seatsHtml .= '<title>'.$titleText."</title>\n";
                    $seatsHtml .= '<circle cx="'.$x.'" cy="'.$y.'" r="'.$seatR.'" stroke="black" stroke-width="2" fill="'.$fillColor."\" />\n";
                    $seatsHtml .= '<text text-anchor="middle" dominant-baseline="middle" fill="'.$textFill.'" font-size="10" x="'.$x.'" y="'.$y.'">'.$safeShort."</text>\n";
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
        // Horizontal: center on conductor (polar origin), not seat bbox midpoint
        $conductorX = $baseWidth / 2;
        $contentH = ($maxY - $minY) + 2 * $pad;
        $cy = ($minY + $maxY) / 2;
        $halfW = max($conductorX - $minX, $maxX - $conductorX) + $pad;

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

        $vbW = max(2 * $halfW, $minViewW);
        $vbH = max($contentH, $minViewH);
        $vbX = $conductorX - $vbW / 2;
        $vbY = $cy - $vbH / 2;
    }

    $str = '<svg class="'.$svgClass.'" viewBox="'
        .htmlspecialchars(sprintf('%.2f %.2f %.2f %.2f', $vbX, $vbY, $vbW, $vbH), ENT_QUOTES, 'UTF-8')
        .'" preserveAspectRatio="xMidYMin meet" role="img" aria-label="Orchesterbesetzung"';
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
        return array('color' => '#4CAF50', 'opacity' => 1, 'label' => 'Zusage', 'textColor' => null);
    case 2:
        // Deckend, aber weich wie früher 50% #f42316 über Weiß (kein Register-Durchscheinen)
        return array('color' => '#fa918a', 'opacity' => 1, 'label' => 'Absage', 'textColor' => null);
    case 3:
        return array('color' => '#2196F3', 'opacity' => 0.6, 'label' => 'unsicher', 'textColor' => null);
    default:
        // Deckend weiß, damit Register-Bänder nicht durchscheinen (MELD-98)
        return array('color' => '#ffffff', 'opacity' => 1, 'label' => 'nicht gemeldet', 'textColor' => null);
    }
}

/**
 * Map orchestra polar angle (same as printOrchestra seats) to SVG x/y.
 * 0° = left, 90° = front/bottom, 180° = right.
 */
function orchestraPolarPoint($cx, $cy, $radius, $arcDeg) {
    $rad = $arcDeg / 180.0 * M_PI;
    return array(
        $cx - $radius * cos($rad),
        $cy + $radius * sin($rad),
    );
}

/**
 * Schematic SVG: register arcs by Row / ArcMin / ArcMax (edit preview).
 * Uses clear per-row radii (no seat spillover), so rows do not collapse onto minrowdistance.
 */
function printRegisterLayoutPreview() {
    $baseWidth = 1000;
    $cx = $baseWidth / 2.0;
    $cy = 40.0;
    // Preview spacing: wide enough that thick arcs on adjacent rows do not overlap.
    $rowGap = 52;
    $minrowdistance = 150;
    $strokeW = 22;

    $sql = sprintf(
        'SELECT * FROM `%sRegister` WHERE LOWER(TRIM(`Name`)) != "keins" ORDER BY `Row`, `Sortierung`, `Name`;',
        $GLOBALS['dbprefix']
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    $registers = array();
    $maxRow = 0;
    while($row = mysqli_fetch_array($dbr)) {
        $registers[] = $row;
        $maxRow = max($maxRow, (int)$row['Row']);
    }

    $arcsHtml = '';
    $guidesHtml = '';
    $minX = $cx;
    $maxX = $cx;
    $minY = $cy;
    $maxY = $cy;
    $halfStroke = $strokeW / 2.0;

    foreach($registers as $register) {
        $rowNum = (int)$register['Row'];
        $name = html_entity_decode((string)$register['Name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $color = normalizeHexColor(isset($register['Color']) ? $register['Color'] : '');
        if($color === '') {
            $color = '#969696';
        }
        $textFill = hexContrastText($color);
        $arcMin = (float)$register['ArcMin'];
        $arcMax = (float)$register['ArcMax'];
        $rid = (int)$register['Index'];

        if($rowNum === 0) {
            $r = 22;
            $arcsHtml .= '<g class="register-layout-arc" data-register="'.$rid.'">'
                .'<title>'.$safeName.' — Reihe 0 (Dirigent)</title>'
                .'<circle cx="'.sprintf('%.1f', $cx).'" cy="'.sprintf('%.1f', $cy).'" r="'.$r
                .'" fill="'.$color.'" stroke="#333" stroke-width="1.5"/>'
                .'<text x="'.sprintf('%.1f', $cx).'" y="'.sprintf('%.1f', $cy)
                .'" text-anchor="middle" dominant-baseline="central" fill="'.$textFill
                .'" font-size="11" font-weight="600">'.$safeName.'</text>'
                .'</g>'."\n";
            $minX = min($minX, $cx - $r);
            $maxX = max($maxX, $cx + $r);
            $minY = min($minY, $cy - $r);
            $maxY = max($maxY, $cy + $r);
            continue;
        }

        // One clear radius per Reihe (same for left/right) so rows stay separated.
        $radius = $minrowdistance + ($rowNum - 1) * $rowGap;

        $span = abs($arcMax - $arcMin);
        $steps = max(8, (int)ceil($span / 3));
        $pts = array();
        for($i = 0; $i <= $steps; $i++) {
            $t = $i / $steps;
            $arc = $arcMin + ($arcMax - $arcMin) * $t;
            $p = orchestraPolarPoint($cx, $cy, $radius, $arc);
            $pts[] = sprintf('%.1f,%.1f', $p[0], $p[1]);
            $minX = min($minX, $p[0] - $halfStroke);
            $maxX = max($maxX, $p[0] + $halfStroke);
            $minY = min($minY, $p[1] - $halfStroke);
            $maxY = max($maxY, $p[1] + $halfStroke);
        }

        $midArc = ($arcMin + $arcMax) / 2.0;
        $lp = orchestraPolarPoint($cx, $cy, $radius, $midArc);
        $title = $safeName.' — Reihe '.$rowNum.', Arc '.$arcMin.'° … '.$arcMax.'°';

        $arcsHtml .= '<g class="register-layout-arc" data-register="'.$rid.'">'
            .'<title>'.$title.'</title>'
            .'<polyline points="'.implode(' ', $pts).'" fill="none" stroke="'.$color
            .'" stroke-width="'.$strokeW.'" stroke-linecap="round" stroke-linejoin="round"'
            .' stroke-opacity="0.92"/>'
            .'<text x="'.sprintf('%.1f', $lp[0]).'" y="'.sprintf('%.1f', $lp[1])
            .'" text-anchor="middle" dominant-baseline="central" fill="'.$textFill
            .'" font-size="10" font-weight="600" style="paint-order:stroke;stroke:#000;stroke-width:2.5px;stroke-opacity:0.35">'
            .$safeName.'</text>'
            .'</g>'."\n";
    }

    for($r = 1; $r <= max(1, $maxRow); $r++) {
        $guideR = $minrowdistance + ($r - 1) * $rowGap;
        $guidesHtml .= '<circle cx="'.sprintf('%.1f', $cx).'" cy="'.sprintf('%.1f', $cy)
            .'" r="'.sprintf('%.1f', $guideR).'" fill="none" stroke="#bbb" stroke-width="1"'
            .' stroke-dasharray="4 6" opacity="0.55"/>'."\n";
        $guidesHtml .= '<text x="'.sprintf('%.1f', $cx + 6).'" y="'.sprintf('%.1f', $cy + $guideR - 4)
            .'" fill="#888" font-size="9">Reihe '.$r.'</text>'."\n";
    }

    $hintR = $minrowdistance + max(0, $maxRow - 1) * $rowGap + 40;
    $hints = array(
        array(0, '0°'),
        array(90, '90°'),
        array(180, '180°'),
    );
    $hintHtml = '';
    foreach($hints as $h) {
        $hp = orchestraPolarPoint($cx, $cy, $hintR, $h[0]);
        $hintHtml .= '<text x="'.sprintf('%.1f', $hp[0]).'" y="'.sprintf('%.1f', $hp[1])
            .'" text-anchor="middle" dominant-baseline="central" fill="#666" font-size="10">'
            .$h[1].'</text>'."\n";
        $minX = min($minX, $hp[0] - 12);
        $maxX = max($maxX, $hp[0] + 12);
        $minY = min($minY, $hp[1] - 12);
        $maxY = max($maxY, $hp[1] + 12);
    }

    $pad = 12;
    // Horizontal: center on conductor (same as printOrchestra)
    $contentH = ($maxY - $minY) + 2 * $pad;
    $midY = ($minY + $maxY) / 2.0;
    $halfW = max($cx - $minX, $maxX - $cx) + $pad;
    $vbW = max(2 * $halfW, 820.0);
    $vbH = max($contentH, 480.0);
    $vbX = $cx - $vbW / 2.0;
    $vbY = $midY - $vbH / 2.0;

    return '<svg class="orchestra-svg register-layout-svg" viewBox="'
        .htmlspecialchars(sprintf('%.1f %.1f %.1f %.1f', $vbX, $vbY, $vbW, $vbH), ENT_QUOTES, 'UTF-8')
        .'" preserveAspectRatio="xMidYMin meet" role="img" aria-label="Registerpositionen im Orchester">'
        .'<g class="register-layout-guides" pointer-events="none">'.$guidesHtml.$hintHtml.'</g>'
        .'<g class="register-layout-arcs">'.$arcsHtml.'</g>'
        .'</svg>';
}
