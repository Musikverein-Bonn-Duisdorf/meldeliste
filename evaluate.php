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
$sql = sprintf('SELECT DATE(`Timestamp`) AS `LogDate`, COUNT(CASE WHEN `Type` = 0 THEN 1 END) AS `NumLogs0`, COUNT(CASE WHEN `Type` = 1 THEN 1 END) AS `NumLogs1`, COUNT(CASE WHEN `Type` = 2 THEN 1 END) AS `NumLogs2`, COUNT(CASE WHEN `Type` = 3 THEN 1 END) AS `NumLogs3`, COUNT(CASE WHEN `Type` = 4 THEN 1 END) AS `NumLogs4`, COUNT(CASE WHEN `Type` = 5 THEN 1 END) AS `NumLogs5`, COUNT(CASE WHEN `Type` = 6 THEN 1 END) AS `NumLogs6`, COUNT(CASE WHEN `Type` = 7 THEN 1 END) AS `NumLogs7` FROM  `%sLog` WHERE `Type` < 6 GROUP BY DATE(`Timestamp`) ORDER BY `LogDate`;',
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
    google.charts.load('current', {packages: ['corechart', 'line']});
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
include "common/footer.php";
?>
