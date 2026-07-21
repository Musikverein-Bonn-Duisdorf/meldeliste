<?php
/**
 * Shared HTML error page (MELD-122). No DB bootstrap — safe for 500/outages.
 * Invoked via errors/403.php, 404.php, 500.php or ?code=
 */
$code = isset($_GET['code']) ? (int)$_GET['code'] : 404;
if (!in_array($code, array(403, 404, 500), true)) {
    $code = 404;
}
http_response_code($code);

$titles = array(
    403 => 'Zugriff verweigert',
    404 => 'Seite nicht gefunden',
    500 => 'Interner Fehler',
);
$messages = array(
    403 => 'Du hast keine Berechtigung, diese Seite aufzurufen.',
    404 => 'Die angeforderte Seite existiert nicht oder wurde verschoben.',
    500 => 'Es ist ein Fehler aufgetreten. Bitte versuche es später erneut.',
);

$siteName = 'Meldeliste';
$homeUrl = '../';
$title = $titles[$code];
$message = $messages[$code];
?><!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo htmlspecialchars($code . ' — ' . $title . ' | ' . $siteName, ENT_QUOTES, 'UTF-8'); ?></title>
  <link rel="stylesheet" href="../styles/w3.css">
  <link rel="stylesheet" href="../styles/w3-colors-highway.css">
  <link rel="stylesheet" href="../styles/w3-color-mvd.css">
  <link rel="stylesheet" href="../styles/custom.css">
</head>
<body class="w3-light-grey">
  <div class="w3-container w3-indigo">
    <h1 style="margin:0.5rem 0;font-size:1.5rem;"><?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?></h1>
  </div>
  <div class="w3-container w3-padding-32">
    <div class="w3-card w3-white w3-padding-large" style="max-width:36rem;margin:2rem auto;">
      <p class="w3-large w3-text-grey" style="margin:0 0 .5rem;"><?php echo (int)$code; ?></p>
      <h2 style="margin-top:0;"><?php echo htmlspecialchars($title, ENT_QUOTES, 'UTF-8'); ?></h2>
      <p><?php echo htmlspecialchars($message, ENT_QUOTES, 'UTF-8'); ?></p>
      <p><a class="w3-button w3-border" href="<?php echo htmlspecialchars($homeUrl, ENT_QUOTES, 'UTF-8'); ?>">Zur Startseite</a></p>
    </div>
  </div>
</body>
</html>
