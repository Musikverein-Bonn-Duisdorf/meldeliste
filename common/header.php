<!DOCTYPE html>
<html lang="de">
  <head>
      <?php
          if(!headers_sent()) {
              header('Content-Type: text/html; charset=utf-8');
          }
      ?>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <?php
          include_once 'include.php';
          // Cache-Bust wie in footer.php: Release-Hash + filemtime erzwingen Reload nach Update
          $assetV = isset($GLOBALS['version']['Hash']) ? $GLOBALS['version']['Hash'] : '0';
          $cssUrl = function ($rel) use ($assetV) {
              $mtime = @filemtime(__DIR__ . '/../' . $rel);
              return htmlspecialchars($rel . '?' . $assetV . '-' . $mtime, ENT_QUOTES, 'UTF-8');
          };
      ?>
      <link rel="stylesheet" href="<?php echo $cssUrl('styles/w3.css'); ?>">
      <link rel="stylesheet" href="<?php echo $cssUrl('styles/w3-colors-highway.css'); ?>">
      <link rel="stylesheet" href="<?php echo $cssUrl('styles/w3-color-mvd.css'); ?>">
      <link rel="stylesheet" href="<?php echo $cssUrl('styles/custom.css'); ?>">
      <link rel="stylesheet" href="<?php echo $cssUrl('styles/fontawesome-free-6.4.2-web/css/fontawesome.css'); ?>">
      <link rel="stylesheet" href="<?php echo $cssUrl('styles/fontawesome-free-6.4.2-web/css/brands.css'); ?>">
      <link rel="stylesheet" href="<?php echo $cssUrl('styles/fontawesome-free-6.4.2-web/css/solid.css'); ?>">
      <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
      <?php echo renderConfigColorCss(); ?>
      <?php echo renderPermissionGroupColorCss(); ?>
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
          // Proxy für AJAX-Modals (getModal) über die Seitenaufrufe hinweg merken
          if(isset($_POST['proxy']) && (int)$_POST['proxy'] > 0) {
              $_SESSION['proxy'] = (int)$_POST['proxy'];
          }
          else {
              unset($_SESSION['proxy']);
          }
      ?>
      <title><?php echo $optionsDB['WebSiteName']; ?></title>
  </head>
  <body class="<?php echo $GLOBALS['optionsDB']['colorBackground']; ?> app-layout">
<?php
include "common/nav.php";
$GLOBALS['mlHeaderRendered'] = true;
?>
