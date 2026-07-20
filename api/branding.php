<?php
/**
 * GET /api/branding.php
 * Public chrome colors from site config (for native status bar / splash).
 * No authentication required.
 */
require_once __DIR__.'/_bootstrap.php';
apiRequireMethod('GET');

$defaults = array(
    'colorTitle' => '#FDF9E7',
    'colorTitleBar' => '#345A95',
    'colorNav' => '#969696',
    'colorBackground' => '#FDFFFC',
);

$resolve = function ($parameter) use ($defaults) {
    $raw = function_exists('getConfigParamRawValue')
        ? getConfigParamRawValue($parameter)
        : null;
    $hex = normalizeHexColor($raw);
    if($hex !== '') {
        return $hex;
    }
    if(function_exists('getBrandPalette') && is_string($raw) && $raw !== '') {
        $palette = getBrandPalette();
        $key = strtolower(trim($raw));
        if(isset($palette[$key])) {
            $hex = normalizeHexColor($palette[$key]);
            if($hex !== '') {
                return $hex;
            }
        }
    }
    return normalizeHexColor($defaults[$parameter]);
};

$colorTitle = $resolve('colorTitle');
$colorTitleBar = $resolve('colorTitleBar');
$colorNav = $resolve('colorNav');
$colorBackground = $resolve('colorBackground');

// Status bar / theme-color: top title strip (matches website chrome above the nav).
$themeColor = $colorTitle;

$siteName = '';
if(isset($GLOBALS['optionsDB']['WebSiteName'])) {
    $siteName = (string)$GLOBALS['optionsDB']['WebSiteName'];
}

apiJsonExit(array(
    'themeColor' => $themeColor,
    'themeColorOn' => hexContrastText($themeColor),
    'colorTitle' => $colorTitle,
    'colorTitleBar' => $colorTitleBar,
    'colorNav' => $colorNav,
    'colorBackground' => $colorBackground,
    'siteName' => $siteName,
));
