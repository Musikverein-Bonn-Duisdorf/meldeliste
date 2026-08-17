<?php
/**
 * Optional photos for inventory items (MELD-191).
 */
class InventoriesPhoto
{
    private $_data = array(
        'Index' => null,
        'Inventory' => null,
        'Sortierung' => 1,
        'File' => null,
        'Created' => null,
    );

    public function __get($key) {
        return isset($this->_data[$key]) ? $this->_data[$key] : null;
    }

    public function __set($key, $val) {
        if(array_key_exists($key, $this->_data)) {
            $this->_data[$key] = $val;
        }
    }

    public static function storageDir($inventoryId) {
        return dirname(__DIR__).'/uploads/inventory/'.(int)$inventoryId;
    }

    public static function publicUrl($photoId) {
        return 'inventory-photo.php?id='.(int)$photoId;
    }

    /**
     * @param int $inventoryId
     * @return list<InventoriesPhoto>
     */
    public static function listForInventory($inventoryId) {
        $inventoryId = (int)$inventoryId;
        $out = array();
        if($inventoryId < 1) {
            return $out;
        }
        $sql = sprintf(
            'SELECT * FROM `%sInventoriesPhotos` WHERE `Inventory` = %d ORDER BY `Sortierung`, `Index`;',
            $GLOBALS['dbprefix'],
            $inventoryId
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if(!$dbr) {
            return $out;
        }
        while($row = mysqli_fetch_array($dbr, MYSQLI_ASSOC)) {
            $p = new self();
            $p->fill_from_array($row);
            $out[] = $p;
        }
        return $out;
    }

    /** @return InventoriesPhoto|null */
    public static function firstForInventory($inventoryId) {
        $list = self::listForInventory($inventoryId);
        return count($list) ? $list[0] : null;
    }

    public function fill_from_array($row) {
        foreach(array('Index', 'Inventory', 'Sortierung', 'File', 'Created') as $key) {
            if(isset($row[$key])) {
                $this->$key = $row[$key];
            }
        }
    }

    public function load_by_id($id) {
        $id = (int)$id;
        if($id < 1) {
            return;
        }
        $sql = sprintf(
            'SELECT * FROM `%sInventoriesPhotos` WHERE `Index` = %d LIMIT 1;',
            $GLOBALS['dbprefix'],
            $id
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        $row = $dbr ? mysqli_fetch_array($dbr, MYSQLI_ASSOC) : null;
        if(is_array($row)) {
            $this->fill_from_array($row);
        }
    }

    public function save() {
        $invId = (int)$this->Inventory;
        $sort = (int)$this->Sortierung;
        if($sort < 1) {
            $sort = 1;
        }
        $file = mysqli_real_escape_string($GLOBALS['conn'], (string)$this->File);
        if((int)$this->Index > 0) {
            $sql = sprintf(
                'UPDATE `%sInventoriesPhotos` SET `Inventory` = %d, `Sortierung` = %d, `File` = "%s" WHERE `Index` = %d;',
                $GLOBALS['dbprefix'],
                $invId,
                $sort,
                $file,
                (int)$this->Index
            );
            $ok = mysqli_query($GLOBALS['conn'], $sql);
            return $ok ? true : false;
        }
        $sql = sprintf(
            'INSERT INTO `%sInventoriesPhotos` (`Inventory`, `Sortierung`, `File`) VALUES (%d, %d, "%s");',
            $GLOBALS['dbprefix'],
            $invId,
            $sort,
            $file
        );
        $ok = mysqli_query($GLOBALS['conn'], $sql);
        if(!$ok) {
            return false;
        }
        $this->Index = (int)mysqli_insert_id($GLOBALS['conn']);
        return true;
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

    public function absolutePath() {
        return self::resolveStoredFile((int)$this->Inventory, (string)$this->File);
    }

    /**
     * @param array $file $_FILES entry
     * @return string lowercase ext or ''
     */
    public static function imageExtension(array $file) {
        $ext = class_exists('LoanForm') ? LoanForm::uploadExtension($file) : '';
        if($ext === '' || $ext === 'pdf') {
            return '';
        }
        return $ext;
    }

    public static function nextSort($inventoryId) {
        $sql = sprintf(
            'SELECT COALESCE(MAX(`Sortierung`), 0) + 1 AS `n` FROM `%sInventoriesPhotos` WHERE `Inventory` = %d;',
            $GLOBALS['dbprefix'],
            (int)$inventoryId
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        $row = $dbr ? mysqli_fetch_assoc($dbr) : null;
        return $row ? (int)$row['n'] : 1;
    }

    /**
     * Store an uploaded image (JPEG/PNG/GIF/WebP).
     * @return InventoriesPhoto|false
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
        if(!isset($file['size']) || (int)$file['size'] > 8e6 || (int)$file['size'] < 1) {
            return false;
        }
        return self::storeFromPath($inventoryId, (string)$file['tmp_name'], (string)$file['name'], true);
    }

    /**
     * Copy/move a local image into storage (tests: $mustBeUpload=false).
     * @return InventoriesPhoto|false
     */
    public static function storeFromPath($inventoryId, $sourcePath, $originalName, $mustBeUpload = false) {
        $inventoryId = (int)$inventoryId;
        $sourcePath = (string)$sourcePath;
        if($inventoryId < 1 || $sourcePath === '' || !is_file($sourcePath)) {
            return false;
        }
        if($mustBeUpload && !is_uploaded_file($sourcePath)) {
            return false;
        }
        $size = filesize($sourcePath);
        if($size === false || $size > 8e6 || $size < 1) {
            return false;
        }
        $ext = self::imageExtension(array(
            'name' => $originalName,
            'tmp_name' => $sourcePath,
        ));
        if($ext === '') {
            return false;
        }
        $dir = self::storageDir($inventoryId);
        if(!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            return false;
        }
        $name = 'foto-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.'.$ext;
        $target = $dir.DIRECTORY_SEPARATOR.$name;
        $ok = $mustBeUpload ? @move_uploaded_file($sourcePath, $target) : @copy($sourcePath, $target);
        if(!$ok) {
            return false;
        }
        $photo = new self();
        $photo->Inventory = $inventoryId;
        $photo->Sortierung = self::nextSort($inventoryId);
        $photo->File = $name;
        if(!$photo->save()) {
            @unlink($target);
            return false;
        }
        return $photo;
    }

    public function delete() {
        if(!(int)$this->Index) {
            return false;
        }
        $path = $this->absolutePath();
        $sql = sprintf(
            'DELETE FROM `%sInventoriesPhotos` WHERE `Index` = %d LIMIT 1;',
            $GLOBALS['dbprefix'],
            (int)$this->Index
        );
        $ok = mysqli_query($GLOBALS['conn'], $sql);
        if(!$ok) {
            return false;
        }
        if($path) {
            @unlink($path);
        }
        $this->_data['Index'] = null;
        return true;
    }

    /**
     * Make this photo the list preview (Sortierung 1); remaining photos keep relative order.
     */
    public function makePrimary() {
        if((int)$this->Index < 1 || (int)$this->Inventory < 1) {
            return false;
        }
        $list = self::listForInventory((int)$this->Inventory);
        $rest = array();
        foreach($list as $p) {
            if((int)$p->Index === (int)$this->Index) {
                continue;
            }
            $rest[] = $p;
        }
        $this->Sortierung = 1;
        if(!$this->save()) {
            return false;
        }
        $n = 2;
        foreach($rest as $p) {
            $p->Sortierung = $n;
            if(!$p->save()) {
                return false;
            }
            $n++;
        }
        return true;
    }

    public static function deleteAllForInventory($inventoryId) {
        $inventoryId = (int)$inventoryId;
        foreach(self::listForInventory($inventoryId) as $photo) {
            $photo->delete();
        }
        $dir = self::storageDir($inventoryId);
        if(is_dir($dir)) {
            @rmdir($dir);
        }
    }

    /**
     * Gallery markup for the inventory modal.
     *
     * @param int $inventoryId
     * @param bool $canEdit
     * @return string
     */
    public static function galleryHtml($inventoryId, $canEdit) {
        $inventoryId = (int)$inventoryId;
        $photos = self::listForInventory($inventoryId);
        $count = count($photos);
        if(!$canEdit && $count === 0) {
            return '';
        }
        $h = function ($s) {
            return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
        };
        $ids = array();
        foreach($photos as $p) {
            $ids[] = (int)$p->Index;
        }
        $html = '<div class="inv-photo-block">';
        $html .= '<h3 class="profile-col-title">Fotos</h3>';
        if($count > 0) {
            $firstId = $ids[0];
            $html .= '<div class="inv-photo-gallery" data-inventar-id="'.$inventoryId.'" data-photo-ids="'.$h(json_encode($ids)).'">';
            $html .= '<div class="inv-photo-stage">';
            if($count > 1) {
                $html .= '<button type="button" class="inv-photo-nav inv-photo-nav--prev" aria-label="Vorheriges Foto">&lsaquo;</button>';
            }
            $html .= '<img class="inv-photo-img" src="'.$h(self::publicUrl($firstId)).'" alt="" data-photo-id="'.$firstId.'">';
            if($count > 1) {
                $html .= '<button type="button" class="inv-photo-nav inv-photo-nav--next" aria-label="Nächstes Foto">&rsaquo;</button>';
            }
            $html .= '</div>';
            if($count > 1) {
                $html .= '<p class="inv-photo-count"><span class="inv-photo-pos">1</span> / '.$count.'</p>';
            }
            $html .= '</div>';
        }
        if($canEdit) {
            $html .= '<form class="inv-photo-upload" action="inventory-photo.php" method="POST" enctype="multipart/form-data">';
            $html .= '<input type="hidden" name="inventory" value="'.$inventoryId.'">';
            $html .= '<input type="hidden" name="action" value="upload">';
            $html .= '<label class="inv-photo-add">';
            $html .= '<input type="file" name="photo" accept="image/jpeg,image/png,image/gif,image/webp,.jpg,.jpeg,.png,.gif,.webp" required onchange="this.form.requestSubmit()">';
            $html .= '<span>Hinzufügen</span>';
            $html .= '</label>';
            $html .= '</form>';
            if($count > 0) {
                if($count > 1) {
                    $html .= '<form class="inv-photo-primary" action="inventory-photo.php" method="POST" hidden>';
                    $html .= '<input type="hidden" name="inventory" value="'.$inventoryId.'">';
                    $html .= '<input type="hidden" name="action" value="primary">';
                    $html .= '<input type="hidden" name="id" class="inv-photo-primary-id" value="'.$ids[0].'">';
                    $html .= '<button type="submit" class="w3-btn w3-border w3-mobile w3-small">Vorschau</button>';
                    $html .= '</form>';
                }
                $html .= '<form class="inv-photo-delete" action="inventory-photo.php" method="POST">';
                $html .= '<input type="hidden" name="inventory" value="'.$inventoryId.'">';
                $html .= '<input type="hidden" name="action" value="delete">';
                $html .= '<input type="hidden" name="id" class="inv-photo-delete-id" value="'.$ids[0].'">';
                $html .= '<button type="submit" class="w3-btn w3-border w3-mobile w3-small">Löschen</button>';
                $html .= '</form>';
            }
        }
        elseif($count === 0) {
            $html .= '<div class="profile-value">—</div>';
        }
        $html .= '</div>';
        return $html;
    }
}
