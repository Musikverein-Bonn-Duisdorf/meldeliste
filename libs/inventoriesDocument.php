<?php
/**
 * Per-inventory document vault (MELD-205).
 * Files under uploads/inventory-docs/{inventoryId}/ — separate from photos and loan scans.
 */
class InventoriesDocument
{
    private $_data = array(
        'Index' => null,
        'Inventory' => null,
        'StoredFile' => null,
        'UploadedAt' => null,
        'Note' => null,
    );

    public function __get($key) {
        return isset($this->_data[$key]) ? $this->_data[$key] : null;
    }

    public function __set($key, $val) {
        if(!array_key_exists($key, $this->_data)) {
            return;
        }
        if($key === 'Index' || $key === 'Inventory') {
            $this->_data[$key] = (int)$val;
            return;
        }
        if(($key === 'Note' || $key === 'StoredFile') && ($val === '' || $val === null)) {
            $this->_data[$key] = null;
            return;
        }
        $this->_data[$key] = trim((string)$val);
    }

    public static function storageDir($inventoryId) {
        return dirname(__DIR__).'/uploads/inventory-docs/'.(int)$inventoryId;
    }

    public static function resolveStoredFile($inventoryId, $stored) {
        $inventoryId = (int)$inventoryId;
        $stored = trim((string)$stored);
        if($inventoryId < 1 || $stored === '') {
            return null;
        }
        $base = realpath(self::storageDir($inventoryId));
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
     * @return string lowercase extension without dot, or ''
     */
    public static function uploadExtension(array $file) {
        if(class_exists('LoanForm')) {
            return LoanForm::uploadExtension($file);
        }
        return '';
    }

    /**
     * @param array $file $_FILES entry
     * @return string|false basename
     */
    public static function storeUpload($inventoryId, array $file) {
        $inventoryId = (int)$inventoryId;
        if($inventoryId < 1) {
            return false;
        }
        if(!isset($file['error']) || (int)$file['error'] !== UPLOAD_ERR_OK) {
            return false;
        }
        if(!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            return false;
        }
        if(!isset($file['size']) || (int)$file['size'] > 20e6 || (int)$file['size'] < 1) {
            return false;
        }
        $ext = self::uploadExtension($file);
        if($ext === '') {
            return false;
        }
        $dir = self::storageDir($inventoryId);
        if(!is_dir($dir) && !@mkdir($dir, 0750, true)) {
            return false;
        }
        $name = 'doc-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.'.$ext;
        $target = $dir.DIRECTORY_SEPARATOR.$name;
        if(!move_uploaded_file($file['tmp_name'], $target)) {
            return false;
        }
        @chmod($target, 0664);
        return $name;
    }

    /**
     * Copy a local file into the vault (tests / already-stored sources).
     * @return string|false basename
     */
    public static function storeCopyFromPath($inventoryId, $sourcePath, $preferredExt = '') {
        $inventoryId = (int)$inventoryId;
        if($inventoryId < 1 || !is_file($sourcePath)) {
            return false;
        }
        $ext = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION));
        if($ext === 'jpeg') {
            $ext = 'jpg';
        }
        if($ext === '' && $preferredExt !== '') {
            $ext = strtolower(ltrim((string)$preferredExt, '.'));
            if($ext === 'jpeg') {
                $ext = 'jpg';
            }
        }
        $allowed = array('pdf', 'jpg', 'png', 'gif', 'webp');
        if(!in_array($ext, $allowed, true)) {
            return false;
        }
        $dir = self::storageDir($inventoryId);
        if(!is_dir($dir) && !@mkdir($dir, 0750, true)) {
            return false;
        }
        $name = 'doc-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.'.$ext;
        $target = $dir.DIRECTORY_SEPARATOR.$name;
        if(!copy($sourcePath, $target)) {
            return false;
        }
        @chmod($target, 0664);
        return $name;
    }

    public function absolutePath() {
        return self::resolveStoredFile((int)$this->Inventory, (string)$this->StoredFile);
    }

    public function displayName() {
        $note = trim((string)$this->Note);
        if($note !== '') {
            return $note;
        }
        $file = trim((string)$this->StoredFile);
        return $file !== '' ? $file : ('Dokument #'.(int)$this->Index);
    }

    public function mimeType() {
        $path = $this->absolutePath();
        $ext = $path ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : '';
        $map = array(
            'pdf' => 'application/pdf',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
        );
        return isset($map[$ext]) ? $map[$ext] : 'application/octet-stream';
    }

    public function downloadName() {
        $path = $this->absolutePath();
        $ext = $path ? strtolower(pathinfo($path, PATHINFO_EXTENSION)) : 'bin';
        $raw = $this->displayName().'-'.(int)$this->Index.'.'.$ext;
        return preg_replace('/[^\w.\-]+/u', '_', $raw);
    }

    public function is_valid() {
        return (int)$this->Inventory > 0
            && $this->StoredFile !== null
            && $this->StoredFile !== '';
    }

    public function load_by_id($id) {
        $id = (int)$id;
        if($id < 1) {
            return false;
        }
        $sql = sprintf(
            'SELECT * FROM `%sInventoriesDocuments` WHERE `Index` = %d LIMIT 1;',
            $GLOBALS['dbprefix'],
            $id
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = $dbr ? mysqli_fetch_assoc($dbr) : null;
        if(!$row) {
            return false;
        }
        $this->fill_from_row($row);
        return true;
    }

    /**
     * @return InventoriesDocument[]
     */
    public static function listForInventory($inventoryId) {
        $inventoryId = (int)$inventoryId;
        $out = array();
        if($inventoryId < 1) {
            return $out;
        }
        $sql = sprintf(
            'SELECT * FROM `%sInventoriesDocuments` WHERE `Inventory` = %d ORDER BY `UploadedAt` DESC, `Index` DESC;',
            $GLOBALS['dbprefix'],
            $inventoryId
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) {
            return $out;
        }
        while($row = mysqli_fetch_assoc($dbr)) {
            $d = new self();
            $d->fill_from_row($row);
            $out[] = $d;
        }
        return $out;
    }

    public static function userMayAccess(Inventories $inv) {
        if(!(int)$inv->Index) {
            return false;
        }
        if(requirePermission('perm_showInventories')) {
            return true;
        }
        $uid = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
        return $inv->userMayView($uid);
    }

    /**
     * Dokumente-Block for the inventory modal (list, MIT-style).
     */
    public static function sectionHtml($inventoryId, $canEdit) {
        $inventoryId = (int)$inventoryId;
        $canEdit = (bool)$canEdit;
        $documents = self::listForInventory($inventoryId);
        ob_start();
        require dirname(__DIR__).'/views/inventar/docs.php';
        return ob_get_clean();
    }

    public static function deleteAllForInventory($inventoryId) {
        $inventoryId = (int)$inventoryId;
        if($inventoryId < 1) {
            return;
        }
        foreach(self::listForInventory($inventoryId) as $doc) {
            $doc->delete();
        }
        $dir = self::storageDir($inventoryId);
        if(is_dir($dir)) {
            @rmdir($dir);
        }
    }

    /**
     * @param array $file $_FILES entry
     * @return InventoriesDocument|null
     */
    public static function createFromUpload($inventoryId, array $file, $note = null) {
        $stored = self::storeUpload($inventoryId, $file);
        if($stored === false) {
            return null;
        }
        return self::persistNew((int)$inventoryId, $stored, $note);
    }

    /**
     * @return InventoriesDocument|null
     */
    public static function createFromPath($inventoryId, $sourcePath, $note = null, $preferredExt = '') {
        $stored = self::storeCopyFromPath($inventoryId, $sourcePath, $preferredExt);
        if($stored === false) {
            return null;
        }
        return self::persistNew((int)$inventoryId, $stored, $note);
    }

    private static function persistNew($inventoryId, $stored, $note) {
        $doc = new self();
        $doc->Inventory = (int)$inventoryId;
        $doc->StoredFile = $stored;
        $doc->Note = $note;
        if(!$doc->save()) {
            $path = self::resolveStoredFile((int)$inventoryId, $stored);
            if($path) {
                @unlink($path);
            }
            return null;
        }
        return $doc;
    }

    private function logHeader() {
        $invId = (int)$this->Inventory;
        $label = '?';
        $inv = new Inventories();
        if($inv->load_by_id($invId) && (int)$inv->Index) {
            $family = $inv->getInstrumentName();
            if($family !== '') {
                $label = $family;
            }
            else {
                $t = RegNumber::loadType($inv->Inventory);
                if($t && !empty($t->Typ)) {
                    $label = $t->Typ;
                }
            }
        }
        return sprintf(
            'Inventory: (%d) <b>%s</b>, Document-ID: %d',
            $invId,
            $label,
            (int)$this->Index
        );
    }

    public function getVars() {
        $parts = array($this->logHeader());
        $parts[] = 'Datei: '.(string)$this->StoredFile;
        logAppendFilled($parts, 'Notiz', $this->Note, (string)$this->Note);
        return implode(', ', $parts);
    }

    public function save() {
        if(!$this->is_valid()) {
            return false;
        }
        if((int)$this->Index > 0) {
            return $this->update();
        }
        if(!$this->insert()) {
            return false;
        }
        if(class_exists('Log')) {
            $log = new Log();
            $log->DBinsert($this->getVars());
        }
        return true;
    }

    public function delete() {
        if((int)$this->Index < 1) {
            return false;
        }
        $path = $this->absolutePath();
        if(class_exists('Log')) {
            $log = new Log();
            $log->DBdelete($this->getVars());
        }
        $sql = sprintf(
            'DELETE FROM `%sInventoriesDocuments` WHERE `Index` = %d LIMIT 1;',
            $GLOBALS['dbprefix'],
            (int)$this->Index
        );
        $ok = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if($ok && $path !== null && is_file($path)) {
            @unlink($path);
        }
        return (bool)$ok;
    }

    protected function insert() {
        $sql = sprintf(
            'INSERT INTO `%sInventoriesDocuments` (`Inventory`, `StoredFile`, `Note`) VALUES (%d, "%s", %s);',
            $GLOBALS['dbprefix'],
            (int)$this->Inventory,
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->StoredFile),
            ($this->Note === null || $this->Note === '')
                ? 'NULL'
                : '"'.mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Note).'"'
        );
        $ok = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$ok) {
            return false;
        }
        $this->_data['Index'] = (int)mysqli_insert_id($GLOBALS['conn']);
        return true;
    }

    protected function update() {
        $sql = sprintf(
            'UPDATE `%sInventoriesDocuments` SET `Inventory` = %d, `StoredFile` = "%s", `Note` = %s WHERE `Index` = %d;',
            $GLOBALS['dbprefix'],
            (int)$this->Inventory,
            mysqli_real_escape_string($GLOBALS['conn'], (string)$this->StoredFile),
            ($this->Note === null || $this->Note === '')
                ? 'NULL'
                : '"'.mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Note).'"',
            (int)$this->Index
        );
        $ok = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        return (bool)$ok;
    }

    private function fill_from_row($row) {
        foreach(array_keys($this->_data) as $key) {
            if(array_key_exists($key, $row)) {
                $this->$key = $row[$key];
            }
        }
    }
}
