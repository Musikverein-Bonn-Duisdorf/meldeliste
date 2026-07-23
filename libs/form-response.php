<?php

function isUserFormPost() {
    return isset($_POST['insert'])
        || isset($_POST['delete'])
        || isset($_POST['deactivate'])
        || isset($_POST['passwd'])
        || isset($_POST['newmail']);
}

/**
 * Handle admin user form POST (insert, delete, deactivate, passwd, newmail).
 *
 * @param array $options allowNewUser (bool) — musiker/new-musiker may create users
 * @return array handled (bool), flash (array|null), successMessage (string|null)
 */
function handleUserFormPost($options = array()) {
    $allowNewUser = !empty($options['allowNewUser']);
    $result = array(
        'handled' => false,
        'flash' => null,
        'successMessage' => null,
    );

    if(!isUserFormPost() || !requirePermission('perm_editUsers')) {
        return $result;
    }

    $result['handled'] = true;

    if(isset($_POST['insert'])) {
        try {
            $n = new User;
            $id = isset($_POST['Index']) ? (int)$_POST['Index'] : 0;
            if($allowNewUser) {
                if($id > 0) {
                    $n->load_by_id($id);
                }
                $n->fill_from_array($_POST);
                if($id < 1) {
                    $n->Index = null;
                    $dup = User::findExistingByName($n->Vorname, $n->Nachname);
                    if($dup) {
                        $result['flash'] = array(
                            'type' => 'error',
                            'message' => sprintf(
                                'Es existiert bereits ein User mit dem Namen „%s %s“ (ID %d). Bitte bestehenden Eintrag öffnen oder den Namen anpassen.',
                                $dup['Vorname'],
                                $dup['Nachname'],
                                $dup['Index']
                            ),
                        );
                        return $result;
                    }
                }
                if((int)$n->Active === 0) {
                    $n->applyGuestMusicianDefaults();
                }
                if(!$n->save()) {
                    $result['flash'] = array(
                        'type' => 'error',
                        'message' => 'Musiker konnte nicht gespeichert werden. Vorname und Nachname sind Pflicht.',
                    );
                    return $result;
                }
            }
            else {
                $n->load_by_id($_POST['Index']);
                $n->fill_from_array($_POST);
                if((int)$n->Active === 0) {
                    $n->applyGuestMusicianDefaults();
                }
                $n->save();
            }

            if(isset($_POST['userNamedGroupsPosted']) && (int)$n->Index > 0) {
                $ids = isset($_POST['userNamedGroups']) ? $_POST['userNamedGroups'] : array();
                if(!is_array($ids)) {
                    $ids = array();
                }
                Group::syncUserExplicitMembership((int)$n->Index, $ids);
            }

            if(isset($_POST['userPermissionsPosted']) && (int)$n->Index > 0) {
                $sessionUserId = isset($_SESSION['userid']) ? (int)$_SESSION['userid'] : 0;
                Permissions::applyPostedForUser((int)$n->Index, $_POST, $sessionUserId);
            }

            if(isset($_POST['pw1']) && isset($_POST['pw2'])) {
                if($_POST['pw1'] == $_POST['pw2'] && $_POST['pw1'] != '') {
                    if(!$n->passwd($_POST['pw1'])) {
                        $result['flash'] = array(
                            'type' => 'error',
                            'message' => 'Passwort konnte nicht gesetzt werden. Bitte Loginname prüfen.',
                        );
                        return $result;
                    }
                }
                elseif($_POST['pw1'] != '' || $_POST['pw2'] != '') {
                    $result['flash'] = array(
                        'type' => 'error',
                        'message' => 'Passwörter stimmen nicht überein.',
                    );
                    return $result;
                }
            }
            $result['successMessage'] = 'Gespeichert.';
        }
        catch(Throwable $e) {
            $logentry = new Log;
            $logentry->error(sprintf(
                'User speichern/Passwort Exception | User-ID: <b>%d</b>, Fehler: <b>%s</b>',
                isset($_POST['Index']) ? (int)$_POST['Index'] : 0,
                htmlspecialchars($e->getMessage())
            ));
            $result['flash'] = array(
                'type' => 'error',
                'message' => 'Fehler: '.$e->getMessage(),
            );
        }
        return $result;
    }

    if(isset($_POST['deactivate'])) {
        $n = new User;
        $n->load_by_id($_POST['Index']);
        $n->applyGuestMusicianDefaults();
        $n->save();
        $result['successMessage'] = 'Als Gastmusiker deaktiviert.';
        return $result;
    }

    if(isset($_POST['delete'])) {
        $n = new User;
        $n->load_by_id($_POST['Index']);
        if(!(int)$n->Index) {
            $result['flash'] = array(
                'type' => 'error',
                'message' => 'Benutzer nicht gefunden.',
            );
            return $result;
        }
        if($n->hasInventories()) {
            $result['flash'] = array(
                'type' => 'error',
                'message' => $n->getDeleteInventoryBlockMessage(),
            );
            return $result;
        }
        if(!$n->delete()) {
            $result['flash'] = array(
                'type' => 'error',
                'message' => 'Löschen fehlgeschlagen.',
            );
            return $result;
        }
        $result['successMessage'] = 'Gelöscht.';
        return $result;
    }

    if(isset($_POST['passwd'])) {
        try {
            $userId = isset($_POST['Index']) ? (int)$_POST['Index'] : 0;
            if($userId < 1) {
                $result['flash'] = array(
                    'type' => 'error',
                    'message' => 'Kein Benutzer ausgewählt.',
                );
                return $result;
            }
            $n = new User;
            $n->load_by_id($userId);
            if(!$n->passwd('')) {
                $result['flash'] = array(
                    'type' => 'error',
                    'message' => 'Zufallspasswort konnte nicht erzeugt werden. Bitte Loginname prüfen.',
                );
                return $result;
            }
            $result['successMessage'] = 'Zufallspasswort erzeugt.';
        }
        catch(Throwable $e) {
            $logentry = new Log;
            $logentry->error(sprintf(
                'Zufallspasswort Exception | User-ID: <b>%d</b>, Fehler: <b>%s</b>',
                isset($_POST['Index']) ? (int)$_POST['Index'] : 0,
                htmlspecialchars($e->getMessage())
            ));
            $result['flash'] = array(
                'type' => 'error',
                'message' => 'Fehler beim Erzeugen des Passworts: '.$e->getMessage(),
            );
        }
        return $result;
    }

    if(isset($_POST['newmail'])) {
        try {
            $userId = isset($_POST['Index']) ? (int)$_POST['Index'] : 0;
            if($userId < 1) {
                $result['flash'] = array(
                    'type' => 'error',
                    'message' => 'Kein Benutzer ausgewählt.',
                );
                return $result;
            }
            $n = new User;
            $n->load_by_id($userId);
            $n->newmail();
            $result['successMessage'] = 'Email mit Link versendet.';
        }
        catch(Throwable $e) {
            $logentry = new Log;
            $logentry->error(sprintf(
                'newmail Exception | User-ID: <b>%d</b>, Fehler: <b>%s</b>',
                isset($_POST['Index']) ? (int)$_POST['Index'] : 0,
                htmlspecialchars($e->getMessage())
            ));
            $result['flash'] = array(
                'type' => 'error',
                'message' => 'Fehler beim Mailversand: '.$e->getMessage(),
            );
        }
        return $result;
    }

    $result['handled'] = false;
    return $result;
}

/**
 * Self-service profile save ("Mein Profil").
 *
 * @return array flash (array|null), successMessage (string|null)
 */
function handleSelfProfilePost($userid) {
    $result = array(
        'flash' => null,
        'successMessage' => null,
    );
    $userid = (int)$userid;

    if(!isset($_POST['insert'])) {
        return $result;
    }

    $selfId = isset($_POST['Index']) ? (int)$_POST['Index'] : 0;
    try {
        if($selfId < 1 || $selfId !== $userid) {
            $logentry = new Log;
            $logentry->error(sprintf(
                'Profil speichern verweigert: User-ID <b>%d</b> wollte Index <b>%d</b> ändern.',
                $userid,
                $selfId
            ));
            $result['flash'] = array('type' => 'error', 'message' => 'Speichern nicht erlaubt.');
            return $result;
        }

        $n = new User;
        $n->load_by_id($selfId);
        if((int)$n->Index !== $selfId) {
            $result['flash'] = array('type' => 'error', 'message' => 'Benutzer nicht gefunden.');
            return $result;
        }

        // Eigenes Profil ohne User-Bearbeiten-Recht: nur Kontakt + Benachrichtigungen.
        // Mit perm_editUsers läuft Speichern über handleUserFormPost (volle Bearbeitung).
        $n->Email = isset($_POST['Email']) ? $_POST['Email'] : $n->Email;
        $n->Email2 = isset($_POST['Email2']) ? $_POST['Email2'] : $n->Email2;
        $n->getMail = isset($_POST['getMail']) ? (int)$_POST['getMail'] : 0;
        $n->notifyInbox = isset($_POST['notifyInbox']) ? (int)$_POST['notifyInbox'] : 0;
        $n->notifyAppMail = isset($_POST['notifyAppMail']) ? (int)$_POST['notifyAppMail'] : 0;
        $n->notifyAppTerminNew = isset($_POST['notifyAppTerminNew']) ? (int)$_POST['notifyAppTerminNew'] : 0;
        $n->notifyAppTerminChange = isset($_POST['notifyAppTerminChange']) ? (int)$_POST['notifyAppTerminChange'] : 0;
        $n->notifyAppTerminSoon = isset($_POST['notifyAppTerminSoon']) ? (int)$_POST['notifyAppTerminSoon'] : 0;
        if(!$n->save()) {
            $result['flash'] = array('type' => 'error', 'message' => 'Profil konnte nicht gespeichert werden.');
            return $result;
        }

        $result['successMessage'] = 'Profil gespeichert.';

        if(isset($_POST['pw1']) && isset($_POST['pw2'])) {
            $pw1 = (string)$_POST['pw1'];
            $pw2 = (string)$_POST['pw2'];
            if($pw1 !== '' || $pw2 !== '') {
                if($pw1 === '' || $pw2 === '') {
                    $result['flash'] = array('type' => 'error', 'message' => 'Bitte beide Passwortfelder ausfüllen.');
                    return $result;
                }
                if($pw1 !== $pw2) {
                    $result['flash'] = array('type' => 'error', 'message' => 'Passwörter stimmen nicht überein.');
                    return $result;
                }
                if(!$n->passwd($pw1)) {
                    $result['flash'] = array('type' => 'error', 'message' => 'Passwort konnte nicht gesetzt werden. Bitte Loginname prüfen.');
                    return $result;
                }
                $result['successMessage'] = trim($result['successMessage'].' Passwort wurde gesetzt.');
            }
        }
    }
    catch(Throwable $e) {
        $logentry = new Log;
        $logentry->error(sprintf(
            'Profil/Passwort Exception | User-ID: <b>%d</b>, Fehler: <b>%s</b>',
            $userid,
            htmlspecialchars($e->getMessage())
        ));
        $result['flash'] = array('type' => 'error', 'message' => 'Unerwarteter Fehler: '.$e->getMessage());
    }

    return $result;
}

function applyUserFormPostRedirect($defaultReturnUrl, $options = array()) {
    $result = handleUserFormPost($options);
    if(!$result['handled']) {
        return false;
    }
    $returnTo = resolvePostReturnUrl($defaultReturnUrl);
    if($result['flash']) {
        setFlash($result['flash']['type'], $result['flash']['message']);
    }
    elseif($result['successMessage']) {
        setFlash('success', $result['successMessage']);
    }
    redirectAfterPost($returnTo);
}

/**
 * POST handler for new-musiker.php: secondary actions stay on edit form.
 */
function applyNewMusikerFormPostRedirect($defaultReturnUrl) {
    $result = handleUserFormPost(array('allowNewUser' => true));
    if(!$result['handled']) {
        return false;
    }
    $returnTo = resolvePostReturnUrl($defaultReturnUrl);
    if($result['flash']) {
        setFlash($result['flash']['type'], $result['flash']['message']);
    }
    elseif($result['successMessage']) {
        setFlash('success', $result['successMessage']);
    }
    $userId = isset($_POST['Index']) ? (int)$_POST['Index'] : 0;
    $stayOnEdit = isset($_POST['passwd']) || isset($_POST['newmail']) || isset($_POST['deactivate']);
    $layout = '';
    if(isset($_POST['profile_layout']) && in_array($_POST['profile_layout'], array('a', 'b', 'c'), true)) {
        $layout = '&layout='.rawurlencode($_POST['profile_layout']);
    }
    if($stayOnEdit && $userId > 0) {
        redirectAfterPost('new-musiker.php?id='.$userId.'&return_to='.rawurlencode($returnTo).$layout);
    }
    redirectAfterPost($returnTo);
}
