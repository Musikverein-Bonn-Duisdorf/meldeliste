<?php

// handle user edits, creation and deletions
if(requirePermission("perm_editUsers")) {
    if(isset($_POST['insert'])) {
        $n = new User;
        $n->load_by_id($_POST['Index']);
        $n->fill_from_array($_POST);
        $n->save();
        if(isset($_POST['pw1']) && isset($_POST['pw2'])) {
            if($_POST['pw1'] == $_POST['pw2'] && $_POST['pw1'] != '') {
                $n->passwd($_POST['pw1']);
            }
        }
    }
    if(isset($_POST['deactivate'])) {
        $n = new User;
        $n->load_by_id($_POST['Index']);
        $n->Instrument=0;
        $n->save();
    }
    if(isset($_POST['delete'])) {
        $n = new User;
        $n->load_by_id($_POST['Index']);
        $n->delete();
    }
    if(isset($_POST['passwd'])) {
        $n = new User;
        $n->load_by_id($_POST['Index']);
        $n->fill_from_array($_POST);
        if($_POST['Index'] > 0) {
            $n->passwd("");
        }
    }
    if(isset($_POST['newmail'])) {
        $n = new User;
        $n->load_by_id($_POST['Index']);
        $n->fill_from_array($_POST);
        if($_POST['Index'] > 0) {
            $n->newmail("");
        }
    }
}
?>