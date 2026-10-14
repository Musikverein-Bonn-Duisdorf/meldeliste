<?php
/**
 * MELD-249: Einführung / Changelog — Pages, Versionen, Ack.
 */
class Intro {
    /**
     * Default multipage intro (short).
     *
     * @return list<array{title:string,html:string}>
     */
    public static function defaultIntroPages() {
        return array(
            self::welcomePageTemplate(),
            array(
                'title' => 'Melden & Termine',
                'html' => '<p>Unter <strong>Termine</strong> siehst du bevorstehende Proben und Auftritte. '
                    .'Mit <strong>Ja</strong>, <strong>Nein</strong> oder <strong>Vielleicht</strong> meldest du dich. '
                    .'Tippe auf einen Termin für mehr Details. Dort findest du auch den genauen Ort und weitere Informationen.</p>',
            ),
            array(
                'title' => 'Kalender abonnieren',
                'html' => '<p>Deine Termine kannst du in Google, Apple oder Outlook abonnieren und so in deinen '
                    .'persönlichen Kalender und auch auf dein Smartphone synchronisieren. Den Link findest du unter '
                    .'<strong>Mein Profil</strong> bzw. auf der Seite <strong>Kalender</strong> (Info-Button).</p>'
                    .'<p>Importiere von dort deine persönliche Kalenderadresse in dein Google-, Apple- oder Outlook-Konto.</p>',
            ),
            array(
                'title' => 'Android-App',
                'html' => '<p>Mit der Meldeliste-App für Android bleibst du nach dem Login angemeldet. '
                    .'So hast du den Musikverein immer mit dabei :)</p>',
                // Play-Promo wird zur Anzeige per enrichPagesForDisplay() ergänzt (wie in der Hilfe).
            ),
        );
    }

    /**
     * Editable welcome page template. Placeholder {Vorname} is filled at display time.
     *
     * @return array{title:string,html:string}
     */
    public static function welcomePageTemplate() {
        return array(
            'title' => 'Willkommen {Vorname}!',
            'html' => '<div class="intro-welcome">'
                .'<p>Hallo <strong>{Vorname}</strong>, willkommen im Musikverein Bonn-Duisdorf.</p>'
                .'<p>wir zeigen dir gerne eine kurze Einführungshilfe. Sie zeigt dir, '
                .'wie du dich meldest, Termine findest und dich in der Meldeliste zurechtfindest.</p>'
                .'</div>',
        );
    }

    /**
     * Replace {Vorname} in title/HTML. Empty name collapses awkward gaps.
     *
     * @param string $text
     * @param string|null $vorname null → $_SESSION['Vorname']
     * @param bool $forHtml escape name when substituting
     * @return string
     */
    public static function applyVornamePlaceholder($text, $vorname = null, $forHtml = true) {
        $text = (string)$text;
        if(strpos($text, '{Vorname}') === false) {
            return $text;
        }
        if($vorname === null) {
            $vorname = isset($_SESSION['Vorname']) ? (string)$_SESSION['Vorname'] : '';
        }
        $name = trim((string)$vorname);
        if($name !== '') {
            $repl = $forHtml ? htmlspecialchars($name, ENT_QUOTES, 'UTF-8') : $name;
            return str_replace('{Vorname}', $repl, $text);
        }
        $text = str_replace(array('<b>{Vorname}</b>', '<strong>{Vorname}</strong>'), '', $text);
        $text = str_replace('{Vorname}', '', $text);
        $text = preg_replace('/Willkommen\s+!/u', 'Willkommen!', $text);
        $text = preg_replace('/Hallo\s+!/u', 'Hallo!', $text);
        $text = preg_replace('/Hallo\s+,/u', 'Hallo,', $text);
        $text = preg_replace('/Hallo\s+[—–-]/u', 'Hallo —', $text);
        $text = preg_replace('/ {2,}/', ' ', $text);
        return $text;
    }

    /**
     * @param mixed $raw JSON string or array
     * @return list<array{title:string,html:string}>
     */
    public static function normalizePages($raw) {
        if(is_string($raw)) {
            $raw = trim($raw);
            if($raw === '') {
                return array();
            }
            $decoded = json_decode($raw, true);
            $raw = is_array($decoded) ? $decoded : array();
        }
        if(!is_array($raw)) {
            return array();
        }
        $out = array();
        foreach($raw as $item) {
            if(!is_array($item)) {
                continue;
            }
            $title = isset($item['title']) ? trim((string)$item['title']) : '';
            $html = isset($item['html']) ? (string)$item['html'] : '';
            if(function_exists('sanitizeMailHtml')) {
                $html = sanitizeMailHtml($html);
            }
            if($title === '' && trim(strip_tags($html)) === '') {
                continue;
            }
            if($title === '') {
                $title = 'Seite';
            }
            $out[] = array('title' => $title, 'html' => $html);
        }
        return $out;
    }

    /**
     * @param list<array{title:string,html:string}> $pages
     * @return string
     */
    public static function pagesToJson($pages) {
        $pages = self::normalizePages($pages);
        $json = json_encode($pages, JSON_UNESCAPED_UNICODE);
        return is_string($json) ? $json : '[]';
    }

    /**
     * @return list<array{title:string,html:string}>
     */
    public static function introPages() {
        $raw = isset($GLOBALS['optionsDB']['introPages'])
            ? $GLOBALS['optionsDB']['introPages']
            : '';
        $pages = self::normalizePages($raw);
        return $pages !== array() ? $pages : self::defaultIntroPages();
    }

    /**
     * @return list<array{title:string,html:string}>
     */
    public static function changelogPages() {
        $raw = isset($GLOBALS['optionsDB']['changelogPages'])
            ? $GLOBALS['optionsDB']['changelogPages']
            : '';
        return self::normalizePages($raw);
    }

    /**
     * Resolve config color class to hex (same rules as Termin melde tint).
     *
     * @param string $colorClass
     * @return string
     */
    private static function demoResolveColorHex($colorClass) {
        $colorClass = trim((string)$colorClass);
        if($colorClass === '') {
            return '';
        }
        if(!empty($GLOBALS['cfgColorCssRules'][$colorClass]['bg'])
            && function_exists('isHexColor')
            && isHexColor($GLOBALS['cfgColorCssRules'][$colorClass]['bg'])) {
            return normalizeHexColor($GLOBALS['cfgColorCssRules'][$colorClass]['bg']);
        }
        if(function_exists('w3ColorToHex')) {
            $hex = w3ColorToHex($colorClass);
            if(function_exists('isHexColor') && isHexColor($hex)) {
                return normalizeHexColor($hex);
            }
        }
        return '';
    }

    /**
     * Row tint for demo: [extraClass, inlineStyleWithoutAttr].
     *
     * @param string $colorClass
     * @return array{0:string,1:string}
     */
    private static function demoMeldeRowTint($colorClass) {
        $colorClass = trim((string)$colorClass);
        if($colorClass === '') {
            return array('', '');
        }
        $raw = isset($GLOBALS['optionsDB']['meldeRowResponseOpacity'])
            ? (string)$GLOBALS['optionsDB']['meldeRowResponseOpacity']
            : '1';
        $opacity = (float)str_replace(',', '.', $raw);
        if(!is_finite($opacity)) {
            $opacity = 1.0;
        }
        $opacity = max(0.0, min(1.0, $opacity));
        if($opacity >= 0.999) {
            return array($colorClass, '');
        }
        $hex = self::demoResolveColorHex($colorClass);
        if($hex === '') {
            return array($colorClass, '');
        }
        $fg = function_exists('hexContrastTextOnFill')
            ? hexContrastTextOnFill($hex, $opacity)
            : '#000000';
        $style = '--melde-response-opacity:'.$opacity
            .';--melde-response-bg:'.$hex
            .';--melde-response-fg:'.$fg;
        return array('melde-row--responded', $style);
    }

    /**
     * Interactive demo melde row (no server save). Looks like a real Termin line.
     *
     * @return string
     */
    public static function demoMeldeRowHtml() {
        $h = function ($s) {
            return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
        };
        $yes = isset($GLOBALS['optionsDB']['colorBtnYes']) ? (string)$GLOBALS['optionsDB']['colorBtnYes'] : 'w3-green';
        $no = isset($GLOBALS['optionsDB']['colorBtnNo']) ? (string)$GLOBALS['optionsDB']['colorBtnNo'] : 'w3-red';
        $maybe = isset($GLOBALS['optionsDB']['colorBtnMaybe']) ? (string)$GLOBALS['optionsDB']['colorBtnMaybe'] : 'w3-yellow';
        $disabled = isset($GLOBALS['optionsDB']['colorDisabled']) ? (string)$GLOBALS['optionsDB']['colorDisabled'] : 'w3-light-grey';
        $hover = isset($GLOBALS['optionsDB']['HoverEffect']) ? (string)$GLOBALS['optionsDB']['HoverEffect'] : '';
        $rowYes = isset($GLOBALS['optionsDB']['colorAppmntYes']) ? (string)$GLOBALS['optionsDB']['colorAppmntYes'] : $yes;
        $rowNo = isset($GLOBALS['optionsDB']['colorAppmntNo']) ? (string)$GLOBALS['optionsDB']['colorAppmntNo'] : $no;
        $rowMaybe = isset($GLOBALS['optionsDB']['colorAppmntMaybe']) ? (string)$GLOBALS['optionsDB']['colorAppmntMaybe'] : $maybe;
        $rowDefault = isset($GLOBALS['optionsDB']['colorAppmntNoConcert'])
            ? (string)$GLOBALS['optionsDB']['colorAppmntNoConcert']
            : '';
        $btnBase = 'melde-btn w3-btn w3-border w3-border-black w3-center';
        $rowBase = trim('intro-demo-melde melde-row w3-card-4 '.$hover);

        $tints = array(
            0 => self::demoMeldeRowTint($rowDefault),
            1 => self::demoMeldeRowTint($rowYes),
            2 => self::demoMeldeRowTint($rowNo),
            3 => self::demoMeldeRowTint($rowMaybe),
        );
        $initExtra = $tints[0][0];
        $initStyle = $tints[0][1];
        $initClass = trim($rowBase.($initExtra !== '' ? ' '.$initExtra : ''));
        $styleAttr = $initStyle !== '' ? ' style="'.$h($initStyle).'"' : '';

        $html = '<div class="intro-demo-melde-wrap">'
            .'<p class="intro-demo-melde-hint">Probiere es aus — tippe auf <b>Ja</b>, <b>Nein</b> oder <b>Vielleicht</b>.</p>'
            .'<div class="'.$h($initClass).'"'.$styleAttr
            .' data-wert="0"'
            .' data-row-base="'.$h($rowBase).'"'
            .' data-color-yes="'.$h($yes).'"'
            .' data-color-no="'.$h($no).'"'
            .' data-color-maybe="'.$h($maybe).'"'
            .' data-color-disabled="'.$h($disabled).'"';
        foreach($tints as $w => $tint) {
            $html .= ' data-row-extra-'.$w.'="'.$h($tint[0]).'"'
                .' data-row-style-'.$w.'="'.$h($tint[1]).'"';
        }
        $html .= ' role="group" aria-label="Beispiel-Meldung">'
            .'<div class="melde-row-main">'
            .'<div class="melde-date-col">'
            .'<div class="melde-date" title="Dienstag, 15. April">'
            .'<span class="melde-weekday">Di</span>'
            .'<span class="melde-date-details">'
            .'<span class="melde-date-day">15.04.</span>'
            .'<span class="melde-date-time">19:30</span>'
            .'</span></div>'
            .'</div>'
            .'<div class="melde-date-rail" aria-hidden="true"></div>'
            .'<div class="melde-body">'
            .'<div class="melde-main">'
            .'<div class="melde-title">Probe (Beispiel)</div>'
            .'<div class="melde-ort">Proberaum</div>'
            .'</div>'
            .'<div class="melde-actions" data-melde-stop>'
            .'<div class="melde-btns">'
            .'<button type="button" class="'.$btnBase.' '.$h($yes).'" data-wert="1" aria-label="Ja" aria-pressed="false">&#10004;</button>'
            .'<button type="button" class="'.$btnBase.' '.$h($no).'" data-wert="2" aria-label="Nein" aria-pressed="false">&#10008;</button>'
            .'<button type="button" class="'.$btnBase.' '.$h($maybe).'" data-wert="3" aria-label="Vielleicht" aria-pressed="false"><b>?</b></button>'
            .'</div>'
            .'</div>'
            .'</div>'
            .'</div>'
            .'</div>'
            .'</div>';
        return $html;
    }

    /**
     * Personalize placeholders, inject demo melde row + Android Play-Promo for display.
     *
     * @param list<array{title:string,html:string}> $pages
     * @param string|null $vorname Override for tests; null → session
     * @return list<array{title:string,html:string}>
     */
    public static function enrichPagesForDisplay($pages, $vorname = null) {
        if(!is_array($pages) || $pages === array()) {
            return array();
        }
        $promo = function_exists('androidAppPromoHtml') ? androidAppPromoHtml() : '';
        $out = array();
        foreach($pages as $page) {
            if(!is_array($page)) {
                continue;
            }
            $title = isset($page['title']) ? (string)$page['title'] : '';
            $html = isset($page['html']) ? (string)$page['html'] : '';
            $title = self::applyVornamePlaceholder($title, $vorname, false);
            $html = self::applyVornamePlaceholder($html, $vorname, true);
            if($title === 'Melden & Termine' && strpos($html, 'intro-demo-melde') === false) {
                $html .= self::demoMeldeRowHtml();
            }
            if($title === 'Android-App' && $promo !== '' && strpos($html, 'class="help-app-promo"') === false) {
                $html .= $promo;
            }
            $out[] = array('title' => $title, 'html' => $html);
        }
        return $out;
    }

    /** @return int */
    public static function introVersion() {
        return isset($GLOBALS['optionsDB']['introVersion'])
            ? (int)$GLOBALS['optionsDB']['introVersion']
            : 0;
    }

    /** @return int */
    public static function changelogVersion() {
        return isset($GLOBALS['optionsDB']['changelogVersion'])
            ? (int)$GLOBALS['optionsDB']['changelogVersion']
            : 0;
    }

    /**
     * @param int $userId
     * @return array{introAck:int,changelogAck:int}
     */
    public static function loadUserAck($userId) {
        $userId = (int)$userId;
        $out = array('introAck' => 0, 'changelogAck' => 0);
        if($userId < 1) {
            return $out;
        }
        $sql = sprintf(
            'SELECT `introAckVersion`, `changelogAckVersion` FROM `%sUser` WHERE `Index` = %d LIMIT 1;',
            $GLOBALS['dbprefix'],
            $userId
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        $row = $dbr ? mysqli_fetch_assoc($dbr) : null;
        if(!is_array($row)) {
            return $out;
        }
        $out['introAck'] = isset($row['introAckVersion']) ? (int)$row['introAckVersion'] : 0;
        $out['changelogAck'] = isset($row['changelogAckVersion']) ? (int)$row['changelogAckVersion'] : 0;
        return $out;
    }

    /**
     * Session-only dismiss (X) — no Ack; shows again on next login.
     *
     * @param string $kind intro|changelog
     */
    public static function dismissForSession($kind) {
        if($kind !== 'intro' && $kind !== 'changelog') {
            return;
        }
        if(!isset($_SESSION['introModalDismissed']) || !is_array($_SESSION['introModalDismissed'])) {
            $_SESSION['introModalDismissed'] = array();
        }
        $_SESSION['introModalDismissed'][$kind] = true;
    }

    /**
     * @param string $kind intro|changelog
     * @return bool
     */
    public static function isDismissedThisSession($kind) {
        return !empty($_SESSION['introModalDismissed'][$kind]);
    }

    /**
     * What the current session user should see: intro | changelog | null.
     *
     * Intro: once per user (any introAck > 0), even if text/version changes later.
     * Changelog: again whenever published version is newer than the user's ack.
     *
     * @param int $userId
     * @return string|null
     */
    public static function pendingKindForUser($userId) {
        if(!empty($_SESSION['singleUsePW'])) {
            return null;
        }
        $ack = self::loadUserAck($userId);
        $introV = self::introVersion();
        if($introV > 0 && $ack['introAck'] < 1 && self::introPages() !== array()
            && !self::isDismissedThisSession('intro')) {
            return 'intro';
        }
        $changeV = self::changelogVersion();
        if($changeV > 0 && $ack['changelogAck'] < $changeV && self::changelogPages() !== array()
            && !self::isDismissedThisSession('changelog')) {
            return 'changelog';
        }
        return null;
    }

    /**
     * @param string $kind intro|changelog
     * @return list<array{title:string,html:string}>
     */
    public static function pagesForKind($kind) {
        if($kind === 'changelog') {
            return self::changelogPages();
        }
        return self::enrichPagesForDisplay(self::introPages());
    }

    /**
     * @param int $userId
     * @param string $kind intro|changelog
     * @return bool
     */
    public static function acknowledge($userId, $kind) {
        $userId = (int)$userId;
        if($userId < 1) {
            return false;
        }
        if($kind === 'changelog') {
            $version = self::changelogVersion();
            $col = 'changelogAckVersion';
        }
        else {
            $version = self::introVersion();
            $col = 'introAckVersion';
        }
        if($version < 1) {
            return false;
        }
        $sql = sprintf(
            'UPDATE `%sUser` SET `%s` = %d WHERE `Index` = %d;',
            $GLOBALS['dbprefix'],
            $col,
            $version,
            $userId
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if($dbr && isset($_SESSION['introModalDismissed'][$kind])) {
            unset($_SESSION['introModalDismissed'][$kind]);
        }
        return (bool)$dbr;
    }

    /**
     * Persist pages; optionally bump published version.
     *
     * @param string $kind intro|changelog
     * @param list<array{title:string,html:string}> $pages
     * @param bool $publish
     * @return int new version (unchanged if not publish)
     */
    public static function savePages($kind, $pages, $publish = false) {
        $pagesParam = $kind === 'changelog' ? 'changelogPages' : 'introPages';
        $versionParam = $kind === 'changelog' ? 'changelogVersion' : 'introVersion';
        $json = self::pagesToJson($pages);
        setConfigParamRawValue($pagesParam, $json);
        $version = $kind === 'changelog' ? self::changelogVersion() : self::introVersion();
        // Reload options after pages write so version read is fresh if needed
        if(function_exists('loadconfig')) {
            $GLOBALS['optionsDB'] = loadconfig();
        }
        $version = isset($GLOBALS['optionsDB'][$versionParam])
            ? (int)$GLOBALS['optionsDB'][$versionParam]
            : $version;
        if($publish) {
            $version = $version + 1;
            if($version < 1) {
                $version = 1;
            }
            setConfigParamRawValue($versionParam, (string)$version);
            if(function_exists('loadconfig')) {
                $GLOBALS['optionsDB'] = loadconfig();
            }
        }
        return $version;
    }
}
