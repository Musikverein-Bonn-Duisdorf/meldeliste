<?php
session_start();
// MELD-84: Termine-Seite in Home aufgegangen; Bookmarks und alte Form-Actions umleiten.
$code = (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') ? 307 : 302;
header('Location: index.php', true, $code);
exit;
