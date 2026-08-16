<?php
/**
 * Printable loan / return forms for InventoriesLoans (any inventory type).
 * MELD-181
 */
class LoanForm
{
    const KIND_LOAN = 'loan';
    const KIND_RETURN = 'return';

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

    /**
     * Who may view/print/store loan forms for this loan.
     * Edit right required for upload; show or edit for print/download.
     */
    public static function userMayView($userId) {
        $userId = (int)$userId;
        if($userId < 1) {
            return false;
        }
        return requirePermission('perm_showInventories') || requirePermission('perm_editInventories');
    }

    public static function userMayEdit($userId) {
        $userId = (int)$userId;
        if($userId < 1) {
            return false;
        }
        return requirePermission('perm_editInventories');
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
        $title = $kind === self::KIND_RETURN ? 'Rückgabeprotokoll' : 'Leihvertrag';
        $borrowerAddress = trim((string)$loan->BorrowerAddress);
        $contractNotes = self::normalizeContractNotes($loan->ContractNotes);

        $ctx = array(
            'kind' => $kind,
            'title' => $title,
            'orgName' => $orgName,
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
            $loan->save();
        }

        return true;
    }

    /**
     * Legal clauses as HTML list items (values escaped; emphasis via self::em).
     * @param array $ctx from buildContext
     * @return list<string>
     */
    public static function buildClauses(array $ctx) {
        $kind = isset($ctx['kind']) ? self::normalizeKind($ctx['kind']) : self::KIND_LOAN;
        $org = self::em(isset($ctx['orgName']) ? (string)$ctx['orgName'] : 'der Verein');
        $item = self::em(isset($ctx['itemLabel']) ? (string)$ctx['itemLabel'] : 'das Leihgut');
        $borrower = self::em(isset($ctx['borrowerName']) ? (string)$ctx['borrowerName'] : 'der Entleiher');
        $isMember = !empty($ctx['isMember']);
        $hasKaution = !empty($ctx['hasKaution']);
        $hasLeihgebuehr = !empty($ctx['hasLeihgebuehr']);
        $hasFixedEnd = !empty($ctx['hasFixedEnd']);
        $kautionFmt = self::em(isset($ctx['kautionFormatted']) ? (string)$ctx['kautionFormatted'] : '');
        $leihgebuehrFmt = self::em(isset($ctx['leihgebuehrFormatted']) ? (string)$ctx['leihgebuehrFormatted'] : '');
        $startDe = self::em(isset($ctx['startDateDe']) ? (string)$ctx['startDateDe'] : '');
        $endDe = self::em(isset($ctx['endDateDe']) ? (string)$ctx['endDateDe'] : '');
        $kautionWord = self::em('Kaution');
        $feeWord = self::em('Leihgebühr');

        if($kind === self::KIND_RETURN) {
            $clauses = array();
            $clauses[] = 'Mit Unterzeichnung bestätigen '.$org.' und '.$borrower
                .', dass das nachstehend bezeichnete Leihgut („'.$item.'“) zurückgegeben wurde.';
            $clauses[] = 'Das Leihgut wurde auf Vollständigkeit und äußerlich erkennbare Schäden geprüft.'
                .' Offensichtliche Mängel sind auf diesem Protokoll zu vermerken;'
                .' andernfalls gilt die Rückgabe als äußerlich ordnungsgemäß.';
            if($hasKaution) {
                $clauses[] = 'Die bei Überlassung hinterlegte '.$kautionWord.' in Höhe von '.$kautionFmt
                    .' wird mit der Rückgabe – soweit keine berechtigten Abzüge wegen Beschädigung,'
                    .' Verlust oder fehlender Bestandteile bestehen – an '.$borrower.' zurückgezahlt.';
            }
            $clauses[] = 'Mit der Rückgabe endet das Leihverhältnis über dieses Inventarstück.';
            return $clauses;
        }

        $clauses = array();
        $clauses[] = $org.' überlässt '.$borrower.' das nachstehend bezeichnete Vereinsinventar'
            .' („'.$item.'“, nachfolgend „Leihgut“) zur Nutzung.';
        $clauses[] = 'Das Eigentum am Leihgut verbleibt beim Verein. Der Entleiher erhält kein Eigentum'
            .' und kein Pfandrecht.';

        if($hasFixedEnd) {
            $clauses[] = 'Die Leihe beginnt am '.$startDe.' und ist bis zum '.$endDe.' befristet.';
        }
        else {
            $clauses[] = 'Die Leihe beginnt am '.$startDe.' und ist unbefristet.';
        }

        $clauses[] = 'Der Entleiher verpflichtet sich, das Leihgut sorgfältig zu behandeln, nur bestimmungsgemäß'
            .' zu nutzen und es vor Verlust, Diebstahl und Beschädigung zu schützen.';
        $clauses[] = 'Weitergabe an Dritte, Verpfändung oder Verkauf sind untersagt.';
        $clauses[] = 'Verlust, Diebstahl oder wesentliche Schäden sind dem Verein unverzüglich anzuzeigen.';

        if($hasLeihgebuehr) {
            $clauses[] = 'Für die Überlassung erhebt der Verein eine '.$feeWord.' in Höhe von '.$leihgebuehrFmt
                .'. Die Leihgebühr ist mit Vertragsschluss fällig und wird nicht erstattet.';
        }

        if($hasKaution) {
            $clauses[] = 'Für die Dauer der Leihe hinterlegt der Entleiher eine '.$kautionWord.' in Höhe von '.$kautionFmt
                .'. Die Kaution wird bei ordnungsgemäßer Rückgabe zurückgezahlt;'
                .' berechtigte Abzüge wegen Beschädigung, Verlust oder fehlender Bestandteile sind zulässig.';
        }

        $clauses[] = 'Der Verein behält sich vor, die Leihe aus wichtigem Grund oder nach billigem Ermessen'
            .' vorzeitig zu beenden und das Leihgut zurückzufordern. Der Entleiher hat das Leihgut'
            .' dann unverzüglich herauszugeben.';

        if($isMember) {
            $clauses[] = 'Endet die Mitgliedschaft des Entleihers im Verein, endet auch dieses Leihverhältnis,'
                .' sofern nicht unverzüglich ein neuer Leihvertrag als Nicht-Mitglied geschlossen wird.';
        }
        else {
            $clauses[] = 'Dieser Vertrag gilt als eigenständiger Leihvertrag für Nicht-Mitglieder'
                .' und ist nicht an eine Vereinsmitgliedschaft gebunden.';
        }

        $clauses[] = 'Bei Beendigung der Leihe ist das Leihgut vollständig und in einem dem Alter'
            .' und der üblichen Abnutzung entsprechenden Zustand zurückzugeben.'
            .' Die Rückgabe wird gesondert protokolliert.';

        return $clauses;
    }
}
?>
