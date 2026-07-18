<?php
/**
 * Default rows for meldeliste_Instrument (Index, Name, Register, Sortierung, Spielbar, Color).
 * Names use HTML entities as in production.
 * Colors: brand-derived register base with light shade steps within the register.
 */
function getInstrumentDefaults() {
    // Same map as scripts/applyBrandRegisterInstrumentColors.php / RegisterDefaults
    $reg = array(
        1  => '#7F9DC1',
        2  => '#5B7FB0',
        3  => '#345A95',
        4  => '#2A4878',
        5  => '#FFC300',
        6  => '#E6B000',
        7  => '#FFD54A',
        8  => '#454545',
        9  => '#969696',
        10 => '#FDF9E7',
        12 => '#CC9C00',
        13 => '#4A6FA8',
        14 => '#1F3659',
    );

    $rows = array(
        array('Index' => 1,  'Name' => 'Fl&ouml;te',                'Register' => 1,  'Sortierung' => 1,  'Spielbar' => 1),
        array('Index' => 2,  'Name' => 'Piccolo-Fl&ouml;te',        'Register' => 1,  'Sortierung' => 2,  'Spielbar' => 1),
        array('Index' => 3,  'Name' => 'B-Klarinette',              'Register' => 2,  'Sortierung' => 1,  'Spielbar' => 1),
        array('Index' => 4,  'Name' => 'Es-Klarinette',             'Register' => 2,  'Sortierung' => 2,  'Spielbar' => 1),
        array('Index' => 5,  'Name' => 'Alt-Saxophon',              'Register' => 4,  'Sortierung' => 1,  'Spielbar' => 1),
        array('Index' => 6,  'Name' => 'Tenor-Saxophon',            'Register' => 13, 'Sortierung' => 2,  'Spielbar' => 1),
        array('Index' => 7,  'Name' => 'Bariton-Saxophon',          'Register' => 14, 'Sortierung' => 3,  'Spielbar' => 1),
        array('Index' => 9,  'Name' => 'Schlagwerk',                'Register' => 9,  'Sortierung' => 1,  'Spielbar' => 1),
        array('Index' => 11, 'Name' => 'Trompete',                  'Register' => 5,  'Sortierung' => 3,  'Spielbar' => 1),
        array('Index' => 12, 'Name' => 'Waldhorn',                  'Register' => 6,  'Sortierung' => 1,  'Spielbar' => 1),
        array('Index' => 13, 'Name' => 'Tenor-Horn',                'Register' => 12, 'Sortierung' => 1,  'Spielbar' => 1),
        array('Index' => 14, 'Name' => 'Bariton-Horn',              'Register' => 12, 'Sortierung' => 2,  'Spielbar' => 1),
        array('Index' => 15, 'Name' => 'Bass-Klarinette',           'Register' => 2,  'Sortierung' => 3,  'Spielbar' => 1),
        array('Index' => 16, 'Name' => 'Posaune',                   'Register' => 7,  'Sortierung' => 2,  'Spielbar' => 1),
        array('Index' => 17, 'Name' => 'Bass-Posaune',              'Register' => 7,  'Sortierung' => 1,  'Spielbar' => 1),
        array('Index' => 18, 'Name' => 'Tuba',                      'Register' => 8,  'Sortierung' => 2,  'Spielbar' => 1),
        array('Index' => 19, 'Name' => 'Kontrabass',                'Register' => 8,  'Sortierung' => 1,  'Spielbar' => 1),
        array('Index' => 20, 'Name' => 'Fl&uuml;gelhorn',           'Register' => 5,  'Sortierung' => 1,  'Spielbar' => 1),
        array('Index' => 22, 'Name' => 'Dirigent',                  'Register' => 10, 'Sortierung' => 1,  'Spielbar' => 1),
        array('Index' => 23, 'Name' => 'Oboe',                      'Register' => 3,  'Sortierung' => 1,  'Spielbar' => 1),
        array('Index' => 24, 'Name' => 'Fagott',                    'Register' => 3,  'Sortierung' => 3,  'Spielbar' => 1),
        array('Index' => 25, 'Name' => 'Englischhorn',              'Register' => 3,  'Sortierung' => 2,  'Spielbar' => 1),
        array('Index' => 26, 'Name' => 'Euphonium',                 'Register' => 12, 'Sortierung' => 3,  'Spielbar' => 1),
        array('Index' => 27, 'Name' => 'Kornett',                   'Register' => 5,  'Sortierung' => 2,  'Spielbar' => 1),
        array('Index' => 28, 'Name' => 'Trompete/Fl&uuml;gelhorn',  'Register' => 5,  'Sortierung' => 1,  'Spielbar' => 1),
        array('Index' => 29, 'Name' => 'Shekere',                   'Register' => 9,  'Sortierung' => 1,  'Spielbar' => 0),
        array('Index' => 30, 'Name' => 'Paukenhocker',              'Register' => 9,  'Sortierung' => 99, 'Spielbar' => 0),
    );

    // Shade within each register by Sortierung order
    $grouped = array();
    foreach($rows as $i => $row) {
        $grouped[(int)$row['Register']][] = $i;
    }
    foreach($grouped as $rid => $idxs) {
        $base = isset($reg[$rid]) ? $reg[$rid] : '#969696';
        $n = count($idxs);
        foreach($idxs as $pos => $rowIdx) {
            if($n === 1) {
                $rows[$rowIdx]['Color'] = $base;
            }
            else {
                $t = $pos / max(1, $n - 1);
                $factor = 0.22 - (0.44 * $t);
                $rows[$rowIdx]['Color'] = instrumentDefaultAdjustHex($base, $factor);
            }
        }
    }
    return $rows;
}

function instrumentDefaultAdjustHex($hex, $factor) {
    $hex = strtoupper(trim($hex));
    if(!preg_match('/^#[0-9A-F]{6}$/', $hex)) {
        return $hex;
    }
    $r = hexdec(substr($hex, 1, 2));
    $g = hexdec(substr($hex, 3, 2));
    $b = hexdec(substr($hex, 5, 2));
    if($factor >= 0) {
        $r = (int)round($r + (255 - $r) * $factor);
        $g = (int)round($g + (255 - $g) * $factor);
        $b = (int)round($b + (255 - $b) * $factor);
    }
    else {
        $f = 1 + $factor;
        $r = (int)round($r * $f);
        $g = (int)round($g * $f);
        $b = (int)round($b * $f);
    }
    return sprintf('#%02X%02X%02X', max(0, min(255, $r)), max(0, min(255, $g)), max(0, min(255, $b)));
}
?>
