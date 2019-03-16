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
        mysqli_select_db($GLOBALS['conn'], $config['database']) or die(mysqli_error($conn));
      ?>
      <!-- successfully connected to MySQL database -->
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title><?php echo $commonStrings['WebSiteName']; ?></title>
  </head>
<body>
  <div class="w3-container <?php echo $titleColor; ?>">
    <h1><?php echo $commonStrings['WebSiteName']; ?></h1>
  </div>
  <?php
    if(isset($_POST['triggerlogin'])) {
        $r=validateUser($_POST['login'], $_POST['password']);
        if(!$r) {
		?>
	      <div class="w3-panel w3-red"><h2>Login fehlgeschlagen.</h2></div>
	      <?php
        }
    }
          if(loggedIn()) {
              ?>
              <meta http-equiv="refresh" content="0; URL='index.php'" />
              <?php
              die("<div class=\"w3-panel w3-green\"><h2>Login erfolgreich.</h2></div>");
		}
		?>
  <div class="w3-panel w3-mobile w3-center w3-col s3 l4">
  </div>
  <div class="w3-panel w3-mobile w3-center w3-border w3-col s6 l4">
  <div class="w3-panel w3-dark-gray w3-mobile">
    <h2>Login</h2>
  </div>
  <form class="w3-container" action="" method="POST">
    
    <label>Benutzer</label>
    <input class="w3-input w3-border w3-light-gray w3-margin-bottom w3-mobile" type="text" name="login">
    
    <label>Passwort</label>
    <input class="w3-input w3-border w3-light-gray w3-margin-bottom w3-mobile" type="password" name="password">
    
    <button class="w3-btn w3-blue w3-border w3-mobile" type="submit" name="triggerlogin">Login</button>
    
  </form>
  </div>
  <div class="w3-panel w3-mobile w3-center w3-col s3 l4">
  </div>
<?php
include "common/footer.php";
?>
