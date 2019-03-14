<!DOCTYPE html>
<html lang="de">
  <head>
      <meta name="viewport" content="width=device-width, initial-scale=1">      
      <link rel="stylesheet" href="styles/MVD.css">
      <link rel="stylesheet" href="styles/w3.css">
      <?php
          include 'include.php';
      ?>
      <!-- successfully included php libraries -->
      <?php
        mysqli_select_db($GLOBALS['conn'], $config['database']) or die(mysqli_error($conn));
      ?>
      <!-- successfully connected to MySQL database -->
      <?php
          if(!loggedIn()) {
              ?>
              <meta http-equiv="refresh" content="2; URL='login.php'" />
              <?php
              die("<div class=\"w3-panel w3-red\"><h2>Nicht eingeloggt...</h2></div>");
          }
      ?>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title><?php echo $commonStrings['WebSiteName']; ?></title>
  </head>
  <body>
<?php
include "common/nav.php";
?>