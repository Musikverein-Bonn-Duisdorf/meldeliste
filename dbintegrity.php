<?php
function DBCheckIntegrity() {
    $str="";
    $path = 'config/DBconfig.json';
    $jsonString = file_get_contents($path);
    $jsonData = json_decode($jsonString, true);
    
    foreach($jsonData as $tableName => $val) { // databases
        $SQL = new SQLtable($tableName);

        $div = new div;
        $div->class="w3-container";
        $str.=$div->open();

        $name = new div;
        $name->class="w3-padding";
        $name->bold();
        $name->body="<i class=\"fa-solid fa-database\"></i> ".$tableName;
        
        if($SQL->exists()) {
            $name->class="w3-green";
        }
        else {
            if($SQL->create()) {
                $name->class="w3-yellow";
            }
            else {
                $name->class="w3-red";
            }
        }

        $str.=$name->print();
        $content = new div;
        
        
        foreach($val as $columnName => $subval) { // columns            
            $row = new div;
            $row->class="w3-row";
            $str.=$row->open();

            $content = new div;
            $content->class="w3-padding w3-hide-small w3-hide-medium";
            $content->col(1,1,1);
            $content->body="&nbsp;";
            $str.=$content->print();
            
            $content = new div;
            $content->class="w3-padding";
            $content->col(11,12,12);
            $content->bold();
            $content->body="<i class=\"fa-solid fa-table-columns\"></i> ".$columnName;

            $skip = false;
            if($SQL->columnExists($columnName)) {
                $content->class="w3-green w3-padding";
            }
            else {
                if($SQL->createColumn($columnName, $subval["Type"])) {
                    $content->class="w3-yellow";
                }
                else {
                    $content->class="w3-red";
                    $skip = true;
                }
            }

            $str.=$content->print();
            $str.=$row->close();

            $config = array();
            if($skip == false) {
                $config = $SQL->getColumnSetting($columnName);
            }

            foreach($subval as $optionkey => $optionval) { // column options
                $updated = false;
                if(strtoupper($config[$optionkey]) != strtoupper($optionval)) {
                    $config = $SQL->setColumnSetting($columnName, $optionkey, $optionval);
                    $updated = true;
                }
                $row = new div;
                $row->class="w3-row";
                $str.=$row->open();
                $content = new div;
                $content->class="w3-hide-small w3-hide-medium";
                $content->col(2,1,1);
                $content->body="&nbsp;";
                $str.=$content->print();

                $content = new div;
                /* $content->class="w3-padding"; */
                $content->col(1,6,6);
                $content->body="<i class=\"fa-solid fa-gear\"></i> ".$optionkey;
                $content->bold();
                if(strtoupper($config[$optionkey]) == strtoupper($optionval)) {
                    if($updated) $content->class="w3-yellow";
                    else $content->class="w3-green";
                }
                else {  
                    $content->class="w3-red";
                }

                $str.=$content->print();

                $content = new div;
                /* $content->class="w3-padding"; */
                $content->col(2,6,6);
                if(strtoupper($config[$optionkey]) == strtoupper($optionval)) {
                    if($updated) $content->class="w3-yellow";
                    else $content->class="w3-green";
                    $content->body=$optionval;
                }
                else {
                    $content->class="w3-red";
                    $content->body=$optionval." (".$config[$optionkey].")";
                }
                
                $str.=$content->print();
            $str.=$row->close();
            }
        }
        $str.=$div->close();
    }
    echo $str;
}
?>