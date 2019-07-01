<?php
session_start();
$_SESSION['page']='mail';
include "common/header.php";

$preview=false;
$memberonly = false;
$register = 0;
if(isset($_POST['preview']) || isset($_POST['send'])) {
    $preview=true;
    if($_POST['gruss'] == 1) {
        $gruss = "Viele Grüße\n".$_SESSION['Vorname'];
    }
    elseif($_POST['gruss'] == 2) {
        $gruss = "Viele Grüße\nder Vorstand";
    }
    elseif($_POST['gruss'] == 3) {
        $gruss = "Viele Grüße\n".$GLOBALS['commonStrings']['MailGreetings'];
    }
    $text = $_POST['Text']."\n\n".$gruss;
    $anrede = "Hallo {VORNAME},";
    if($_POST['to'] == 'aktiv') {
        $memberonly = true;
    }
    if(!isset($_POST['allReg'])) {
	$register = $_POST['register'];
    }
}
if(isset($_POST['send'])) {
    $mail = new Usermail;
    $mail->subject($_POST['Betreff']);
    $mail->memberonly($memberonly);
    $mail->register($register);
    $mail->sendlink(true);
    $mail->send($text);
}
?>
<div class="w3-container <?php echo $GLOBALS['commonColors']['titlebar']; ?>">
  <h2>Email versenden</h2>
</div>
<div class="w3-panel w3-mobile w3-center w3-col s1 m1 l4">
</div>
<div class="w3-panel w3-mobile w3-center w3-border w3-col s10 m10 l4">
  <form name="mailform" class="w3-container w3-margin" action="mail.php" method="POST">
    <label>Empfänger</label>
    <div class="w3-mobile w3-margin-bottom w3-padding w3-border <?php echo $GLOBALS['commonColors']['inputs']; ?>">
      <div class="w3-mobile">
	<input class="w3-radio w3-mobile" type="radio" name="to" value="aktiv" <?php if($preview && $_POST['to'] == 'aktiv') echo "checked"; ?> />
	<label>aktive Vereinsmitglieder</label>
    </div>
    <div class="w3-mobile">
	<input class="w3-radio w3-mobile" type="radio" name="to" value="all" <?php if(($preview && $_POST['to'] == 'all') || $preview==false) echo "checked"; ?> />
	<label>alle Musiker</label>	
      </div>
    </div>
    <label>Register</label>
    <div class="w3-mobile w3-margin-bottom w3-padding w3-border <?php echo $GLOBALS['commonColors']['inputs']; ?>">
    <input class="w3-check" type="checkbox" name="allReg" <?php if(!$preview || ($preview && isset($_POST['allReg']))) echo "checked"; ?>>
    <label>alle Register</label>
    <select id="register" class="w3-select w3-margin-top" name="register">
    <?php RegisterOption($register); ?>
    </select>
    </div>
<script style="text/javascript">
    var rad = document.mailform.allReg;
var select = document.getElementById("register");
if(!rad.checked) {
	select.style.display = 'block';
    }
else {
    select.style.display = 'none';
    
}
    rad.onclick = function () {
	console.log(this.value)
	if(this.checked) {
	    select.style.display = 'none';
	}
	else {
	    select.style.display = 'block';
	}
    };
</script>
    
    <label>Betreff</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['commonColors']['inputs']; ?> w3-margin-bottom w3-mobile" name="Betreff" placeholder="Hier Betreff einfügen" value="<?php if($preview) echo $_POST['Betreff']; ?>"/>
    <label>Text</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['commonColors']['inputs']; ?> w3-mobile" name="anrede" value="Hallo {VORNAME}," disabled/>
    <textarea rows="10" cols="50" class="w3-input w3-border <?php echo $GLOBALS['commonColors']['inputs']; ?> w3-mobile" name="Text" placeholder="Hier Emailtext einfügen"><?php if($preview) echo $_POST['Text']; ?></textarea>
    <select class="w3-select w3-margin-bottom" name="gruss">
      <option value="1" <?php if($preview && $_POST['gruss']==1) echo "selected"; ?>>Viele Grüße, <?php echo $_SESSION['Vorname']; ?></option>
      <option value="2" <?php if($preview && $_POST['gruss']==2) echo "selected"; ?>>Viele Grüße, der Vorstand</option>
      <option value="3" <?php if($preview && $_POST['gruss']==3) echo "selected"; ?>>Viele Grüße, <?php echo $GLOBALS['commonStrings']['MailGreetings']; ?></option>
    </select>
    <button class="w3-btn <?php echo $GLOBALS['commonColors']['submit']; ?> w3-margin-bottom w3-mobile" name="preview">Vorschau</button>
    <?php if($preview) { ?>
    <textarea rows="10" cols="50" class="w3-input w3-mobile w3-border" disabled><?php echo $anrede."\n\n".$text; ?></textarea>
        <button class="w3-btn <?php echo $GLOBALS['commonColors']['submit']; ?> w3-margin-top w3-mobile" name="send">Senden (nur einmal klicken, es dauert ein paar Sekunden)</button>
    <?php } ?>
  </form>
</div>
<?php
 include "common/footer.php";
?>
