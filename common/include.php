<?php

include "common/config.php";
include "libs/helpers.php";
$optionsDB = loadconfig();
global $optionsDB;
include "version.php";
include "libs/git.php";
include "libs/log.php";
include "libs/div.php";
include "libs/user.php";
include "libs/permissions.php";
include "libs/SQLtable.php";
include "libs/termin.php";
include "libs/shift.php";
include "libs/meldung.php";
include "libs/externmeldung.php";
include "libs/shiftmeldung.php";
include "libs/instrument.php";
include "libs/instruments.php";
include "libs/loan.php";
include "libs/register.php";
include "libs/usermail.php";
include "libs/ics.php";
include "libs/usercalendar.php";
include "libs/aushilfe.php";
include "libs/aushilfeShift.php";

?>
