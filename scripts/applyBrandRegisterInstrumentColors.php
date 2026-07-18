<?php
/**
 * Derive Register + Instrument colors from corporate brand palette and apply to DB.
 *
 * Brand kit (libs/colorschemes.php):
 *   #FDFFFC Weiß | #040006 Schwarz | #345A95 Blau | #969696 Grau
 *   #454545 Dunkelgrau | #FDF9E7 Creme | #FFC300 Gold | #7F9DC1 Hellblau
 *
 * Usage: php scripts/applyBrandRegisterInstrumentColors.php
 */
if(php_sapi_name() !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

$root = dirname(__DIR__);
require_once $root.'/common/config.php';
require_once $root.'/libs/helpers.php';
require_once $root.'/libs/colorschemes.php';
require_once $root.'/config/RegisterDefaults.php';
require_once $root.'/config/InstrumentDefaults.php';

if(empty($GLOBALS['conn'])) {
    fwrite(STDERR, "No DB connection.\n");
    exit(1);
}

$conn = $GLOBALS['conn'];
$prefix = $GLOBALS['dbprefix'];

/**
 * Brand-derived register colors: woodwinds = blue family, brass = gold family,
 * low/rhythm = gray family, conductor = cream.
 */
function getBrandRegisterColors() {
    $p = getBrandPalette();
    return array(
        1  => '#7F9DC1', // Flöten — Hellblau
        2  => '#5B7FB0', // Klarinetten — Blau hell
        3  => '#345A95', // Oboen — Markenblau
        4  => '#2A4878', // Alt-Sax — Blau dunkel
        13 => '#4A6FA8', // Tenor-Sax — Blau mittel
        14 => '#1F3659', // Bariton-Sax — Blau sehr dunkel
        5  => '#FFC300', // Trompeten — Gold
        6  => '#E6B000', // Waldhörner — Gold dunkel
        12 => '#CC9C00', // Bügelhörner — Gold tiefer
        7  => '#FFD54A', // Posaunen — Gold hell
        8  => '#454545', // Bass — Dunkelgrau
        9  => '#969696', // Schlagwerk — Grau
        10 => '#FDF9E7', // Dirigent — Creme
    );
}

/**
 * Lighten/darken hex by factor (-1..1). Keeps result as #RRGGBB.
 */
function brandAdjustHex($hex, $factor) {
    $hex = normalizeHexColor($hex);
    if($hex === '') {
        return '';
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
    $r = max(0, min(255, $r));
    $g = max(0, min(255, $g));
    $b = max(0, min(255, $b));
    return sprintf('#%02X%02X%02X', $r, $g, $b);
}

$regColors = getBrandRegisterColors();

// Ensure Instrument.Color exists
$colCheck = mysqli_query($conn, "SHOW COLUMNS FROM `{$prefix}Instrument` LIKE 'Color'");
if(!$colCheck || mysqli_num_rows($colCheck) === 0) {
    $alter = "ALTER TABLE `{$prefix}Instrument` ADD `Color` text COLLATE utf8mb4_unicode_ci NULL";
    if(!mysqli_query($conn, $alter)) {
        fwrite(STDERR, "ALTER Instrument.Color failed: ".mysqli_error($conn)."\n");
        exit(1);
    }
    echo "SCHEMA\tInstrument.Color added\n";
}

// Update registers
foreach($regColors as $id => $color) {
    $sql = sprintf(
        'UPDATE `%sRegister` SET `Color` = "%s" WHERE `Index` = %d;',
        $prefix,
        mysqli_real_escape_string($conn, $color),
        (int)$id
    );
    if(mysqli_query($conn, $sql) && mysqli_affected_rows($conn) >= 0) {
        echo "REGISTER\t$id\t$color\n";
    }
    else {
        fwrite(STDERR, "REGISTER FAIL $id: ".mysqli_error($conn)."\n");
    }
}

// Instruments: base = register color, slight shade by Sortierung within register
$byReg = array();
$sql = sprintf(
    'SELECT `Index`, `Register`, `Sortierung`, `Name` FROM `%sInstrument` ORDER BY `Register`, `Sortierung`, `Index`;',
    $prefix
);
$dbr = mysqli_query($conn, $sql);
if(!$dbr) {
    fwrite(STDERR, "Instrument SELECT failed: ".mysqli_error($conn)."\n");
    exit(1);
}
while($row = mysqli_fetch_assoc($dbr)) {
    $rid = (int)$row['Register'];
    if(!isset($byReg[$rid])) {
        $byReg[$rid] = array();
    }
    $byReg[$rid][] = $row;
}

foreach($byReg as $rid => $instruments) {
    $base = isset($regColors[$rid]) ? $regColors[$rid] : '#969696';
    $n = count($instruments);
    foreach($instruments as $i => $inst) {
        // Spread shades: first lighter, last darker (or single = base)
        if($n === 1) {
            $color = $base;
        }
        else {
            $t = $i / max(1, $n - 1); // 0..1
            $factor = 0.22 - (0.44 * $t); // +0.22 .. -0.22
            $color = brandAdjustHex($base, $factor);
        }
        $sql = sprintf(
            'UPDATE `%sInstrument` SET `Color` = "%s" WHERE `Index` = %d;',
            $prefix,
            mysqli_real_escape_string($conn, $color),
            (int)$inst['Index']
        );
        if(mysqli_query($conn, $sql)) {
            $name = html_entity_decode($inst['Name'], ENT_QUOTES | ENT_HTML5, 'UTF-8');
            echo "INSTRUMENT\t{$inst['Index']}\t$name\t$color\n";
        }
        else {
            fwrite(STDERR, "INSTRUMENT FAIL {$inst['Index']}: ".mysqli_error($conn)."\n");
        }
    }
}

echo "DONE\n";
?>
