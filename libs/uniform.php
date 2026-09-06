<?php
/**
 * Clothing / uniform categories for appointments (MELD-234).
 */
class Uniform
{
    private $_data = array('Index' => null, 'Name' => null, 'Sortierung' => 1, 'ThumbFile' => null);

    /** @var array<int,string> */
    private static $thumbUrlCache = array();

    public function __get($key) {
        switch($key) {
            case 'Index':
            case 'Name':
            case 'Sortierung':
            case 'ThumbFile':
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
            case 'ThumbFile':
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
            if($key === 'ThumbFile') {
                $this->ThumbFile = $val;
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

    public static function clearThumbUrlCache($typeId = null) {
        if($typeId === null) {
            self::$thumbUrlCache = array();
            return;
        }
        unset(self::$thumbUrlCache[(int)$typeId]);
    }

    /**
     * Public URL for a clothing thumbnail, or '' if none.
     */
    public static function thumbUrl($typeId) {
        $typeId = (int)$typeId;
        if($typeId < 1) {
            return '';
        }
        if(array_key_exists($typeId, self::$thumbUrlCache)) {
            return self::$thumbUrlCache[$typeId];
        }
        $t = new self();
        $t->load_by_id($typeId);
        $url = '';
        if((int)$t->Index && $t->thumbAbsolutePath() !== null) {
            $url = 'uniform-thumb.php?id='.$typeId;
        }
        self::$thumbUrlCache[$typeId] = $url;
        return $url;
    }

    public function thumbAbsolutePath() {
        $typeId = (int)$this->Index;
        $stored = trim((string)$this->ThumbFile);
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
     * @param array $file $_FILES entry
     * @return bool
     */
    public function storeThumb(array $file) {
        if(!isset($file['error']) || (int)$file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        $tmp = isset($file['tmp_name']) ? (string)$file['tmp_name'] : '';
        $orig = isset($file['name']) ? (string)$file['name'] : 'thumb.png';
        return $this->storeThumbFromPath($tmp, $orig, true);
    }

    /**
     * @return bool
     */
    public function storeThumbFromPath($sourcePath, $originalName, $mustBeUpload = false) {
        if((int)$this->Index < 1) {
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
        $name = 'thumb-'.bin2hex(random_bytes(4)).'.'.$ext;
        $target = $dir.DIRECTORY_SEPARATOR.$name;
        $ok = $mustBeUpload ? @move_uploaded_file($sourcePath, $target) : @copy($sourcePath, $target);
        if(!$ok) {
            return false;
        }
        $old = $this->thumbAbsolutePath();
        $this->ThumbFile = $name;
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

    public function deleteThumb() {
        if((int)$this->Index < 1) {
            return false;
        }
        $path = $this->thumbAbsolutePath();
        $this->ThumbFile = null;
        if(!$this->save()) {
            return false;
        }
        if($path) {
            @unlink($path);
        }
        $dir = self::thumbStorageDir((int)$this->Index);
        if(is_dir($dir)) {
            @rmdir($dir);
        }
        self::clearThumbUrlCache((int)$this->Index);
        return true;
    }

    private function sqlThumbFileValue() {
        $v = trim((string)$this->ThumbFile);
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
            'INSERT INTO `%sUniform` (`Name`, `Sortierung`, `ThumbFile`) VALUES ("%s", "%d", %s);',
            $GLOBALS['dbprefix'],
            mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
            (int)$this->Sortierung ? (int)$this->Sortierung : 1,
            $this->sqlThumbFileValue()
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
            'UPDATE `%sUniform` SET `Name` = "%s", `Sortierung` = "%d", `ThumbFile` = %s WHERE `Index` = "%d";',
            $GLOBALS['dbprefix'],
            mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
            (int)$this->Sortierung,
            $this->sqlThumbFileValue(),
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
        $path = $this->thumbAbsolutePath();
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
        if($path) {
            @unlink($path);
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
