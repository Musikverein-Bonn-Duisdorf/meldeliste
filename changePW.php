<?php
session_start();
include 'common/include.php';

$error = '';
$userid = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;

if(isset($_POST['pw1']) && isset($_POST['pw2'])) {
    $pw1 = (string)$_POST['pw1'];
    $pw2 = (string)$_POST['pw2'];
    $logentry = new Log;
    $logentry->info(sprintf(
        "Passwort ändern gestartet | User-ID: <b>%d</b>",
        $userid
    ));
    if($pw1 === '' || $pw2 === '') {
        $error = 'Bitte beide Passwortfelder ausfüllen.';
        $logentry = new Log;
        $logentry->error(sprintf(
            "Passwort ändern abgebrochen: leere Felder | User-ID: <b>%d</b>",
            $userid
        ));
    }
    elseif($pw1 !== $pw2) {
        $error = 'Die Passwörter stimmen nicht überein.';
        $logentry = new Log;
        $logentry->error(sprintf(
            "Passwort ändern abgebrochen: Passwörter stimmen nicht überein | User-ID: <b>%d</b>",
            $userid
        ));
    }
    elseif($userid < 1) {
        $error = 'Nicht eingeloggt. Bitte erneut anmelden.';
        $logentry = new Log;
        $logentry->error("Passwort ändern abgebrochen: keine gültige Session-User-ID.");
    }
    else {
        try {
            $user = new User;
            $user->load_by_id($userid);
            if(!$user->passwd($pw1)) {
                $error = 'Passwort konnte nicht gespeichert werden. Bitte Loginname prüfen oder Admin kontaktieren.';
            }
            else {
                ?>
<!DOCTYPE html>
<html lang="de">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="refresh" content="0; URL='index.php'" />
    <title><?php echo htmlspecialchars($GLOBALS['optionsDB']['WebSiteName']); ?></title>
  </head>
  <body>
    <div class="w3-panel <?php echo $GLOBALS['optionsDB']['colorLogWarning']; ?>"><h2>Passwort geändert...</h2></div>
  </body>
</html>
<?php
                die();
            }
        }
        catch(Throwable $e) {
            $error = 'Unerwarteter Fehler beim Speichern des Passworts.';
            $logentry = new Log;
            $logentry->error(sprintf(
                "Passwort ändern Exception | User-ID: <b>%d</b>, Fehler: <b>%s</b>",
                $userid,
                htmlspecialchars($e->getMessage())
            ));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">
  <head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="styles/w3.css">
    <link rel="stylesheet" href="styles/w3-colors-highway.css">
    <meta charset="utf-8">
    <link rel="icon" href="<?php echo $GLOBALS['optionsDB']['favicon']; ?>" type="image/x-icon">
    <title><?php echo htmlspecialchars($optionsDB['WebSiteName']); ?></title>
  </head>
  <body class="<?php echo $GLOBALS['optionsDB']['colorBackground']; ?>">
    <div class="w3-container <?php echo $optionsDB['colorTitle']; ?>">
      <h1><?php echo htmlspecialchars($optionsDB['WebSiteName']); ?></h1>
    </div>
    <div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
      <h2>Passwort ändern</h2>
    </div>
    <div class="w3-panel w3-mobile w3-center w3-col s3 l4">
    </div>
    <div class="w3-panel w3-mobile w3-center w3-border w3-col s6 l4">
<?php if($error !== '') { ?>
      <div class="w3-panel w3-red w3-padding"><b><?php echo htmlspecialchars($error); ?></b></div>
<?php } ?>
      <form class="w3-container w3-margin" action="" method="POST">
        <label>neues Passwort</label>
        <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="pw1" type="password" placeholder="*****" required>
        <label>neues Passwort wiederholen</label>
        <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="pw2" type="password" placeholder="*****" required>
        <input class="w3-btn <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-border w3-margin w3-mobile" type="submit" name="insert" value="speichern">
      </form>
    </div>
    <div class="w3-panel w3-mobile w3-center w3-col s3 l4">
    </div>
<?php
include "common/footer.php";
?>
