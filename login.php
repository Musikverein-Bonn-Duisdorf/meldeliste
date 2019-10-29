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
    <body class="<?php echo $GLOBALS['optionsDB']['ColorBackground']; ?>">
	<div class="w3-container <?php echo $optionsDB['colorTitle']; ?>">
	    <h1><?php echo $optionsDB['WebSiteName']; ?></h1>
	</div>
	<?php
    if(isset($_GET['alink'])) {
            validateLink($_GET['alink']);
    }
	if(isset($_POST['triggerlogin'])) {
            $r=validateUser($_POST['login'], $_POST['password']);
            if(!$r) {
	?>
	    <div class="w3-panel <?php echo $GLOBALS['commonColors']['notLoggedIn']; ?>"><h2>Login fehlgeschlagen.</h2></div>
	<?php
        }
	}
        if(loggedIn()) {

                if($_SESSION['singleUsePW']) {
?>
        <meta http-equiv="refresh" content="0; URL='changePW.php'" />
<?php
        die("<div class=\"w3-panel ".$GLOBALS['commonColors']['changePWMsg']."\"><h2>Passwort &auml;ndern...</h2></div>");
    }
            
        ?>
            <meta http-equiv="refresh" content="0; URL='index.php'" />
            <?php
            die("<div class=\"w3-panel ".$GLOBALS['commonColors']['success']."\"><h2>Login erfolgreich.</h2></div>");
	    }
	    ?>
	    <div class="w3-panel w3-mobile w3-center w3-col s3 l4">
	    </div>
	    <div class="w3-panel w3-mobile w3-center w3-border w3-col s6 l4">
                 <div class="w3-panel <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?> w3-mobile">
		    <h2>Login</h2>
		</div>
		<form class="w3-container" action="" method="POST">
		    
		    <label>Benutzer</label>
		    <input class="w3-input w3-border <?php echo $GLOBALS['commonColors']['inputs']; ?> w3-margin-bottom w3-mobile" type="text" name="login">
		    
		    <label>Passwort</label>
		    <input class="w3-input w3-border <?php echo $GLOBALS['commonColors']['inputs']; ?> w3-margin-bottom w3-mobile" type="password" name="password">
		    
		    <button class="w3-btn <?php echo $GLOBALS['commonColors']['submit']; ?> w3-border w3-mobile" type="submit" name="triggerlogin">Login</button>
		    
		</form>
	    </div>
	    <div class="w3-panel w3-mobile w3-center w3-col s3 l4">
	    </div>
	    <?php
	    include "common/footer.php";
	    ?>
