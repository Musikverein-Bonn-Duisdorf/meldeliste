<?php
session_start();
?>
<!DOCTYPE html>
<html lang="de">
  <head>
      <meta name="viewport" content="width=device-width, initial-scale=1">      
      <link rel="stylesheet" href="styles/w3.css">
	<link rel="stylesheet" href="styles/w3-colors-highway.css">
      <?php
          include 'common/include.php';
      ?>
      <link rel="icon" href="<?php echo $GLOBALS['optionsDB']['favicon']; ?>" type="image/x-icon">
      <!-- successfully included php libraries -->
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title><?php echo $optionsDB['WebSiteName']; ?></title>
  </head>
  <body class="<?php echo $GLOBALS['optionsDB']['colorBackground']; ?>">
      <div class="w3-container <?php echo $optionsDB['colorTitle']; ?>">
<h1><?php echo $optionsDB['WebSiteName']; ?></h1>
</div>
<meta http-equiv="refresh" content="3; URL='login.php'" />
  <div class="w3-panel w3-mobile w3-center <?php echo $GLOBALS['optionsDB']['colorSuccess']; ?>"><h2>Logout erfolgreich.</h2></div>
<?php
    if($_SESSION['userid']) {
        $logentry = new Log;
        $logentry->info("Logout.");
    }
session_destroy();
include "common/footer.php";
?>
