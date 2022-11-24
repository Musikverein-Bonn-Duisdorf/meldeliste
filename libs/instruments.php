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
        
        $line = new div;
        $line->class="w3-row w3-padding";
        $line->onclick="document.getElementById('".$this->Index."').style.display='block'";
        if($this->Insurance) {
            $line->class=$GLOBALS['optionsDB']['colorUserMember'];
        }
        $line->class=$GLOBALS['optionsDB']['HoverEffect'];
$line->class="w3-mobile w3-border-bottom w3-border-black";
        $str=$str.$line->open();
        
        $field = new div;
        $field->class="w3-center w3-border-right w3-hide-medium w3-hide-small";
        $field->col(1,1,1);
        $field->body=$row['RegNumber'];
        $str=$str.$field->print();

        $field = new div;
        $field->class="w3-center w3-border-right";
        $field->col(2,4,4);
        $field->body=$row['iName'];
        $str=$str.$field->print();

        $field = new div;
        $field->class="w3-center w3-border-right";
        $field->col(2,4,4);
        $field->body=$row['Vendor'];
        $str=$str.$field->print();

        $field = new div;
        $field->class="w3-center w3-border-right w3-hide-medium w3-hide-small";
        $field->col(1,1,1);
        $field->body=$row['SerialNr'];
        $str=$str.$field->print();

        $field = new div;
        $field->class="w3-center w3-border-right w3-hide-medium w3-hide-small";
        $field->col(1,1,1);
        $field->body=germanDate($row['PurchaseDate'], 0);
        $str=$str.$field->print();

        $field = new div;
        $field->class="w3-center w3-border-right w3-hide-medium w3-hide-small";
        $field->col(1,1,1);
        $field->body=mkPrize($row['PurchasePrize']);
        $str=$str.$field->print();

        $field = new div;
        $field->class="w3-center w3-border-right w3-hide-medium w3-hide-small";
        $field->col(1,1,1);
        $field->body=mkPrize($this->getCurrentValue($row['PurchasePrize']));
        $str=$str.$field->print();

        $field = new div;
        $field->class="w3-center w3-border-right w3-hide-medium w3-hide-small";
        $field->col(1,1,1);
        $field->body=getOwner($row['Owner']);
        $str=$str.$field->print();

        $field = new div;
        $field->class="w3-center";
        $field->col(1,4,4);
        $field->body=$this->getActiveLoanName();
        $str=$str.$field->print();

        $field = new div;
        $field->class="w3-center w3-border-right w3-hide-medium w3-hide-small";
        $field->col(1,1,1);
        $field->body=$this->getActiveLoanDate();
        $str=$str.$field->print();

        $str=$str.$line->close();
        
        $modal = new div;
        $modal->class="w3-modal";
        $modal->id=$this->Index;
        $str=$str.$modal->open();
        
        $modalcontent = new div;
        $modalcontent->class="w3-modal-content";
        $str=$str.$modalcontent->open();
        $container = new div;
        $container->class="w3-container";
        $str=$str.$container->open();
        $span = new div;
        $span->tag="span";
        $span->onclick="document.getElementById('".$this->Index."').style.display='none'";
        $span->class="w3-button w3-display-topright";
        $span->body="&times;";
        $str=$str.$span->print();
        
        $content = new div;
        $content->body="<h2>".$row['iName']."</h2>";
        $str=$str.$content->print();
        
        $modalrow = new div;
        $modalrow->class="w3-row w3-padding";
        $str=$str.$modalrow->open();
        $content = new div;
        $content->col(2,6,6);
        $content->body="<b>Inventarnummer:</b>";
        $str=$str.$content->print();
        $content = new div;
        $content->col(4,6,6);
        $content->body=$this->RegNumber;
        $str=$str.$content->print();
        $str=$str.$modalrow->close();

        $modalrow = new div;
        $modalrow->class="w3-row w3-padding";
        $str=$str.$modalrow->open();
        $content = new div;
        $content->col(2,6,6);
        $content->body="<b>Hersteller:</b>";
        $str=$str.$content->print();
        $content = new div;
        $content->col(4,6,6);
        $content->body=$this->Vendor;
        $str=$str.$content->print();
        $str=$str.$modalrow->close();

        $modalrow = new div;
        $modalrow->class="w3-row w3-padding";
        $str=$str.$modalrow->open();
        $content = new div;
        $content->col(2,6,6);
        $content->body="<b>Seriennummer:</b>";
        $str=$str.$content->print();
        $content = new div;
        $content->col(4,6,6);
        $content->body=$this->SerialNr;
        $str=$str.$content->print();
        $str=$str.$modalrow->close();

        $modalrow = new div;
        $modalrow->class="w3-row w3-padding";
        $str=$str.$modalrow->open();
        $content = new div;
        $content->col(2,6,6);
        $content->body="<b>Kaufdatum:</b>";
        $str=$str.$content->print();
        $content = new div;
        $content->col(4,6,6);
        $content->body=germanDate($this->PurchaseDate,1);
        $str=$str.$content->print();
        $str=$str.$modalrow->close();

        $modalrow = new div;
        $modalrow->class="w3-row w3-padding";
        $str=$str.$modalrow->open();
        $content = new div;
        $content->col(2,6,6);
        $content->body="<b>Kaufpreis:</b>";
        $str=$str.$content->print();
        $content = new div;
        $content->col(4,6,6);
        $content->body=mkPrize($this->PurchasePrize);
        $str=$str.$content->print();
        $str=$str.$modalrow->close();

        $modalrow = new div;
        $modalrow->class="w3-row w3-padding";
        $str=$str.$modalrow->open();
        $content = new div;
        $content->col(2,6,6);
        $content->body="<b>Zeitwert:</b>";
        $str=$str.$content->print();
        $content = new div;
        $content->col(4,6,6);
        $content->body=mkPrize($this->getCurrentValue($row['PurchasePrize']));
        $str=$str.$content->print();
        $str=$str.$modalrow->close();

        $modalrow = new div;
        $modalrow->class="w3-row w3-padding";
        $str=$str.$modalrow->open();
        $content = new div;
        $content->col(2,6,6);
        $content->body="<b>Besitzer:</b>";
        $str=$str.$content->print();
        $content = new div;
        $content->col(4,6,6);
        $content->body=getOwner($row['Owner']);
        $str=$str.$content->print();
        $str=$str.$modalrow->close();

        $modalrow = new div;
        $modalrow->class="w3-row w3-padding";
        $str=$str.$modalrow->open();
        $content = new div;
        $content->col(2,6,6);
        $content->body="<b>Versichert:</b>";
        $str=$str.$content->print();
        $content = new div;
        $content->col(4,6,6);
        $content->body=bool2string($this->Insurance);
        $str=$str.$content->print();
        $str=$str.$modalrow->close();

        $loans = $this->getLoans();
        if($loans) {
            $modalrow = new div;
            $modalrow->class="w3-padding w3-margin-bottom";
            $modalrow->class=$GLOBALS['optionsDB']['colorInputBackground'];
            $modalrow->body="<b>Leihhistorie:</b>";
            $str=$str.$modalrow->open();

            $modalrow = new div;
            $modalrow->class="w3-row w3-center w3-padding w3-border-bottom";
            $str=$str.$modalrow->open();
            $content = new div;
            $content->col(2,4,4);
            $content->class="w3-border-right";
            $content->body="<b>an</b>";
            $str=$str.$content->print();
            $content = new div;
            $content->col(2,4,4);
            $content->class="w3-border-right";
            $content->body="<b>von</b>";
            $str=$str.$content->print();
            $content = new div;
            $content->col(2,4,4);
            $content->class="w3-border-right";
            $content->body="<b>bis</b>";
            $str=$str.$content->print();
            $str=$str.$modalrow->close();            
        }
        for($i=0; $i<count($loans); $i++) {           
            $L = new Loan;
            $L->load_by_id($loans[$i]);
            $modalrow = new div;
            $modalrow->class="w3-row w3-center w3-padding";
            if($L->EndDate == null) {
                $modalrow->class="w3-teal";
            }
            $str=$str.$modalrow->open();
            $content = new div;
            $content->col(2,4,4);
            $content->class="w3-border-right";
            $content->body=$L->getName();
            $str=$str.$content->print();
            $content = new div;
            $content->col(2,4,4);
            $content->class="w3-border-right";
            $content->body=germanDate($L->StartDate,0);
            $str=$str.$content->print();
            $content = new div;
            $content->col(2,4,4);
            $content->class="w3-border-right";
            $content->body=germanDate($L->EndDate,0);
            $str=$str.$content->print();
            $str=$str.$modalrow->close();            
        }
        $str=$str.$modalrow->close();
        
        $str=$str.$container->close();
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