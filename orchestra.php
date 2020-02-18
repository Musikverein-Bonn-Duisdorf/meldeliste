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
<svg width="1500" height="1000">
<?php
$registerColor = ["#ff0000", "#00ff00", "#0000ff", "#afafaf", "#0ff0f0", "#0fa0a0", "#a00f00", "#ff00ff", "#f00000", "#00f000", "#0000f0"];
$registerArcMin$registerArcMin = [0, 0, 180, 0, 0, 35, 180, 0, 25, 0, 77];
$register['ArcMax'] = [0, 90, 90, 90, 90, 160, 135, 90, 90, 45, 135];


$sql = sprintf('SELECT * FROM `%sRegister` ORDER BY `Sortierung`;',
$GLOBALS['dbprefix']
);
$dbregister = mysqli_query($GLOBALS['conn'], $sql);
sqlerror();
$k=0;
$i=0;
$j=0;
while($register = mysqli_fetch_array($dbregister)) {
    $r = new Register;
    $r->load_by_id($register['Index']);
    
    $sql = sprintf('SELECT * FROM `%sInstrument` WHERE `Register` = %d ORDER BY `Sortierung`;',
    $GLOBALS['dbprefix'],
    $r->Index
    );
    $dbinstrument = mysqli_query($GLOBALS['conn'], $sql);
    sqlerror();
    while($instrument = mysqli_fetch_array($dbinstrument)) {
        $sql = sprintf('SELECT * FROM `%sUser` WHERE `Instrument` = %d ORDER BY `Nachname`;',
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
            $radius = $register['Row']*75+50*$j+125;
            $arc = $register['ArcMin']+$k*($register['ArcMax']-$register['ArcMin'])/abs($register['ArcMax']-$register['ArcMin'])*50/(2*pi()*$radius)*360;
            if($register['ArcMin'] < $register['ArcMax']) {
                if($arc+20/(2*pi()*$radius)*360 >=$register['ArcMax']) {
                    $j++;
                    $radius = $register['Row']*75+50*$j+125;
                    $k=0;
                }
            }
            elseif($register['ArcMin'] > $register['ArcMax']) {
                if($arc-20/(2*pi()*$radius)*360 <=$register['ArcMax']) {
                    $j++;
                    $radius = $register['Row']*75+50*$j+125;
                    $k=0;
                }
            }
            $arc = $register['ArcMin']+$k*($register['ArcMax']-$register['ArcMin'])/abs($register['ArcMax']-$register['ArcMin'])*50/(2*pi()*$radius)*360;
            }
            $x = 750-$radius*cos($arc/180*pi());
            $y = 50+$radius*sin($arc/180*pi());
            echo "<!-- ".$radius." ".$arc." -->";
            
            echo "<circle cx=\"".$x."\" cy=\"".$y."\" r=\"20\" stroke=\"black\" stroke-width=\"2\" fill=\"".$registerColor[$i]."\" />\n";
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
