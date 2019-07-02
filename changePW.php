<!DOCTYPE html>
<html lang="de">
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1">      
    <link rel="stylesheet" href="styles/MVD.css">
    <link rel="stylesheet" href="styles/w3.css">
    <!-- successfully included php libraries -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<?php
 session_start();
include 'common/include.php';
?>
    <link rel="icon" href="<?php echo $GLOBALS['site']['favicon']; ?>" type="image/x-icon">
<?php
mysqli_select_db($GLOBALS['conn'], $sql['database']) or die(mysqli_error($conn));
if(isset($_POST['pw1']) && isset($_POST['pw2'])) {
    $user = new User;
    if($_SESSION['userid'] > 0) {
        $user->load_by_id($_SESSION['userid']);
        $user->passwd($_POST['pw1']);
    }
?>
        <meta http-equiv="refresh" content="0; URL='index.php'" />
<?php
        die("<div class=\"w3-panel ".$GLOBALS['commonColors']['changePWMsg']."\"><h2>Passwort &auml;ndern...</h2></div>");
                  }
 ?>
    <title><?php echo $site['WebSiteName']; ?></title>
  </head>
  <body class="<?php echo $GLOBALS['commonColors']['bgcolor']; ?>">
    <div class="w3-container <?php echo $commonColors['Title']; ?>">
      <h1><?php echo $site['WebSiteName']; ?></h1>
    </div>
    <div class="w3-container <?php echo $GLOBALS['commonColors']['titlebar']; ?>">
      <h2>Passwort ändern</h2>
    </div>
    <div class="w3-panel w3-mobile w3-center w3-col s3 l4">
    </div>
    <div class="w3-panel w3-mobile w3-center w3-border w3-col s6 l4">
      <form class="w3-container w3-margin" action="" method="POST">
	<label>neues Passwort</label>
	<input class="w3-input w3-border <?php echo $GLOBALS['commonColors']['inputs']; ?> w3-margin-bottom w3-mobile" name="pw1" type="password" placeholder="*****">
	<label>neues Passwort wiederholen</label>
	<input class="w3-input w3-border <?php echo $GLOBALS['commonColors']['inputs']; ?> w3-margin-bottom w3-mobile" name="pw2" type="password" placeholder="*****">                                                                            	  <input class="w3-btn <?php echo $GLOBALS['commonColors']['submit']; ?> w3-border w3-margin w3-mobile" type="submit" name="insert" value="speichern">
      </form>
    </div>
    <div class="w3-panel w3-mobile w3-center w3-col s3 l4">
    </div>
<?php
include "common/footer.php";
?>
