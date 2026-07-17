<?php

include "common/config.php";
if(isset($GLOBALS['conn']) && $GLOBALS['conn']) {
    mysqli_set_charset($GLOBALS['conn'], 'utf8mb4');
    @mysqli_query($GLOBALS['conn'], "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci");
}
include "config/ConfigDefaults.php";
include "config/SchemaVersion.php";
include "libs/helpers.php";
include "libs/colorschemes.php";
$optionsDB = loadconfig();
global $optionsDB;
include "version.php";
include "libs/git.php";
include "libs/log.php";
include "libs/discord.php";
include "libs/div.php";
include "libs/user.php";
include "libs/permissions.php";
include "libs/SQLtable.php";
include "libs/DatabaseManager.php";
include "libs/RegNumber.php";
include "libs/termin.php";
include "libs/shift.php";
include "libs/meldung.php";
include "libs/externmeldung.php";
include "libs/shiftmeldung.php";
include "libs/instrument.php";
include "libs/instruments.php";
include "libs/inventory.php";
include "libs/inventories.php";
include "libs/loan.php";
include "libs/inventoriesLoan.php";
include "libs/register.php";
include "libs/usermail.php";
include "libs/mailJob.php";
include "libs/mailOutbox.php";
include "libs/ics.php";
include "libs/usercalendar.php";
include "libs/aushilfe.php";
include "libs/aushilfeShift.php";
include "libs/AppmntFreeTextResponse.php";
include "libs/listChunk.php";
?>
