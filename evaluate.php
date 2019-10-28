<?php
session_start();
$_SESSION['page']='evaluate';
include "common/header.php";
requireAdmin();
?>
<div id="header" class="w3-container <?php echo $GLOBALS['commonColors']['titlebar']; ?>">
<h2>Datenauswertung</h2>
</div>
<?php
$now = date("Y-m-d");
$sql = sprintf('SELECT DATE(`Timestamp`) AS `LogDate`, COUNT(*) AS `NumLogs` FROM  `%sLog` WHERE `Type` < 7 GROUP BY DATE(`Timestamp`) ORDER BY `LogDate`;',
$GLOBALS['dbprefix']
);
$dbr = mysqli_query($conn, $sql);
sqlerror();
$plotline = "[";
while($row = mysqli_fetch_array($dbr)) {
    $plotline = $plotline."[".string2gDate($row['LogDate']).",".$row['NumLogs']."],\n";
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
    data.addColumn('number', 'Logs');
    
    data.addRows(<?php echo $plotline; ?>);

    var options = {
        hAxis: {
            title: 'Datum'
        },
        vAxis: {
            title: 'Anzahl Logs'
        },
	height: 450,
        timeline: {
          groupByRowLabel: true
        }
    };

    var chart = new google.visualization.LineChart(document.getElementById('chart_div'));

    chart.draw(data, options);
}

     </script>
                                     
  <div id="chart_div"></div>
<?php
include "common/footer.php";
?>
