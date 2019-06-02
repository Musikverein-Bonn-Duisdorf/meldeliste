<?php
session_start();
?>
<!DOCTYPE html>
<html lang="de">
  <head>
      <meta name="viewport" content="width=device-width, initial-scale=1">      
      <link rel="stylesheet" href="styles/MVD.css">
      <link rel="stylesheet" href="styles/w3.css">
      <?php
          include 'common/include.php';
      ?>
      <!-- successfully included php libraries -->
      <?php
        mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($conn));
      ?>
      <!-- successfully connected to MySQL database -->
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title><?php echo $commonStrings['WebSiteName']; ?></title>
  </head>
  <body>
      <div class="w3-container <?php echo $commonColors['Title']; ?>">
<h1><?php echo $commonStrings['WebSiteName']; ?></h1>
</div>
<meta http-equiv="refresh" content="3; URL='login.php'" />
  <div class="w3-panel w3-mobile w3-center w3-green"><h2>Logout erfolgreich.</h2></div>
<?php
$logentry = new Log;
$logentry->info("Logout.");
session_destroy();
include "common/footer.php";
?>
