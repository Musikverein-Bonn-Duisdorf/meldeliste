<!DOCTYPE html>
<html lang="de">
  <head>
      <meta name="viewport" content="width=device-width, initial-scale=1">      
      <link rel="stylesheet" href="styles/w3.css">
      <link rel="stylesheet" href="styles/w3-colors-highway.css">
      <link rel="stylesheet" href="styles/w3-colors-mvd.css">
      <link rel="stylesheet" href="styles/custom.css">
      <link rel="stylesheet" href="styles/fontawesome-free-6.4.2-web/css/fontawesome.css">
      <link rel="stylesheet" href="styles/fontawesome-free-6.4.2-web/css/brands.css">
      <link rel="stylesheet" href="styles/fontawesome-free-6.4.2-web/css/solid.css">
      <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
      <link rel="icon" href="imgs/MVDLogo32x32.png" sizes="32x32" />

	  <?php
          include 'include.php';
      ?>
      <link rel="icon" href="<?php echo $GLOBALS['optionsDB']['favicon']; ?>" type="image/x-icon">
      <!-- successfully included php libraries -->
      <?php
        mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($conn));
      ?>
      <!-- successfully connected to MySQL database -->
      <?php
          /* $_SESSION['userid'] = 1; */
          if(!loggedIn()) {
              ?>
              <meta http-equiv="refresh" content="2; URL='login.php'" />
              <?php
              die("<div class=\"w3-panel ".$optionsDB['colorLogWarning']."\"><h2>Nicht eingeloggt...</h2></div>");
          }
          if($_SESSION['singleUsePW']) {
              ?>
              <meta http-equiv="refresh" content="0; URL='changePW.php'" />
              <?php
              die("<div class=\"w3-panel ".$optionsDB['colorLogWarning']."\"><h2>Passwort &auml;ndern...</h2></div>");
          }
      ?>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title><?php echo $optionsDB['WebSiteName']; ?></title>
  </head>
  <body class="<?php echo $GLOBALS['optionsDB']['colorBackground']; ?>">
<?php
include "common/nav.php";
?>
