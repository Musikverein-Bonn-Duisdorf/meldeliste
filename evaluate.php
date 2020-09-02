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
<?php
$now = date("Y-m-d");
$sql = sprintf('SELECT DATE(`Timestamp`) AS `LogDate`, COUNT(CASE WHEN `Type` = 0 THEN 1 END) AS `NumLogs0`, COUNT(CASE WHEN `Type` = 1 THEN 1 END) AS `NumLogs1`, COUNT(CASE WHEN `Type` = 2 THEN 1 END) AS `NumLogs2`, COUNT(CASE WHEN `Type` = 3 THEN 1 END) AS `NumLogs3`, COUNT(CASE WHEN `Type` = 4 THEN 1 END) AS `NumLogs4`, COUNT(CASE WHEN `Type` = 5 THEN 1 END) AS `NumLogs5`, COUNT(CASE WHEN `Type` = 6 THEN 1 END) AS `NumLogs6`, COUNT(CASE WHEN `Type` = 7 THEN 1 END) AS `NumLogs7` FROM  `%sLog` GROUP BY DATE(`Timestamp`) ORDER BY `LogDate` DESC LIMIT 365;',
$GLOBALS['dbprefix']
);
$dbr = mysqli_query($conn, $sql);
sqlerror();
$plotline = "[";
while($row = mysqli_fetch_array($dbr)) {
    $plotline = $plotline."[".string2gDate($row['LogDate']).",".$row['NumLogs0'].",".$row['NumLogs1'].",".$row['NumLogs2'].",".$row['NumLogs3'].",".$row['NumLogs4'].",".$row['NumLogs5'].",".$row['NumLogs6'].",".$row['NumLogs7']."],\n";
}
$plotline = $plotline."]";

?>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script>
    google.charts.load('current', {packages: ['corechart', 'line'], 'language': 'de'});
google.charts.setOnLoadCallback(drawBasic);

function drawBasic() {
    var data = new google.visualization.DataTable();
    data.addColumn('date', 'Datum');
    data.addColumn('number', 'FATAL');
    data.addColumn('number', 'ERROR');
    data.addColumn('number', 'WARNING');
    data.addColumn('number', 'DBDELETE');
    data.addColumn('number', 'DBINSERT');
    data.addColumn('number', 'DBUPDATE');
    data.addColumn('number', 'EMAIL');
    data.addColumn('number', 'INFO');
    
    data.addRows(<?php echo $plotline; ?>);

    var options = {
        hAxis: {
            title: 'Datum'
        },
        vAxis: {
            title: 'Anzahl'
        },
        height: 450,
        timeline: {
          groupByRowLabel: true
        },
        bar: {
          groupWidth: '100%',
        },
        isStacked: true,
    };

    var chart = new google.visualization.ColumnChart(document.getElementById('chart_div'));

    chart.draw(data, options);
}

     </script>
                                     
  <div id="chart_div"></div>


<?php
    $sql = sprintf("SELECT * FROM `%sTermine` WHERE `Shifts` = 0 AND `published` = 1 AND `Datum` > NOW() - INTERVAL 365 DAY ORDER BY `Datum`;",
    $GLOBALS['dbprefix']
    );
$dbr = mysqli_query($GLOBALS['conn'], $sql);
sqlerror();
while($row = mysqli_fetch_array($dbr)) {
    $t = new Termin;
    $t->load_by_id($row['Index']);
    /* $str=$str."[".string2gDate($t->Datum).", ".($t->getMeldungRatio()*100)."],\n"; */
    $yes = $t->getMeldungenVal(1);
    $no = $t->getMeldungenVal(2);
    $maybe = $t->getMeldungenVal(3);
    $all = $yes + $no + $maybe;
    $str=$str."[".string2gDate($t->Datum).", ".$yes.", ".$no.", ".$maybe."],\n";
}
    ?>

    <script>

google.charts.load('current', {packages: ['corechart'], 'language': 'de'});
google.charts.setOnLoadCallback(drawBasic);

function drawBasic() {
    var data = new google.visualization.DataTable();
    data.addColumn('date', 'Datum');
    data.addColumn('number', 'ja');
    data.addColumn('number', 'nein');
    data.addColumn('number', 'vielleicht');
    
    data.addRows(<?php echo "[".$str."]"; ?>);

    var options = {
        hAxis: {
            title: 'Datum'
        },
        vAxis: {
          title: 'Meldungen',
          minValue: 0,
          /* maxValue: 100, */
        },
        height: 450,
        timeline: {
          groupByRowLabel: true
        },
        bar: {
          groupWidth: '100%',
        },
        legend: 'true',
        isStacked: true
    };

    var chart = new google.visualization.ColumnChart(document.getElementById('chart_rate'));

    chart.draw(data, options);
}

     </script>
<div id="chart_rate"></div>

<?php
include "common/footer.php";
?>
