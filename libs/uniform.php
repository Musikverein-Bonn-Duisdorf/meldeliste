<?php
/**
 * Clothing / uniform categories for appointments (MELD-234 / MELD-235).
 */
class Uniform
{
    private $_data = array(
        'Index' => null,
        'Name' => null,
        'Sortierung' => 1,
        'ThumbMale' => null,
        'ThumbFemale' => null,
    );

    /** @var array<string,string> cache key "id:m|w|" → url */
    private static $thumbUrlCache = array();

    public function __get($key) {
        switch($key) {
            case 'Index':
            case 'Name':
            case 'Sortierung':
            case 'ThumbMale':
            case 'ThumbFemale':
                return $this->_data[$key];
            default:
                break;
        }
    }

    public function __set($key, $val) {
        switch($key) {
            case 'Index':
            case 'Sortierung':
                $this->_data[$key] = (int)$val;
                break;
            case 'Name':
                $this->_data[$key] = trim((string)$val);
                break;
            case 'ThumbMale':
            case 'ThumbFemale':
                $v = trim((string)$val);
                $this->_data[$key] = ($v === '') ? null : $v;
                break;
            default:
                break;
        }
    }

    public function is_valid() {
        return $this->Name !== null && $this->Name !== '';
    }

    public function fill_from_array($row) {
        foreach($row as $key => $val) {
            if(is_int($key)) {
                continue;
            }
            if($key === 'ThumbMale' || $key === 'ThumbFemale') {
                $this->$key = $val;
            }
            elseif($key === 'Name') {
                $this->Name = $val;
            }
            elseif($key === 'Sortierung' || $key === 'Index') {
                $this->_data[$key] = (int)$val;
            }
        }
    }

    public function load_by_id($Index) {
        $Index = (int)$Index;
        if($Index < 1) {
            return;
        }
        $sql = sprintf(
            'SELECT * FROM `%sUniform` WHERE `Index` = "%d";',
            $GLOBALS['dbprefix'],
            $Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = mysqli_fetch_array($dbr);
        if(is_array($row)) {
            $this->fill_from_array($row);
        }
    }

    public function usageCount() {
        $sql = sprintf(
            'SELECT COUNT(`Index`) AS `CNT` FROM `%sTermine` WHERE `Uniform` = %d;',
            $GLOBALS['dbprefix'],
            (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if($dbr && ($row = mysqli_fetch_array($dbr))) {
            return (int)$row['CNT'];
        }
        return 0;
    }

    public function canDelete() {
        if(!(int)$this->Index) {
            return false;
        }
        return $this->usageCount() === 0;
    }

    public static function thumbStorageDir($typeId) {
        return dirname(__DIR__).'/uploads/uniform/'.(int)$typeId;
    }

    /**
     * @param mixed $gender
     * @return 'm'|'w'|null
     */
    public static function normalizeGender($gender) {
        $g = strtolower(trim((string)$gender));
        if($g === 'm' || $g === 'w') {
            return $g;
        }
        return null;
    }

    /** @param 'm'|'w' $gender */
    private static function thumbColumn($gender) {
        return $gender === 'w' ? 'ThumbFemale' : 'ThumbMale';
    }

    public static function clearThumbUrlCache($typeId = null) {
        if($typeId === null) {
            self::$thumbUrlCache = array();
            return;
        }
        $prefix = ((int)$typeId).':';
        foreach(array_keys(self::$thumbUrlCache) as $key) {
            if(strpos($key, $prefix) === 0) {
                unset(self::$thumbUrlCache[$key]);
            }
        }
    }

    /**
     * Which gender image to serve for preferred gender (fallback other → none).
     * @param mixed $preferredGender 'm'|'w'|null
     * @return 'm'|'w'|null
     */
    public function resolveThumbGender($preferredGender = null) {
        $pref = self::normalizeGender($preferredGender);
        $order = array();
        if($pref !== null) {
            $order[] = $pref;
            $order[] = ($pref === 'm') ? 'w' : 'm';
        }
        else {
            $order = array('m', 'w');
        }
        foreach($order as $g) {
            if($this->thumbAbsolutePathForGender($g) !== null) {
                return $g;
            }
        }
        return null;
    }

    /**
     * Public URL for a clothing thumbnail, or '' if none.
     * @param mixed $preferredGender 'm'|'w'|null
     */
    public static function thumbUrl($typeId, $preferredGender = null) {
        $typeId = (int)$typeId;
        if($typeId < 1) {
            return '';
        }
        $pref = self::normalizeGender($preferredGender);
        $cacheKey = $typeId.':'.($pref === null ? '' : $pref);
        if(array_key_exists($cacheKey, self::$thumbUrlCache)) {
            return self::$thumbUrlCache[$cacheKey];
        }
        $t = new self();
        $t->load_by_id($typeId);
        $url = '';
        if((int)$t->Index) {
            $resolved = $t->resolveThumbGender($pref);
            if($resolved !== null) {
                $url = 'uniform-thumb.php?id='.$typeId.'&g='.$resolved;
            }
        }
        self::$thumbUrlCache[$cacheKey] = $url;
        return $url;
    }

    /**
     * @param 'm'|'w' $gender
     * @return string|null absolute path
     */
    public function thumbAbsolutePathForGender($gender) {
        $gender = self::normalizeGender($gender);
        if($gender === null) {
            return null;
        }
        $typeId = (int)$this->Index;
        $col = self::thumbColumn($gender);
        $stored = trim((string)$this->$col);
        if($typeId < 1 || $stored === '') {
            return null;
        }
        $base = realpath(self::thumbStorageDir($typeId));
        if($base === false || !is_dir($base)) {
            return null;
        }
        $name = basename(str_replace('\\', '/', $stored));
        if($name === '' || $name === '.' || $name === '..') {
            return null;
        }
        $full = realpath($base.DIRECTORY_SEPARATOR.$name);
        if($full === false || !is_file($full)) {
            return null;
        }
        if(strpos($full, $base.DIRECTORY_SEPARATOR) !== 0 && $full !== $base) {
            return null;
        }
        return $full;
    }

    /**
     * Path for preferred gender with fallback, or null.
     * @param mixed $preferredGender
     */
    public function thumbAbsolutePath($preferredGender = null) {
        $g = $this->resolveThumbGender($preferredGender);
        return $g === null ? null : $this->thumbAbsolutePathForGender($g);
    }

    /**
     * @param array $file $_FILES entry
     * @param string $gender 'm'|'w'
     * @return bool
     */
    public function storeThumb(array $file, $gender) {
        if(!isset($file['error']) || (int)$file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        $tmp = isset($file['tmp_name']) ? (string)$file['tmp_name'] : '';
        $orig = isset($file['name']) ? (string)$file['name'] : 'thumb.png';
        return $this->storeThumbFromPath($tmp, $orig, true, $gender);
    }

    /**
     * @param string $gender 'm'|'w'
     * @return bool
     */
    public function storeThumbFromPath($sourcePath, $originalName, $mustBeUpload = false, $gender = 'm') {
        $gender = self::normalizeGender($gender);
        if($gender === null || (int)$this->Index < 1) {
            return false;
        }
        $sourcePath = (string)$sourcePath;
        if($sourcePath === '' || !is_file($sourcePath)) {
            return false;
        }
        if($mustBeUpload && !is_uploaded_file($sourcePath)) {
            return false;
        }
        $size = filesize($sourcePath);
        if($size === false || $size > 8e6 || $size < 1) {
            return false;
        }
        $ext = InventoriesPhoto::imageExtension(array(
            'name' => $originalName,
            'tmp_name' => $sourcePath,
        ));
        if($ext === '') {
            return false;
        }
        $dir = self::thumbStorageDir((int)$this->Index);
        if(!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            return false;
        }
        $name = 'thumb-'.$gender.'-'.bin2hex(random_bytes(4)).'.'.$ext;
        $target = $dir.DIRECTORY_SEPARATOR.$name;
        $ok = $mustBeUpload ? @move_uploaded_file($sourcePath, $target) : @copy($sourcePath, $target);
        if(!$ok) {
            return false;
        }
        $col = self::thumbColumn($gender);
        $old = $this->thumbAbsolutePathForGender($gender);
        $this->$col = $name;
        if(!$this->save()) {
            @unlink($target);
            return false;
        }
        if($old && is_file($old) && realpath($old) !== realpath($target)) {
            @unlink($old);
        }
        self::clearThumbUrlCache((int)$this->Index);
        return true;
    }

    /**
     * @param string $gender 'm'|'w'
     */
    public function deleteThumb($gender) {
        $gender = self::normalizeGender($gender);
        if($gender === null || (int)$this->Index < 1) {
            return false;
        }
        $col = self::thumbColumn($gender);
        $path = $this->thumbAbsolutePathForGender($gender);
        $this->$col = null;
        if(!$this->save()) {
            return false;
        }
        if($path) {
            @unlink($path);
        }
        $dir = self::thumbStorageDir((int)$this->Index);
        if(is_dir($dir) && $this->thumbAbsolutePathForGender('m') === null && $this->thumbAbsolutePathForGender('w') === null) {
            @rmdir($dir);
        }
        self::clearThumbUrlCache((int)$this->Index);
        return true;
    }

    private function sqlThumbValue($col) {
        $v = trim((string)$this->$col);
        if($v === '') {
            return 'NULL';
        }
        return '"'.mysqli_real_escape_string($GLOBALS['conn'], $v).'"';
    }

    public function save() {
        if(!$this->is_valid()) {
            return false;
        }
        if((int)$this->Index > 0) {
            return $this->update();
        }
        return $this->insert();
    }

    protected function insert() {
        $sql = sprintf(
            'INSERT INTO `%sUniform` (`Name`, `Sortierung`, `ThumbMale`, `ThumbFemale`) VALUES ("%s", "%d", %s, %s);',
            $GLOBALS['dbprefix'],
            mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
            (int)$this->Sortierung ? (int)$this->Sortierung : 1,
            $this->sqlThumbValue('ThumbMale'),
            $this->sqlThumbValue('ThumbFemale')
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) {
            return false;
        }
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }

    protected function update() {
        $sql = sprintf(
            'UPDATE `%sUniform` SET `Name` = "%s", `Sortierung` = "%d", `ThumbMale` = %s, `ThumbFemale` = %s WHERE `Index` = "%d";',
            $GLOBALS['dbprefix'],
            mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
            (int)$this->Sortierung,
            $this->sqlThumbValue('ThumbMale'),
            $this->sqlThumbValue('ThumbFemale'),
            (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        return (bool)$dbr;
    }

    public function delete() {
        if(!$this->canDelete()) {
            return false;
        }
        $id = (int)$this->Index;
        $pathM = $this->thumbAbsolutePathForGender('m');
        $pathW = $this->thumbAbsolutePathForGender('w');
        $dir = self::thumbStorageDir($id);
        $sql = sprintf(
            'DELETE FROM `%sUniform` WHERE `Index` = "%d" LIMIT 1;',
            $GLOBALS['dbprefix'],
            $id
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) {
            return false;
        }
        if($pathM) {
            @unlink($pathM);
        }
        if($pathW) {
            @unlink($pathW);
        }
        if(is_dir($dir)) {
            @rmdir($dir);
        }
        self::clearThumbUrlCache($id);
        $this->_data['Index'] = null;
        return true;
    }

    /**
     * @return list<self>
     */
    public static function allOrdered() {
        $out = array();
        $sql = sprintf(
            'SELECT * FROM `%sUniform` ORDER BY `Sortierung`, `Name`;',
            $GLOBALS['dbprefix']
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) {
            return $out;
        }
        while($row = mysqli_fetch_array($dbr)) {
            $u = new self();
            $u->fill_from_array($row);
            $out[] = $u;
        }
        return $out;
    }
}
?>
