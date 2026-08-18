<?php
/**
 * Printable loan / return forms for InventoriesLoans (any inventory type).
 * MELD-181
 */
class LoanForm
{
    const KIND_LOAN = 'loan';
    const KIND_RETURN = 'return';
    const MAIL_SOURCE_SIGNED = 'loan-sign';
    const MAIL_SOURCE_SIGN_REQUEST = 'loan-sign-request';
    const MAIL_SOURCE_SIGN_REQUEST_STALE = 'loan-sign-request-stale';

    /** @return list<string> */
    public static function kinds() {
        return array(self::KIND_LOAN, self::KIND_RETURN);
    }

    public static function normalizeKind($kind) {
        $kind = strtolower(trim((string)$kind));
        return in_array($kind, self::kinds(), true) ? $kind : self::KIND_LOAN;
    }

    /** Parse money amount from form/DB (comma or dot decimals). */
    public static function parseAmount($raw) {
        if($raw === null || $raw === '') {
            return 0.0;
        }
        if(is_int($raw) || is_float($raw)) {
            return max(0.0, (float)$raw);
        }
        $s = trim((string)$raw);
        $s = str_replace(array('€', ' '), '', $s);
        if(strpos($s, ',') !== false && strpos($s, '.') !== false) {
            // 1.234,56 → drop thousands separator
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        }
        elseif(strpos($s, ',') !== false) {
            $s = str_replace(',', '.', $s);
        }
        if(!is_numeric($s)) {
            return 0.0;
        }
        return max(0.0, round((float)$s, 2));
    }

    public static function hasAmount($raw) {
        return self::parseAmount($raw) > 0.0;
    }

    public static function formatAmount($raw) {
        $amount = self::parseAmount($raw);
        return number_format($amount, 2, ',', '.').' €';
    }

    /** @deprecated Prefer parseAmount — kept for Kaution call sites. */
    public static function parseKaution($raw) {
        return self::parseAmount($raw);
    }

    public static function hasKaution($raw) {
        return self::hasAmount($raw);
    }

    public static function formatKaution($raw) {
        return self::formatAmount($raw);
    }

    /** True if EndDate is set to a concrete calendar day (loan is/was fixed-term). */
    public static function hasFixedEndDate($endDate) {
        if($endDate === null || $endDate === '' || $endDate === '0000-00-00') {
            return false;
        }
        try {
            new DateTime((string)$endDate);
            return true;
        }
        catch(Exception $e) {
            return false;
        }
    }

    /**
     * Normalize YYYY-MM-DD for form/DB; empty → ''; invalid → null.
     * @return string|null
     */
    public static function normalizeDateYmd($raw) {
        $s = trim((string)$raw);
        if($s === '' || $s === '0000-00-00') {
            return '';
        }
        if(!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $s, $m)) {
            return null;
        }
        $y = (int)$m[1];
        $mo = (int)$m[2];
        $d = (int)$m[3];
        if(!checkdate($mo, $d, $y)) {
            return null;
        }
        return sprintf('%04d-%02d-%02d', $y, $mo, $d);
    }

    /** Amount for editable inputs (German decimal, empty when 0). */
    public static function formatAmountInput($raw) {
        $amount = self::parseAmount($raw);
        if($amount <= 0.0) {
            return '';
        }
        return number_format($amount, 2, ',', '');
    }

    /** Store amount as DECIMAL-friendly string. */
    public static function amountSqlValue($raw) {
        return number_format(self::parseAmount($raw), 2, '.', '');
    }

    /** Sanitize a single path segment for print/PDF filenames. */
    public static function sanitizeFileNamePart($raw) {
        $s = trim((string)$raw);
        if($s === '') {
            return '';
        }
        $s = strtr($s, array(
            'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue',
            'Ä' => 'Ae', 'Ö' => 'Oe', 'Ü' => 'Ue', 'ß' => 'ss',
        ));
        $s = preg_replace('/[^A-Za-z0-9._-]+/', '-', $s);
        $s = preg_replace('/-+/', '-', (string)$s);
        return trim((string)$s, '-._');
    }

    /**
     * Basename without extension for print-to-PDF (browser uses document.title).
     * Loan: MVD-Leihvertrag-{Name}-{Instrument}
     * Return: MVD-Rueckgabe-{Name}-{Instrument}
     */
    public static function printFileBasename($kind, $borrowerName, $itemLabel) {
        $kind = self::normalizeKind($kind);
        $prefix = $kind === self::KIND_RETURN ? 'MVD-Rueckgabe' : 'MVD-Leihvertrag';
        $name = self::sanitizeFileNamePart($borrowerName);
        if($name === '') {
            $name = 'ohne-Namen';
        }
        $item = self::sanitizeFileNamePart($itemLabel);
        if($item === '') {
            $item = 'Inventar';
        }
        return $prefix.'-'.$name.'-'.$item;
    }

    const ROLE_LENDER = 'lender';
    const ROLE_BORROWER = 'borrower';

    /**
     * Who may view/print this form.
     * Inventory show/edit, or the borrower of $loan (own contract only).
     * @param InventoriesLoan|null $loan
     */
    public static function userMayView($userId, $loan = null) {
        $userId = (int)$userId;
        if($userId < 1) {
            return false;
        }
        if(requirePermission('perm_showInventories') || requirePermission('perm_editInventories')) {
            return true;
        }
        if($loan instanceof InventoriesLoan && (int)$loan->Index > 0 && (int)$loan->User === $userId) {
            return true;
        }
        return false;
    }

    public static function userMayEdit($userId) {
        $userId = (int)$userId;
        if($userId < 1) {
            return false;
        }
        return requirePermission('perm_editInventories');
    }

    /** Stored loan/return form or scan: Inventar-Schreibrecht (Admin). */
    public static function userMayDeleteScan($userId) {
        return self::userMayEdit($userId);
    }

    /**
     * Return form: admins always; borrowers only when lender signed and their signature is pending.
     */
    public static function userMayViewReturnForm($userId, InventoriesLoan $loan) {
        if(self::userMayEdit($userId)) {
            return true;
        }
        return self::borrowerHasPendingReturnSignature($userId, $loan);
    }

    /** Borrower may open return form only after the club started it (lender signed, borrower pending). */
    public static function borrowerHasPendingReturnSignature($userId, InventoriesLoan $loan) {
        $userId = (int)$userId;
        if($userId < 1 || (int)$loan->Index < 1 || (int)$loan->User !== $userId) {
            return false;
        }
        foreach(self::listPendingForBorrower($userId) as $row) {
            if((int)$row['loan']->Index === (int)$loan->Index
                && self::normalizeKind($row['kind']) === self::KIND_RETURN) {
                return true;
            }
        }
        return false;
    }

    public static function normalizeRole($role) {
        $role = strtolower(trim((string)$role));
        return ($role === self::ROLE_BORROWER) ? self::ROLE_BORROWER : self::ROLE_LENDER;
    }

    /** Admin may sign both fields; borrower only their own. */
    public static function userMaySign($userId, InventoriesLoan $loan, $role) {
        $userId = (int)$userId;
        if($userId < 1 || !(int)$loan->Index) {
            return false;
        }
        $role = self::normalizeRole($role);
        if(self::userMayEdit($userId)) {
            return true;
        }
        return $role === self::ROLE_BORROWER && (int)$loan->User === $userId;
    }

    /** Remove one stored signature before both parties have signed. */
    public static function userMayClearSignature($userId, InventoriesLoan $loan, $role, $kind = self::KIND_LOAN) {
        if(self::isDigitallyComplete($loan, $kind)) {
            return false;
        }
        if(self::getSignature($loan, $kind, $role) === null) {
            return false;
        }
        return self::userMaySign($userId, $loan, $role);
    }

    /** Reopen a completed digital form: Inventar-Schreibrecht only. */
    public static function userMayRestartWorkflow($userId, InventoriesLoan $loan, $kind) {
        return self::userMayEdit($userId) && self::isDigitallyComplete($loan, $kind);
    }

    /** Confirm text for restarting a completed loan/return form. */
    public static function restartWorkflowConfirmMessage() {
        return 'Unterschriften und das gespeicherte Formular werden unwiderruflich gelöscht. '
            .'Danach kann das Formular wieder bearbeitet und neu unterschrieben werden. '
            .'Die Leihe bleibt erhalten.';
    }

    /** Place for signature forms: orgPlace, else last word of orgNameShort. */
    public static function defaultSignPlace() {
        $place = isset($GLOBALS['optionsDB']['orgPlace'])
            ? trim((string)$GLOBALS['optionsDB']['orgPlace']) : '';
        if($place !== '') {
            return $place;
        }
        $short = isset($GLOBALS['optionsDB']['orgNameShort'])
            ? trim((string)$GLOBALS['optionsDB']['orgNameShort']) : '';
        if($short !== '' && preg_match('/\s+(\S+)\s*$/u', $short, $m)) {
            return $m[1];
        }
        return '';
    }

    public static function normalizeSignPlace($raw) {
        $s = trim(preg_replace('/\s+/u', ' ', (string)$raw));
        if(function_exists('mb_substr')) {
            if(mb_strlen($s, 'UTF-8') > 80) {
                $s = mb_substr($s, 0, 80, 'UTF-8');
            }
        }
        elseif(strlen($s) > 80) {
            $s = substr($s, 0, 80);
        }
        return $s;
    }

    /** Multiline org address from config (Leihverträge). */
    public static function normalizeOrgAddress($raw) {
        $s = str_replace(array("\r\n", "\r"), "\n", (string)$raw);
        return trim($s);
    }

    /** "Ort, Datum Uhrzeit" from a stored signature row. */
    public static function formatSignPlaceDate(array $sig) {
        $place = isset($sig['Place']) ? trim((string)$sig['Place']) : '';
        $whenDe = '';
        $signedAt = isset($sig['SignedAt']) ? trim((string)$sig['SignedAt']) : '';
        if($signedAt !== '' && $signedAt !== '0000-00-00 00:00:00') {
            $ts = strtotime($signedAt);
            if($ts !== false) {
                $whenDe = date('d.m.Y H:i', $ts);
            }
        }
        if($whenDe === '') {
            $dateRaw = isset($sig['SignDate']) ? trim((string)$sig['SignDate']) : '';
            if($dateRaw !== '' && $dateRaw !== '0000-00-00') {
                $whenDe = function_exists('germanDate') ? (string)germanDate($dateRaw, 0) : $dateRaw;
            }
        }
        if($place !== '' && $whenDe !== '') {
            return $place.', '.$whenDe;
        }
        if($place !== '') {
            return $place;
        }
        return $whenDe;
    }

    public static function signerDisplayName($userId) {
        $userId = (int)$userId;
        if($userId < 1) {
            return '';
        }
        $u = new User();
        $u->load_by_id($userId);
        if(!(int)$u->Index) {
            return '';
        }
        return trim((string)$u->getName());
    }

    /** "vertreten durch Vorname Nachname" for the signing club representative. */
    public static function lenderRepresentativeLabel($signedByUserId) {
        $name = self::signerDisplayName($signedByUserId);
        if($name === '') {
            return '';
        }
        return 'vertreten durch '.$name;
    }

    public static function lenderRepresentativeFromSig($sig) {
        if(!is_array($sig)) {
            return '';
        }
        return self::lenderRepresentativeLabel(isset($sig['SignedBy']) ? (int)$sig['SignedBy'] : 0);
    }

    /** CSS for self-contained snapshot HTML (print layout). */
    public static function embeddedDocumentCss() {
        $path = dirname(__DIR__).'/styles/loan-form-document.css';
        if(!is_file($path)) {
            return '';
        }
        $css = file_get_contents($path);
        return is_string($css) ? $css : '';
    }

    /** Logo as data-URI for offline snapshot HTML. */
    public static function logoDataUri() {
        $path = dirname(__DIR__).'/imgs/Logo.png';
        if(!is_file($path)) {
            return '';
        }
        $bin = file_get_contents($path);
        if($bin === false || $bin === '') {
            return '';
        }
        return 'data:image/png;base64,'.base64_encode($bin);
    }

    /** Set ContractFile / ReturnContractFile to the digital snapshot (like a scan). */
    public static function attachSnapshotToLoan(InventoriesLoan $loan, $kind, $snapshotName) {
        if(!(int)$loan->Index) {
            return false;
        }
        $kind = self::normalizeKind($kind);
        $snapshotName = basename(trim((string)$snapshotName));
        if($snapshotName === '') {
            return false;
        }
        $loanId = (int)$loan->Index;
        $field = $kind === self::KIND_RETURN ? 'ReturnContractFile' : 'ContractFile';
        $oldStored = trim((string)$loan->$field);
        if($oldStored !== '' && $oldStored !== $snapshotName) {
            $oldPath = self::resolveStoredFile($loanId, $oldStored);
            if($oldPath !== null && is_file($oldPath)) {
                @unlink($oldPath);
            }
        }
        $loan->$field = $snapshotName;
        return (bool)$loan->save();
    }

    /** Absolute directory for scanned contracts of one loan. */
    public static function storageDir($loanId) {
        $loanId = (int)$loanId;
        return dirname(__DIR__).'/uploads/loans/'.$loanId;
    }

    /** Relative path stored in DB (under uploads/). */
    public static function relativeStorageDir($loanId) {
        return 'uploads/loans/'.(int)$loanId;
    }

    /**
     * Resolve a stored ContractFile / ReturnContractFile to an absolute path
     * under uploads/loans/{id}/, or null if missing/unsafe.
     */
    public static function resolveStoredFile($loanId, $stored) {
        $loanId = (int)$loanId;
        $stored = trim((string)$stored);
        if($loanId < 1 || $stored === '') {
            return null;
        }
        $base = realpath(self::storageDir($loanId));
        if($base === false || !is_dir($base)) {
            return null;
        }
        // Accept basename only or relative uploads/loans/{id}/name
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
     * Resolve upload extension from filename and/or file content (pdf/jpg/png/…).
     * @param array $file One $_FILES entry
     * @return string lowercase extension without dot, or '' if unsupported
     */
    public static function uploadExtension(array $file) {
        $allowed = array('pdf', 'jpg', 'png', 'gif', 'webp');
        $orig = isset($file['name']) ? basename((string)$file['name']) : '';
        $orig = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string)$orig);
        $ext = strtolower(pathinfo((string)$orig, PATHINFO_EXTENSION));
        if($ext === 'jpeg') {
            $ext = 'jpg';
        }
        if($ext !== '' && in_array($ext, $allowed, true)) {
            return $ext;
        }

        $tmp = isset($file['tmp_name']) ? (string)$file['tmp_name'] : '';
        $mime = '';
        if($tmp !== '' && is_file($tmp)) {
            if(function_exists('finfo_open')) {
                $fi = finfo_open(FILEINFO_MIME_TYPE);
                if($fi) {
                    $detected = finfo_file($fi, $tmp);
                    finfo_close($fi);
                    if(is_string($detected)) {
                        $mime = strtolower(trim($detected));
                    }
                }
            }
            elseif(function_exists('mime_content_type')) {
                $detected = mime_content_type($tmp);
                if(is_string($detected)) {
                    $mime = strtolower(trim($detected));
                }
            }
        }
        $mimeMap = array(
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/pjpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        );
        return isset($mimeMap[$mime]) ? $mimeMap[$mime] : '';
    }

    /**
     * Store an uploaded scan; returns basename for DB or false.
     * Accepts PDF and images (JPEG/PNG/GIF/WebP).
     * @param array $file One $_FILES entry
     * @param string $kind loan|return
     */
    public static function storeUpload($loanId, array $file, $kind) {
        $loanId = (int)$loanId;
        $kind = self::normalizeKind($kind);
        if($loanId < 1) {
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
        $dir = self::storageDir($loanId);
        if(!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            return false;
        }
        $prefix = $kind === self::KIND_RETURN ? 'rueckgabe' : 'leihvertrag';
        $name = $prefix.'-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.'.$ext;
        $target = $dir.DIRECTORY_SEPARATOR.$name;
        if(!move_uploaded_file($file['tmp_name'], $target)) {
            return false;
        }
        @chmod($target, 0664);
        return $name;
    }

    /**
     * Remove stored scan for loan|return; clears DB pointer and deletes the file.
     * @return bool True if DB was cleared (file may already have been missing).
     */
    public static function deleteScan(InventoriesLoan $loan, $kind) {
        if(!(int)$loan->Index) {
            return false;
        }
        $kind = self::normalizeKind($kind);
        $loanId = (int)$loan->Index;
        $stored = $kind === self::KIND_RETURN
            ? trim((string)$loan->ReturnContractFile)
            : trim((string)$loan->ContractFile);
        if($stored === '') {
            return true;
        }
        $path = self::resolveStoredFile($loanId, $stored);
        if($path !== null && is_file($path)) {
            @unlink($path);
        }
        if($kind === self::KIND_RETURN) {
            $loan->ReturnContractFile = '';
        }
        else {
            $loan->ContractFile = '';
        }
        $loan->save();
        return true;
    }

    /** Normalize free-text contract remarks (max 2000). */
    public static function normalizeContractNotes($raw) {
        $notes = trim((string)$raw);
        if(strlen($notes) > 2000) {
            $notes = substr($notes, 0, 2000);
        }
        return $notes;
    }

    /**
     * Build context for form rendering / tests.
     * @return array|null
     */
    public static function buildContext(InventoriesLoan $loan, $kind = self::KIND_LOAN) {
        $kind = self::normalizeKind($kind);
        if(!(int)$loan->Index || !(int)$loan->Inventory || !(int)$loan->User) {
            return null;
        }

        $inv = new Inventories;
        $inv->load_by_id($loan->Inventory);
        if(!(int)$inv->Index) {
            return null;
        }

        $user = new User;
        $user->load_by_id($loan->User);
        if(!(int)$user->Index) {
            return null;
        }

        $isMember = $user->isVereinMitglied();
        $kaution = self::parseAmount($loan->Kaution);
        $hasKaution = $kaution > 0.0;
        $leihgebuehr = self::parseAmount($loan->Leihgebuehr);
        $hasLeihgebuehr = $leihgebuehr > 0.0;
        $hasEnd = self::hasFixedEndDate($loan->EndDate);
        $orgName = isset($GLOBALS['optionsDB']['orgName'])
            ? trim((string)$GLOBALS['optionsDB']['orgName'])
            : '';
        if($orgName === '') {
            $orgName = isset($GLOBALS['optionsDB']['orgNameShort'])
                ? trim((string)$GLOBALS['optionsDB']['orgNameShort'])
                : 'Verein';
        }
        $orgAddress = self::normalizeOrgAddress(
            isset($GLOBALS['optionsDB']['orgAddress']) ? $GLOBALS['optionsDB']['orgAddress'] : ''
        );

        $typeName = $inv->getInventoryType();
        $instrName = $inv->getInstrumentName();
        $reg = RegNumber::displayInventory($inv->Inventory, $inv->RegNumber);

        $itemLabel = $typeName !== '' ? $typeName : 'Inventar';
        if($instrName !== '') {
            $itemLabel = $instrName;
        }

        $details = array();
        if($reg !== '') {
            $details[] = array('label' => 'Inventarnummer', 'value' => $reg);
        }
        // Skip inventory-type row when the instrument family is already the heading
        // (avoids redundant "Typ: Instrument" under e.g. "Flöte").
        if($typeName !== '' && $typeName !== $itemLabel && $instrName === '') {
            $details[] = array('label' => 'Typ', 'value' => $typeName);
        }
        if($instrName !== '' && $instrName !== $itemLabel) {
            $details[] = array('label' => 'Instrument', 'value' => $instrName);
        }
        if(trim((string)$inv->Description) !== '') {
            $details[] = array('label' => 'Beschreibung', 'value' => trim((string)$inv->Description));
        }
        if(trim((string)$inv->Vendor) !== '') {
            $details[] = array('label' => 'Hersteller', 'value' => trim((string)$inv->Vendor));
        }
        if(trim((string)$inv->Model) !== '') {
            $details[] = array('label' => 'Modell', 'value' => trim((string)$inv->Model));
        }
        if(trim((string)$inv->SerialNr) !== '') {
            $details[] = array('label' => 'Seriennummer', 'value' => trim((string)$inv->SerialNr));
        }

        $borrowerLabel = 'Entleiher';
        $title = 'Leihvertrag';
        if($kind === self::KIND_RETURN) {
            $title = 'Rückgabeprotokoll';
        }
        $borrowerAddress = trim((string)$loan->BorrowerAddress);
        if($borrowerAddress === '' && function_exists('mitPrefillLoanBorrowerAddress')) {
            if(mitPrefillLoanBorrowerAddress($loan)) {
                $borrowerAddress = trim((string)$loan->BorrowerAddress);
            }
        }
        $contractNotes = self::normalizeContractNotes($loan->ContractNotes);

        $ctx = array(
            'kind' => $kind,
            'title' => $title,
            'orgName' => $orgName,
            'orgAddress' => $orgAddress,
            'loanId' => (int)$loan->Index,
            'inventoryId' => (int)$inv->Index,
            'borrowerUserId' => (int)$user->Index,
            'itemLabel' => $itemLabel,
            'itemDetails' => $details,
            'borrowerName' => trim($user->getName()),
            'borrowerEmail' => trim((string)$user->Email),
            'borrowerLabel' => $borrowerLabel,
            'isMember' => $isMember,
            // Address always optional and re-editable (MELD-188).
            'needAddressField' => true,
            'needAddressEditField' => true,
            'borrowerAddress' => $borrowerAddress,
            'contractNotes' => $contractNotes,
            'needContractNotesField' => true,
            // Dates/fees editable on the form itself (MELD-196), like MIT membership-form.
            'needLoanParamsField' => true,
            'startDate' => (string)$loan->StartDate,
            'startDateDe' => germanDate($loan->StartDate, 0),
            'endDate' => $hasEnd ? (string)$loan->EndDate : '',
            'endDateDe' => $hasEnd ? germanDate($loan->EndDate, 0) : '',
            'hasFixedEnd' => $hasEnd,
            'kaution' => $kaution,
            'kautionFormatted' => self::formatAmount($kaution),
            'hasKaution' => $hasKaution,
            'leihgebuehr' => $leihgebuehr,
            'leihgebuehrFormatted' => self::formatAmount($leihgebuehr),
            'hasLeihgebuehr' => $hasLeihgebuehr,
            'contractFile' => trim((string)$loan->ContractFile),
            'returnContractFile' => trim((string)$loan->ReturnContractFile),
            'checklist' => self::parseChecklist($loan->ReturnChecklist),
        );
        $ctx['clauses'] = self::buildClauses($ctx);
        $ctx['printFileBase'] = self::printFileBasename(
            $kind,
            isset($ctx['borrowerName']) ? $ctx['borrowerName'] : '',
            isset($ctx['itemLabel']) ? $ctx['itemLabel'] : ''
        );
        return $ctx;
    }

    /** Escape text and wrap in strong for contract body HTML. */
    public static function em($text) {
        return '<strong class="loan-form-em">'.htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8').'</strong>';
    }

    /** @return array{returned:bool,depositReturned:bool,deductions:string,notes:string} */
    public static function defaultChecklist() {
        return array(
            'returned' => false,
            'depositReturned' => false,
            'deductions' => '',
            'notes' => '',
        );
    }

    /**
     * Decode ReturnChecklist JSON (or array) into a normalized checklist.
     * @param mixed $raw
     * @return array{returned:bool,depositReturned:bool,deductions:string,notes:string}
     */
    public static function parseChecklist($raw) {
        $out = self::defaultChecklist();
        if(is_array($raw)) {
            $data = $raw;
        }
        else {
            $s = trim((string)$raw);
            if($s === '') {
                return $out;
            }
            $data = json_decode($s, true);
            if(!is_array($data)) {
                return $out;
            }
        }
        $out['returned'] = !empty($data['returned']);
        $out['depositReturned'] = !empty($data['depositReturned']);
        $deductions = isset($data['deductions']) ? trim((string)$data['deductions']) : '';
        $notes = isset($data['notes']) ? trim((string)$data['notes']) : '';
        if(strlen($deductions) > 500) {
            $deductions = substr($deductions, 0, 500);
        }
        if(strlen($notes) > 1000) {
            $notes = substr($notes, 0, 1000);
        }
        $out['deductions'] = $deductions;
        $out['notes'] = $notes;
        return $out;
    }

    /** @param array|string $raw */
    public static function encodeChecklist($raw) {
        return json_encode(self::parseChecklist($raw), JSON_UNESCAPED_UNICODE);
    }

    /**
     * Build checklist from return-form POST (unchecked boxes omitted → false).
     * @return array{returned:bool,depositReturned:bool,deductions:string,notes:string}
     */
    public static function checklistFromPost(array $post) {
        return self::parseChecklist(array(
            'returned' => !empty($post['checklist_returned']),
            'depositReturned' => !empty($post['checklist_depositReturned']),
            'deductions' => isset($post['checklist_deductions']) ? $post['checklist_deductions'] : '',
            'notes' => isset($post['checklist_notes']) ? $post['checklist_notes'] : '',
        ));
    }

    /**
     * Persist free fields from the online contract form.
     * Leihbeginn/ende, Kaution, Leihgebühr → InventoriesLoans.*;
     * Adresse (optional) → InventoriesLoans.BorrowerAddress;
     * Bemerkungen → InventoriesLoans.ContractNotes;
     * return checklist → InventoriesLoans.ReturnChecklist (JSON).
     * @return bool
     */
    public static function saveContractFields(InventoriesLoan $loan, array $post, $kind = self::KIND_LOAN) {
        if(!(int)$loan->Index || !(int)$loan->User) {
            return false;
        }
        $kind = self::normalizeKind($kind);
        $user = new User;
        $user->load_by_id($loan->User);
        if(!(int)$user->Index) {
            return false;
        }
        $loanDirty = false;

        if(array_key_exists('StartDate', $post)) {
            $start = self::normalizeDateYmd($post['StartDate']);
            if($start === '') {
                $start = date('Y-m-d');
            }
            if($start !== null && (string)$loan->StartDate !== $start) {
                $loan->StartDate = $start;
                $loanDirty = true;
            }
        }

        if(array_key_exists('EndDate', $post)) {
            $end = self::normalizeDateYmd($post['EndDate']);
            if($end !== null) {
                $newEnd = ($end === '') ? null : $end;
                $oldEnd = self::hasFixedEndDate($loan->EndDate) ? (string)$loan->EndDate : null;
                if($oldEnd !== $newEnd) {
                    $loan->EndDate = $newEnd;
                    $loanDirty = true;
                }
            }
        }

        if(array_key_exists('Kaution', $post)) {
            $kaution = self::amountSqlValue($post['Kaution']);
            if(self::amountSqlValue($loan->Kaution) !== $kaution) {
                $loan->Kaution = $kaution;
                $loanDirty = true;
            }
        }

        if(array_key_exists('Leihgebuehr', $post)) {
            $fee = self::amountSqlValue($post['Leihgebuehr']);
            if(self::amountSqlValue($loan->Leihgebuehr) !== $fee) {
                $loan->Leihgebuehr = $fee;
                $loanDirty = true;
            }
        }

        if(array_key_exists('BorrowerAddress', $post)) {
            $addr = trim((string)$post['BorrowerAddress']);
            if((string)$loan->BorrowerAddress !== $addr) {
                $loan->BorrowerAddress = $addr;
                $loanDirty = true;
            }
        }

        if(array_key_exists('ContractNotes', $post)) {
            $notes = self::normalizeContractNotes($post['ContractNotes']);
            if((string)$loan->ContractNotes !== $notes) {
                $loan->ContractNotes = $notes;
                $loanDirty = true;
            }
        }

        if($kind === self::KIND_RETURN && !empty($post['checklist_save'])) {
            $encoded = self::encodeChecklist(self::checklistFromPost($post));
            if((string)$loan->ReturnChecklist !== $encoded) {
                $loan->ReturnChecklist = $encoded;
                $loanDirty = true;
            }
        }

        if($loanDirty) {
            if(self::hasAnySignature($loan, $kind)) {
                self::clearSignatures($loan, $kind);
            }
            $loan->save();
        }

        return true;
    }

    /** @return string */
    public static function ctxDetailValue(array $ctx, $label) {
        if(empty($ctx['itemDetails']) || !is_array($ctx['itemDetails'])) {
            return '';
        }
        foreach($ctx['itemDetails'] as $row) {
            if(is_array($row) && isset($row['label']) && (string)$row['label'] === (string)$label) {
                return trim((string)$row['value']);
            }
        }
        return '';
    }

    /**
     * Contract text from config (optionsDB), else ConfigDefaults.
     */
    public static function clauseTemplate($param) {
        $param = (string)$param;
        $opts = isset($GLOBALS['optionsDB']) && is_array($GLOBALS['optionsDB'])
            ? $GLOBALS['optionsDB']
            : array();
        if(isset($opts[$param]) && trim((string)$opts[$param]) !== '') {
            return (string)$opts[$param];
        }
        $map = self::clauseDefaultsMap();
        return isset($map[$param]) ? (string)$map[$param] : '';
    }

    /** @return array<string,string> */
    private static function clauseDefaultsMap() {
        static $map = null;
        if($map !== null) {
            return $map;
        }
        $map = array();
        if(function_exists('getConfigDefaults')) {
            foreach(getConfigDefaults() as $item) {
                if(isset($item['Parameter'])) {
                    $map[(string)$item['Parameter']] = isset($item['Value']) ? (string)$item['Value'] : '';
                }
            }
        }
        return $map;
    }

    public static function fillEm($text, $key) {
        $key = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$key);
        return '<strong class="loan-form-em" data-loan-fill="'.$key.'">'
            .htmlspecialchars((string)$text, ENT_QUOTES, 'UTF-8').'</strong>';
    }

    /** Open vs fixed duration phrase (HTML). */
    public static function durationPhraseHtml($endDe, $hasFixedEnd) {
        if(!empty($hasFixedEnd)) {
            return 'bis zum '.self::fillEm((string)$endDe, 'end').' befristet';
        }
        return 'unbefristet';
    }

    /** Return deadline: open-ended vs fixed end date (HTML). */
    public static function returnDuePhraseHtml($endDe, $hasFixedEnd) {
        if(!empty($hasFixedEnd)) {
            return 'spätestens am '.self::fillEm((string)$endDe, 'end');
        }
        return 'bei Beendigung der Leihe unverzüglich';
    }

    /**
     * Fill a config template: escape static text, insert already-safe {placeholders},
     * turn lines starting with "- " into a nested list.
     * @param array<string,string> $vars
     */
    public static function applyClauseTemplate($param, array $vars) {
        return self::fillTemplateString(self::clauseTemplate($param), $vars);
    }

    /**
     * @param array<string,string> $vars
     * @return string
     */
    public static function fillTemplateString($tpl, array $vars) {
        $tpl = (string)$tpl;
        if(trim($tpl) === '') {
            return '';
        }
        $escaped = htmlspecialchars($tpl, ENT_QUOTES, 'UTF-8');
        $filled = preg_replace_callback('/\{([a-zA-Z0-9_]+)\}/', function ($m) use ($vars) {
            $key = $m[1];
            return isset($vars[$key]) ? (string)$vars[$key] : '';
        }, $escaped);
        return self::clauseDashListHtml($filled);
    }

    /** @return list<string> */
    public static function splitParagraphs($tpl) {
        $tpl = str_replace(array("\r\n", "\r"), "\n", (string)$tpl);
        $parts = preg_split("/\n[ \t]*\n/", $tpl);
        $out = array();
        if(!is_array($parts)) {
            return $out;
        }
        foreach($parts as $p) {
            $p = trim((string)$p);
            if($p !== '') {
                $out[] = $p;
            }
        }
        return $out;
    }

    /**
     * @param array<string,string> $vars
     * @param array{keepOptional?:bool,hasFee?:bool,hasKaution?:bool} $opts
     * @return list<string>
     */
    public static function clausesFromTemplate($param, array $vars, array $opts = array()) {
        $keepOptional = !empty($opts['keepOptional']);
        $hasFee = !empty($opts['hasFee']);
        $hasKaution = !empty($opts['hasKaution']);
        $clauses = array();
        foreach(self::splitParagraphs(self::clauseTemplate($param)) as $p) {
            $isFee = strpos($p, '{fee}') !== false;
            $isKaution = strpos($p, '{kaution}') !== false;
            if($isFee && !$hasFee && !$keepOptional) {
                continue;
            }
            if($isKaution && !$hasKaution && !$keepOptional) {
                continue;
            }
            $html = self::fillTemplateString($p, $vars);
            if($html !== '') {
                $clauses[] = $html;
            }
        }
        return $clauses;
    }

    /**
     * @param array<string,string> $vars
     * @return string
     */
    public static function paragraphWithPlaceholder($param, $placeholder, array $vars) {
        $needle = '{'.(string)$placeholder.'}';
        foreach(self::splitParagraphs(self::clauseTemplate($param)) as $p) {
            if(strpos($p, $needle) !== false) {
                return self::fillTemplateString($p, $vars);
            }
        }
        return '';
    }

    private static function clauseDashListHtml($html) {
        $lines = explode("\n", str_replace(array("\r\n", "\r"), "\n", (string)$html));
        $out = array();
        $items = array();
        $flush = function () use (&$out, &$items) {
            if(!$items) {
                return;
            }
            $lis = '';
            foreach($items as $it) {
                $lis .= '<li>'.$it.'</li>';
            }
            $out[] = '<ul class="loan-form-clause-sub">'.$lis.'</ul>';
            $items = array();
        };
        foreach($lines as $line) {
            if(preg_match('/^-\s+(.*)$/', $line, $m)) {
                $items[] = $m[1];
                continue;
            }
            $flush();
            $out[] = $line;
        }
        $flush();
        return trim(implode("\n", $out));
    }

    /** @param array<string,string> $extra */
    public static function clauseVars(array $ctx, array $extra = array()) {
        $org = isset($ctx['orgName']) ? (string)$ctx['orgName'] : 'der Verein';
        $startDe = isset($ctx['startDateDe']) ? (string)$ctx['startDateDe'] : '';
        $endDe = isset($ctx['endDateDe']) ? (string)$ctx['endDateDe'] : '';
        $hasFixedEnd = !empty($ctx['hasFixedEnd']);
        $feeAmt = '';
        if(isset($ctx['leihgebuehrFormatted'])) {
            $feeAmt = (string)$ctx['leihgebuehrFormatted'];
        }
        elseif(isset($ctx['amountFormatted'])) {
            $feeAmt = (string)$ctx['amountFormatted'];
        }
        $kautionAmt = '';
        if(isset($ctx['kautionFormatted'])) {
            $kautionAmt = (string)$ctx['kautionFormatted'];
        }
        elseif(isset($ctx['amountFormatted'])) {
            $kautionAmt = (string)$ctx['amountFormatted'];
        }
        $vars = array(
            'org' => self::em($org),
            'start' => self::fillEm($startDe, 'start'),
            'end' => self::fillEm($endDe, 'end'),
            'duration' => '<span data-loan-fill="duration">'.self::durationPhraseHtml($endDe, $hasFixedEnd).'</span>',
            'returnDue' => '<span data-loan-fill="returnDue">'.self::returnDuePhraseHtml($endDe, $hasFixedEnd).'</span>',
            'borrower' => self::em(isset($ctx['borrowerName']) ? (string)$ctx['borrowerName'] : 'der Entleiher'),
            'item' => self::em(isset($ctx['itemLabel']) ? (string)$ctx['itemLabel'] : 'die Leihsache'),
            'fee' => self::fillEm($feeAmt, 'fee'),
            'kaution' => self::fillEm($kautionAmt, 'kaution'),
            'rep' => '',
            'repPhrase' => '',
            'invNr' => '',
            'invNrPhrase' => '',
        );
        return array_merge($vars, $extra);
    }

    /**
     * First contract clause (loan start / optional end).
     * @return string
     */
    public static function durationClauseHtml($startDe, $endDe, $hasFixedEnd) {
        $clauses = self::clausesFromTemplate('loanText', self::clauseVars(array(
            'startDateDe' => (string)$startDe,
            'endDateDe' => (string)$endDe,
            'hasFixedEnd' => !empty($hasFixedEnd),
        )), array('keepOptional' => true));
        return isset($clauses[0]) ? $clauses[0] : '';
    }

    /**
     * Legal clauses as HTML list items (values escaped; emphasis via self::em).
     * @param array $ctx from buildContext
     * @return list<string>
     */
    public static function buildClauses(array $ctx) {
        $kind = isset($ctx['kind']) ? self::normalizeKind($ctx['kind']) : self::KIND_LOAN;
        if($kind === self::KIND_RETURN) {
            return self::buildReturnClauses($ctx);
        }
        return self::buildLoanClauses($ctx);
    }

    /** @return list<string> */
    public static function buildReturnClauses(array $ctx) {
        $invNr = self::ctxDetailValue($ctx, 'Inventarnummer');
        $repName = '';
        if(!empty($ctx['loanId'])) {
            $loan = new InventoriesLoan;
            $loan->load_by_id((int)$ctx['loanId']);
            if((int)$loan->Index) {
                $sig = self::getSignature($loan, self::KIND_RETURN, self::ROLE_LENDER);
                $repName = self::signerDisplayName(isset($sig['SignedBy']) ? (int)$sig['SignedBy'] : 0);
            }
        }

        $extra = array();
        if($repName !== '') {
            $extra['rep'] = self::em($repName);
            $extra['repPhrase'] = ', vertreten durch '.self::em($repName);
        }
        if($invNr !== '') {
            $extra['invNr'] = self::em($invNr);
            $extra['invNrPhrase'] = ', Inventarnummer '.self::em($invNr);
        }
        $vars = self::clauseVars($ctx, $extra);
        return self::clausesFromTemplate('loanReturnText', $vars, array(
            'keepOptional' => !empty($ctx['keepOptionalMoney']),
            'hasKaution' => !empty($ctx['hasKaution']),
        ));
    }

    /** @return list<string> */
    public static function buildLoanClauses(array $ctx) {
        $vars = self::clauseVars($ctx);
        $opts = array(
            'keepOptional' => !empty($ctx['keepOptionalMoney']),
            'hasFee' => !empty($ctx['hasLeihgebuehr']),
            'hasKaution' => !empty($ctx['hasKaution']),
        );
        $clauses = self::clausesFromTemplate('loanText', $vars, $opts);
        return $clauses;
    }

    /** @return string */
    public static function feeClauseHtml($orgName, $amountFormatted) {
        return self::paragraphWithPlaceholder('loanText', 'fee', self::clauseVars(array(
            'orgName' => isset($orgName) ? (string)$orgName : 'der Verein',
            'leihgebuehrFormatted' => (string)$amountFormatted,
            'hasLeihgebuehr' => true,
        )));
    }

    /** @return string */
    public static function depositClauseHtml($amountFormatted) {
        return self::paragraphWithPlaceholder('loanText', 'kaution', self::clauseVars(array(
            'kautionFormatted' => (string)$amountFormatted,
            'hasKaution' => true,
        )));
    }

    /** @return string */
    public static function returnDepositClauseHtml($borrowerName, $amountFormatted) {
        return self::paragraphWithPlaceholder('loanReturnText', 'kaution', self::clauseVars(array(
            'borrowerName' => isset($borrowerName) ? (string)$borrowerName : 'der Entleiher',
            'kautionFormatted' => (string)$amountFormatted,
            'hasKaution' => true,
        )));
    }

    /** @return array{File:string,SignedAt:string,SignedBy:int,Place:string,SignDate:string,SnapshotFile:string}|null */
    public static function getSignature(InventoriesLoan $loan, $kind, $role) {
        $loanId = (int)$loan->Index;
        if($loanId < 1) {
            return null;
        }
        $kind = self::normalizeKind($kind);
        $role = self::normalizeRole($role);
        $sql = sprintf(
            'SELECT `File`, `SignedAt`, `SignedBy`, `Place`, `SignDate`, `SnapshotFile` FROM `%sInventoriesLoanSignatures` WHERE `Loan` = %d AND `Kind` = "%s" AND `Role` = "%s" LIMIT 1;',
            $GLOBALS['dbprefix'],
            $loanId,
            mysqli_real_escape_string($GLOBALS['conn'], $kind),
            mysqli_real_escape_string($GLOBALS['conn'], $role)
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = $dbr ? mysqli_fetch_assoc($dbr) : null;
        if(!is_array($row) || trim((string)$row['File']) === '') {
            return null;
        }
        return array(
            'File' => trim((string)$row['File']),
            'SignedAt' => isset($row['SignedAt']) ? (string)$row['SignedAt'] : '',
            'SignedBy' => isset($row['SignedBy']) ? (int)$row['SignedBy'] : 0,
            'Place' => isset($row['Place']) ? trim((string)$row['Place']) : '',
            'SignDate' => isset($row['SignDate']) ? trim((string)$row['SignDate']) : '',
            'SnapshotFile' => isset($row['SnapshotFile']) ? trim((string)$row['SnapshotFile']) : '',
        );
    }

    public static function hasAnySignature(InventoriesLoan $loan, $kind) {
        return self::getSignature($loan, $kind, self::ROLE_LENDER) !== null
            || self::getSignature($loan, $kind, self::ROLE_BORROWER) !== null;
    }

    public static function isDigitallyComplete(InventoriesLoan $loan, $kind) {
        $lender = self::getSignature($loan, $kind, self::ROLE_LENDER);
        $borrower = self::getSignature($loan, $kind, self::ROLE_BORROWER);
        if($lender === null || $borrower === null) {
            return false;
        }
        return $lender['SnapshotFile'] !== '' || $borrower['SnapshotFile'] !== '';
    }

    /**
     * Short status suffix for inventory loan rows (HTML-safe text, leading space).
     */
    public static function signatureStatusMetaSuffix(InventoriesLoan $loan, $kind) {
        $kind = self::normalizeKind($kind);
        if(self::isDigitallyComplete($loan, $kind)) {
            return $kind === self::KIND_RETURN
                ? ' · Rückgabe unterschrieben'
                : ' · Vertrag unterschrieben';
        }
        if(self::getSignature($loan, $kind, self::ROLE_LENDER) !== null
            && self::getSignature($loan, $kind, self::ROLE_BORROWER) === null) {
            $label = $kind === self::KIND_RETURN ? 'Rückgabe' : 'Vertrag';
            return ' · '.$label.': Unterschrift Entleiher offen';
        }
        return '';
    }

    /**
     * Link label for stored loan/return contract file in lists.
     */
    public static function storedContractLinkLabel(InventoriesLoan $loan, $kind) {
        $kind = self::normalizeKind($kind);
        $stored = $kind === self::KIND_RETURN
            ? trim((string)$loan->ReturnContractFile)
            : trim((string)$loan->ContractFile);
        if($stored === '') {
            return '';
        }
        if(self::isDigitallyComplete($loan, $kind) || strpos($stored, 'snapshot-') === 0) {
            return $kind === self::KIND_RETURN ? 'Rückgabe' : 'Leihvertrag';
        }
        return $kind === self::KIND_RETURN ? 'Scan Rückgabe' : 'Scan Vertrag';
    }

    /** Confirm text for deleting a stored scan or digital form. */
    public static function deleteStoredFileConfirmMessage(InventoriesLoan $loan, $kind) {
        $label = self::storedContractLinkLabel($loan, $kind);
        if($label === 'Leihvertrag') {
            return 'Leihvertrag löschen? Die Leihe bleibt erhalten.';
        }
        if($label === 'Rückgabe') {
            return 'Rückgabeprotokoll löschen? Die Leihe bleibt erhalten.';
        }
        if(self::normalizeKind($kind) === self::KIND_RETURN) {
            return 'Scan Rückgabe löschen? Die Leihe bleibt erhalten.';
        }
        return 'Scan Vertrag löschen? Die Leihe bleibt erhalten.';
    }

    public static function formHref($loanId, $kind) {
        return 'loan-form.php?loan='.(int)$loanId.'&kind='.rawurlencode(self::normalizeKind($kind));
    }

    public static function signatureUrl(InventoriesLoan $loan, $kind, $role) {
        return 'loan-contract.php?loan='.(int)$loan->Index
            .'&kind='.rawurlencode(self::normalizeKind($kind))
            .'&sig='.rawurlencode(self::normalizeRole($role));
    }

    public static function snapshotUrl(InventoriesLoan $loan, $kind) {
        return 'loan-contract.php?loan='.(int)$loan->Index
            .'&kind='.rawurlencode(self::normalizeKind($kind))
            .'&file=snapshot';
    }

    /**
     * Read the frozen document body from a completed digital snapshot HTML file.
     * @return string|null article/body HTML or null when not available
     */
    public static function readFrozenSnapshotArticle(InventoriesLoan $loan, $kind) {
        if(!self::isDigitallyComplete($loan, $kind)) {
            return null;
        }
        $kind = self::normalizeKind($kind);
        $stored = $kind === self::KIND_RETURN
            ? trim((string)$loan->ReturnContractFile)
            : trim((string)$loan->ContractFile);
        if($stored === '' || strpos($stored, 'snapshot-') !== 0) {
            return null;
        }
        $path = self::resolveStoredFile((int)$loan->Index, $stored);
        if($path === null || !is_file($path)) {
            return null;
        }
        $html = file_get_contents($path);
        if(!is_string($html) || $html === '') {
            return null;
        }
        if(preg_match('/<article class="loan-form-doc"[^>]*>.*<\/article>/is', $html, $m)) {
            return $m[0];
        }
        if(preg_match('/<body[^>]*>(.*)<\/body>/is', $html, $m)) {
            return trim($m[1]);
        }
        return null;
    }

    /**
     * Decode a PNG from a data URL or raw binary. Returns binary or ''.
     */
    public static function decodePngPayload($raw) {
        $raw = (string)$raw;
        if($raw === '') {
            return '';
        }
        if(preg_match('#^data:image/(?:png|jpe?g);base64,(.+)$#is', $raw, $m)) {
            $bin = base64_decode(str_replace(' ', '+', $m[1]), true);
            return is_string($bin) ? $bin : '';
        }
        return $raw;
    }

    public static function isPngBinary($bin) {
        return is_string($bin) && strlen($bin) >= 8 && substr($bin, 0, 8) === "\x89PNG\r\n\x1a\n";
    }

    /**
     * Store a signature PNG for one role. Completes (snapshot + mail) when both exist.
     * @return array{ok:bool,complete:bool,mailed:bool,error:string}
     */
    public static function storeSignature(InventoriesLoan $loan, $kind, $role, $pngRaw, $signedBy, $place = '') {
        $out = array('ok' => false, 'complete' => false, 'mailed' => false, 'error' => '');
        if(!(int)$loan->Index) {
            $out['error'] = 'Leihe fehlt.';
            return $out;
        }
        $kind = self::normalizeKind($kind);
        $role = self::normalizeRole($role);
        $signedBy = (int)$signedBy;
        $place = self::normalizeSignPlace($place);
        if($place === '') {
            $place = self::defaultSignPlace();
        }
        $ymd = date('Y-m-d');
        $bin = self::decodePngPayload($pngRaw);
        if(!self::isPngBinary($bin) || strlen($bin) > 800000) {
            $out['error'] = 'Unterschrift ungültig.';
            return $out;
        }
        $dir = self::storageDir((int)$loan->Index);
        if(!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            $out['error'] = 'Speicher fehlgeschlagen.';
            return $out;
        }
        $old = self::getSignature($loan, $kind, $role);
        if($old !== null) {
            $oldPath = self::resolveStoredFile((int)$loan->Index, $old['File']);
            if($oldPath !== null && is_file($oldPath)) {
                @unlink($oldPath);
            }
        }
        $name = 'sig-'.$kind.'-'.$role.'-'.date('Ymd-His').'-'.bin2hex(random_bytes(3)).'.png';
        $target = $dir.DIRECTORY_SEPARATOR.$name;
        if(@file_put_contents($target, $bin) === false) {
            $out['error'] = 'Speicher fehlgeschlagen.';
            return $out;
        }
        @chmod($target, 0664);

        $escKind = mysqli_real_escape_string($GLOBALS['conn'], $kind);
        $escRole = mysqli_real_escape_string($GLOBALS['conn'], $role);
        $escFile = mysqli_real_escape_string($GLOBALS['conn'], $name);
        $escPlace = mysqli_real_escape_string($GLOBALS['conn'], $place);
        $escDate = mysqli_real_escape_string($GLOBALS['conn'], $ymd);
        mysqli_query($GLOBALS['conn'], sprintf(
            'DELETE FROM `%sInventoriesLoanSignatures` WHERE `Loan` = %d AND `Kind` = "%s" AND `Role` = "%s";',
            $GLOBALS['dbprefix'],
            (int)$loan->Index,
            $escKind,
            $escRole
        ));
        $sql = sprintf(
            'INSERT INTO `%sInventoriesLoanSignatures` (`Loan`, `Kind`, `Role`, `File`, `SignedBy`, `Place`, `SignDate`) VALUES (%d, "%s", "%s", "%s", %d, "%s", "%s");',
            $GLOBALS['dbprefix'],
            (int)$loan->Index,
            $escKind,
            $escRole,
            $escFile,
            $signedBy,
            $escPlace,
            $escDate
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) {
            $out['error'] = 'Speicher fehlgeschlagen.';
            return $out;
        }

        $logentry = new Log;
        $logentry->DBupdate(sprintf(
            'InventoriesLoan-ID: %d, digitale Unterschrift %s/%s',
            (int)$loan->Index,
            $kind,
            $role
        ));

        $out['ok'] = true;
        if(self::getSignature($loan, $kind, self::ROLE_LENDER) !== null
            && self::getSignature($loan, $kind, self::ROLE_BORROWER) !== null) {
            $complete = self::completeDigital($loan, $kind);
            $out['complete'] = !empty($complete['ok']);
            $out['mailed'] = !empty($complete['mailed']);
            if(!$out['complete'] && !empty($complete['error'])) {
                $out['error'] = $complete['error'];
            }
        }
        return $out;
    }

    /**
     * Delete one digital signature while the form is not yet complete.
     * @return bool
     */
    public static function clearSignature(InventoriesLoan $loan, $kind, $role) {
        $loanId = (int)$loan->Index;
        $kind = self::normalizeKind($kind);
        $role = self::normalizeRole($role);
        if($loanId < 1 || self::isDigitallyComplete($loan, $kind)) {
            return false;
        }
        $sig = self::getSignature($loan, $kind, $role);
        if($sig === null) {
            return false;
        }
        $path = self::resolveStoredFile($loanId, $sig['File']);
        if($path !== null && is_file($path)) {
            @unlink($path);
        }
        if($sig['SnapshotFile'] !== '') {
            $snap = self::resolveStoredFile($loanId, $sig['SnapshotFile']);
            if($snap !== null && is_file($snap)) {
                @unlink($snap);
            }
        }
        mysqli_query($GLOBALS['conn'], sprintf(
            'DELETE FROM `%sInventoriesLoanSignatures` WHERE `Loan` = %d AND `Kind` = "%s" AND `Role` = "%s";',
            $GLOBALS['dbprefix'],
            $loanId,
            mysqli_real_escape_string($GLOBALS['conn'], $kind),
            mysqli_real_escape_string($GLOBALS['conn'], $role)
        ));
        sqlerror();

        $logentry = new Log;
        $logentry->DBupdate(sprintf(
            'InventoriesLoan-ID: %d, digitale Unterschrift gelöscht %s/%s',
            $loanId,
            $kind,
            $role
        ));
        if($role === self::ROLE_LENDER) {
            self::invalidateBorrowerSignReminders($loan, $kind);
        }
        return true;
    }

    public static function clearSignatures(InventoriesLoan $loan, $kind) {
        $loanId = (int)$loan->Index;
        $kind = self::normalizeKind($kind);
        if($loanId < 1) {
            return;
        }
        self::invalidateBorrowerSignReminders($loan, $kind);
        $snapNames = array();
        foreach(array(self::ROLE_LENDER, self::ROLE_BORROWER) as $role) {
            $sig = self::getSignature($loan, $kind, $role);
            if($sig === null) {
                continue;
            }
            $path = self::resolveStoredFile($loanId, $sig['File']);
            if($path !== null && is_file($path)) {
                @unlink($path);
            }
            if($sig['SnapshotFile'] !== '') {
                $snapNames[] = $sig['SnapshotFile'];
                $snap = self::resolveStoredFile($loanId, $sig['SnapshotFile']);
                if($snap !== null && is_file($snap)) {
                    @unlink($snap);
                }
            }
        }
        $field = $kind === self::KIND_RETURN ? 'ReturnContractFile' : 'ContractFile';
        $stored = trim((string)$loan->$field);
        if($stored !== '' && in_array($stored, $snapNames, true)) {
            $loan->$field = '';
            $loan->save();
        }
        mysqli_query($GLOBALS['conn'], sprintf(
            'DELETE FROM `%sInventoriesLoanSignatures` WHERE `Loan` = %d AND `Kind` = "%s";',
            $GLOBALS['dbprefix'],
            $loanId,
            mysqli_real_escape_string($GLOBALS['conn'], $kind)
        ));
        sqlerror();
    }

    /**
     * Admin: drop signatures and frozen snapshot so the form can be edited again.
     * @return bool
     */
    public static function restartWorkflow(InventoriesLoan $loan, $kind) {
        $loanId = (int)$loan->Index;
        $kind = self::normalizeKind($kind);
        if($loanId < 1 || !self::isDigitallyComplete($loan, $kind)) {
            return false;
        }
        self::clearSignatures($loan, $kind);
        $loan->load_by_id($loanId);
        $field = $kind === self::KIND_RETURN ? 'ReturnContractFile' : 'ContractFile';
        $stored = trim((string)$loan->$field);
        if($stored !== '' && strpos($stored, 'snapshot-') === 0) {
            $path = self::resolveStoredFile($loanId, $stored);
            if($path !== null && is_file($path)) {
                @unlink($path);
            }
            $loan->$field = '';
            $loan->save();
        }
        $logentry = new Log;
        $logentry->DBupdate(sprintf(
            'InventoriesLoan-ID: %d, digitaler Workflow neu gestartet (%s)',
            $loanId,
            $kind
        ));
        return !self::hasAnySignature($loan, $kind)
            && !self::isDigitallyComplete($loan, $kind);
    }

    /**
     * Loans waiting for the borrower's signature (lender already signed).
     * @return list<array{loan:InventoriesLoan,kind:string}>
     */
    public static function listPendingForBorrower($userId) {
        $userId = (int)$userId;
        $out = array();
        if($userId < 1) {
            return $out;
        }
        $sql = sprintf(
            'SELECT DISTINCT s.`Loan`, s.`Kind` FROM `%sInventoriesLoanSignatures` s
             INNER JOIN `%sInventoriesLoans` l ON l.`Index` = s.`Loan`
             WHERE l.`User` = %d AND s.`Role` = "%s"
               AND NOT EXISTS (
                 SELECT 1 FROM `%sInventoriesLoanSignatures` b
                 WHERE b.`Loan` = s.`Loan` AND b.`Kind` = s.`Kind` AND b.`Role` = "%s"
               )
             ORDER BY s.`Loan` DESC;',
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $userId,
            self::ROLE_LENDER,
            $GLOBALS['dbprefix'],
            self::ROLE_BORROWER
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) {
            return $out;
        }
        while($row = mysqli_fetch_assoc($dbr)) {
            $loan = new InventoriesLoan;
            $loan->load_by_id((int)$row['Loan']);
            if(!(int)$loan->Index) {
                continue;
            }
            $out[] = array(
                'loan' => $loan,
                'kind' => self::normalizeKind($row['Kind']),
            );
        }
        return $out;
    }

    /**
     * Write HTML snapshot and queue a copy to the borrower.
     * @return array{ok:bool,mailed:bool,error:string,snapshot:string}
     */
    public static function completeDigital(InventoriesLoan $loan, $kind) {
        $out = array('ok' => false, 'mailed' => false, 'error' => '', 'snapshot' => '');
        $kind = self::normalizeKind($kind);
        $ctx = self::buildContext($loan, $kind);
        if(!$ctx) {
            $out['error'] = 'Formular fehlt.';
            return $out;
        }
        $html = self::buildSnapshotHtml($loan, $ctx);
        $dir = self::storageDir((int)$loan->Index);
        if(!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            $out['error'] = 'Speicher fehlgeschlagen.';
            return $out;
        }
        $name = ($kind === self::KIND_RETURN ? 'snapshot-rueckgabe' : 'snapshot-leihvertrag')
            .'-'.date('Ymd-His').'.html';
        $target = $dir.DIRECTORY_SEPARATOR.$name;
        if(@file_put_contents($target, $html) === false) {
            $out['error'] = 'Speicher fehlgeschlagen.';
            return $out;
        }
        @chmod($target, 0664);

        mysqli_query($GLOBALS['conn'], sprintf(
            'UPDATE `%sInventoriesLoanSignatures` SET `SnapshotFile` = "%s" WHERE `Loan` = %d AND `Kind` = "%s";',
            $GLOBALS['dbprefix'],
            mysqli_real_escape_string($GLOBALS['conn'], $name),
            (int)$loan->Index,
            mysqli_real_escape_string($GLOBALS['conn'], $kind)
        ));
        sqlerror();

        self::attachSnapshotToLoan($loan, $kind, $name);

        $mailed = self::queueSignedCopy($loan, $ctx, $target);
        $out['snapshot'] = $name;
        $out['ok'] = true;
        $out['mailed'] = $mailed;
        if(!$mailed) {
            $out['error'] = 'Kopie konnte nicht in die Mailwarteschlange.';
        }
        return $out;
    }

    /**
     * @return bool
     */
    public static function queueSignedCopy(InventoriesLoan $loan, array $ctx, $snapshotPath) {
        $uid = (int)$loan->User;
        if($uid < 1 || !is_file($snapshotPath)) {
            return false;
        }
        if(class_exists('MailJob') && method_exists('MailJob', 'ensureSchema')) {
            MailJob::ensureSchema();
        }
        $title = isset($ctx['title']) ? (string)$ctx['title'] : 'Leihvertrag';
        $item = isset($ctx['itemLabel']) ? (string)$ctx['itemLabel'] : 'Inventar';
        $subject = $title.': '.$item;
        $body = '<p>anbei das fertige Formular <b>'
            .htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
            .'</b> zu <b>'.htmlspecialchars($item, ENT_QUOTES, 'UTF-8')
            .'</b> (Leihe Nr. '.(int)$loan->Index.') inkl. Unterschriften.</p>'
            .'<p>Bitte bewahre diese Kopie auf.</p>';

        $job = new MailJob;
        $job->CreatedBy = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : $uid;
        $job->Subject = $subject;
        $job->BodyText = $body;
        $job->Source = self::MAIL_SOURCE_SIGNED;
        $job->Status = 'queued';
        $job->Total = 0;
        $job->Sent = 0;
        $job->Failed = 0;
        $job->setRecipientSpecArray(array(
            'groups' => array(),
            'registers' => array(),
            'users' => array($uid),
            'namedGroups' => array(),
        ));
        if(!$job->save()) {
            return false;
        }
        $job->ensureAttachmentDir();
        $destDir = (string)$job->AttachmentPath;
        if($destDir === '' || !is_dir($destDir)) {
            return false;
        }
        $attachName = self::printFileBasename(
            isset($ctx['kind']) ? $ctx['kind'] : self::KIND_LOAN,
            isset($ctx['borrowerName']) ? $ctx['borrowerName'] : '',
            $item
        ).'.html';
        @copy($snapshotPath, $destDir.DIRECTORY_SEPARATOR.$attachName);

        $mail = new Usermail;
        $mail->User = $uid;
        $mail->subject = $subject;
        $mail->source = self::MAIL_SOURCE_SIGNED;
        $mail->quiet = true;
        $n = $mail->enqueue($body, $job);
        return $n > 0;
    }

    /**
     * Active "Zur Unterschrift senden" jobs for this loan/kind.
     * @return list<array{Index:int,Subject:string,BodyText:string,Created:string}>
     */
    private static function borrowerSignReminderJobRows(InventoriesLoan $loan, $kind) {
        $loanId = (int)$loan->Index;
        $kind = self::normalizeKind($kind);
        $out = array();
        if($loanId < 1 || !class_exists('MailJob')) {
            return $out;
        }
        MailJob::ensureSchema();
        $hrefNeedle = 'loan='.$loanId.'&';
        $nrNeedle = '(Leihe Nr. '.$loanId.')';
        $sql = sprintf(
            'SELECT `Index`, `Subject`, `BodyText`, `Created` FROM `%sMailJob` WHERE `Source` = "%s";',
            $GLOBALS['dbprefix'],
            mysqli_real_escape_string($GLOBALS['conn'], self::MAIL_SOURCE_SIGN_REQUEST)
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) {
            return $out;
        }
        while($row = mysqli_fetch_assoc($dbr)) {
            $body = (string)$row['BodyText'];
            $parsed = MailJob::parseLoanIdFromBody($body);
            if($parsed !== $loanId
                && strpos($body, $hrefNeedle) === false
                && strpos($body, $nrNeedle) === false) {
                continue;
            }
            if($parsed > 0 && $parsed !== $loanId) {
                continue;
            }
            $probe = new MailJob;
            $probe->Subject = (string)$row['Subject'];
            if($probe->inferLoanKindFromSubject() !== $kind) {
                continue;
            }
            $out[] = $row;
        }
        return $out;
    }

    /**
     * Drop old "Zur Unterschrift senden" jobs for this loan/kind so a restarted
     * workflow can queue a new reminder (MailJob rows stay for history).
     */
    public static function invalidateBorrowerSignReminders(InventoriesLoan $loan, $kind) {
        foreach(self::borrowerSignReminderJobRows($loan, $kind) as $row) {
            $job = new MailJob;
            $job->load_by_id((int)$row['Index']);
            if(!(int)$job->Index) {
                continue;
            }
            if($job->canCancel()) {
                $job->cancel();
            }
            $job->Source = self::MAIL_SOURCE_SIGN_REQUEST_STALE;
            $job->save();
        }
    }

    public static function borrowerReminderAlreadyQueued(InventoriesLoan $loan, $kind) {
        $kind = self::normalizeKind($kind);
        $lender = self::getSignature($loan, $kind, self::ROLE_LENDER);
        if($lender === null) {
            return false;
        }
        $sinceRaw = isset($lender['SignedAt']) ? trim((string)$lender['SignedAt']) : '';
        if($sinceRaw === '0000-00-00 00:00:00') {
            $sinceRaw = '';
        }
        foreach(self::borrowerSignReminderJobRows($loan, $kind) as $row) {
            if($sinceRaw !== '') {
                $createdRaw = isset($row['Created']) ? trim((string)$row['Created']) : '';
                if($createdRaw === '' || $createdRaw < $sinceRaw) {
                    continue;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Queue inbox + e-mail asking the borrower to sign (no snapshot).
     * Idempotent per current Vereinsunterschrift. No-op if lender missing or borrower already signed.
     * @return bool true if a reminder exists or was queued
     */
    public static function queueBorrowerSignReminder(InventoriesLoan $loan, $kind) {
        $kind = self::normalizeKind($kind);
        $uid = (int)$loan->User;
        if($uid < 1 || !(int)$loan->Index) {
            return false;
        }
        if(self::getSignature($loan, $kind, self::ROLE_LENDER) === null) {
            return false;
        }
        if(self::getSignature($loan, $kind, self::ROLE_BORROWER) !== null
            || self::isDigitallyComplete($loan, $kind)) {
            return false;
        }
        if(self::borrowerReminderAlreadyQueued($loan, $kind)) {
            return true;
        }

        $ctx = self::buildContext($loan, $kind);
        $title = $ctx && isset($ctx['title']) ? (string)$ctx['title'] : 'Leihvertrag';
        $item = $ctx && isset($ctx['itemLabel']) ? (string)$ctx['itemLabel'] : 'Inventar';
        $subject = $kind === self::KIND_RETURN
            ? 'Rückgabe zur Unterschrift'
            : 'Leihvertrag zur Unterschrift';
        $href = self::formHref((int)$loan->Index, $kind);
        $body = '<p>Bitte unterschreibe das Formular <b>'
            .htmlspecialchars($title, ENT_QUOTES, 'UTF-8')
            .'</b> zu <b>'.htmlspecialchars($item, ENT_QUOTES, 'UTF-8')
            .'</b> (Leihe Nr. '.(int)$loan->Index.').</p>'
            .'<p><a href="'.htmlspecialchars($href, ENT_QUOTES, 'UTF-8').'">Zum Formular</a></p>'
            .'<p>Du findest es auch unter Mein Inventar → Zur Unterschrift.</p>';

        if(class_exists('MailJob') && method_exists('MailJob', 'ensureSchema')) {
            MailJob::ensureSchema();
        }
        $job = new MailJob;
        $job->CreatedBy = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : $uid;
        $job->Subject = $subject;
        $job->BodyText = $body;
        $job->Source = self::MAIL_SOURCE_SIGN_REQUEST;
        $job->Status = 'queued';
        $job->Total = 0;
        $job->Sent = 0;
        $job->Failed = 0;
        $job->setRecipientSpecArray(array(
            'groups' => array(),
            'registers' => array(),
            'users' => array($uid),
            'namedGroups' => array(),
        ));
        if(!$job->save()) {
            return false;
        }

        $mail = new Usermail;
        $mail->User = $uid;
        $mail->subject = $subject;
        $mail->source = self::MAIL_SOURCE_SIGN_REQUEST;
        $mail->quiet = true;
        $n = $mail->enqueue($body, $job);
        return $n > 0;
    }

    /**
     * Self-contained HTML snapshot matching the print form layout.
     */
    public static function buildSnapshotHtml(InventoriesLoan $loan, array $ctx) {
        $h = function ($s) {
            return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
        };
        $kind = isset($ctx['kind']) ? self::normalizeKind($ctx['kind']) : self::KIND_LOAN;
        $lenderMeta = self::getSignature($loan, $kind, self::ROLE_LENDER);
        $borrowerMeta = self::getSignature($loan, $kind, self::ROLE_BORROWER);
        $lenderWhen = $lenderMeta ? self::formatSignPlaceDate($lenderMeta) : '';
        $borrowerWhen = $borrowerMeta ? self::formatSignPlaceDate($borrowerMeta) : '';
        $lenderRep = self::lenderRepresentativeFromSig($lenderMeta);
        $lenderImg = self::signatureDataUri($loan, $kind, self::ROLE_LENDER);
        $borrowerImg = self::signatureDataUri($loan, $kind, self::ROLE_BORROWER);
        $checklist = isset($ctx['checklist']) && is_array($ctx['checklist'])
            ? $ctx['checklist']
            : self::defaultChecklist();

        $brandBar = '#345A95';
        if(isset($GLOBALS['optionsDB']['colorTitleBar'])) {
            $raw = (string)$GLOBALS['optionsDB']['colorTitleBar'];
            if(function_exists('normalizeHexColor')) {
                $hex = normalizeHexColor($raw);
                if($hex !== '') {
                    $brandBar = $hex;
                }
            }
        }

        $logoSrc = self::logoDataUri();
        ob_start();
        include dirname(__DIR__).'/views/loan/form_document.php';
        $body = ob_get_clean();

        $title = $h(isset($ctx['printFileBase']) ? $ctx['printFileBase'] : $ctx['title']);
        $css = self::embeddedDocumentCss();
        return '<!DOCTYPE html><html lang="de"><head><meta charset="utf-8">'
            .'<meta name="viewport" content="width=device-width, initial-scale=1">'
            .'<title>'.$title.'</title>'
            .'<style>'.$css.'</style></head>'
            .'<body class="loan-form-print loan-form-snapshot">'.$body.'</body></html>';
    }

    public static function signatureDataUri(InventoriesLoan $loan, $kind, $role) {
        $sig = self::getSignature($loan, $kind, $role);
        if($sig === null) {
            return '';
        }
        $path = self::resolveStoredFile((int)$loan->Index, $sig['File']);
        if($path === null || !is_file($path)) {
            return '';
        }
        $bin = file_get_contents($path);
        if($bin === false || $bin === '') {
            return '';
        }
        return 'data:image/png;base64,'.base64_encode($bin);
    }
}
?>
