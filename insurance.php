<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
header('Location: inventories.php?versichert=1', true, 302);
exit;
