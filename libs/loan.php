<?php
class Loan
{
    private $_data = array('Index' => null, 'User' => null, 'Instrument' => null, 'StartDate' => null, 'EndDate' => null, 'ContractFile' => null);
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'User':
	    case 'Instrument':
	    case 'StartDate':
	    case 'EndDate':
	    case 'ContractFile':
            return $this->_data[$key];
            break;
        default:
            break;
        }
    }

    public function __set($key, $val) {
        switch($key) {
	    case 'StartDate':
	    case 'EndDate':
	    case 'ContractFile':
            $this->_data[$key] = $val;
            break;
	    case 'Index':
	    case 'Instrument':
	    case 'User':
            $this->_data[$key] = (int)$val;
            break;
        default:
            break;
        }	
    }

    public function is_valid() {
        if(!$this->Instrument) return false;
        if(!$this->User) return false;
        return true;
    }

    public function getChanges() {
        $old = new Loan;
        $old->load_by_id($this->Index);

        $u = new User;
        $u->load_by_id($this->User);

        $Instrument = new Instruments;
        $Instrument->load_by_id($this->Instrument);
        
        $str = sprintf("Loan-ID: %d, Instrument: (%d) <b>%s</b>, User: (%d) <b>%s</b>",
        $this->Index,
        $this->Instrument,
        $Instrument->getInstrumentName(),
        $this->User,
        $u->getName()
        );
        if($this->StartDate != $old->StartDate) $str.=", StartDate: ".germanDate($old->StartDate,0)." &rArr; <b>".germanDate($this->StartDate,0)."</b>";
        if($this->EndDate != $old->EndDate) $str.=", EndDate: ".germanDate($old->EndDate,0)." &rArr; <b>".germanDate($this->EndDate,0)."</b>";

        return $str;
    }

    public function getVars() {
        $Instrument = new Instruments;
        $Instrument->load_by_id($this->Instrument);

        $u = new User;
        $u->load_by_id($this->User);
        
        return sprintf("Loan-ID: %d, Instrument: (%d) <b>%s</b>, User: (%d) <b>%s</b>, StartDate: <b>%s</b>, EndDate: <b>%s</b>, ContractFile: <b>%s</b>",
        $this->Index,
        $this->Instrument,
        $Instrument->getInstrumentName(),
        $this->User,
        $u->getName(),
        germanDate($this->StartDate,0),
        germanDate($this->EndDate,0),
        $Instrument->ContractFile
        );
    }

    public function save() {
        if(!$this->is_valid()) return false;
        if($this->Index > 0) {
            $logentry = new Log;
            $logentry->DBupdate($this->getChanges());
            $this->update();
        }
        else {
            $this->insert();
            $logentry = new Log;
            $logentry->DBinsert($this->getVars());
        }
    }

    protected function insert() {
        $sql = sprintf('INSERT INTO `%sLoans` (`User`, `Instrument`, `StartDate`, `EndDate`, `ContractFile`) VALUES ("%d", "%d", %s, %s, "%s");',
        $GLOBALS['dbprefix'],
        $this->User,
        $this->Instrument,
        mkNULLstr($this->StartDate),
        mkNULLstr($this->EndDate),
        $this->ContractFile
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }
    
    protected function update() {
        $sql = sprintf('UPDATE `%sLoans` SET `User` = "%d", `Instrument` = "%d", `StartDate` = %s, `EndDate` = %s, `ContractFile` = "%s" WHERE `Index` = "%d";',
        $GLOBALS['dbprefix'],
        $this->User,
        $this->Instrument,
        mkNULLstr($this->StartDate),
        mkNULLstr($this->EndDate),
        $this->ContractFile,
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

        $sql = sprintf('DELETE FROM `%sLoans` WHERE `Index` = "%d" LIMIT 1;',
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
        $sql = sprintf('SELECT * FROM `%sLoans` WHERE `Index` = "%d";',
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

    public function getName() {
        $u = new User;
        $u->load_by_id($this->User);
        return $u->getName();
    }
};
?>