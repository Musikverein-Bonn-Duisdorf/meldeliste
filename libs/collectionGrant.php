<?php
/**
 * Global freischalten of Notenarchiv collections for Meldeliste users/groups (MELD-236).
 * AccessSpec uses AudienceSpec (users + namedGroups + roles/registers).
 * Empty AccessSpec = not pinned (row may remain for Sortierung).
 */
class CollectionGrant
{
    private $_data = array(
        'Index' => null,
        'Collection' => 0,
        'AccessSpec' => null,
        'Sortierung' => 0,
        'CreatedBy' => 0,
        'Created' => null,
    );

    public function __get($key) {
        if(array_key_exists($key, $this->_data)) {
            return $this->_data[$key];
        }
        return null;
    }

    public function __set($key, $val) {
        switch($key) {
        case 'Index':
        case 'Collection':
        case 'Sortierung':
        case 'CreatedBy':
            $this->_data[$key] = (int)$val;
            break;
        case 'AccessSpec':
        case 'Created':
            $this->_data[$key] = $val === null ? null : trim((string)$val);
            break;
        default:
            break;
        }
    }

    public function fill_from_array($row) {
        foreach($row as $key => $val) {
            if(is_int($key)) {
                continue;
            }
            if(array_key_exists($key, $this->_data)) {
                $this->$key = $val;
            }
        }
    }

    public static function ensureSchema() {
        static $done = false;
        if($done) {
            return true;
        }
        $table = new SQLtable('CollectionGrant');
        if(!$table->exists()
            || !$table->columnExists('AccessSpec')
            || !$table->columnExists('Sortierung')) {
            $manager = new DatabaseManager();
            $manager->create();
            $manager->repair();
        }
        $done = true;
        return (new SQLtable('CollectionGrant'))->exists();
    }

    /**
     * @return array{groups:string[],registers:int[],users:int[],namedGroups:int[],termine:int[]}
     */
    public function getAccessSpecArray() {
        return AudienceSpec::normalize($this->AccessSpec, array(
            'allowNamedGroups' => true,
            'allowTermine' => true,
            'defaultGroups' => null,
        ));
    }

    /**
     * @param array|string|null $spec
     */
    public function setAccessSpecArray($spec) {
        $norm = AudienceSpec::normalize($spec, array(
            'allowNamedGroups' => true,
            'allowTermine' => true,
            'defaultGroups' => null,
        ));
        if(AudienceSpec::isEmpty($norm)) {
            $this->AccessSpec = null;
            return;
        }
        $this->AccessSpec = json_encode(array(
            'groups' => $norm['groups'],
            'registers' => $norm['registers'],
            'users' => $norm['users'],
            'namedGroups' => $norm['namedGroups'],
            'termine' => $norm['termine'],
        ));
    }

    public function hasAccess() {
        return !AudienceSpec::isEmpty($this->getAccessSpecArray());
    }

    public function load_by_id($Index) {
        self::ensureSchema();
        $Index = (int)$Index;
        if($Index < 1) {
            return;
        }
        $sql = sprintf(
            'SELECT * FROM `%sCollectionGrant` WHERE `Index` = %d LIMIT 1;',
            $GLOBALS['dbprefix'],
            $Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = $dbr ? mysqli_fetch_array($dbr) : null;
        if(is_array($row)) {
            $this->fill_from_array($row);
        }
    }

    public function load_by_collection($collectionId) {
        self::ensureSchema();
        $collectionId = (int)$collectionId;
        if($collectionId < 1) {
            return;
        }
        $sql = sprintf(
            'SELECT * FROM `%sCollectionGrant` WHERE `Collection` = %d LIMIT 1;',
            $GLOBALS['dbprefix'],
            $collectionId
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = $dbr ? mysqli_fetch_array($dbr) : null;
        if(is_array($row)) {
            $this->fill_from_array($row);
        }
    }

    /**
     * Persist AccessSpec + Sortierung (empty AccessSpec keeps stub row for order).
     * @return bool
     */
    public function save() {
        self::ensureSchema();
        $collectionId = (int)$this->Collection;
        if($collectionId < 1) {
            return false;
        }
        if((int)$this->Index < 1) {
            $existing = new self();
            $existing->load_by_collection($collectionId);
            if((int)$existing->Index > 0) {
                $this->_data['Index'] = (int)$existing->Index;
                if(!(int)$this->CreatedBy) {
                    $this->CreatedBy = (int)$existing->CreatedBy;
                }
                if(!(int)$this->Sortierung && (int)$existing->Sortierung) {
                    $this->Sortierung = (int)$existing->Sortierung;
                }
            }
        }
        if(!(int)$this->Sortierung) {
            $this->Sortierung = self::nextSortierung();
        }
        if((int)$this->Index > 0) {
            return $this->update();
        }
        return $this->insert();
    }

    /**
     * Ensure a grant/sort stub exists for the Archiv collection.
     * @param int $collectionId
     * @return CollectionGrant
     */
    public static function ensureStub($collectionId) {
        $collectionId = (int)$collectionId;
        $g = new self();
        if($collectionId < 1) {
            return $g;
        }
        $g->load_by_collection($collectionId);
        if((int)$g->Index > 0) {
            return $g;
        }
        $g->Collection = $collectionId;
        $g->AccessSpec = null;
        $g->Sortierung = self::nextSortierung();
        $g->CreatedBy = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
        $g->save();
        return $g;
    }

    /** @return int */
    public static function nextSortierung() {
        self::ensureSchema();
        $sql = sprintf(
            'SELECT COALESCE(MAX(`Sortierung`), 0) AS `m` FROM `%sCollectionGrant`;',
            $GLOBALS['dbprefix']
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = $dbr ? mysqli_fetch_assoc($dbr) : null;
        return ($row ? (int)$row['m'] : 0) + 10;
    }

    /**
     * Swap Sortierung with neighbour (up|down) in admin list order.
     * @param int $collectionId
     * @param string $direction up|down
     * @return bool
     */
    public static function move($collectionId, $direction) {
        $collectionId = (int)$collectionId;
        $direction = strtolower(trim((string)$direction));
        if($collectionId < 1 || ($direction !== 'up' && $direction !== 'down')) {
            return false;
        }
        $rows = self::listAllWithMeta();
        if(!count($rows)) {
            return false;
        }
        // Persist current display order so unstubbed rows do not jump after a swap.
        foreach($rows as $i => $row) {
            $g = self::ensureStub((int)$row['id']);
            $want = ($i + 1) * 10;
            if((int)$g->Sortierung !== $want) {
                $g->Sortierung = $want;
                if(!$g->save()) {
                    return false;
                }
            }
        }
        $rows = self::listAllWithMeta();
        $idx = -1;
        foreach($rows as $i => $row) {
            if((int)$row['id'] === $collectionId) {
                $idx = $i;
                break;
            }
        }
        if($idx < 0) {
            return false;
        }
        $swap = ($direction === 'up') ? $idx - 1 : $idx + 1;
        if($swap < 0 || $swap >= count($rows)) {
            return false;
        }
        $a = self::ensureStub((int)$rows[$idx]['id']);
        $b = self::ensureStub((int)$rows[$swap]['id']);
        $sortA = (int)$a->Sortierung;
        $sortB = (int)$b->Sortierung;
        $a->Sortierung = $sortB;
        $b->Sortierung = $sortA;
        return $a->save() && $b->save();
    }

    protected function insert() {
        $createdBy = (int)$this->CreatedBy;
        if($createdBy <= 0 && isset($_SESSION['userid'])) {
            $createdBy = (int)$_SESSION['userid'];
        }
        $sql = sprintf(
            'INSERT INTO `%sCollectionGrant` (`Collection`, `AccessSpec`, `Sortierung`, `CreatedBy`) VALUES (%d, %s, %d, %d);',
            $GLOBALS['dbprefix'],
            (int)$this->Collection,
            $this->sqlAccessSpec(),
            (int)$this->Sortierung,
            $createdBy
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) {
            return false;
        }
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        $this->_data['CreatedBy'] = $createdBy;
        return true;
    }

    protected function update() {
        $sql = sprintf(
            'UPDATE `%sCollectionGrant` SET `Collection` = %d, `AccessSpec` = %s, `Sortierung` = %d WHERE `Index` = %d;',
            $GLOBALS['dbprefix'],
            (int)$this->Collection,
            $this->sqlAccessSpec(),
            (int)$this->Sortierung,
            (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        return (bool)$dbr;
    }

    protected function sqlAccessSpec() {
        $raw = $this->AccessSpec;
        if($raw === null || $raw === '') {
            return 'NULL';
        }
        return '"'.mysqli_real_escape_string($GLOBALS['conn'], (string)$raw).'"';
    }

    public function delete() {
        if(!(int)$this->Index) {
            return false;
        }
        self::ensureSchema();
        $sql = sprintf(
            'DELETE FROM `%sCollectionGrant` WHERE `Index` = %d;',
            $GLOBALS['dbprefix'],
            (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if($dbr) {
            $this->_data['Index'] = null;
            return true;
        }
        return false;
    }

    /**
     * @param int $userId
     * @param int $collectionId
     * @return bool
     */
    public static function userCanAccess($userId, $collectionId) {
        $userId = (int)$userId;
        $collectionId = (int)$collectionId;
        if($userId < 1 || $collectionId < 1) {
            return false;
        }
        $g = new self();
        $g->load_by_collection($collectionId);
        if(!(int)$g->Index || !$g->hasAccess()) {
            return false;
        }
        return AudienceSpec::userMatches($userId, $g->getAccessSpecArray());
    }

    /**
     * Pin-grant, or Sammlung attached to a Termin the user may see.
     * @param int $userId
     * @param int $collectionId
     * @return bool
     */
    public static function userMayView($userId, $collectionId) {
        $userId = (int)$userId;
        $collectionId = (int)$collectionId;
        if($userId < 1 || $collectionId < 1) {
            return false;
        }
        if(self::userCanAccess($userId, $collectionId)) {
            return true;
        }
        return self::attachedToVisibleTermin($userId, $collectionId);
    }

    /**
     * @param int $userId
     * @param int $collectionId
     * @return bool
     */
    public static function attachedToVisibleTermin($userId, $collectionId) {
        $userId = (int)$userId;
        $collectionId = (int)$collectionId;
        if($userId < 1 || $collectionId < 1 || !class_exists('Termin')) {
            return false;
        }
        $needle = (string)$collectionId;
        $sql = sprintf(
            'SELECT * FROM `%sTermine` WHERE `Sammlungen` IS NOT NULL AND `Sammlungen` != \'\' AND `Sammlungen` LIKE \'%%%s%%\';',
            $GLOBALS['dbprefix'],
            mysqli_real_escape_string($GLOBALS['conn'], $needle)
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) {
            return false;
        }
        while($row = mysqli_fetch_array($dbr)) {
            $t = new Termin();
            $t->fill_from_array($row);
            if(!in_array($collectionId, $t->getSammlungenArray(), true)) {
                continue;
            }
            if($t->isVisibleToUser($userId)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @param int $userId
     * @return list<array{id:int,name:string,sort:int}>
     */
    public static function listVisibleToUser($userId) {
        $userId = (int)$userId;
        $out = array();
        if($userId < 1 || !function_exists('archivFeatureEnabled') || !archivFeatureEnabled()) {
            return $out;
        }
        self::ensureSchema();
        $sql = sprintf(
            'SELECT * FROM `%sCollectionGrant` ORDER BY `Sortierung` ASC, `Collection` ASC, `Index` ASC;',
            $GLOBALS['dbprefix']
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) {
            return $out;
        }
        while($row = mysqli_fetch_array($dbr)) {
            $g = new self();
            $g->fill_from_array($row);
            if(!$g->hasAccess()) {
                continue;
            }
            if(!AudienceSpec::userMatches($userId, $g->getAccessSpecArray())) {
                continue;
            }
            $cid = (int)$g->Collection;
            $name = function_exists('archivCollectionName') ? archivCollectionName($cid) : '';
            if($name === '') {
                $name = 'Sammlung #'.$cid;
            }
            $out[] = array(
                'id' => $cid,
                'name' => $name,
                'sort' => (int)$g->Sortierung,
            );
        }
        return $out;
    }

    /**
     * Admin overview: all archiv collections + grant status, ordered by Sortierung.
     * @return list<array{id:int,name:string,grant:?CollectionGrant,accessHtml:string,sort:int}>
     */
    public static function listAllWithMeta() {
        $out = array();
        if(!function_exists('archivListCollectionsForSelect') || !archivFeatureEnabled()) {
            return $out;
        }
        self::ensureSchema();
        $byCollection = array();
        $sql = sprintf('SELECT * FROM `%sCollectionGrant`;', $GLOBALS['dbprefix']);
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if($dbr) {
            while($row = mysqli_fetch_array($dbr)) {
                $g = new self();
                $g->fill_from_array($row);
                $byCollection[(int)$g->Collection] = $g;
            }
        }
        foreach(archivListCollectionsForSelect() as $item) {
            $id = (int)$item['id'];
            $name = isset($item['name']) ? (string)$item['name'] : ('Sammlung #'.$id);
            $grant = isset($byCollection[$id]) ? $byCollection[$id] : null;
            $accessHtml = '<span class="w3-text-gray">—</span>';
            if($grant && $grant->hasAccess()) {
                $accessHtml = AudienceSpec::renderChipsHtml($grant->getAccessSpecArray(), array(
                    'ariaLabel' => 'Freigabe',
                    'emptyHtml' => '<span class="w3-text-gray">—</span>',
                ));
            }
            $sort = $grant ? (int)$grant->Sortierung : (100000 + $id);
            $out[] = array(
                'id' => $id,
                'name' => $name,
                'grant' => $grant,
                'accessHtml' => $accessHtml,
                'sort' => $sort,
            );
        }
        usort($out, function($a, $b) {
            if($a['sort'] === $b['sort']) {
                return strcasecmp($a['name'], $b['name']);
            }
            return $a['sort'] < $b['sort'] ? -1 : 1;
        });
        return $out;
    }
}

/**
 * Expandable Sammlung block with piece list (MELD-236).
 *
 * @param int $collectionId
 * @param string $name
 * @param array $opts toolbarHtml (string), grantHtml (string), open (bool)
 * @return string
 */
function sammlungFoldHtml($collectionId, $name, $opts = array()) {
    $collectionId = (int)$collectionId;
    $name = trim((string)$name);
    if($name === '') {
        $name = 'Sammlung #'.$collectionId;
    }
    $h = function ($s) {
        return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
    };
    $toolbar = isset($opts['toolbarHtml']) ? (string)$opts['toolbarHtml'] : '';
    $grantHtml = isset($opts['grantHtml']) ? (string)$opts['grantHtml'] : '';
    $open = !empty($opts['open']);
    $data = function_exists('archivLoadCollectionModalData')
        ? archivLoadCollectionModalData($collectionId)
        : null;
    $items = ($data && isset($data['items']) && is_array($data['items'])) ? $data['items'] : array();
    $body = count($items)
        ? render('sammlung/piece_list', array('items' => $items))
        : '<div class="profile-value">Keine Stücke.</div>';

    $html = '<div class="sammlung-fold-wrap" data-collection="'.$collectionId.'">';
    $html .= '<div class="sammlung-fold-head">';
    $html .= '<details class="sammlung-fold"'.($open ? ' open' : '').'>';
    $html .= '<summary class="sammlung-fold-summary">';
    $html .= '<span class="sammlung-fold-label">'.$h($name).'</span>';
    $html .= '</summary>';
    $html .= '<div class="sammlung-fold-body">'.$body.'</div>';
    $html .= '</details>';
    if($toolbar !== '') {
        $html .= '<div class="sammlung-fold-toolbar">'.$toolbar.'</div>';
    }
    $html .= '</div>';
    if($grantHtml !== '') {
        $html .= '<div class="sammlung-fold-grant-edit">'.$grantHtml.'</div>';
    }
    $html .= '</div>';
    return $html;
}
