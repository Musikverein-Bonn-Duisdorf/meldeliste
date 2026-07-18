<?php
class Instruments
{
    private $_data = array('Index' => null, 'RegNumber' => null, 'Instrument' => null, 'Vendor' => null, 'Model' => null, 'SerialNr' => null, 'PurchasePrize' => null, 'PurchaseDate' => null, 'Owner' => null, 'Insurance' => null, 'Comment' => null);
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'RegNumber':
	    case 'Instrument':
	    case 'Vendor':
	    case 'SerialNr':
	    case 'Model':
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
	    case 'Instrument':
	    case 'Owner':
	    case 'Insurance':
        case 'RegNumber':
            $this->_data[$key] = (int)$val;
            break;
	    case 'Vendor':
	    case 'SerialNr':
	    case 'Model':
	    case 'Comment':
            $this->_data[$key] = trim((string)$val);
            break;
        default:
            break;
        }	
    }

    public function is_valid() {
        if(!$this->Instrument) return false;
        return true;
    }

    public function getChanges() {
        $old = new Instruments;
        $old->load_by_id($this->Index);

        $sql = sprintf('SELECT `Name` FROM `%sInstrument` WHERE `Index` = %d;',
        $GLOBALS['dbprefix'],
        (int)$this->Instrument
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = $dbr ? mysqli_fetch_array($dbr) : null;
        $Instrument = ($row && isset($row['Name'])) ? $row['Name'] : '?';

        $str = sprintf('Instrument-ID: %d, <b>%s</b> %s',
            (int)$this->Index,
            $Instrument,
            RegNumber::displayInstrument($this->RegNumber)
        );
        if($this->Instrument != $old->Instrument) {
            $oldName = '?';
            $sqlOld = sprintf('SELECT `Name` FROM `%sInstrument` WHERE `Index` = %d;',
                $GLOBALS['dbprefix'],
                (int)$old->Instrument
            );
            $dbrOld = mysqli_query($GLOBALS['conn'], $sqlOld);
            sqlerror();
            $rowOld = $dbrOld ? mysqli_fetch_array($dbrOld) : null;
            if($rowOld && isset($rowOld['Name'])) $oldName = $rowOld['Name'];
            $str .= ', Instrument: '.$oldName.' &rArr; <b>'.$Instrument.'</b>';
        }
        if($this->RegNumber != $old->RegNumber) {
            $str .= ', Inventarnummer: '.RegNumber::displayInstrument($old->RegNumber)
                .' &rArr; <b>'.RegNumber::displayInstrument($this->RegNumber).'</b>';
        }
        if($this->Vendor != $old->Vendor) $str .= ', Hersteller: '.$old->Vendor.' &rArr; <b>'.$this->Vendor.'</b>';
        if($this->Model != $old->Model) $str .= ', Model: '.$old->Model.' &rArr; <b>'.$this->Model.'</b>';
        if($this->SerialNr != $old->SerialNr) $str .= ', Seriennummer: '.$old->SerialNr.' &rArr; <b>'.$this->SerialNr.'</b>';
        if($this->PurchaseDate != $old->PurchaseDate) $str .= ', Kaufdatum: '.germanDate($old->PurchaseDate,0).' &rArr; <b>'.germanDate($this->PurchaseDate,0).'</b>';
        if($this->PurchasePrize != $old->PurchasePrize) $str .= ', Kaufpreis: '.mkPrize($old->PurchasePrize).' &rArr; <b>'.mkPrize($this->PurchasePrize).'</b>';
        if($this->Owner != $old->Owner) $str .= ', Besitzer: '.getOwner((int)$old->Owner).' &rArr; <b>'.getOwner((int)$this->Owner).'</b>';
        if($this->Insurance != $old->Insurance) $str .= ', Versichert: '.bool2string($old->Insurance).' &rArr; <b>'.bool2string($this->Insurance).'</b>';
        if($this->Comment != $old->Comment) $str .= ', Kommentar: '.$old->Comment.' &rArr; <b>'.$this->Comment.'</b>';
        return $str;
    }

    public function getVars() {
        $sql = sprintf('SELECT `Name` FROM `%sInstrument` WHERE `Index` = %d;',
        $GLOBALS['dbprefix'],
        (int)$this->Instrument
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = $dbr ? mysqli_fetch_array($dbr) : null;
        $Instrument = ($row && isset($row['Name'])) ? $row['Name'] : '?';

        $parts = array();
        $parts[] = sprintf('Instrument-ID: %d', (int)$this->Index);
        $parts[] = logPart('Instrument', $Instrument);
        $parts[] = logPart('Inventarnummer', RegNumber::displayInstrument($this->RegNumber));
        logAppendFilled($parts, 'Hersteller', $this->Vendor, (string)$this->Vendor);
        logAppendFilled($parts, 'Model', $this->Model, (string)$this->Model);
        logAppendFilled($parts, 'Seriennummer', $this->SerialNr, (string)$this->SerialNr);
        $pdate = germanDate($this->PurchaseDate, 0);
        logAppendFilled($parts, 'Kaufdatum', $pdate, (string)$pdate);
        $prize = mkPrize($this->PurchasePrize);
        logAppendFilled($parts, 'Kaufpreis', $prize, (string)$prize);
        if((int)$this->Owner > 0) {
            $parts[] = logPart('Besitzer', getOwner((int)$this->Owner));
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

    protected function instrInventoryTypeId() {
        RegNumber::ensureInstrType();
        $instrType = RegNumber::loadInstrType();
        return $instrType ? (int)$instrType->Index : 0;
    }

    protected function insert() {
        $inventoryTypeId = $this->instrInventoryTypeId();
        if($inventoryTypeId < 1) {
            sqlerror();
            return false;
        }
        $description = trim((string)$this->Vendor.' '.(string)$this->Model);
        $sql = sprintf(
            'INSERT INTO `%sInventories` (`RegNumber`, `Inventory`, `Instrument`, `Description`, `Vendor`, `Model`, `SerialNr`, `PurchaseDate`, `PurchasePrize`, `Owner`, `Insurance`, `Comment`) VALUES ("%d", "%d", "%d", "%s", "%s", "%s", "%s", %s, "%s", "%d", "%d", "%s");',
            $GLOBALS['dbprefix'],
            (int)$this->RegNumber,
            $inventoryTypeId,
            (int)$this->Instrument,
            mysqli_real_escape_string($GLOBALS['conn'], $description),
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
        $inventoryTypeId = $this->instrInventoryTypeId();
        if($inventoryTypeId < 1) return false;
        $description = trim((string)$this->Vendor.' '.(string)$this->Model);
        $sql = sprintf(
            'UPDATE `%sInventories` SET `RegNumber` = "%d", `Inventory` = "%d", `Instrument` = "%d", `Description` = "%s", `Vendor` = "%s", `Model` = "%s", `SerialNr` = "%s", `PurchaseDate` = %s, `PurchasePrize` = "%s", `Owner` = "%d", `Insurance` = "%d", `Comment` = "%s" WHERE `Index` = "%d";',
            $GLOBALS['dbprefix'],
            (int)$this->RegNumber,
            $inventoryTypeId,
            (int)$this->Instrument,
            mysqli_real_escape_string($GLOBALS['conn'], $description),
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
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();

        $sql = sprintf('DELETE FROM `%sInventories` WHERE `Index` = "%d" LIMIT 1;',
        $GLOBALS['dbprefix'],
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        
        $this->_data['Index'] = null;
        return true;
    }
    
    public function fill_from_array($row) {
        $allowed = array('Index', 'RegNumber', 'Instrument', 'Vendor', 'Model', 'SerialNr', 'PurchasePrize', 'PurchaseDate', 'Owner', 'Insurance', 'Comment');
        foreach($row as $key => $val) {
            if(!is_string($key) || !in_array($key, $allowed, true)) continue;
            $this->$key = $val;
        }
    }

    public function load_by_id($Index) {
        $Index = (int) $Index;
        $sql = sprintf('SELECT * FROM `%sInventories` WHERE `Index` = "%d";',
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

    public function getInstrumentName() {
        $sql = sprintf('SELECT `Name` FROM `%sInstrument` WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        (int)$this->Instrument
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = $dbr ? mysqli_fetch_array($dbr) : null;
        return ($row && isset($row['Name'])) ? $row['Name'] : '';
    }

    /** Label (l4) + value (l8) row for instrument detail modal — keeps inputs out of w3-col floats. */
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

    public function printTableLine() {
        $sql = sprintf('SELECT * FROM `%sInventories` INNER JOIN (SELECT `Index` AS `iIndex`, `Register`, `Name` AS `iName`, `Sortierung` AS `iSort` FROM `%sInstrument`) `%sInstrument` ON `Instrument` = `iIndex` INNER JOIN (SELECT `Index` AS `rIndex`, `Name` AS `rName`, `Sortierung` AS `rSort` FROM `%sRegister`) `%sRegister` ON `Register` = `rIndex` WHERE `Index` = "%d";',
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
        if($this->Insurance) {
            $line->class=$GLOBALS['optionsDB']['colorUserMember'];
        }
        $line->class=$GLOBALS['optionsDB']['HoverEffect'];
        $line->class="w3-mobile w3-border-bottom w3-border-black";
        $str=$str.$line->open();
        
        $indent++;
        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right list-primary";
        $field->col(1,1,12);
        $field->body='<b>'.RegNumber::displayInstrument($row['RegNumber']).'</b>';
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right list-secondary";
        $field->col(1,4,12);
        $field->body=$row['iName'];
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right list-secondary";
        $field->col(1,4,12);
        $field->body=$row['Vendor'];
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right list-meta w3-hide-medium";
        $field->col(1,1,12);
        $field->body=$row['Model'];
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right list-meta w3-hide-medium";
        $field->col(1,1,12);
        $field->body=$row['SerialNr'];
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right list-meta w3-hide-medium";
        $field->col(1,1,12);
        $field->body=germanDate($row['PurchaseDate'], 0);
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right list-meta w3-hide-medium";
        $field->col(1,1,12);
        $field->body=mkPrize($row['PurchasePrize']);
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right list-meta w3-hide-medium";
        $field->col(1,1,12);
        $field->body=mkPrize($this->getCurrentValue($row['PurchasePrize']));
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right list-meta w3-hide-medium";
        $field->col(2,1,12);
        $field->body=getOwner($row['Owner']);
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center list-secondary";
        $field->col(1,1,12);
        $field->body=$this->getActiveLoanNameShort();
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right list-meta w3-hide-medium";
        $field->col(1,1,12);
        $field->body=$this->getActiveLoanDate();
        $str=$str.$field->print();

        $str=$str.$line->close();
        return $str;
    }

    public function getModalHtml() {
        $sql = sprintf('SELECT * FROM `%sInventories` INNER JOIN (SELECT `Index` AS `iIndex`, `Register`, `Name` AS `iName`, `Sortierung` AS `iSort` FROM `%sInstrument`) `%sInstrument` ON `Instrument` = `iIndex` INNER JOIN (SELECT `Index` AS `rIndex`, `Name` AS `rName`, `Sortierung` AS `rSort` FROM `%sRegister`) `%sRegister` ON `Register` = `rIndex` WHERE `Index` = "%d";',
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

        // Start above 0: extracted from nested modal wrappers; loan loop still decrements
        $indent = 2;
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
        $content->body="<h2>".$row['iName']."</h2>";
        $str=$str.$content->print();
        $str=$str.$header->close();

        $indent--;
        $detailform = new div;
        $detailform->indent = $indent;
        $detailform->tag="form";
        $detailform->action="";
        $detailform->method="POST";
        $str=$str.$detailform->open();

        $canEdit = requirePermission("perm_editInstruments");
        $indent++;

        // Inventarnummer
        $str .= $this->modalDetailRow($indent, 'Inventarnummer', $canEdit
            ? '<input class="w3-input w3-border" type="number" name="RegNumber" value="'.htmlspecialchars((string)$this->RegNumber, ENT_QUOTES, 'UTF-8').'">'
              .'<div class="w3-small w3-text-gray" style="margin-top:4px;">'.htmlspecialchars(RegNumber::displayInstrument($this->RegNumber), ENT_QUOTES, 'UTF-8').'</div>'
            : htmlspecialchars(RegNumber::displayInstrument($this->RegNumber), ENT_QUOTES, 'UTF-8')
        );
        if($canEdit) {
            $str .= '<input type="hidden" name="Index" value="'.(int)$this->Index.'">';
        }

        // Hersteller
        $str .= $this->modalDetailRow($indent, 'Hersteller', $canEdit
            ? '<input class="w3-input w3-border" type="text" name="Vendor" value="'.htmlspecialchars((string)$this->Vendor, ENT_QUOTES, 'UTF-8').'">'
            : $this->modalDisplayText($this->Vendor)
        );

        // Modell
        $str .= $this->modalDetailRow($indent, 'Modell', $canEdit
            ? '<input class="w3-input w3-border" type="text" name="Model" value="'.htmlspecialchars((string)$this->Model, ENT_QUOTES, 'UTF-8').'">'
            : $this->modalDisplayText($this->Model)
        );

        // Seriennummer
        $str .= $this->modalDetailRow($indent, 'Seriennummer', $canEdit
            ? '<input class="w3-input w3-border" type="text" name="SerialNr" value="'.htmlspecialchars((string)$this->SerialNr, ENT_QUOTES, 'UTF-8').'">'
            : $this->modalDisplayText($this->SerialNr)
        );

        // Kaufdatum
        $str .= $this->modalDetailRow($indent, 'Kaufdatum', $canEdit
            ? '<input class="w3-input w3-border" type="date" name="PurchaseDate" value="'.htmlspecialchars((string)$this->PurchaseDate, ENT_QUOTES, 'UTF-8').'">'
            : $this->modalDisplayText(germanDate($this->PurchaseDate, 0))
        );

        // Kaufpreis
        $str .= $this->modalDetailRow($indent, 'Kaufpreis', $canEdit
            ? '<input class="w3-input w3-border" type="number" step="0.01" name="PurchasePrize" value="'.htmlspecialchars((string)$this->PurchasePrize, ENT_QUOTES, 'UTF-8').'">'
            : $this->modalDisplayText(mkPrize($this->PurchasePrize))
        );

        // Zeitwert (immer Anzeige)
        $str .= $this->modalDetailRow($indent, 'Zeitwert', $this->modalDisplayText(mkPrize($this->getCurrentValue())));

        // Besitzer
        $str .= $this->modalDetailRow($indent, 'Besitzer', $canEdit
            ? '<select class="w3-select w3-border w3-input" name="Owner">'.userOptionAll($this->Owner).'</select>'
            : $this->modalDisplayText(getOwner($this->Owner))
        );

        // Versichert
        if($canEdit) {
            $insHtml = '<input type="hidden" name="Insurance" value="0">'
                .'<input class="w3-check" type="checkbox" name="Insurance" value="1"'.($this->Insurance ? ' checked' : '').'>';
        }
        else {
            $insHtml = $this->modalDisplayText(bool2string($this->Insurance));
        }
        $str .= $this->modalDetailRow($indent, 'Versichert', $insHtml);

        // Kommentar
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
            $content->col(4,6,6);
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
            $hidden->name="Index";
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
        if(requirePermission("perm_editInstruments")) {
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
            $content->name = "Instrument";
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
                $L = new Loan;
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
                
                if($L->EndDate == null && requirePermission("perm_editInstruments")) {
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
                    $content->name = "Index";
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

    public function printInsuranceLine() {
        $sql = sprintf('SELECT * FROM `%sInventories` INNER JOIN (SELECT `Index` AS `iIndex`, `Register`, `Name` AS `iName`, `Sortierung` AS `iSort` FROM `%sInstrument`) `%sInstrument` ON `Instrument` = `iIndex` INNER JOIN (SELECT `Index` AS `rIndex`, `Name` AS `rName`, `Sortierung` AS `rSort` FROM `%sRegister`) `%sRegister` ON `Register` = `rIndex` WHERE `Index` = "%d";',
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
        if($this->Insurance) {
            /* $line->class=$GLOBALS['optionsDB']['colorUserMember']; */
        }
        $line->class=$GLOBALS['optionsDB']['HoverEffect'];
        $line->class="w3-mobile w3-border-bottom w3-border-black";
        $str=$str.$line->open();
        
        $indent++;
        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right list-primary";
        $field->col(1,1,12);
        $field->body='<b>'.RegNumber::displayInstrument($row['RegNumber']).'</b>';
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right list-secondary";
        $field->col(2,4,12);
        $field->body=$row['iName'];
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right list-secondary";
        $field->col(2,4,12);
        $field->body=$row['Vendor'];
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right list-meta w3-hide-medium";
        $field->col(2,1,12);
        $field->body=$row['Model'];
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right list-meta w3-hide-medium";
        $field->col(2,1,12);
        $field->body=$row['SerialNr'];
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right list-secondary";
        $field->col(1,3,12);
        $field->body=mkPrize($this->getCurrentValue($row['PurchasePrize']));
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right list-meta w3-hide-medium";
        $field->col(2,1,12);
        $field->body=getOwner($row['Owner']);
        $str=$str.$field->print();

        $str=$str.$line->close();
        
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
            $l = new Loan;
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
            $L = new Loan;
            $L->load_by_id($loan);
            $u = new User;
            $u->load_by_id($L->User);
            return $u->getName();
        }
    }

    public function getActiveLoanNameShort() {
        $loan = $this->getActiveLoan();
        if($loan) {
            $L = new Loan;
            $L->load_by_id($loan);
            $u = new User;
            $u->load_by_id($L->User);
            return $u->getShort();
        }
    }

    public function getActiveLoanDate() {
        $loan = $this->getActiveLoan();
        if($loan) {
            $L = new Loan;
            $L->load_by_id($loan);
            return germanDate($L->StartDate,0);
        }
    }

    public function getCurrentValue() {
        if($this->PurchasePrize) {
            $purchase = date_create($this->PurchaseDate);
            $now = date_create(date("Y-m-d"));
            $age = date_diff($purchase, $now);
            $years = $age->format("%y");
            if($years < 1) return $this->PurchasePrize;
            if($years < 2) return $this->PurchasePrize*0.9;
            if($years < 3) return $this->PurchasePrize*0.8;
            if($years < 4) return $this->PurchasePrize*0.7;
            if($years < 15) return $this->PurchasePrize*0.6;
            if($years < 20) return $this->PurchasePrize*0.5;
            else return $this->PurchasePrize*0.4;
            return "";
        }
    }

    public function getCsvLine() {
        $sql = sprintf('SELECT * FROM `%sInventories` INNER JOIN (SELECT `Index` AS `iIndex`, `Register`, `Name` AS `iName`, `Sortierung` AS `iSort` FROM `%sInstrument`) `%sInstrument` ON `Instrument` = `iIndex` INNER JOIN (SELECT `Index` AS `rIndex`, `Name` AS `rName`, `Sortierung` AS `rSort` FROM `%sRegister`) `%sRegister` ON `Register` = `rIndex` WHERE `Index` = "%d";',
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
        
        return array("Instrument" => html_entity_decode($row['iName']), "Hersteller" => html_entity_decode($row['Vendor']), "Modell" => html_entity_decode($row['Model']), "Seriennummer" => html_entity_decode($row['SerialNr']), "Zeitwert" => $this->getCurrentValue(), "Besitzer" => html_entity_decode(getOwner($row['Owner'])));
    }
};
?>
