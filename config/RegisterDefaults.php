<?php
/**
 * Default rows for meldeliste_Register (from production dump).
 * Names use HTML entities as in production.
 * Colors derived from corporate brand palette (see libs/colorschemes.php).
 */
function getRegisterDefaults() {
    return array(
        array('Index' => 1,  'Name' => 'Fl&ouml;ten',           'Sortierung' => 10,  'Row' => 1, 'ArcMin' => 0,   'ArcMax' => 110, 'Color' => '#7F9DC1'),
        array('Index' => 2,  'Name' => 'Klarinetten',          'Sortierung' => 20,  'Row' => 1, 'ArcMin' => 180, 'ArcMax' => 110, 'Color' => '#5B7FB0'),
        array('Index' => 3,  'Name' => 'Oboen',                'Sortierung' => 30,  'Row' => 2, 'ArcMin' => 0,   'ArcMax' => 90,  'Color' => '#345A95'),
        array('Index' => 4,  'Name' => 'Alt-Saxophone',        'Sortierung' => 50,  'Row' => 3, 'ArcMin' => 0,   'ArcMax' => 60,  'Color' => '#2A4878'),
        array('Index' => 5,  'Name' => 'Trompeten',            'Sortierung' => 80,  'Row' => 4, 'ArcMin' => 35,  'ArcMax' => 170, 'Color' => '#FFC300'),
        array('Index' => 6,  'Name' => 'Waldh&ouml;rner',      'Sortierung' => 100, 'Row' => 2, 'ArcMin' => 45,  'ArcMax' => 110, 'Color' => '#E6B000'),
        array('Index' => 7,  'Name' => 'Posaunen',             'Sortierung' => 110, 'Row' => 5, 'ArcMin' => 35,  'ArcMax' => 90,  'Color' => '#FFD54A'),
        array('Index' => 8,  'Name' => 'Bass',                 'Sortierung' => 120, 'Row' => 4, 'ArcMin' => 0,   'ArcMax' => 30,  'Color' => '#454545'),
        array('Index' => 9,  'Name' => 'Schlagwerk',           'Sortierung' => 130, 'Row' => 5, 'ArcMin' => 80,  'ArcMax' => 135, 'Color' => '#969696'),
        array('Index' => 10, 'Name' => 'Dirigent',             'Sortierung' => -1,  'Row' => 0, 'ArcMin' => 0,   'ArcMax' => 0,   'Color' => '#FDF9E7'),
        array('Index' => 12, 'Name' => 'B&uuml;gelh&ouml;rner', 'Sortierung' => 90,  'Row' => 2, 'ArcMin' => 180, 'ArcMax' => 160, 'Color' => '#CC9C00'),
        array('Index' => 13, 'Name' => 'Tenor-Saxophone',      'Sortierung' => 60,  'Row' => 3, 'ArcMin' => 35,  'ArcMax' => 85,  'Color' => '#4A6FA8'),
        array('Index' => 14, 'Name' => 'Bariton-Saxophone',    'Sortierung' => 70,  'Row' => 3, 'ArcMin' => 85,  'ArcMax' => 110, 'Color' => '#1F3659'),
    );
}
?>
