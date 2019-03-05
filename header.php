<!DOCTYPE html>
<html lang="de">
  <head>
      <link rel="stylesheet" href="MVD.css">
      <?php
          include 'include.php';
      ?>
      <!-- successfully included php libraries -->
      <?php
          $conn=mysqli_connect($config['server'], $config['user'], $config['password']) or die (mysqli_error($conn));
          mysqli_select_db($conn, $config['database']) or die(mysqli_error($conn));
      ?>
      <!-- successfully connected to MySQL database -->
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title><?php echo $commonStrings['WebSiteName']; ?></title>
  </head>
  <body>
