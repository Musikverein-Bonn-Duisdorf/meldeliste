<?php
/**
 * Expected DB schema version number (MELD-51).
 * Bump when DBconfig.json, DatabaseManager migrations, or ConfigDefaults.php change.
 * v34: drop User.Birthday (owned by mit_MemberProfile); Fördernde filter via mit_Membership.
 * v35: drop User.Mitglied (owned by MIT MembershipPeriod tenure; Melde Active stays Betriebsflag).
 * v36: drop User.RefID (Mitgliedsnummer obsolete; canonical id is User.Index).
 * v37: Termine.Sammlungen (JSON int[] → archiv_Collection when Notenarchiv is attached).
 * v38: ensure Termine.Sammlungen (text JSON); drop obsolete Termine.Sammlung if present.
 * v39: InventoriesPhotos (MELD-191) and InventoriesDocuments (MELD-205).
 */
return 39;
?>
