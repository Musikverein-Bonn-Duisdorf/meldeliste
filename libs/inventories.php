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

        return sprintf("Inventory-ID: %d, Inventory: <b>%s</b>, Inventarnummer: <b>%s</b>, Beschreibung: <b>%s</b>, Hersteller: <b>%s</b>, Modell: <b>%s</b>, Seriennummer: <b>%s</b>, Kaufdatum: <b>%s</b>, Kaufpreis: <b>%s</b>, Besitzer: <b>%s</b>, Versichert: <b>%s</b>, Kommentar: <b>%s</b>",
        (int)$this->Index,
        $typeName,
        RegNumber::displayInventory($this->Inventory, $this->RegNumber),
        (string)$this->Description,
        (string)$this->Vendor,
        (string)$this->Model,
        (string)$this->SerialNr,
        germanDate($this->PurchaseDate,0),
        mkPrize($this->PurchasePrize),
        getOwner((int)$this->Owner),
        bool2string($this->Insurance),
        (string)$this->Comment
        );
    }

    public function save() {
        if(!$this->is_valid()) return false;
        if($this->Index > 0) {
            if(!$this->update()) return false;
            $logentry = new Log;
            $logentry->DBupdate($this->getVars());
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
        $logentry = new Log;
        $logentry->DBdelete($this->getVars());

        $sql = sprintf('DELETE FROM `%sInventoriesLoans` WHERE `Inventory` = "%d";',
        $GLOBALS['dbprefix'],
        (int)$this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();

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

    /** Label + value row for detail modal. */
    private function modalDetailRow($indent, $label, $valueHtml) {
        $row = new div;
        $row->indent = $indent;
        $row->class = "w3-row w3-padding";
        $str = $row->open();

        $lab = new div;
        $lab->indent = $indent + 1;
        $lab->col(4, 12, 12);
        $lab->body = "<b>".htmlspecialchars($label, ENT_QUOTES, 'UTF-8').":</b>";
        $str .= $lab->print();

        $val = new div;
        $val->indent = $indent + 1;
        $val->col(8, 12, 12);
        $str .= $val->open();
        $str .= $valueHtml;
        $str .= $val->close();

        $str .= $row->close();
        return $str;
    }

    private function modalDisplayText($text) {
        $text = trim(html_entity_decode(strip_tags((string)$text)));
        if($text === '') return '<span class="w3-text-gray">—</span>';
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
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
        $line->class="w3-row w3-padding";
        $line->onclick="openModal('inventory', ".$this->Index.")";
        if(!empty($this->Insurance)) {
            $line->class=$GLOBALS['optionsDB']['colorUserMember'];
        }
        $line->class=$GLOBALS['optionsDB']['HoverEffect'];
        $line->class="w3-mobile w3-border-bottom w3-border-black";
        $str=$str.$line->open();
        
        $indent++;
        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right w3-hide-medium w3-hide-small";
        $field->col(1,2,2);
        $field->body=RegNumber::displayInventory($row['Inventory'], $row['RegNumber']);
        $str=$str.$field->print();

        $typLabel = !empty($row['instName']) ? $row['instName'] : $row['iTyp'];
        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right";
        $field->col(2,2,2);
        $field->body=$typLabel;
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right";
        if(requirePermission("perm_showInventories")) {
            $field->col(2,4,4);
        }
        else {
            $field->col(4,4,4);
        }
        $field->body=$row['Description'];
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right";
        $field->col(2,2,2);
        $field->body=$row['Comment'];
        $str=$str.$field->print();

        if(requirePermission("perm_showInventories")) {
        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right w3-hide-medium w3-hide-small";
        $field->col(1,4,4);
        $field->body=germanDate($row['PurchaseDate'], 0);
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right w3-hide-medium w3-hide-small";
        $field->col(1,4,4);
        $field->body=mkPrize($row['PurchasePrize']);
        $str=$str.$field->print();
        }
        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center";
        $field->col(2,2,2);
        $field->body=$this->getActiveLoanNameShort();
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right w3-hide-medium w3-hide-small";
        $field->col(1,2,2);
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
        $row = mysqli_fetch_array($dbr);

        $str = "";

        $indent = 0;
        $header = new div;
        $header->indent=$indent;
        $header->class="w3-container";
        $header->class=$GLOBALS['optionsDB']['colorTitleBar'];
        $header->tag="header";
        $str=$str.$header->open();
        $indent++;
        $span = new div;
        $span->indent=$indent;
        $span->tag="span";
        $span->onclick="closeModal()";
        $span->class="w3-button w3-display-topright";
        $span->body="&times;";
        $str=$str.$span->print();
        
        $content = new div;
        $content->indent=$indent;
        $headerTitle = !empty($row['instName']) ? $row['instName'] : $row['iTyp'];
        $content->body="<h2>".htmlspecialchars($headerTitle, ENT_QUOTES, 'UTF-8')."</h2>";
        $str=$str.$content->print();
        $str=$str.$header->close();

        $indent--;
        $detailform = new div;
        $detailform->indent = $indent;
        if($canEdit) {
            $detailform->tag="form";
            $detailform->action="";
            $detailform->method="POST";
        }
        $str=$str.$detailform->open();
        $indent++;

        $str .= $this->modalDetailRow($indent, 'Inventarnummer', $canEdit
            ? '<input class="w3-input w3-border" type="number" name="RegNumber" value="'.htmlspecialchars((string)$this->RegNumber, ENT_QUOTES, 'UTF-8').'">'
              .'<div class="w3-small w3-text-gray" style="margin-top:4px;">'.htmlspecialchars(RegNumber::displayInventory($this->Inventory, $this->RegNumber), ENT_QUOTES, 'UTF-8').'</div>'
            : htmlspecialchars(RegNumber::displayInventory($this->Inventory, $this->RegNumber), ENT_QUOTES, 'UTF-8')
        );
        if($canEdit) {
            $str .= '<input type="hidden" name="InventoriesIndex" value="'.(int)$this->Index.'">';
        }

        $isInstr = $this->isInstrType();
        if($isInstr || (int)$this->Instrument > 0) {
            $str .= $this->modalDetailRow($indent, 'Instrument', $canEdit
                ? '<select class="w3-select w3-border w3-input" name="Instrument">'.instrumentOptionAll((int)$this->Instrument).'</select>'
                : $this->modalDisplayText($this->getInstrumentName())
            );
        }

        $str .= $this->modalDetailRow($indent, 'Beschreibung', $canEdit
            ? '<input class="w3-input w3-border" type="text" name="Description" value="'.htmlspecialchars((string)$this->Description, ENT_QUOTES, 'UTF-8').'">'
            : $this->modalDisplayText($this->Description)
        );

        $str .= $this->modalDetailRow($indent, 'Hersteller', $canEdit
            ? '<input class="w3-input w3-border" type="text" name="Vendor" value="'.htmlspecialchars((string)$this->Vendor, ENT_QUOTES, 'UTF-8').'">'
            : $this->modalDisplayText($this->Vendor)
        );

        $str .= $this->modalDetailRow($indent, 'Modell', $canEdit
            ? '<input class="w3-input w3-border" type="text" name="Model" value="'.htmlspecialchars((string)$this->Model, ENT_QUOTES, 'UTF-8').'">'
            : $this->modalDisplayText($this->Model)
        );

        $str .= $this->modalDetailRow($indent, 'Seriennummer', $canEdit
            ? '<input class="w3-input w3-border" type="text" name="SerialNr" value="'.htmlspecialchars((string)$this->SerialNr, ENT_QUOTES, 'UTF-8').'">'
            : $this->modalDisplayText($this->SerialNr)
        );

        $str .= $this->modalDetailRow($indent, 'Kaufdatum', $canEdit
            ? '<input class="w3-input w3-border" type="date" name="PurchaseDate" value="'.htmlspecialchars((string)$this->PurchaseDate, ENT_QUOTES, 'UTF-8').'">'
            : $this->modalDisplayText(germanDate($this->PurchaseDate, 0))
        );

        $str .= $this->modalDetailRow($indent, 'Kaufpreis', $canEdit
            ? '<input class="w3-input w3-border" type="number" step="0.01" name="PurchasePrize" value="'.htmlspecialchars((string)$this->PurchasePrize, ENT_QUOTES, 'UTF-8').'">'
            : $this->modalDisplayText(mkPrize($this->PurchasePrize))
        );

        $str .= $this->modalDetailRow($indent, 'Besitzer', $canEdit
            ? '<select class="w3-select w3-border w3-input" name="Owner">'.UserOptionAll((int)$this->Owner).'</select>'
            : $this->modalDisplayText(getOwner((int)$this->Owner))
        );

        if($canEdit) {
            $insHtml = '<input type="hidden" name="Insurance" value="0">'
                .'<label><input class="w3-check" type="checkbox" name="Insurance" value="1"'.(!empty($this->Insurance) ? ' checked' : '').'> ja</label>';
        }
        else {
            $insHtml = $this->modalDisplayText(bool2string(!empty($this->Insurance)));
        }
        $str .= $this->modalDetailRow($indent, 'Versichert', $insHtml);

        $str .= $this->modalDetailRow($indent, 'Kommentar', $canEdit
            ? '<input class="w3-input w3-border" type="text" name="Comment" value="'.htmlspecialchars((string)$this->Comment, ENT_QUOTES, 'UTF-8').'">'
            : $this->modalDisplayText($this->Comment)
        );

        if($canEdit) {
            $indent--;
            $modalrow = new div;
            $modalrow->indent=$indent;
            $modalrow->class="w3-row w3-padding";
            $str=$str.$modalrow->open();
            $indent++;
            $content = new div;
            $content->indent=$indent;
            $content->tag="button";
            $content->type="submit";
            $content->name="update";
            $content->value="update";
            $content->class="w3-button";
            $content->class=$GLOBALS['optionsDB']['colorBtnSubmit'];
            $content->col(2,6,6);
            $content->body="speichern";
            $str=$str.$content->print();        
            $str=$str.$modalrow->close();
        }

        $str=$str.$detailform->close();
        $indent--;

        if($canEdit) {
            $indent--;
            $modalrow = new div;
            $modalrow->indent=$indent;
            $modalrow->class="w3-row w3-padding";
            $str=$str.$modalrow->open();
            $indent++;
            $content = new div;
            $content->indent=$indent;
            $content->tag="button";
            $content->class="w3-button";
            $content->class=$GLOBALS['optionsDB']['colorBtnSubmit'];
            $content->onclick="document.getElementById('del".$this->Index."').style.display='block'";
            $content->col(2,6,6);
            $content->body="l&ouml;schen";
            $str=$str.$content->print();        
            $str=$str.$modalrow->close();

            $indent--;
            $modalrow = new div;
            $modalrow->indent=$indent;
            $modalrow->class="w3-row w3-padding";
            $modalrow->class=$GLOBALS['optionsDB']['colorWarning'];
            $modalrow->style="display: none;";
            $modalrow->id="del".$this->Index;
            $modalrow->tag="form";
            $modalrow->action="";
            $modalrow->method="POST";
            $str=$str.$modalrow->open();
            $indent++;
            $content = new div;
            $content->indent=$indent;
            $content->class="w3-padding";
            $content->col(4,6,6);
            $content->body="Diesen Eintrag wirklich l&ouml;schen?";
            $str=$str.$content->print();        
            $content = new div;
            $content->indent=$indent;
            $content->tag="button";
            $content->class="w3-button";
            $content->class=$GLOBALS['optionsDB']['colorBtnSubmit'];
            $content->type="submit";
            $content->name="delete";
            $content->value="delete";
            $content->col(2,6,6);
            $content->body="Ja";
            $str=$str.$content->print();        
            $hidden = new div;
            $hidden->indent=$indent;
            $hidden->tag="input";
            $hidden->type="hidden";
            $hidden->name="InventoriesIndex";
            $hidden->value=$this->Index;
            $str=$str.$hidden->print();
            $str=$str.$modalrow->close();

        }
        
        $indent--;
        $modalrow = new div;
        $modalrow->indent=$indent;
        $modalrow->class="w3-padding w3-margin-bottom";
        $modalrow->class=$GLOBALS['optionsDB']['colorInputBackground'];
        $modalrow->body="<b>Leihhistorie:</b>";
        $str=$str.$modalrow->open();

        $indent++;
        $modalrow2 = new div;
        $modalrow2->indent=$indent;
        $modalrow2->class="w3-row w3-center w3-padding w3-border-bottom";
        $str=$str.$modalrow2->open();
        $indent++;
        $content = new div;
        $content->indent=$indent;
        $content->col(2,4,4);
        $content->class="w3-border-right";
        $content->body="<b>an</b>";
        $str=$str.$content->print();
        $content = new div;
        $content->indent=$indent;
        $content->col(2,4,4);
        $content->class="w3-border-right";
        $content->body="<b>von</b>";
        $str=$str.$content->print();
        $content = new div;
        $content->indent=$indent;
        $content->col(2,4,4);
        $content->class="w3-border-right";
        $content->body="<b>bis</b>";
        $str=$str.$content->print();
        $str=$str.$modalrow2->close();

        // --> new Loan
        if($canEdit) {
            $indent--;
            $modalrow2 = new div;
            $modalrow2->indent=$indent;
            $modalrow2->class="w3-row w3-center w3-padding";
            $modalrow2->tag="form";
            $modalrow2->action="";
            $modalrow2->method="POST";
            $str=$str.$modalrow2->open();
            $indent++;
            $content = new div;
            $content->indent=$indent;
            $content->tag="input";
            $content->name = "Inventory";
            $content->type = "hidden";
            $content->value = $this->Index;
            $str=$str.$content->print();
            $content = new div;
            $content->indent=$indent;
            $content->col(2,4,4);
            $content->class="w3-border-right w3-input";
            $content->tag="select";
            $content->name = "User";
            $content->body=UserOptionAll(0);
            $str=$str.$content->print();
            $content = new div;
            $content->indent=$indent;
            $content->col(2,4,4);
            $content->class="w3-border-right w3-input";
            $content->tag = "input";
            $content->type = "date";
            $content->name = "StartDate";
            $str=$str.$content->print();
            $content = new div;
            $content->indent=$indent;
            $content->col(2,4,4);
            $content->class="w3-border-right w3-input";
            $content->tag = "input";
            $content->type = "date";
            $content->name = "EndDate";
            $str=$str.$content->print();
            $content = new div;
            $content->indent=$indent;
            $content->col(2,4,4);
            $content->class="w3-border-right w3-input";
            $content->class=$GLOBALS['optionsDB']['colorBtnSubmit'];
            $content->tag = "input";
            $content->type = "submit";
            $content->name = "newLoan";
            $content->value = "eintragen";
            $str=$str.$content->print();
            $str=$str.$modalrow2->close();
        }
        // <-- new Loan
            
        $loans = $this->getLoans();
        for($i=0; $i<count($loans); $i++) {           
                $L = new InventoriesLoan;
                $L->load_by_id($loans[$i]);
                $indent--;
                $modalrow2 = new div;
                $modalrow2->indent=$indent;
                $modalrow2->class="w3-row w3-center w3-padding";
                if($L->EndDate == null) {
                    $modalrow2->class="w3-teal";
                }
                $str=$str.$modalrow2->open();
                $indent++;
                $content = new div;
                $content->indent=$indent;
                $content->col(2,4,4);
                $content->class="w3-border-right";
                $content->body=$L->getName();
                $str=$str.$content->print();
                $content = new div;
                $content->indent=$indent;
                $content->col(2,4,4);
                $content->class="w3-border-right";
                $content->body=germanDate($L->StartDate,0);
                $str=$str.$content->print();
                
                if($L->EndDate == null && $canEdit) {
                    $form = new div;
                    $form->indent=$indent;
                    $form->tag="form";
                    $form->action="";
                    $form->method="POST";
                    $str=$str.$form->open();
                    
                    $content = new div;
                    $content->indent=$indent;
                    $content->tag = "input";
                    $content->type = "hidden";
                    $content->name = "LoanIndex";
                    $content->value = $L->Index;
                    $str=$str.$content->print();
                    $content = new div;
                    $content->indent=$indent;
                    $content->col(2,4,4);
                    $content->class="w3-border-right w3-input";
                    $content->tag = "input";
                    $content->type = "date";
                    $content->name = "EndDate";
                    $str=$str.$content->print();
                    $content = new div;
                    $content->indent=$indent;
                    $content->col(2,4,4);
                    $content->class="w3-border-right w3-input";
                    $content->class=$GLOBALS['optionsDB']['colorBtnSubmit'];
                    $content->tag = "input";
                    $content->type = "submit";
                    $content->name = "endLoan";
                    $content->value = "eintragen";
                    $str=$str.$content->print();

                    $str=$str.$form->close();
                }
                else {
                    $content = new div;
                    $content->indent=$indent;
                    $content->col(2,4,4);
                    $content->class="w3-border-right";
                    $content->body=germanDate($L->EndDate,0);
                    $str=$str.$content->print();
                }
                $str=$str.$modalrow2->close();
            }
        $str=$str.$modalrow->close();

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
?>
