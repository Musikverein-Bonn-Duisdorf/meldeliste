<?php
class Inventories
{
    private $_data = array(
        'Index' => null,
        'RegNumber' => null,
        'Inventory' => null,
        'Instrument' => null,
        'Description' => null,
        'Vendor' => null,
        'Model' => null,
        'SerialNr' => null,
        'PurchasePrize' => null,
        'PurchaseDate' => null,
        'Owner' => null,
        'Insurance' => null,
        'Comment' => null
    );

    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'RegNumber':
	    case 'Inventory':
	    case 'Instrument':
	    case 'Description':
	    case 'Vendor':
	    case 'Model':
	    case 'SerialNr':
	    case 'PurchasePrize':
	    case 'PurchaseDate':
	    case 'Owner':
	    case 'Insurance':
	    case 'Comment':
            return $this->_data[$key];
            break;
        default:
            break;
        }
    }

    public function __set($key, $val) {
        switch($key) {
	    case 'PurchasePrize':
	    case 'PurchaseDate':
            $this->_data[$key] = $val;
            break;
	    case 'Index':
	    case 'Inventory':
	    case 'Instrument':
	    case 'Owner':
	    case 'Insurance':
        case 'RegNumber':
            $this->_data[$key] = (int)$val;
            break;
	    case 'Description':
	    case 'Vendor':
	    case 'Model':
	    case 'SerialNr':
	    case 'Comment':
            $this->_data[$key] = trim((string)$val);
            break;
        default:
            break;
        }	
    }

    public function isInstrType() {
        $t = RegNumber::loadType($this->Inventory);
        if(!$t) return false;
        return RegNumber::normalizePrefix($t->Prefix) === RegNumber::DEFAULT_INSTR_PREFIX;
    }

    public function is_valid() {
        if(!$this->Inventory) return false;
        if($this->isInstrType() && (int)$this->Instrument < 1) return false;
        return true;
    }

    public function getInstrumentName() {
        if((int)$this->Instrument < 1) return '';
        $sql = sprintf('SELECT `Name` FROM `%sInstrument` WHERE `Index` = %d;',
        $GLOBALS['dbprefix'],
        (int)$this->Instrument
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = $dbr ? mysqli_fetch_array($dbr) : null;
        return ($row && isset($row['Name'])) ? $row['Name'] : '';
    }

    public function getChanges() {
        $old = new Inventories;
        $old->load_by_id($this->Index);

        $sql = sprintf('SELECT `Typ` FROM `%sInventory` WHERE `Index` = %d;',
        $GLOBALS['dbprefix'],
        (int)$this->Inventory
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = $dbr ? mysqli_fetch_array($dbr) : null;
        $typeName = ($row && isset($row['Typ'])) ? $row['Typ'] : '?';
        $family = $this->getInstrumentName();
        if($family !== '') $typeName = $family;

        $str = sprintf('Inventory-ID: %d, <b>%s</b> %s',
            (int)$this->Index,
            $typeName,
            RegNumber::displayInventory($this->Inventory, $this->RegNumber)
        );
        if($this->RegNumber != $old->RegNumber || $this->Inventory != $old->Inventory) {
            $str .= ', Inventarnummer: '.RegNumber::displayInventory($old->Inventory, $old->RegNumber)
                .' &rArr; <b>'.RegNumber::displayInventory($this->Inventory, $this->RegNumber).'</b>';
        }
        if($this->Instrument != $old->Instrument) {
            $str .= ', Instrument: '.$old->getInstrumentName().' &rArr; <b>'.$this->getInstrumentName().'</b>';
        }
        if($this->Description != $old->Description) $str .= ', Beschreibung: '.$old->Description.' &rArr; <b>'.$this->Description.'</b>';
        if($this->Vendor != $old->Vendor) $str .= ', Hersteller: '.$old->Vendor.' &rArr; <b>'.$this->Vendor.'</b>';
        if($this->Model != $old->Model) $str .= ', Modell: '.$old->Model.' &rArr; <b>'.$this->Model.'</b>';
        if($this->SerialNr != $old->SerialNr) $str .= ', Seriennummer: '.$old->SerialNr.' &rArr; <b>'.$this->SerialNr.'</b>';
        if($this->PurchaseDate != $old->PurchaseDate) $str .= ', Kaufdatum: '.germanDate($old->PurchaseDate,0).' &rArr; <b>'.germanDate($this->PurchaseDate,0).'</b>';
        if($this->PurchasePrize != $old->PurchasePrize) $str .= ', Kaufpreis: '.mkPrize($old->PurchasePrize).' &rArr; <b>'.mkPrize($this->PurchasePrize).'</b>';
        if($this->Owner != $old->Owner) $str .= ', Eigentümer: '.getOwner((int)$old->Owner).' &rArr; <b>'.getOwner((int)$this->Owner).'</b>';
        if(boolsDiffer($this->Insurance, $old->Insurance)) $str .= ', Versichert: '.bool2string($old->Insurance).' &rArr; <b>'.bool2string($this->Insurance).'</b>';
        if($this->Comment != $old->Comment) $str .= ', Kommentar: '.$old->Comment.' &rArr; <b>'.$this->Comment.'</b>';
        return $str;
    }

    public function getVars() {
        $sql = sprintf('SELECT `Typ` FROM `%sInventory` WHERE `Index` = %d;',
        $GLOBALS['dbprefix'],
        (int)$this->Inventory
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = $dbr ? mysqli_fetch_array($dbr) : null;
        $typeName = ($row && isset($row['Typ'])) ? $row['Typ'] : '?';
        $family = $this->getInstrumentName();
        if($family !== '') $typeName = $family;

        $parts = array();
        $parts[] = sprintf('Inventory-ID: %d', (int)$this->Index);
        $parts[] = logPart('Inventory', $typeName);
        $parts[] = logPart('Inventarnummer', RegNumber::displayInventory($this->Inventory, $this->RegNumber));
        logAppendFilled($parts, 'Beschreibung', $this->Description, (string)$this->Description);
        logAppendFilled($parts, 'Hersteller', $this->Vendor, (string)$this->Vendor);
        logAppendFilled($parts, 'Modell', $this->Model, (string)$this->Model);
        logAppendFilled($parts, 'Seriennummer', $this->SerialNr, (string)$this->SerialNr);
        $pdate = germanDate($this->PurchaseDate, 0);
        logAppendFilled($parts, 'Kaufdatum', $pdate, (string)$pdate);
        $prize = mkPrize($this->PurchasePrize);
        logAppendFilled($parts, 'Kaufpreis', $prize, (string)$prize);
        if((int)$this->Owner > 0) {
            $parts[] = logPart('Eigentümer', getOwner((int)$this->Owner));
        }
        logAppendTrue($parts, 'Versichert', $this->Insurance);
        logAppendFilled($parts, 'Kommentar', $this->Comment, (string)$this->Comment);
        return implode(', ', $parts);
    }

    public function save() {
        if(!$this->is_valid()) return false;
        if($this->Index > 0) {
            $logentry = new Log;
            $logentry->DBupdate($this->getChanges());
            if(!$this->update()) return false;
            return true;
        }
        if(!$this->insert()) return false;
        $logentry = new Log;
        $logentry->DBinsert($this->getVars());
        return true;
    }

    protected function insert() {
      $sql = sprintf('INSERT INTO `%sInventories` (`RegNumber`, `Inventory`, `Instrument`, `Description`, `Vendor`, `Model`, `SerialNr`, `PurchaseDate`, `PurchasePrize`, `Owner`, `Insurance`, `Comment`) VALUES ("%d", "%d", "%d", "%s", "%s", "%s", "%s", %s, "%s", "%d", "%d", "%s");',
        $GLOBALS['dbprefix'],
        (int)$this->RegNumber,
        (int)$this->Inventory,
        (int)$this->Instrument,
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Description),
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Vendor),
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Model),
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->SerialNr),
        mkNULLstr($this->PurchaseDate),
        mysqli_real_escape_string($GLOBALS['conn'], (string)mkEmpty($this->PurchasePrize)),
        (int)$this->Owner,
        (int)$this->Insurance,
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Comment)
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }
    
    protected function update() {
        $sql = sprintf('UPDATE `%sInventories` SET `RegNumber` = "%d", `Inventory` = "%d", `Instrument` = "%d", `Description` = "%s", `Vendor` = "%s", `Model` = "%s", `SerialNr` = "%s", `PurchaseDate` = %s, `PurchasePrize` = "%s", `Owner` = "%d", `Insurance` = "%d", `Comment` = "%s" WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        (int)$this->RegNumber,
        (int)$this->Inventory,
        (int)$this->Instrument,
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Description),
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Vendor),
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Model),
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->SerialNr),
        mkNULLstr($this->PurchaseDate),
        mysqli_real_escape_string($GLOBALS['conn'], (string)mkEmpty($this->PurchasePrize)),
        (int)$this->Owner,
        (int)$this->Insurance,
        mysqli_real_escape_string($GLOBALS['conn'], (string)$this->Comment),
        (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        return true;
    }

    public function delete() {
        if(!$this->Index) return false;

        // Log each loan before cascade-remove (MELD-129)
        $sql = sprintf('SELECT `Index` FROM `%sInventoriesLoans` WHERE `Inventory` = "%d";',
        $GLOBALS['dbprefix'],
        (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        while($dbr && ($row = mysqli_fetch_array($dbr))) {
            $loan = new InventoriesLoan;
            $loan->load_by_id($row['Index']);
            if($loan->Index) $loan->delete();
        }

        $logentry = new Log;
        $logentry->DBdelete($this->getVars());

        $sql = sprintf('DELETE FROM `%sInventories` WHERE `Index` = "%d" LIMIT 1;',
        $GLOBALS['dbprefix'],
        (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        
        $this->_data['Index'] = null;
        return true;
    }
    
    public function fill_from_array($row) {
        $allowed = array('Index', 'RegNumber', 'Inventory', 'Instrument', 'Description', 'Vendor', 'Model', 'SerialNr', 'PurchasePrize', 'PurchaseDate', 'Owner', 'Insurance', 'Comment');
        foreach($row as $key => $val) {
            if(!is_string($key) || !in_array($key, $allowed, true)) continue;
            $this->$key = $val;
        }
    }

    public function load_by_id($Index) {
        $Index = (int) $Index;
        if($Index < 1) return;
        $sql = sprintf('SELECT * FROM `%sInventories` WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        $Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = $dbr ? mysqli_fetch_array($dbr, MYSQLI_ASSOC) : null;
        if(is_array($row)) {
            $this->fill_from_array($row);
        }
    }

    public function getInventoryType() {
        $sql = sprintf('SELECT `Typ` FROM `%sInventory` WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        $this->Inventory
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = mysqli_fetch_array($dbr);
        return $row['Typ'];
    }


    public function printTableLine($editable = true) {
        $sql = sprintf(
            'SELECT `%sInventories`.*, `iTyp`, `iSort`, `iPrefix`, `instName` FROM `%sInventories` INNER JOIN (SELECT `Index` AS `iIndex`, `Typ` AS `iTyp`, `Sortierung` AS `iSort`, `Prefix` AS `iPrefix` FROM `%sInventory`) `%sInventory` ON `Inventory` = `iIndex` LEFT JOIN (SELECT `Index` AS `instIndex`, `Name` AS `instName` FROM `%sInstrument`) `%sInstrument` ON `Instrument` = `instIndex` WHERE `%sInventories`.`Index` = "%d";',
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = mysqli_fetch_array($dbr);

        $str="";

        $indent=0;
        $line = new div;
        $line->indent=$indent;
        $line->class="w3-row list-row w3-padding";
        $line->onclick="openModal('inventar', ".$this->Index.")";
        if(!empty($this->Insurance)) {
            $line->class=$GLOBALS['optionsDB']['colorUserMember'];
        }
        $line->class=$GLOBALS['optionsDB']['HoverEffect'];
        $line->class="w3-mobile w3-border-bottom w3-border-black";
        $typLabel = !empty($row['instName']) ? $row['instName'] : $row['iTyp'];
        $loanShort = (string)($this->getActiveLoanNameShort() ?? '');
        $loanFull = (string)($this->getActiveLoanName() ?? '');
        $ownerName = getOwner((int)$row['Owner']);
        $regDisplay = RegNumber::displayInventory($row['Inventory'], $row['RegNumber']);
        $searchParts = array(
            $regDisplay,
            (string)(int)$row['RegNumber'],
            $typLabel,
            (string)$row['Description'],
            (string)$row['Comment'],
            (string)$row['Vendor'],
            (string)$row['Model'],
            (string)$row['SerialNr'],
            (string)$row['PurchaseDate'],
            (string)$row['PurchasePrize'],
            $ownerName,
            $loanFull,
            $loanShort,
        );
        $line->extraAttrs = 'data-sort-regnumber="'.htmlspecialchars((string)(int)$row['RegNumber'], ENT_QUOTES, 'UTF-8').'"'
            .' data-sort-typ="'.htmlspecialchars((string)$typLabel, ENT_QUOTES, 'UTF-8').'"'
            .' data-sort-description="'.htmlspecialchars((string)$row['Description'], ENT_QUOTES, 'UTF-8').'"'
            .' data-sort-comment="'.htmlspecialchars((string)$row['Comment'], ENT_QUOTES, 'UTF-8').'"'
            .' data-sort-vendor="'.htmlspecialchars((string)$row['Vendor'], ENT_QUOTES, 'UTF-8').'"'
            .' data-sort-model="'.htmlspecialchars((string)$row['Model'], ENT_QUOTES, 'UTF-8').'"'
            .' data-sort-serial="'.htmlspecialchars((string)$row['SerialNr'], ENT_QUOTES, 'UTF-8').'"'
            .' data-sort-owner="'.htmlspecialchars((string)$ownerName, ENT_QUOTES, 'UTF-8').'"'
            .' data-sort-purchasedate="'.htmlspecialchars((string)$row['PurchaseDate'], ENT_QUOTES, 'UTF-8').'"'
            .' data-sort-purchaseprize="'.htmlspecialchars((string)$row['PurchasePrize'], ENT_QUOTES, 'UTF-8').'"'
            .' data-sort-loan="'.htmlspecialchars($loanFull !== '' ? $loanFull : $loanShort, ENT_QUOTES, 'UTF-8').'"'
            .' data-search="'.htmlspecialchars(trim(implode(' ', $searchParts)), ENT_QUOTES, 'UTF-8').'"';
        $str=$str.$line->open();
        
        $indent++;
        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right list-primary";
        $field->col(1,2,12);
        $field->body='<b>'.RegNumber::displayInventory($row['Inventory'], $row['RegNumber']).'</b>';
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right list-secondary";
        $field->col(2,2,12);
        $field->body=$typLabel;
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right list-secondary";
        if(requirePermission("perm_showInventories")) {
            $field->col(2,4,12);
        }
        else {
            $field->col(4,4,12);
        }
        $field->body=$row['Description'];
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right list-secondary";
        $field->col(2,2,12);
        $field->body=$row['Comment'];
        $str=$str.$field->print();

        if(requirePermission("perm_showInventories")) {
        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right list-meta w3-hide-medium";
        $field->col(1,4,12);
        $field->body=germanDate($row['PurchaseDate'], 0);
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right list-meta w3-hide-medium";
        $field->col(1,4,12);
        $field->body=mkPrize($row['PurchasePrize']);
        $str=$str.$field->print();
        }
        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center list-secondary";
        $field->col(2,2,12);
        $field->body=$loanShort;
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right list-meta w3-hide-medium";
        $field->col(1,2,12);
        $field->body=$this->getActiveLoanDate();
        $str=$str.$field->print();

        $str=$str.$line->close();
        return $str;
    }

    public function getModalHtml($editable = true) {
        $canEdit = $editable && requirePermission("perm_editInventories");
        $sql = sprintf(
            'SELECT `%sInventories`.*, `iTyp`, `iSort`, `iPrefix`, `instName` FROM `%sInventories` INNER JOIN (SELECT `Index` AS `iIndex`, `Typ` AS `iTyp`, `Sortierung` AS `iSort`, `Prefix` AS `iPrefix` FROM `%sInventory`) `%sInventory` ON `Inventory` = `iIndex` LEFT JOIN (SELECT `Index` AS `instIndex`, `Name` AS `instName` FROM `%sInstrument`) `%sInstrument` ON `Instrument` = `instIndex` WHERE `%sInventories`.`Index` = "%d";',
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $GLOBALS['dbprefix'],
            $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = $dbr ? mysqli_fetch_array($dbr) : null;
        if(!is_array($row)) {
            return '<div class="profile-shell modal-shell"><header class="profile-hero"><div class="profile-hero-text"><h2 class="profile-title">Inventar</h2></div><button type="button" class="modal-close w3-button" onclick="closeModal()" aria-label="Schließen">&times;</button></header><p class="profile-value">Datensatz konnte nicht geladen werden.</p></div>';
        }

        $inv = $this;
        $loansHtml = $this->getLoansModalHtml($canEdit);
        ob_start();
        require __DIR__.'/../views/inventar/modal.php';
        return ob_get_clean();
    }

    /**
     * Leihen-Block im Inventar-Modal: neue Leihe + Historie mit Aktionen.
     */
    private function getLoansModalHtml($canEdit) {
        $indent = 1;
        $section = new div;
        $section->indent = $indent;
        $section->class = "inventory-loans profile-col";
        $str = $section->open();

        $title = new div;
        $title->indent = $indent + 1;
        $title->tag = "h3";
        $title->class = "profile-col-title";
        $title->body = "Leihen";
        $str .= $title->print();

        if($canEdit) {
            $str .= $this->getNewLoanFormHtml($indent + 1);
        }

        $loans = $this->getLoans();
        if(!$loans) {
            $empty = new div;
            $empty->indent = $indent + 1;
            $empty->class = "profile-value";
            $empty->body = "Noch keine Leihen.";
            $str .= $empty->print();
        }
        else {
            $listTitle = new div;
            $listTitle->indent = $indent + 1;
            $listTitle->class = "profile-label";
            $listTitle->body = "Historie";
            $str .= $listTitle->print();

            foreach($loans as $loanId) {
                $L = new InventoriesLoan;
                $L->load_by_id($loanId);
                $str .= $this->getLoanRowHtml($indent + 1, $L, $canEdit);
            }
        }

        $str .= $section->close();
        return $str;
    }

    private function getNewLoanFormHtml($indent) {
        $btn = $GLOBALS['optionsDB']['colorBtnSubmit'];
        $inputBg = $GLOBALS['optionsDB']['colorInputBackground'];
        $form = new div;
        $form->indent = $indent;
        $form->tag = "form";
        $form->action = "";
        $form->method = "POST";
        $form->class = "inventar-loan-new";
        $str = $form->open();
        $str .= '<input type="hidden" name="Inventory" value="'.(int)$this->Index.'">';
        $str .= '<div class="profile-field"><label class="profile-label" for="loan-user">Person</label>'
            .'<select id="loan-user" class="w3-select w3-border w3-input profile-control '.$inputBg.'" name="User">'.UserOptionAll(0).'</select></div>';
        $str .= '<div class="profile-field"><label class="profile-label" for="loan-start">Von</label>'
            .'<input id="loan-start" class="w3-input w3-border profile-control '.$inputBg.'" type="date" name="StartDate" required></div>';
        $str .= '<div class="profile-field"><label class="profile-label" for="loan-end">Bis</label>'
            .'<input id="loan-end" class="w3-input w3-border profile-control '.$inputBg.'" type="date" name="EndDate"></div>';
        $str .= '<div class="profile-field"><button type="submit" name="newLoan" value="1" class="w3-btn profile-btn-primary '.$btn.' w3-border w3-mobile">Leihe eintragen</button></div>';
        $str .= $form->close();
        return $str;
    }

    private function getLoanRowHtml($indent, InventoriesLoan $L, $canEdit) {
        $active = ($L->EndDate === null || $L->EndDate === '');
        $btnSubmit = $GLOBALS['optionsDB']['colorBtnSubmit'];
        $btnDelete = $GLOBALS['optionsDB']['colorBtnDelete'];

        $row = new div;
        $row->indent = $indent;
        $row->class = "w3-padding w3-border-top inventory-loan-row";
        if($active) {
            $row->class = "w3-leftbar w3-border-teal";
        }
        $str = $row->open();

        $head = new div;
        $head->indent = $indent + 1;
        $head->class = "w3-row";
        $str .= $head->open();

        $info = new div;
        $info->indent = $indent + 2;
        $info->col(7, 12, 12);
        $name = htmlspecialchars((string)$L->getName(), ENT_QUOTES, 'UTF-8');
        $from = htmlspecialchars((string)germanDate($L->StartDate, 0), ENT_QUOTES, 'UTF-8');
        $until = $active
            ? '<span class="w3-tag w3-teal w3-round">offen</span>'
            : htmlspecialchars((string)germanDate($L->EndDate, 0), ENT_QUOTES, 'UTF-8');
        $info->body = '<div><b>'.$name.'</b></div>'
            .'<div class="w3-small" style="margin-top:4px;">'.$from.' &ndash; '.$until.'</div>';
        $str .= $info->print();

        if($canEdit) {
            $actions = new div;
            $actions->indent = $indent + 2;
            $actions->col(5, 12, 12);
            $actions->class = "w3-right-align";
            $str .= $actions->open();
            $str .= '<form method="POST" action="" style="display:inline;" '
                .'onsubmit="return confirm(\'Diese Leih-Information wirklich löschen?\');">'
                .'<input type="hidden" name="LoanIndex" value="'.(int)$L->Index.'">'
                .'<button type="submit" name="deleteLoan" value="1" class="w3-button w3-small '.$btnDelete.'">Löschen</button>'
                .'</form>';
            $str .= $actions->close();
        }

        $str .= $head->close();

        if($active && $canEdit) {
            $endForm = new div;
            $endForm->indent = $indent + 1;
            $endForm->tag = "form";
            $endForm->method = "POST";
            $endForm->action = "";
            $endForm->class = "w3-row w3-padding-16";
            $str .= $endForm->open();
            $str .= '<input type="hidden" name="LoanIndex" value="'.(int)$L->Index.'">';
            $str .= '<div class="w3-col l4 m12 s12 w3-padding-small"><label class="profile-label" for="loan-end-'.(int)$L->Index.'">Rückgabe</label></div>';
            $str .= '<div class="w3-col l4 m6 s12 w3-padding-small">'
                .'<input id="loan-end-'.(int)$L->Index.'" class="w3-input w3-border profile-control" type="date" name="EndDate" required value="'.htmlspecialchars(date('Y-m-d'), ENT_QUOTES, 'UTF-8').'">'
                .'</div>';
            $str .= '<div class="w3-col l4 m6 s12 w3-padding-small">'
                .'<button type="submit" name="endLoan" value="1" class="w3-button '.$btnSubmit.'">Beenden</button>'
                .'</div>';
            $str .= $endForm->close();
        }

        $str .= $row->close();
        return $str;
    }

    public function getLoans() {
        $sql = sprintf('SELECT `Index` FROM `%sInventoriesLoans` WHERE `Inventory` = "%d" ORDER BY `StartDate` DESC;',
        $GLOBALS['dbprefix'],
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();

        $loans = array();
        if(!$dbr) return $loans;
        while($row = mysqli_fetch_array($dbr)) {
            array_push($loans, (int)$row['Index']);
        }
        return $loans;
    }

    public function getActiveLoan() {
        $loans = $this->getLoans();
        if($loans) {
            $l = new InventoriesLoan;
            $l->load_by_id($loans[0]);
            
            if($l->EndDate) {
                $end = new DateTime($l->EndDate);
                $now = new DateTime(date("Y-m-d"));
                if($end > $now) return $loans[0];
            }
            else {
                return $loans[0];
            }
        }
    }

    /**
     * Whether $userId may view this inventory without perm_showInventories
     * (owner or active loan recipient).
     */
    public function userMayView($userId) {
        $userId = (int)$userId;
        if($userId < 1 || !(int)$this->Index) {
            return false;
        }
        if((int)$this->Owner === $userId) {
            return true;
        }
        $loanId = $this->getActiveLoan();
        if(!$loanId) {
            return false;
        }
        $loan = new InventoriesLoan;
        $loan->load_by_id($loanId);
        return (int)$loan->User === $userId;
    }

    public function getActiveLoanName() {
        $loan = $this->getActiveLoan();
        if($loan) {
            $L = new InventoriesLoan;
            $L->load_by_id($loan);
            $u = new User;
            $u->load_by_id($L->User);
            return $u->getName();
        }
    }

    public function getActiveLoanNameShort() {
        $loan = $this->getActiveLoan();
        if($loan) {
            $L = new InventoriesLoan;
            $L->load_by_id($loan);
            $u = new User;
            $u->load_by_id($L->User);
            return $u->getShort();
        }
    }

    public function getActiveLoanDate() {
        $loan = $this->getActiveLoan();
        if($loan) {
            $L = new InventoriesLoan;
            $L->load_by_id($loan);
            return germanDate($L->StartDate,0);
        }
    }

};

/**
 * Process inventar create/update/delete/loan POSTs.
 * @return bool true if a mutation was handled
 */
function handleInventoriesMutations() {
    $mutating = isset($_POST['newLoan']) || isset($_POST['endLoan']) || isset($_POST['deleteLoan'])
        || isset($_POST['insert']) || isset($_POST['update']) || isset($_POST['delete']);
    if(!$mutating) {
        return false;
    }
    if(!requirePermission('perm_editInventories')) {
        denyAccess('Keine Berechtigung zum Ändern von Inventar.');
    }

    if(isset($_POST['newLoan'])) {
        $n = new InventoriesLoan;
        $n->fill_from_array($_POST);
        $n->save();
    }
    if(isset($_POST['endLoan'])) {
        $n = new InventoriesLoan;
        $loanId = isset($_POST['LoanIndex']) ? $_POST['LoanIndex'] : $_POST['Index'];
        $n->load_by_id($loanId);
        $n->EndDate = $_POST['EndDate'];
        $n->save();
    }
    if(isset($_POST['deleteLoan'])) {
        $n = new InventoriesLoan;
        $loanId = isset($_POST['LoanIndex']) ? (int)$_POST['LoanIndex'] : (int)$_POST['Index'];
        $n->load_by_id($loanId);
        if($n->Index) {
            $n->delete();
        }
    }
    if(isset($_POST['insert'])) {
        $n = new Inventories;
        $n->fill_from_array($_POST);
        if(empty($_POST['Insurance'])) $n->Insurance = 0;
        $type = RegNumber::loadType((int)$n->Inventory);
        if(!$type || RegNumber::normalizePrefix($type->Prefix) !== RegNumber::DEFAULT_INSTR_PREFIX) {
            $n->Instrument = 0;
        }
        $n->save();
    }
    if(isset($_POST['update'])) {
        $id = isset($_POST['InventoriesIndex']) ? (int)$_POST['InventoriesIndex'] : (int)$_POST['Index'];
        $n = new Inventories;
        $n->load_by_id($id);
        if($n->Index) {
            // Keep type FK; do not let loan-form field "Inventory" overwrite it
            $typeId = (int)$n->Inventory;
            $n->RegNumber = isset($_POST['RegNumber']) ? $_POST['RegNumber'] : $n->RegNumber;
            $n->Description = isset($_POST['Description']) ? $_POST['Description'] : $n->Description;
            $n->Vendor = isset($_POST['Vendor']) ? $_POST['Vendor'] : $n->Vendor;
            $n->Model = isset($_POST['Model']) ? $_POST['Model'] : $n->Model;
            $n->SerialNr = isset($_POST['SerialNr']) ? $_POST['SerialNr'] : $n->SerialNr;
            $n->PurchaseDate = isset($_POST['PurchaseDate']) ? $_POST['PurchaseDate'] : $n->PurchaseDate;
            $n->PurchasePrize = isset($_POST['PurchasePrize']) ? $_POST['PurchasePrize'] : $n->PurchasePrize;
            $n->Owner = isset($_POST['Owner']) ? $_POST['Owner'] : $n->Owner;
            $n->Insurance = isset($_POST['Insurance']) ? (int)$_POST['Insurance'] : 0;
            $n->Comment = isset($_POST['Comment']) ? $_POST['Comment'] : $n->Comment;
            $n->Inventory = $typeId;
            $type = RegNumber::loadType($typeId);
            if($type && RegNumber::normalizePrefix($type->Prefix) === RegNumber::DEFAULT_INSTR_PREFIX) {
                $n->Instrument = isset($_POST['Instrument']) ? (int)$_POST['Instrument'] : (int)$n->Instrument;
            }
            else {
                $n->Instrument = 0;
            }
            $n->save();
        }
    }
    if(isset($_POST['delete'])) {
        $id = isset($_POST['InventoriesIndex']) ? (int)$_POST['InventoriesIndex'] : (int)$_POST['Index'];
        $n = new Inventories;
        $n->load_by_id($id);
        $n->delete();
    }
    return true;
}
?>
