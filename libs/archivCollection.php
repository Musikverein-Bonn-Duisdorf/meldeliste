<?php
/**
 * Read-only access to Notenarchiv collections (shared DB, prefix archiv_).
 * MELD-197 — no PHP include from Notenarchiv.
 */

/** @return string */
function archivDbPrefix() {
    return 'archiv_';
}

/**
 * Whether archiv collection + composition (+ people/publisher) tables exist.
 * @return bool
 */
function archivCollectionsReady() {
    static $ready = null;
    static $probedMissing = false;
    if($ready === true) {
        return true;
    }
    if($ready === false && $probedMissing) {
        $probedMissing = false;
    }
    if(!isset($GLOBALS['conn']) || !($GLOBALS['conn'] instanceof mysqli)) {
        $ready = false;
        $probedMissing = true;
        return false;
    }
    $prefix = archivDbPrefix();
    $tables = array(
        $prefix.'Collection',
        $prefix.'CollectionItem',
        $prefix.'Composition',
        $prefix.'Composer',
        $prefix.'Publisher',
    );
    foreach($tables as $table) {
        $like = mysqli_real_escape_string($GLOBALS['conn'], $table);
        $dbr = mysqli_query($GLOBALS['conn'], "SHOW TABLES LIKE '".$like."'");
        if(!$dbr || !mysqli_fetch_array($dbr)) {
            $ready = false;
            $probedMissing = true;
            return false;
        }
    }
    $ready = true;
    return true;
}

/**
 * Feature gate: Archiv URL configured and collection tables present.
 * @return bool
 */
function archivFeatureEnabled() {
    $url = isset($GLOBALS['optionsDB']['urlNotenarchiv'])
        ? trim((string)$GLOBALS['optionsDB']['urlNotenarchiv'])
        : '';
    if($url === '') {
        return false;
    }
    return archivCollectionsReady();
}

/**
 * Base URL of the Notenarchiv app (trailing slash), or ''.
 * @return string
 */
function archivNotenarchivBaseUrl() {
    $url = isset($GLOBALS['optionsDB']['urlNotenarchiv'])
        ? trim((string)$GLOBALS['optionsDB']['urlNotenarchiv'])
        : '';
    if($url === '') {
        return '';
    }
    return rtrim($url, '/').'/';
}

/**
 * Active collections for a select list: list of array{id:int,name:string}.
 * @return list<array{id:int,name:string}>
 */
function archivListCollectionsForSelect() {
    $out = array();
    if(!archivCollectionsReady()) {
        return $out;
    }
    $sql = sprintf(
        'SELECT `Index`, `Name` FROM `%sCollection` WHERE `Archived` = 0 ORDER BY `Name` ASC, `Index` ASC;',
        archivDbPrefix()
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    if(!$dbr) {
        return $out;
    }
    while($row = mysqli_fetch_array($dbr)) {
        $id = (int)$row['Index'];
        $name = trim((string)$row['Name']);
        if($id < 1) {
            continue;
        }
        if($name === '') {
            $name = 'Sammlung #'.$id;
        }
        $out[] = array('id' => $id, 'name' => $name);
    }
    return $out;
}

/**
 * Collection name or '' if missing.
 * @param int $id
 * @return string
 */
function archivCollectionName($id) {
    $id = (int)$id;
    if($id < 1 || !archivCollectionsReady()) {
        return '';
    }
    $sql = sprintf(
        'SELECT `Name` FROM `%sCollection` WHERE `Index` = "%d" LIMIT 1;',
        archivDbPrefix(),
        $id
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    if(!$dbr || !($row = mysqli_fetch_array($dbr))) {
        return '';
    }
    $name = trim((string)$row['Name']);
    return $name !== '' ? $name : ('Sammlung #'.$id);
}

/**
 * Legacy Archiv text may store HTML entities (&uuml;, &bdquo;, …).
 * @param mixed $value
 * @return string
 */
function archivPlainText($value) {
    return html_entity_decode((string)$value, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

/**
 * @param string $first
 * @param string $last
 * @return string
 */
function archivPersonDisplayName($first, $last) {
    $parts = array();
    $first = trim(archivPlainText($first));
    $last = trim(archivPlainText($last));
    if($first !== '') {
        $parts[] = $first;
    }
    if($last !== '') {
        $parts[] = $last;
    }
    return implode(' ', $parts);
}

/**
 * Up to two initials from a title (Archiv cover placeholder parity).
 * @param string $title
 * @return string
 */
function archivCoverInitials($title) {
    $title = trim((string)$title);
    if($title === '') {
        return '—';
    }
    $skip = array(
        'der', 'die', 'das', 'den', 'dem', 'des',
        'ein', 'eine', 'einen', 'einem', 'einer',
        'the', 'a', 'an', 'le', 'la', 'les',
        'of', 'und', 'and', 'im', 'in', 'am', 'zu', 'zur', 'zum',
    );
    $words = preg_split('/[\s\-–—]+/u', $title, -1, PREG_SPLIT_NO_EMPTY);
    if(!is_array($words)) {
        $words = array($title);
    }
    $letters = array();
    $firstWord = '';
    foreach($words as $w) {
        $plain = preg_replace('/[^\p{L}\p{N}]+/u', '', (string)$w);
        if($plain === null || $plain === '') {
            continue;
        }
        if(in_array(mb_strtolower($plain, 'UTF-8'), $skip, true)) {
            continue;
        }
        if($firstWord === '') {
            $firstWord = $plain;
        }
        $letters[] = mb_strtoupper(mb_substr($plain, 0, 1, 'UTF-8'), 'UTF-8');
        if(count($letters) >= 2) {
            break;
        }
    }
    if(count($letters) >= 2) {
        return implode('', $letters);
    }
    if($firstWord !== '') {
        return mb_strtoupper(mb_substr($firstWord, 0, 2, 'UTF-8'), 'UTF-8');
    }
    $compact = preg_replace('/\s+/u', '', $title);
    return mb_strtoupper(mb_substr((string)$compact, 0, 2, 'UTF-8'), 'UTF-8');
}

/**
 * Absolute http(s) URL for a stored website value, or empty string.
 * @param string $url
 * @return string
 */
function archivNormalizeWebsiteUrl($url) {
    $url = trim((string)$url);
    if($url === '') {
        return '';
    }
    if(!preg_match('#^https?://#i', $url)) {
        $url = 'https://'.$url;
    }
    return $url;
}

/**
 * Relative cover paths under Archiv web root (png/jpg/jpeg/gif), FilePath normalized.
 * @param string $filePath
 * @return list<string>
 */
function archivCompositionCoverRelCandidates($filePath) {
    $rel = str_replace('\\', '/', trim((string)$filePath));
    if($rel === '') {
        return array();
    }
    if(substr($rel, -1) !== '/') {
        $rel .= '/';
    }
    $rel = ltrim($rel, '/');
    $out = array();
    foreach(array('cover.png', 'cover.jpg', 'cover.jpeg', 'cover.gif') as $name) {
        $out[] = $rel.$name;
    }
    return $out;
}

/**
 * Local filesystem roots that may contain Archiv `data/` (optional config + sibling checkout).
 * @return list<string> Absolute paths with trailing slash
 */
function archivCompositionDataRoots() {
    $roots = array();
    $cfg = isset($GLOBALS['optionsDB']['notenarchivDataDirectory'])
        ? trim((string)$GLOBALS['optionsDB']['notenarchivDataDirectory'])
        : '';
    if($cfg !== '') {
        $roots[] = rtrim($cfg, '/\\').'/';
    }
    // Sibling repo layout: …/MVD/meldeliste + …/MVD/notenarchiv
    $sibling = dirname(__DIR__).'/../notenarchiv/';
    $real = realpath($sibling);
    if($real !== false && is_dir($real)) {
        $roots[] = rtrim($real, '/\\').'/';
    }
    return array_values(array_unique($roots));
}

/**
 * Prefer a cover relative path that exists on a local data root, else ''.
 * @param string $filePath
 * @return string
 */
function archivPreferLocalCoverRel($filePath) {
    $cands = archivCompositionCoverRelCandidates($filePath);
    if(!count($cands)) {
        return '';
    }
    foreach(archivCompositionDataRoots() as $root) {
        foreach($cands as $rel) {
            if(is_file($root.$rel)) {
                return $rel;
            }
        }
    }
    return '';
}

/**
 * Cover thumbnail HTML (Archiv list parity). Uses urlNotenarchiv + FilePath.
 * Tries png/jpg/jpeg/gif (local probe when possible, else onerror chain).
 * @param int $compositionId
 * @param string $title
 * @param string|null $filePath Relative path under Archiv web root (trailing slash typical)
 * @return string
 */
function archivCompositionCoverHtml($compositionId, $title, $filePath) {
    $compositionId = (int)$compositionId;
    $title = trim(archivPlainText($title));
    $filePath = trim((string)$filePath);
    $cls = 'archiv-thumb piece-cover';
    $ini = htmlspecialchars(archivCoverInitials($title), ENT_QUOTES, 'UTF-8');
    if($ini === '') {
        $ini = '—';
    }
    $seed = (string)$compositionId.'|'.$title;
    $hue = (int)(sprintf('%u', crc32($seed)) % 360);
    $placeholderInner = '<span class="piece-cover-initials">'.$ini.'</span>';
    $base = archivNotenarchivBaseUrl();
    $rels = archivCompositionCoverRelCandidates($filePath);
    if($base === '' || !count($rels)) {
        return '<span class="'.$cls.' piece-cover--placeholder" style="background-color:hsl('.$hue.',42%,38%)" aria-hidden="true">'
            .$placeholderInner.'</span>';
    }
    $preferred = archivPreferLocalCoverRel($filePath);
    if($preferred !== '') {
        $rels = array_values(array_unique(array_merge(array($preferred), $rels)));
    }
    $urls = array();
    foreach($rels as $rel) {
        $urls[] = $base.$rel;
    }
    $src = htmlspecialchars($urls[0], ENT_QUOTES, 'UTF-8');
    $fallbacks = array_slice($urls, 1);
    $fallbackAttr = htmlspecialchars(implode('|', $fallbacks), ENT_QUOTES, 'UTF-8');
    // Try next extension on error, then initials placeholder (attribute-safe JS).
    $onerror = htmlspecialchars(
        'var i=this,f=(i.getAttribute(\'data-cover-fallbacks\')||\'\').split(\'|\').filter(Boolean);'
        .'if(f.length){i.src=f.shift();i.setAttribute(\'data-cover-fallbacks\',f.join(\'|\'));return;}'
        .'i.onerror=null;var p=i.parentNode;if(!p)return;i.remove();'
        ."p.classList.add('piece-cover--placeholder');"
        ."p.style.backgroundColor='hsl(".$hue.",42%,38%)';"
        ."var s=p.querySelector('.piece-cover-fallback');if(s)s.hidden=false;",
        ENT_QUOTES,
        'UTF-8'
    );
    return '<span class="'.$cls.'" aria-hidden="true">'
        .'<img src="'.$src.'" alt="" data-cover-fallbacks="'.$fallbackAttr.'" onerror="'.$onerror.'">'
        .'<span class="piece-cover-fallback piece-cover-initials" hidden>'.$ini.'</span>'
        .'</span>';
}

/**
 * Link label for Verlag: publisher name, else host of URL (not the full URL).
 * @param string $name
 * @param string $href
 * @return string
 */
function archivPublisherLabel($name, $href) {
    $name = trim(archivPlainText($name));
    if($name !== '') {
        return $name;
    }
    $href = trim((string)$href);
    if($href === '') {
        return '';
    }
    $host = parse_url($href, PHP_URL_HOST);
    if(is_string($host) && $host !== '') {
        return preg_replace('/^www\./i', '', $host);
    }
    return 'Link';
}

/**
 * Modal payload: name + ordered pieces with Archiv list meta.
 * @param int $id
 * @return array{id:int,name:string,items:list<array<string,mixed>}|null
 */
function archivLoadCollectionModalData($id) {
    $id = (int)$id;
    if($id < 1 || !archivCollectionsReady()) {
        return null;
    }
    $prefix = archivDbPrefix();
    $sql = sprintf(
        'SELECT `Index`, `Name`, `Archived` FROM `%sCollection` WHERE `Index` = "%d" LIMIT 1;',
        $prefix,
        $id
    );
    $dbr = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    if(!$dbr || !($row = mysqli_fetch_array($dbr))) {
        return null;
    }
    $name = trim((string)$row['Name']);
    if($name === '') {
        $name = 'Sammlung #'.$id;
    }
    $items = array();
    $sqlItems = sprintf(
        'SELECT i.`CollectionNumber`, i.`Composition`,
                c.`Title`, c.`Year`, c.`Grade`, c.`FilePath`, c.`Website`,
                cf.`FirstName` AS `ComposerFirst`, cf.`LastName` AS `ComposerLast`,
                ar.`FirstName` AS `ArrangerFirst`, ar.`LastName` AS `ArrangerLast`,
                p.`Name` AS `PublisherName`, p.`Website` AS `PublisherWebsite`
         FROM `%sCollectionItem` i
         LEFT JOIN `%sComposition` c ON c.`Index` = i.`Composition`
         LEFT JOIN `%sComposer` cf ON cf.`Index` = c.`Composer`
         LEFT JOIN `%sComposer` ar ON ar.`Index` = c.`Arranger`
         LEFT JOIN `%sPublisher` p ON p.`Index` = c.`Publisher`
         WHERE i.`Collections` = "%d"
         ORDER BY i.`CollectionNumber` ASC, i.`Index` ASC;',
        $prefix,
        $prefix,
        $prefix,
        $prefix,
        $prefix,
        $id
    );
    $dbrItems = mysqli_query($GLOBALS['conn'], $sqlItems);
    sqlerror();
    $seen = array();
    if($dbrItems) {
        while($item = mysqli_fetch_array($dbrItems)) {
            $compId = (int)$item['Composition'];
            if($compId < 1 || isset($seen[$compId])) {
                continue;
            }
            $seen[$compId] = true;
            $num = $item['CollectionNumber'];
            $title = trim(archivPlainText(isset($item['Title']) ? $item['Title'] : ''));
            if($title === '') {
                $title = 'Stück #'.$compId;
            }
            $composer = archivPersonDisplayName(
                isset($item['ComposerFirst']) ? $item['ComposerFirst'] : '',
                isset($item['ComposerLast']) ? $item['ComposerLast'] : ''
            );
            $arranger = archivPersonDisplayName(
                isset($item['ArrangerFirst']) ? $item['ArrangerFirst'] : '',
                isset($item['ArrangerLast']) ? $item['ArrangerLast'] : ''
            );
            $publisher = trim(archivPlainText(isset($item['PublisherName']) ? $item['PublisherName'] : ''));
            $year = isset($item['Year']) && $item['Year'] !== null && $item['Year'] !== ''
                ? (string)(int)$item['Year']
                : '';
            $grade = isset($item['Grade']) && $item['Grade'] !== null && $item['Grade'] !== ''
                ? rtrim(rtrim(sprintf('%.1f', (float)$item['Grade']), '0'), '.')
                : '';
            $productUrl = archivNormalizeWebsiteUrl(isset($item['Website']) ? $item['Website'] : '');
            $publisherUrl = archivNormalizeWebsiteUrl(
                isset($item['PublisherWebsite']) ? $item['PublisherWebsite'] : ''
            );
            $publisherHref = $productUrl !== '' ? $productUrl : $publisherUrl;
            $filePath = isset($item['FilePath']) ? trim((string)$item['FilePath']) : '';
            $items[] = array(
                'compositionId' => $compId,
                'number' => ($num !== null && $num !== '') ? (string)(int)$num : '',
                'title' => $title,
                'composer' => $composer,
                'arranger' => $arranger,
                'publisher' => $publisher,
                'publisherHref' => $publisherHref,
                'publisherLabel' => archivPublisherLabel($publisher, $publisherHref),
                'year' => $year,
                'grade' => $grade,
                'coverHtml' => archivCompositionCoverHtml($compId, $title, $filePath),
            );
        }
    }
    return array(
        'id' => $id,
        'name' => $name,
        'items' => $items,
    );
}

/**
 * Modal HTML for Melde AJAX host.
 * @param int $id
 * @return string
 */
function archivCollectionModalHtml($id) {
    $data = archivLoadCollectionModalData($id);
    if($data === null) {
        return '';
    }
    return render('sammlung/modal', array(
        'collectionId' => $data['id'],
        'collectionName' => $data['name'],
        'items' => $data['items'],
    ));
}

/**
 * Programm modal payload for a Termin (all linked collections + pieces).
 * @param int $terminId
 * @return array{terminId:int,terminName:string,collections:list<array{id:int,name:string,items:list}>}|null
 */
function archivLoadTerminProgrammModalData($terminId) {
    $terminId = (int)$terminId;
    if($terminId < 1 || !archivFeatureEnabled()) {
        return null;
    }
    $t = new Termin();
    $t->load_by_id($terminId);
    if((int)$t->Index < 1) {
        return null;
    }
    $ids = $t->getSammlungenArray();
    $collections = array();
    foreach($ids as $cid) {
        $data = archivLoadCollectionModalData($cid);
        if($data === null) {
            continue;
        }
        $collections[] = array(
            'id' => $data['id'],
            'name' => $data['name'],
            'items' => $data['items'],
        );
    }
    $terminName = trim((string)$t->Name);
    if($terminName === '') {
        $terminName = 'Termin #'.$terminId;
    }
    return array(
        'terminId' => $terminId,
        'terminName' => $terminName,
        'collections' => $collections,
    );
}

/**
 * Programm modal HTML (Termin id).
 * @param int $terminId
 * @return string
 */
function archivTerminProgrammModalHtml($terminId) {
    $data = archivLoadTerminProgrammModalData($terminId);
    if($data === null) {
        return '';
    }
    return render('programm/modal', array(
        'terminId' => $data['terminId'],
        'terminName' => $data['terminName'],
        'collections' => $data['collections'],
    ));
}
?>
