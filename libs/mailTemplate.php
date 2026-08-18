<?php
/**
 * HTML wrapper for outgoing MailJob e-mails (MELD-214).
 * Table layout + inline styles so Gmail/Outlook can render brand chrome.
 */
class MailTemplate
{
    const DEFAULT_TITLE = '#FDF9E7';
    const DEFAULT_SUBMIT = '#9C27B0';
    const PAGE_BG = '#E8EAED';
    const CARD_BG = '#FFFFFF';
    const INK = '#111111';
    const CARD_WIDTH = 600;

    /**
     * Resolve a config color parameter to hex (optionsDB class or #hex).
     * @param string $parameter
     * @param string $default
     * @return string
     */
    public static function colorHex($parameter, $default) {
        $raw = '';
        if(isset($GLOBALS['optionsDB'][$parameter])) {
            $raw = (string)$GLOBALS['optionsDB'][$parameter];
        }
        elseif(function_exists('getConfigParamRawValue')) {
            $got = getConfigParamRawValue($parameter);
            if(is_string($got)) {
                $raw = $got;
            }
        }
        $hex = function_exists('normalizeHexColor') ? normalizeHexColor($raw) : '';
        if($hex !== '') {
            return $hex;
        }
        if($raw !== '' && !empty($GLOBALS['cfgColorCssRules'][$raw]['bg'])
            && function_exists('isHexColor')
            && isHexColor($GLOBALS['cfgColorCssRules'][$raw]['bg'])) {
            $hex = normalizeHexColor($GLOBALS['cfgColorCssRules'][$raw]['bg']);
            if($hex !== '') {
                return $hex;
            }
        }
        if($raw !== '' && function_exists('getBrandPalette')) {
            $palette = getBrandPalette();
            $key = strtolower(trim($raw));
            if(isset($palette[$key])) {
                $hex = normalizeHexColor($palette[$key]);
                if($hex !== '') {
                    return $hex;
                }
            }
        }
        if($raw !== '' && function_exists('w3ColorToHex')) {
            $hex = function_exists('normalizeHexColor')
                ? normalizeHexColor(w3ColorToHex($raw))
                : '';
            if($hex !== '') {
                return $hex;
            }
        }
        return function_exists('normalizeHexColor')
            ? normalizeHexColor($default)
            : (string)$default;
    }

    /**
     * Absolute logo URL when imgs/Logo.png exists and WebSiteURL is set.
     * @return string
     */
    public static function logoUrl() {
        $path = dirname(__DIR__).'/imgs/Logo.png';
        if(!is_file($path)) {
            return '';
        }
        $base = isset($GLOBALS['optionsDB']['WebSiteURL'])
            ? rtrim((string)$GLOBALS['optionsDB']['WebSiteURL'], '/')
            : '';
        if($base === '') {
            return '';
        }
        return $base.'/imgs/Logo.png';
    }

    /**
     * Full HTML document for SMTP / compose preview.
     *
     * $ctx keys: greeting, ctaUrl, ctaLabel, siteName (optional).
     * Empty ctaUrl or ctaLabel omits the button (preview).
     *
     * @param string $innerHtml already-safe body HTML (TinyMCE / formatMailBodyForEmail)
     * @param array $ctx
     * @return string
     */
    public static function wrap($innerHtml, array $ctx = array()) {
        $h = function ($s) {
            return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
        };
        $site = isset($ctx['siteName'])
            ? (string)$ctx['siteName']
            : (isset($GLOBALS['optionsDB']['WebSiteName'])
                ? (string)$GLOBALS['optionsDB']['WebSiteName']
                : '');
        $greeting = isset($ctx['greeting']) ? (string)$ctx['greeting'] : '';
        $ctaUrl = isset($ctx['ctaUrl']) ? trim((string)$ctx['ctaUrl']) : '';
        $ctaLabel = isset($ctx['ctaLabel']) ? trim((string)$ctx['ctaLabel']) : '';
        $titleBg = self::colorHex('colorTitle', self::DEFAULT_TITLE);
        $titleFg = function_exists('hexContrastText') ? hexContrastText($titleBg) : '#111111';
        $btnBg = self::colorHex('colorBtnSubmit', self::DEFAULT_SUBMIT);
        $btnFg = function_exists('hexContrastText') ? hexContrastText($btnBg) : '#FFFFFF';
        $logo = self::logoUrl();
        $width = (int)self::CARD_WIDTH;
        $pageBg = self::PAGE_BG;
        $cardBg = self::CARD_BG;
        $ink = self::INK;
        $font = 'Arial, Helvetica, sans-serif';

        $logoCell = '';
        if($logo !== '') {
            $logoCell = '<td valign="middle" align="right" width="72" style="padding:0 0 0 12px;">'
                .'<img src="'.$h($logo).'" width="56" height="56" alt="" style="display:block;border:0;width:56px;height:56px;object-fit:contain;">'
                .'</td>';
        }

        $greetingHtml = '';
        if($greeting !== '') {
            $greetingHtml = '<p style="margin:0 0 16px 0;">'.$h($greeting).'</p>';
        }

        $ctaHtml = '';
        if($ctaUrl !== '' && $ctaLabel !== '') {
            $ctaHtml = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:24px 0 0 0;">'
                .'<tr><td bgcolor="'.$h($btnBg).'" style="background-color:'.$h($btnBg).';border-radius:4px;">'
                .'<a href="'.$h($ctaUrl).'" style="display:inline-block;padding:12px 20px;font-family:'.$font.';font-size:15px;font-weight:bold;line-height:1.25;color:'.$h($btnFg).';text-decoration:none;">'
                .$h($ctaLabel)
                .'</a></td></tr></table>';
        }

        return '<!DOCTYPE html><html lang="de"><head><meta charset="utf-8">'
            .'<meta name="viewport" content="width=device-width, initial-scale=1">'
            .'<title>'.$h($site).'</title></head>'
            .'<body style="margin:0;padding:0;background-color:'.$pageBg.';">'
            .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color:'.$pageBg.';">'
            .'<tr><td align="center" style="padding:24px 12px;">'
            .'<table role="presentation" width="'.$width.'" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:'.$width.'px;background-color:'.$cardBg.';">'
            .'<tr><td bgcolor="'.$h($titleBg).'" style="background-color:'.$h($titleBg).';padding:16px 20px;">'
            .'<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"><tr>'
            .'<td valign="middle" style="font-family:'.$font.';font-size:22px;font-weight:bold;line-height:1.25;color:'.$h($titleFg).';">'
            .$h($site)
            .'</td>'.$logoCell
            .'</tr></table></td></tr>'
            .'<tr><td style="padding:24px 20px;font-family:'.$font.';font-size:16px;line-height:1.5;color:'.$ink.';">'
            .$greetingHtml
            .$innerHtml
            .$ctaHtml
            .'</td></tr></table>'
            .'</td></tr></table></body></html>';
    }
}
