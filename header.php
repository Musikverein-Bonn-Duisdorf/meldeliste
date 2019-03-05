<!DOCTYPE html>
<html lang="de">
  <head>
      <link rel="stylesheet" href="MVD.css">
<?php
      try {
          include 'include.php';
      }
      catch(Exception $e) {
          echo 'Exception: ',  $e->getMessage(), "\n";
      }

try {
    $conn=mysqli_connect($config->server, $config->user, $config->password) or die (mysqli_error($conn));
    mysqli_select_db($conn, $config->database) or die(mysqli_error($conn));
}
catch(Exception $e) {
    echo 'Exception: ',  $e->getMessage(), "\n";
}          
?>
<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $commonStrings->WebSiteName; ?></title>
  </head>
  <body>
