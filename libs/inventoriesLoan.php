<?php
class InventoriesLoan
{
    private $_data = array(
        'Index' => null,
        'User' => null,
        'Inventory' => null,
        'StartDate' => null,
        'EndDate' => null,
        'Kaution' => null,
        'ContractFile' => null,
        'ReturnContractFile' => null,
    );

    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'User':
	    case 'Inventory':
	    case 'StartDate':
	    case 'EndDate':
	    case 'Kaution':
	    case 'ContractFile':
	    case 'ReturnContractFile':
            return $this->_data[$key];
        default:
            break;
        }
    }

    public function __set($key, $val) {
        switch($key) {
	    case 'StartDate':
	    case 'EndDate':
	    case 'ContractFile':
	    case 'ReturnContractFile':
            $this->_data[$key] = $val;
            break;
	    case 'Kaution':
            if($val === null || $val === '') {
                $this->_data[$key] = '0.00';
            }
            else {
                $this->_data[$key] = number_format(LoanForm::parseKaution($val), 2, '.', '');
            }
            break;
	    case 'Index':
	    case 'Inventory':
	    case 'User':
            $this->_data[$key] = (int)$val;
            break;
        default:
            break;
        }
    }

    public function is_valid() {
        if(!$this->Inventory) return false;
        if(!$this->User) return false;
        return true;
    }

    /** Type label + RegNumber for log messages. */
    private function inventoryLogLabel($inventoryId = null) {
        $inv = new Inventories;
        $inv->load_by_id($inventoryId !== null ? $inventoryId : $this->Inventory);
        $typeName = $inv->getInventoryType();
        $family = $inv->getInstrumentName();
        if($family !== '') $typeName = $family;
        return sprintf('(%d) <b>%s</b> %s',
            (int)$inv->Index,
            $typeName,
            RegNumber::displayInventory($inv->Inventory, $inv->RegNumber)
        );
    }

    private function escapeDb($val) {
        return mysqli_real_escape_string($GLOBALS['conn'], (string)$val);
    }

    public function getChanges() {
        $old = new InventoriesLoan;
        $old->load_by_id($this->Index);

        $u = new User;
        $u->load_by_id($this->User);

        $str = sprintf('InventoriesLoan-ID: %d, Inventory: %s, User: (%d) <b>%s</b>',
            (int)$this->Index,
            $this->inventoryLogLabel(),
            (int)$this->User,
            $u->getName()
        );
        if((int)$this->User !== (int)$old->User) {
            $ou = new User;
            $ou->load_by_id($old->User);
            $str .= ', User: ('.(int)$old->User.') '.$ou->getName()
                .' &rArr; <b>('.((int)$this->User).') '.$u->getName().'</b>';
        }
        if((int)$this->Inventory !== (int)$old->Inventory) {
            $str .= ', Inventory: '.$this->inventoryLogLabel($old->Inventory)
                .' &rArr; <b>'.$this->inventoryLogLabel().'</b>';
        }
        if($this->StartDate != $old->StartDate) {
            $str .= ', StartDate: '.germanDate($old->StartDate, 0)
                .' &rArr; <b>'.germanDate($this->StartDate, 0).'</b>';
        }
        if($this->EndDate != $old->EndDate) {
            $str .= ', EndDate: '.germanDate($old->EndDate, 0)
                .' &rArr; <b>'.germanDate($this->EndDate, 0).'</b>';
        }
        if(LoanForm::parseKaution($this->Kaution) !== LoanForm::parseKaution($old->Kaution)) {
            $str .= ', Kaution: '.LoanForm::formatKaution($old->Kaution)
                .' &rArr; <b>'.LoanForm::formatKaution($this->Kaution).'</b>';
        }
        if($this->ContractFile != $old->ContractFile) {
            $str .= ', ContractFile: '.$old->ContractFile
                .' &rArr; <b>'.$this->ContractFile.'</b>';
        }
        if($this->ReturnContractFile != $old->ReturnContractFile) {
            $str .= ', ReturnContractFile: '.$old->ReturnContractFile
                .' &rArr; <b>'.$this->ReturnContractFile.'</b>';
        }

        return $str;
    }

    public function getVars() {
        $u = new User;
        $u->load_by_id($this->User);

        $parts = array();
        $parts[] = sprintf('InventoriesLoan-ID: %d', (int)$this->Index);
        $parts[] = 'Inventory: '.$this->inventoryLogLabel();
        $parts[] = sprintf('User: (%d) <b>%s</b>', (int)$this->User, $u->getName());
        $start = germanDate($this->StartDate, 0);
        logAppendFilled($parts, 'StartDate', $start, (string)$start);
        $end = germanDate($this->EndDate, 0);
        logAppendFilled($parts, 'EndDate', $end, (string)$end);
        if(LoanForm::hasKaution($this->Kaution)) {
            $parts[] = logPart('Kaution', LoanForm::formatKaution($this->Kaution));
        }
        logAppendFilled($parts, 'ContractFile', $this->ContractFile, (string)$this->ContractFile);
        logAppendFilled($parts, 'ReturnContractFile', $this->ReturnContractFile, (string)$this->ReturnContractFile);
        return implode(', ', $parts);
    }

    public function save() {
        if(!$this->is_valid()) return false;
        if($this->Kaution === null || $this->Kaution === '') {
            $this->Kaution = '0.00';
        }
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
        $sql = sprintf(
            'INSERT INTO `%sInventoriesLoans` (`User`, `Inventory`, `StartDate`, `EndDate`, `Kaution`, `ContractFile`, `ReturnContractFile`) VALUES ("%d", "%d", %s, %s, "%s", "%s", "%s");',
            $GLOBALS['dbprefix'],
            $this->User,
            $this->Inventory,
            mkNULLstr($this->StartDate),
            mkNULLstr($this->EndDate),
            $this->escapeDb(number_format(LoanForm::parseKaution($this->Kaution), 2, '.', '')),
            $this->escapeDb($this->ContractFile),
            $this->escapeDb($this->ReturnContractFile)
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        sqlerror();
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }

    protected function update() {
        $sql = sprintf(
            'UPDATE `%sInventoriesLoans` SET `User` = "%d", `Inventory` = "%d", `StartDate` = %s, `EndDate` = %s, `Kaution` = "%s", `ContractFile` = "%s", `ReturnContractFile` = "%s" WHERE `Index` = "%d";',
            $GLOBALS['dbprefix'],
            $this->User,
            $this->Inventory,
            mkNULLstr($this->StartDate),
            mkNULLstr($this->EndDate),
            $this->escapeDb(number_format(LoanForm::parseKaution($this->Kaution), 2, '.', '')),
            $this->escapeDb($this->ContractFile),
            $this->escapeDb($this->ReturnContractFile),
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

        $sql = sprintf('DELETE FROM `%sInventoriesLoans` WHERE `Index` = "%d" LIMIT 1;',
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
            if(!array_key_exists($key, $this->_data)) {
                continue;
            }
            if($key === 'Kaution') {
                $this->Kaution = $val;
                continue;
            }
            if($key === 'Index' || $key === 'Inventory' || $key === 'User') {
                $this->_data[$key] = (int)$val;
                continue;
            }
            $this->_data[$key] = $val;
        }
    }

    public function load_by_id($Index) {
        $Index = (int) $Index;
        $sql = sprintf('SELECT * FROM `%sInventoriesLoans` WHERE `Index` = "%d";',
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

    /**
     * Offene/aktive Leihe: kein EndDate oder Rückgabe liegt in der Zukunft.
     * (EndDate = heute oder früher = beendet)
     */
    public static function isOpen($endDate) {
        if($endDate === null || $endDate === '' || $endDate === '0000-00-00') {
            return true;
        }
        try {
            $end = new DateTime((string)$endDate);
            $now = new DateTime(date('Y-m-d'));
            return $end > $now;
        }
        catch(Exception $e) {
            return false;
        }
    }

    public function isActive() {
        return self::isOpen($this->EndDate);
    }
};
?>
