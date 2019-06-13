<!DOCTYPE html>
<html lang="de">
  <head>
      <meta name="viewport" content="width=device-width, initial-scale=1">      
      <link rel="stylesheet" href="styles/MVD.css">
      <link rel="stylesheet" href="styles/w3.css">
      <link rel="stylesheet" href="styles/w3-colors-highway.css">
      <link rel="stylesheet" href="styles/w3-colors-fashion.css">
      <link rel="icon" href="<?php echo $GLOBALS['site']['favicon']; ?>" type="image/x-icon">
      <link rel="apple-touch-icon" sizes="120x120"  href="{uploads_url}/designs/MVD-responsive/apple-touch-icon-120x120-precomposed.png" title="apple touch icon"/>
      <link rel="apple-touch-icon" sizes="152x152"  href="{uploads_url}/designs/MVD-responsive/apple-touch-icon-152x152-precomposed.png" title="apple touch icon"/>
      <?php
          include 'include.php';
      ?>
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
              die("<div class=\"w3-panel ".$commonColors['notLoggedIn']."\"><h2>Nicht eingeloggt...</h2></div>");
          }
          if($_SESSION['singleUsePW']) {
              ?>
              <meta http-equiv="refresh" content="0; URL='changePW.php'" />
              <?php
              die("<div class=\"w3-panel ".$commonColors['changePWMsg']."\"><h2>Passwort &auml;ndern...</h2></div>");
          }
      ?>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title><?php echo $site['WebSiteName']; ?></title>
  </head>
  <body class="<?php echo $GLOBALS['commonColors']['bgcolor']; ?>">
<?php
include "common/nav.php";
?>
