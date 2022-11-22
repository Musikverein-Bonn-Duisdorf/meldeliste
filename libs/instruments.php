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
        $line->class=$GLOBALS['optionsDB']['HoverEffect'];
$line->class="w3-mobile w3-border-bottom w3-border-black";
        $str=$str.$line->open();
        
        $field = new div;
        $field->class="w3-col l1 m1 s1 w3-center w3-border-right";
        $field->body=$row['RegNumber'];
        $str=$str.$field->print();

        $field = new div;
        $field->class="w3-col l2 m2 s2 w3-center w3-border-right";
        $field->body=$row['iName'];
        $str=$str.$field->print();

        $field = new div;
        $field->class="w3-col l2 m2 s2 w3-center w3-border-right";
        $field->body=$row['Vendor'];
        $str=$str.$field->print();

        $field = new div;
        $field->class="w3-col l1 m1 s1 w3-center w3-border-right";
        $field->body=$row['SerialNr'];
        $str=$str.$field->print();

        $field = new div;
        $field->class="w3-col l2 m2 s2 w3-center w3-border-right";
        $field->body=germanDate($row['PurchaseDate'], 0);
        $str=$str.$field->print();

        $field = new div;
        $field->class="w3-col l1 m1 s1 w3-center w3-border-right";
        $field->body=mkPrize($row['PurchasePrize']);
        $str=$str.$field->print();

        $field = new div;
        $field->class="w3-col l2 m2 s2 w3-center w3-border-right";
        $field->body=getOwner($row['Owner']);
        $str=$str.$field->print();

        $str=$str.$line->close();
        return $str;
    }
};
?>