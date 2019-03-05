<!DOCTYPE html>
<html lang="de">
  <head>
      <link rel="stylesheet" href="MVD.css">
      <?php
          include 'include.php';
      ?>
      <!-- successfully included php libraries -->
      <?php
        mysqli_select_db($GLOBALS['conn'], $config['database']) or die(mysqli_error($conn));
      ?>
      <!-- successfully connected to MySQL database -->
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title><?php echo $commonStrings['WebSiteName']; ?></title>
  </head>
  <body>
