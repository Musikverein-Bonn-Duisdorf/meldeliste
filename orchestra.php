<?php
session_start();
$_SESSION['page']='evaluate';
$_SESSION['adminpage']=true;
include "common/header.php";
requireAdmin();
?>
<div id="header" class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
    <h2>Datenauswertung</h2>
    </div>
    <svg width="1000" height="600">
<?php

    $termin = new Termin;
$termin->load_by_id(60);
    
    $sql = sprintf('SELECT * FROM `%sRegister` ORDER BY `Row`;',
    $GLOBALS['dbprefix']
    );
$dbregister = mysqli_query($GLOBALS['conn'], $sql);
sqlerror();
$k=0;
$i=0;
$j=0;
$lastrow=0;
$lmaxradius = array();
$rmaxradius = array();
array_push($lmaxradius, 0);
array_push($rmaxradius, 0);
while($register = mysqli_fetch_array($dbregister)) {
    if($lastrow != $register['Row']) {
        array_push($lmaxradius, $lmaxradius[count($lmaxradius)-1]+60);
        array_push($rmaxradius, $rmaxradius[count($rmaxradius)-1]+60);
    }
    $lastrow = $register['Row'];
    if($register['Row'] > 0) {
        if($register['ArcMin'] < 90) {
            $radius = $lmaxradius[$register['Row']-1]+60;
        }
        else {
            $radius = $rmaxradius[$register['Row']-1]+60;
        }
    }
    if($radius<150) {
        $radius = 150;
    }
    $r = new Register;
    $r->load_by_id($register['Index']);
    
    $sql = sprintf('SELECT * FROM `%sInstrument` WHERE `Register` = %d ORDER BY `Sortierung`;',
    $GLOBALS['dbprefix'],
    $r->Index
    );
    $dbinstrument = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    while($instrument = mysqli_fetch_array($dbinstrument)) {
        $sql = sprintf('SELECT * FROM `%sUser` WHERE `Instrument` = %d AND `Deleted` = 0 ORDER BY `Nachname`;',
        $GLOBALS['dbprefix'],
        $instrument['Index']
        );
        $dbuser = mysqli_query($GLOBALS['conn'], $sql);
        while($user = mysqli_fetch_array($dbuser)) {
            $u = new User;
            $u->load_by_id($user['Index']);
            if($register['Row']==0) {
                $radius=0;
                $arc=0;
            }
            else {
                $arc = $register['ArcMin']+$k*($register['ArcMax']-$register['ArcMin'])/abs($register['ArcMax']-$register['ArcMin'])*40/(2*pi()*$radius)*360;
                if($register['ArcMin'] < $register['ArcMax']) {
                    if($arc+20/(2*pi()*$radius)*360 >=$register['ArcMax']) {
                        $j++;
                        $radius += 40;
                        $k=0;
                    }
                }
                elseif($register['ArcMin'] > $register['ArcMax']) {
                    if($arc-20/(2*pi()*$radius)*360 <=$register['ArcMax']) {
                        $j++;
                        $radius += 40;
                        $k=0;
                    }
                }
                if($register['ArcMin'] < 90) {
                    if($radius > $lmaxradius[$register['Row']]) {
                        $lmaxradius[$register['Row']] = $radius;
                    }
                }
                else {
                    if($radius > $rmaxradius[$register['Row']]) {
                        $rmaxradius[$register['Row']] = $radius;
                    }
                }
                $arc = $register['ArcMin']+$k*($register['ArcMax']-$register['ArcMin'])/abs($register['ArcMax']-$register['ArcMin'])*40/(2*pi()*$radius)*360;
                }
                $x = 500-$radius*cos($arc/180*pi());
                $y = 40+$radius*sin($arc/180*pi());
                $m = $termin->getMeldungenByUser($u->Index);
                if(count($m)) {
                    $meldung = new Meldung;
                    $meldung->load_by_id($m);
                    switch($meldung->Wert) {
                    case 1:
                        $color = "#00ff00";
                        break;
                    case 2:
                        $color = "#ff0000";
                        break;
                    case 2:
                        $color = "#0000ff";
                        break;
                    }
                }
                else {
                    $color = "#ffffff";
                }
                
                echo "<circle cx=\"".$x."\" cy=\"".$y."\" r=\"18\" stroke=\"black\" stroke-width=\"2\" fill=\"".$color."\" />\n";
                /* echo "<circle cx=\"".$x."\" cy=\"".$y."\" r=\"18\" stroke=\"black\" stroke-width=\"2\" fill=\"".$register['Color']."\" />\n"; */
                echo "<text text-anchor=\"middle\" alignment-baseline=\"central\" fill=\"#000000\" font-size=\"10\" x=\"".$x."\" y=\"".$y."\">".$u->getShort()."</text>\n";

                $k++;
            }
        }
        $k=0;
        $j=0;
        $i++;
    }

?>
    </svg>
<?php
    include "common/footer.php";
?>
