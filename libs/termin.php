<?php
class Termin
{
    private $_data = array('Index' => null, 'Datum' => null, 'Uhrzeit' => null, 'Uhrzeit2' => null, 'Name' => null, 'Auftritt' => null, 'Ort1' => null, 'Ort2' => null, 'Ort3' => null, 'Ort4' => null, 'Beschreibung' => null, 'published' => null, 'Wert' => null);
    public function __get($key) {
        switch($key) {
	    case 'Index':
	    case 'Datum':
	    case 'Uhrzeit':
	    case 'Uhrzeit2':
	    case 'Name':
	    case 'Auftritt':
	    case 'Ort1':
	    case 'Ort2':
	    case 'Ort3':
	    case 'Ort4':
	    case 'Beschreibung':
	    case 'published':
	    case 'Wert':
            return $this->_data[$key];
            break;
        default:
            break;
        }
    }
    public function __set($key, $val) {
        switch($key) {
	    case 'Index':
            $this->_data[$key] = (int)$val;
            break;
	    case 'Datum':
            $this->_data[$key] = trim($val);
            break;
	    case 'Uhrzeit':
            $this->_data[$key] = trim($val);
            break;
	    case 'Uhrzeit2':
            $this->_data[$key] = trim($val);
            break;
	    case 'Name':
            $this->_data[$key] = trim($val);
            break;
	    case 'Beschreibung':
            $this->_data[$key] = trim($val);
            break;
	    case 'Auftritt':
            $this->_data[$key] = (bool)$val;
            break;
	    case 'Ort1':
            $this->_data[$key] = trim($val);
            break;
	    case 'Ort2':
            $this->_data[$key] = trim($val);
            break;
	    case 'Ort3':
            $this->_data[$key] = trim($val);
            break;
	    case 'Ort4':
            $this->_data[$key] = trim($val);
            break;
	    case 'published':
            $this->_data[$key] = (bool)$val;
            break;
	    case 'Wert':
            $this->_data[$key] = (int)$val;
            break;
        default:
            break;
        }	
    }
    public function save() {
        if(!$this->is_valid()) return false;
        if($this->Index > 0) {
            $this->update();	    
        }
        else {
            $this->insert();
        }
    }
    public function is_valid() {
        if(!$this->Datum) return false;
        if(!$this->Name) return false;
        return true;
    }
    protected function insert() {
        $sql = sprintf('INSERT INTO `MVD`.`Termine` (`Datum`, `Uhrzeit`, `Uhrzeit2`, `Name`, `Beschreibung`, `Auftritt`, `Ort1`, `Ort2`, `Ort3`, `Ort4`, `published`) VALUES ("%s", %s, %s, "%s", "%s", "%d", "%s", "%s", "%s", "%s", "%d");',
        mysqli_real_escape_string($GLOBALS['conn'], $this->Datum),
        $this->Uhrzeit == '' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Uhrzeit)."\"",
        $this->Uhrzeit2 == '' ? 'NULL': "\"".mysqli_real_escape_string($GLOBALS['conn'], $this->Uhrzeit2)."\"",
        mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Beschreibung),
        $this->Auftritt,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Ort1),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Ort2),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Ort3),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Ort4),
        $this->published
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if(!$dbr) return false;
        $this->_data['Index'] = mysqli_insert_id($GLOBALS['conn']);
        return true;
    }
    protected function update() {
        $sql = sprintf('UPDATE `MVD`.`Termine` SET `Datum` = "%s", `Uhrzeit` = "%s", `Uhrzeit2` = "%s", `Name` = "%s", `Beschreibung` = "%s", `Auftritt` = "%d", `Ort1` = "%s", `Ort2` = "%s", `Ort3` = "%s", `Ort4` = "%s", `published` = "%d" WHERE `Index` = "%d";',
        mysqli_real_escape_string($GLOBALS['conn'], $this->Datum),
        $this->Uhrzeit == 'NULL' ? 'NULL': mysqli_real_escape_string($GLOBALS['conn'], $this->Uhrzeit),
        $this->Uhrzeit2 == 'NULL' ? 'NULL': mysqli_real_escape_string($GLOBALS['conn'], $this->Uhrzeit2),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Name),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Beschreibung),
        $this->Auftritt,
        mysqli_real_escape_string($GLOBALS['conn'], $this->Ort1),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Ort2),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Ort3),
        mysqli_real_escape_string($GLOBALS['conn'], $this->Ort4),
        $this->published,
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if(!$dbr) return false;
        return true;
    }
    public function delete() {
        if(!$this->Index) return false;
        $sql = sprintf('DELETE FROM `MVD`.`Termine` WHERE `Index` = "%d";',
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        if(!$dbr) return false;

        $sql = sprintf('DELETE FROM `MVD`.`Meldungen` WHERE `Termin` = "%d";',
        $this->Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
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
        $sql = sprintf('SELECT * FROM `MVD`.`Termine` WHERE `Index` = "%d";',
        $Index
        );
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        $row = mysqli_fetch_array($dbr);
        if(is_array($row)) {
            $this->fill_from_array($row);
        }
        if(isset($_SESSION['userid'])) {
            $sql = sprintf('SELECT `Wert` FROM `MVD`.`Meldungen` WHERE `Termin` = "%d" AND `User` = "%d";',
            $Index,
            $_SESSION['userid']
            );
            $dbr = mysqli_query($GLOBALS['conn'], $sql);
            $row = mysqli_fetch_array($dbr);
            if(is_array($row)) {
                $this->fill_from_array($row);
            }
        }
    }
    public function printTableLine() {
        if($this->Auftritt) {
            echo "<tr class=\"w3-lime\">\n";
        }
        else {
            echo "<tr class=\"w3-khaki\">\n";            
        }
        echo "  <td>".germanDate($this->Datum, 0)."</td>\n";
        echo "  <td>".$this->Uhrzeit."</td>\n";
        echo "  <td>".$this->Uhrzeit2."</td>\n";
        echo "  <td>".$this->Name."</td>\n";
        echo "  <td>".$this->Beschreibung."</td>\n";
        echo "  <td>".$this->Ort1."</td>\n";
        echo "  <td>".$this->Ort2."</td>\n";
        echo "  <td>".$this->Ort3."</td>\n";
        echo "  <td>".$this->Ort4."</td>\n";
        echo "  <td>".bool2string($this->published)."</td>\n";
        echo "</tr>\n";
    }
    public function printBasicTableLine() {
        if($this->Wert) {
            echo "<div class=\"w3-row w3-hover-gray w3-padding w3-mobile w3-border-bottom w3-border-black ";
            switch($this->Wert) {
            case 1:
                echo "w3-highway-green";
                break;
            case 2:
                echo "w3-highway-red";
                break;
            case 3:
                echo "w3-highway-blue";
                break;
            default:
                echo "w3-pale-yellow";
            }
            echo "\">\n";            
        }
        else if($this->Auftritt) {
            echo "<div class=\"w3-row w3-hover-gray w3-padding w3-pale-yellow w3-mobile w3-border-bottom w3-border-black\">\n";            
        }
        else {
            echo "<div class=\"w3-row w3-hover-gray w3-padding w3-light-pale-green w3-mobile w3-border-bottom w3-border-black\">\n";            
        }
        echo "  <div onclick=\"document.getElementById('id".$this->Index."').style.display='block'\" class=\"w3-col l3 w3-container\"><b>".$this->Name."</b></div>\n";
        echo "  <div class=\"w3-col l3 w3-container\">".germanDate($this->Datum, 1).", ".sql2time($this->Uhrzeit)." - ".sql2time($this->Uhrzeit2)."</div>\n";
        echo "  <div class=\"w3-col l3 w3-container\">".$this->Ort1."</div>\n";
        ?>
        <div class="w3-col l3 w3-row w3-mobile">
        <form action="" method="POST">
        <input type="hidden" name="Index" value="<?php echo $this->Index; ?>">
        <button class="w3-btn<?php if($this->Wert > 1) echo " w3-opacity"; ?> w3-border w3-border-black w3-margin-left w3-margin-top w3-margin-right w3-green w3-center w3-col s3 m3 l2" type="submit" name="meldung" value="1">&#10004;</button>
        <button class="w3-btn<?php if($this->Wert == 1 || $this->Wert == 3 ) echo " w3-opacity"; ?> w3-border w3-border-black w3-margin-top w3-red w3-center w3-col s3 m3 l2" type="submit" name="meldung" value="2">&#10008;</button>
        <button class="w3-btn<?php if($this->Wert == 1 || $this->Wert == 2 ) echo " w3-opacity"; ?> w3-border w3-border-black w3-margin-left w3-margin-top w3-blue w3-center w3-col s3 m3 l2" type="submit" name="meldung" value="3"><b>?</b></button>
        </form>
        </div>
        </div>


        <div id="id<?php echo $this->Index; ?>" class="w3-modal">
        <div class="w3-modal-content">
        <header class="w3-container w3-teal"> 
      <span onclick="document.getElementById('id<?php echo $this->Index; ?>').style.display='none'" 
      class="w3-button w3-display-topright">&times;</span>
      <h2><?php echo $this->Name; ?></h2>
    </header>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l3">Datum:</div><div class="w3-col l9"><b><?php echo germanDate($this->Datum, 1); ?></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l3">Beginn:</div><div class="w3-col l9"><b><?php echo sql2time($this->Uhrzeit); ?></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l3">Ende:</div><div class="w3-col l9"><b><?php echo sql2time($this->Uhrzeit2); ?></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l3">Beschreibung:</div><div class="w3-col l9"><b><?php echo $this->Beschreibung; ?></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l3">Ort:</div><div class="w3-col l9"><b><?php echo $this->Ort1; ?></b><br><?php echo $this->Ort2; ?><br><?php echo $this->Ort3; ?><br><?php echo $this->Ort4; ?></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l3">Auftritt:</div><div class="w3-col l9"><b><?php echo bool2string($this->Auftritt); ?></b></div>
    </div>
    <div class="w3-container w3-row w3-margin">
      <div class="w3-col l3">sichtbar:</div><div class="w3-col l9"><b><?php echo bool2string($this->published); ?></b></div>
    </div>
      <form class="w3-center w3-bar w3-mobile" action="new-termin.php" method="POST">
      <button class="w3-button w3-center w3-mobile w3-block w3-teal" type="submit" name="id" value="<?php echo $this->Index; ?>">bearbeiten</button>
      </form>
      </div>
      </div>
        <?php
    }
    public function printResponseLine() {
        ?>
        <div class="w3-container w3-mobile w3-lime">
          <b><?php echo $this->Name; ?></b>
        </div>
        <?php
        $sql = "SELECT * FROM `Register` ORDER BY `Sortierung`;";
        $dbr = mysqli_query($GLOBALS['conn'], $sql);
        while($row = mysqli_fetch_array($dbr)) {
        $sql = sprintf("SELECT * FROM `Meldungen`
INNER JOIN (SELECT `Index` AS `uIndex`, `Vorname`, `Nachname`, `Instrument` FROM `User`) `User` ON `User` = `uIndex`
INNER JOIN (SELECT `Index` AS `iIndex`, `Register` FROM `Instrument`) `Instrument` ON `Instrument` = `iIndex`
INNER JOIN (SELECT `Index` AS `rIndex`, `Name` AS `rName`, `Sortierung` FROM `Register`) `Register` ON `Register` = `rIndex`
WHERE `Termin` = '%d'
AND `rIndex` = '%d'
ORDER BY `Sortierung`",
            $this->Index,
            $row['Index']
            );
        $dbr2 = mysqli_query($GLOBALS['conn'], $sql);
        $i=0;
        while($row2 = mysqli_fetch_array($dbr2)) {
        /* echo "user = ".$row2['Vorname']." register = ".$row2['rName']."<br>"; */
        $i++;
    }
        echo "<div class=\"w3-container\">".$row['Name']." ".$i."</div>";
    }
    }
};
?>
