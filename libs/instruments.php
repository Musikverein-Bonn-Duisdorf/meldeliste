<?php
class Instruments
{
    private $_data = array('Index' => null, 'RegNumber' => null, 'Instrument' => null, 'Vendor' => null, 'SerialNr' => null, 'PurchasePrize' => null, 'PurchaseDate' => null, 'Owner' => null, 'Insurance' => null, 'Comment' => null);
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'RegNumber':
	    case 'Instrument':
	    case 'Vendor':
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
	    case 'Instrument':
	    case 'Vendor':
	    case 'Owner':
	    case 'Insurance':
        case 'RegNumber':
            $this->_data[$key] = (int)$val;
            break;
	    case 'SerialNr':
	    case 'Comment':
            $this->_data[$key] = trim($val);
            break;
        default:
            break;
        }	
    }

    public function is_valid() {
        if(!$this->Instrument) return false;
        return true;
    }

    public function getVars() {
        $sql = sprintf('SELECT * FROM `%sInstrument` WHERE `Index` = %d;',
        $GLOBALS['dbprefix'],
        $this->Instrument
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = mysqli_fetch_array($dbr);
        $Instrument = $row['Name'];

        return sprintf("Instrument-ID: %d, Instrument: %s, Inventarnummer: %d, Hersteller: %s, Seriennummer: %s, Kaufdatum: %s, Kaufpreis: %s, Besitzer: %s, Versichert: %s, Kommentar: %s",
        $this->Index,
        $Instrument,
        $this->RegNumber,
        $this->Vendor,
        $this->SerialNr,
        germanDate($this->PurchaseDate,0),
        mkPrize($this->PurchasePrize),
        getOwner($this->Owner),
        bool2string($this->Insurance),
        $this->Comment
        );
    }

    public function save() {
        if(!$this->is_valid()) return false;
        if($this->Index > 0) {
            $this->update();
            $logentry = new Log;
            $logentry->DBupdate($this->getVars());
        }
        else {
            $this->insert();
            $logentry = new Log;
            $logentry->DBinsert($this->getVars());
        }
    }

    protected function insert() {
        $sql = sprintf('INSERT INTO `%sInstruments` (`RegNumber`, `Instrument`, `Vendor`, `SerialNr`, `PurchaseDate`, `PurchasePrize`, `Owner`, `Insurance`, `Comment`) VALUES ("%d", "%d", "%s", "%s", "%s", "%f", "%s", "%d", "%s");',
        $GLOBALS['dbprefix'],
        $this->RegNumber,
        $this->Instrument,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Vendor),
        mysqli_real_escape_string($GLOBALS['conn'], $this->SerialNr),
        $this->PurchaseDate,
        $this->PurchasePrize,
        $this->Owner,
        $this->Insurance,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Comment)
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }
    
    protected function update() {
        $sql = sprintf('UPDATE `%sInstrument` SET `RegNumber` = "%d", `Instrument` = "%d", `Vendor` = "%s", `SerialNr` = "%s", `PurchaseDate` = "%s", `PurchasePrize` = "%s", `Owner` = "%d", `Insurance` = "%d", `Comment` = "%s" WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        $this->RegNumber,
        $this->Instrument,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Vendor),
        mysqli_real_escape_string($GLOBALS['conn'], $this->SerialNr),
        $this->PurchaseDate,
        $this->PurchasePrize,
        $this->Owner,
        $this->Insurance,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Comment),
        $this->Index
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

        $sql = sprintf('DELETE FROM `%sLoans` WHERE `Instrument` = "%d";',
        $GLOBALS['dbprefix'],
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();

        $sql = sprintf('DELETE FROM `%sInstruments` WHERE `Index` = "%d" LIMIT 1;',
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
        foreach($row as $key => $val) {
                $this->_data[$key] = $val;
        }
    }

    public function load_by_id($Index) {
        $Index = (int) $Index;
        $sql = sprintf('SELECT * FROM `%sInstruments` WHERE `Index` = "%d";',
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
        $this->Instrument
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        $row = mysqli_fetch_array($dbr);
        return $row['Name'];
    }

    public function printTableLine() {
        $sql = sprintf('SELECT * FROM `%sInstruments` INNER JOIN (SELECT `Index` AS `iIndex`, `Register`, `Name` AS `iName`, `Sortierung` AS `iSort` FROM `%sInstrument`) `%sInstrument` ON `Instrument` = `iIndex` INNER JOIN (SELECT `Index` AS `rIndex`, `Name` AS `rName`, `Sortierung` AS `rSort` FROM `%sRegister`) `%sRegister` ON `Register` = `rIndex` WHERE `Index` = "%d";',
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
        $line->onclick="document.getElementById('".$this->Index."').style.display='block'";
        if($this->Insurance) {
            $line->class=$GLOBALS['optionsDB']['colorUserMember'];
        }
        $line->class=$GLOBALS['optionsDB']['HoverEffect'];
$line->class="w3-mobile w3-border-bottom w3-border-black";
        $str=$str.$line->open();
        
        $indent++;
        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right w3-hide-medium w3-hide-small";
        $field->col(1,1,1);
        $field->body=$row['RegNumber'];
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right";
        $field->col(2,4,4);
        $field->body=$row['iName'];
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right";
        $field->col(2,4,4);
        $field->body=$row['Vendor'];
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right w3-hide-medium w3-hide-small";
        $field->col(1,1,1);
        $field->body=$row['SerialNr'];
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right w3-hide-medium w3-hide-small";
        $field->col(1,1,1);
        $field->body=germanDate($row['PurchaseDate'], 0);
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right w3-hide-medium w3-hide-small";
        $field->col(1,1,1);
        $field->body=mkPrize($row['PurchasePrize']);
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right w3-hide-medium w3-hide-small";
        $field->col(1,1,1);
        $field->body=mkPrize($this->getCurrentValue($row['PurchasePrize']));
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right w3-hide-medium w3-hide-small";
        $field->col(1,1,1);
        $field->body=getOwner($row['Owner']);
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center";
        $field->col(1,4,4);
        $field->body=$this->getActiveLoanName();
        $str=$str.$field->print();

        $field = new div;
        $field->indent=$indent;
        $field->class="w3-center w3-border-right w3-hide-medium w3-hide-small";
        $field->col(1,1,1);
        $field->body=$this->getActiveLoanDate();
        $str=$str.$field->print();

        $str=$str.$line->close();
        
        $indent--;
        $modal = new div;
        $modal->indent=$indent;
        $modal->class="w3-modal";
        $modal->id=$this->Index;
        $str=$str.$modal->open();

        $indent++;
        $modalcontent = new div;
        $modalcontent->indent=$indent;
        $modalcontent->class="w3-modal-content";
        $str=$str.$modalcontent->open();
        $indent++;
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
        $span->onclick="document.getElementById('".$this->Index."').style.display='none'";
        $span->class="w3-button w3-display-topright";
        $span->body="&times;";
        $str=$str.$span->print();
        
        $content = new div;
        $content->indent=$indent;
        $content->body="<h2>".$row['iName']."</h2>";
        $str=$str.$content->print();
        $str=$str.$header->close();
        
        $indent--;
        $modalrow = new div;
        $modalrow->indent=$indent;
        $modalrow->class="w3-row w3-padding";
        $str=$str.$modalrow->open();
        $indent++;
        $content = new div;
        $content->indent=$indent;
        $content->col(2,6,6);
        $content->body="<b>Inventarnummer:</b>";
        $str=$str.$content->print();
        $content = new div;
        $content->indent=$indent;
        $content->col(4,6,6);
        $content->body=$this->RegNumber;
        $str=$str.$content->print();
        $str=$str.$modalrow->close();

        $indent--;
        $modalrow = new div;
        $modalrow->indent=$indent;
        $modalrow->class="w3-row w3-padding";
        $str=$str.$modalrow->open();
        $indent++;
        $content = new div;
        $content->indent=$indent;
        $content->col(2,6,6);
        $content->body="<b>Hersteller:</b>";
        $str=$str.$content->print();
        $content = new div;
        $content->indent=$indent;
        $content->col(4,6,6);
        $content->body=$this->Vendor;
        $str=$str.$content->print();
        $str=$str.$modalrow->close();

        $indent--;
        $modalrow = new div;
        $modalrow->indent=$indent;
        $modalrow->class="w3-row w3-padding";
        $str=$str.$modalrow->open();
        $indent++;
        $content = new div;
        $content->indent=$indent;
        $content->col(2,6,6);
        $content->body="<b>Seriennummer:</b>";
        $str=$str.$content->print();
        $content = new div;
        $content->indent=$indent;
        $content->col(4,6,6);
        $content->body=$this->SerialNr;
        $str=$str.$content->print();
        $str=$str.$modalrow->close();

        $indent--;
        $modalrow = new div;
        $modalrow->indent=$indent;
        $modalrow->class="w3-row w3-padding";
        $str=$str.$modalrow->open();
        $indent++;
        $content = new div;
        $content->indent=$indent;
        $content->col(2,6,6);
        $content->body="<b>Kaufdatum:</b>";
        $str=$str.$content->print();
        $content = new div;
        $content->indent=$indent;
        $content->col(4,6,6);
        $content->body=germanDate($this->PurchaseDate,1);
        $str=$str.$content->print();
        $str=$str.$modalrow->close();

        $indent--;
        $modalrow = new div;
        $modalrow->indent=$indent;
        $modalrow->class="w3-row w3-padding";
        $str=$str.$modalrow->open();
        $indent++;
        $content = new div;
        $content->indent=$indent;
        $content->col(2,6,6);
        $content->body="<b>Kaufpreis:</b>";
        $str=$str.$content->print();
        $content = new div;
        $content->indent=$indent;
        $content->col(4,6,6);
        $content->body=mkPrize($this->PurchasePrize);
        $str=$str.$content->print();
        $str=$str.$modalrow->close();

        $indent--;
        $modalrow = new div;
        $modalrow->indent=$indent;
        $modalrow->class="w3-row w3-padding";
        $str=$str.$modalrow->open();
        $indent++;
        $content = new div;
        $content->indent=$indent;
        $content->col(2,6,6);
        $content->body="<b>Zeitwert:</b>";
        $str=$str.$content->print();
        $content = new div;
        $content->indent=$indent;
        $content->col(4,6,6);
        $content->body=mkPrize($this->getCurrentValue($row['PurchasePrize']));
        $str=$str.$content->print();
        $str=$str.$modalrow->close();

        $indent--;
        $modalrow = new div;
        $modalrow->indent=$indent;
        $modalrow->class="w3-row w3-padding";
        $str=$str.$modalrow->open();
        $indent++;
        $content = new div;
        $content->indent=$indent;
        $content->col(2,6,6);
        $content->body="<b>Besitzer:</b>";
        $str=$str.$content->print();
        $content = new div;
        $content->indent=$indent;
        $content->col(4,6,6);
        $content->body=getOwner($row['Owner']);
        $str=$str.$content->print();
        $str=$str.$modalrow->close();

        $indent--;
        $modalrow = new div;
        $modalrow->indent=$indent;
        $modalrow->class="w3-row w3-padding";
        $str=$str.$modalrow->open();
        $indent++;
        $content = new div;
        $content->indent=$indent;
        $content->col(2,6,6);
        $content->body="<b>Versichert:</b>";
        $str=$str.$content->print();
        $content = new div;
        $content->indent=$indent;
        $content->col(4,6,6);
        $content->body=bool2string($this->Insurance);
        $str=$str.$content->print();
        $str=$str.$modalrow->close();

        $indent--;
        $modalrow = new div;
        $modalrow->indent=$indent;
        $modalrow->class="w3-row w3-padding";
        $str=$str.$modalrow->open();
        $indent++;
        $content = new div;
        $content->indent=$indent;
        $content->col(2,6,6);
        $content->body="<b>Kommentar:</b>";
        $str=$str.$content->print();
        $content = new div;
        $content->indent=$indent;
        $content->col(4,6,6);
        $content->body=$this->Comment;
        $str=$str.$content->print();
        $str=$str.$modalrow->close();

        $loans = $this->getLoans();
        if($loans) {
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
                $content = new div;
                $content->indent=$indent;
                $content->col(2,4,4);
                $content->class="w3-border-right";
                $content->body=germanDate($L->EndDate,0);
                $str=$str.$content->print();
                $str=$str.$modalrow2->close();            
            }
            $str=$str.$modalrow->close();            
            $indent--;
        }
        
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
        
        $str=$str.$modalcontent->close();
        $str=$str.$modal->close();
        
        return $str;
    }

    public function getLoans() {
        $sql = sprintf('SELECT `Index` FROM `%sLoans` WHERE `Instrument` = "%d" ORDER BY `StartDate` DESC;',
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
            return $loans[0];
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
            return $this->PurchasePrize*pow(0.95,$years);
        }
    }
};
?>