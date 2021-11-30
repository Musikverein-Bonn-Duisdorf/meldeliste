<?php
session_start();
$_SESSION['page']='mail';
$_SESSION['adminpage']=true;
include "common/header.php";
requireAdmin();

$preview=false;
$memberonly = false;
$register = 0;
$termin = 0;
if(isset($_POST['termin'])) {
    $termin = $_POST['termin'];
}
if(isset($_POST['preview']) || isset($_POST['send'])) {
    $preview=true;
    if($_POST['gruss'] == 1) {
        $gruss = "Viele Grüße\n".$_SESSION['Vorname'];
    }
    elseif($_POST['gruss'] == 2) {
        $gruss = "Viele Grüße\nder Vorstand";
    }
    elseif($_POST['gruss'] == 3) {
        $gruss = "Viele Grüße\n".$GLOBALS['optionsDB']['MailGreetings'];
    }
    $text = $_POST['Text']."\n\n".$gruss;
    $anrede = "Hallo {VORNAME},";
    if(isset($_POST['to']) && $_POST['to'] == 'aktiv') {
        $memberonly = true;
    }
    if(!isset($_POST['allReg']) && $termin == 0) {
        $register = $_POST['register'];
    }
}
if(isset($_POST['send']) && $termin == 0) {
    $mail = new Usermail;
    $mail->attachments = true;
    $mail->subject($_POST['Betreff']);
    $mail->memberonly($memberonly);
    $mail->register($register);
    $mail->sendlink(true);
    $mail->send($text);
    $files = scandir("uploads/");
    foreach($files as $file) {
        if($file == "." || $file == ".." || $file == "README") continue;
        unlink("uploads/".$file);
    }
}
if(isset($_POST['termin']) && isset($_POST['send'])) {
    $mail = new Usermail;
    $mail->attachments = true;
    $mail->subject($_POST['Betreff']);
    $mail->termin($termin);
    $mail->sendlink(true);
    $mail->send($text);
    $files = scandir("uploads/");
    foreach($files as $file) {
        if($file == "." || $file == ".." || $file == "README") continue;
        unlink("uploads/".$file);
    }
}
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Email versenden</h2>
</div>
<div class="w3-panel w3-mobile w3-center w3-col s1 m1 l4">
</div>
<div class="w3-panel w3-mobile w3-center w3-border w3-col s10 m10 l4">
  <form name="mailform" class="w3-container w3-margin" action="mail.php" method="POST" enctype="multipart/form-data">
    <label>Empfänger</label>
    <?php
         if($termin) {
             $t=new Termin;
             $t->load_by_id($termin);
    ?>
             <div class="w3-mobile w3-margin-bottom w3-padding">Alle Teilnehmer von <?php echo $t->Name." (".$t->getGermanDate().")" ?></div>
    <input type="hidden" name="termin" value="<?php echo $termin; ?>" />
    <?php
         }
         else {
    ?>
    <div class="w3-mobile w3-margin-bottom w3-padding w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>">
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
    <div class="w3-mobile w3-margin-bottom w3-padding w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?>">
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
<?php } ?>
    <label>Betreff</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" name="Betreff" placeholder="Hier Betreff einfügen" value="<?php if($preview) echo $_POST['Betreff']; ?>"/>
    
    <label>Text</label>
    <input class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-mobile" name="anrede" value="Hallo {VORNAME}," disabled/>

    <textarea rows="10" cols="50" class="w3-input w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-mobile" name="Text" placeholder="Hier Emailtext einfügen"><?php if($preview) echo $_POST['Text']; ?></textarea>
    <select class="w3-select w3-margin-bottom" name="gruss">
      <option value="1" <?php if($preview && $_POST['gruss']==1) echo "selected"; ?>>Viele Grüße, <?php echo $_SESSION['Vorname']; ?></option>
      <option value="2" <?php if($preview && $_POST['gruss']==2) echo "selected"; ?>>Viele Grüße, der Vorstand</option>
      <option value="3" <?php if($preview && $_POST['gruss']==3) echo "selected"; ?>>Viele Grüße, <?php echo $GLOBALS['optionsDB']['MailGreetings']; ?></option>
    </select>
    <button class="w3-btn <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-margin-bottom w3-mobile" name="preview">Vorschau</button>

                                                                                                                 
    <?php if($preview) { ?>
                         <div class="w3-container w3-mobile w3-border w3-border-black w3-left-align w3-margin-bottom"><b>Betreff:</b> <?php echo $_POST['Betreff']; ?></div>
                         <div class="w3-row w3-mobile w3-border w3-border-black w3-left-align"><?php echo "<div class=\"w3-container ".$GLOBALS['optionsDB']['colorTitle']." w3-mobile\"><h1>".$GLOBALS['optionsDB']['WebSiteName']."</h1></div><div class=\"w3-container\"><p>".$anrede."<br /><br />\n\n".nl2br($text); ?></p></div></div>
        <button class="w3-btn <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-margin-top w3-mobile" name="send">Senden (nur einmal klicken, es dauert ein paar Sekunden)</button>
    <?php } ?>
  </form>

        <form id="uploadform" name="uploadform" action="uploadfiles.php" method="POST" enctype="multipart/form-data">
              <label>Anhang</label>
      <div class="w3-row">
	<input id="attachment" class="w3-input w3-col l6 w3-border <?php echo $GLOBALS['optionsDB']['colorInputBackground']; ?> w3-margin-bottom w3-mobile" type="file" name="attachment[]" onchange="showUploadButton()"/>
	<div class="w3-col l2">&nbsp;</div>
	<button id="upload" class="w3-btn w3-col l4 <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?> w3-margin-bottom w3-mobile" name="Upload" style="display:none" type="submit" role="button">Upload</button>
      </div>
            </form>
    <script style="text/javascript">
    function showUploadButton() {
        var fileinput = document.getElementById("attachment");
	var upload = document.getElementById("upload");
	if('files' in fileinput) {
	    if(fileinput.files.length != 0) {
		upload.style.display='block';
	    }
	}
    }
    </script>
    <script style="text/javascript">
    const form = document.getElementById('uploadform');
    form.addEventListener('submit', (event) => {
        event.preventDefault();

	if (window.XMLHttpRequest) {
	    // AJAX nutzen mit IE7+, Chrome, Firefox, Safari, Opera
	    xmlhttp=new XMLHttpRequest();
	}
	else {
	    // AJAX mit IE6, IE5
	    xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	xmlhttp.onreadystatechange=function() {
		if (xmlhttp.readyState==4 && xmlhttp.status==200) {
	        var attdiv = document.getElementById("attachments");
	        var attline = document.createElement('div');
	        attline.innerHTML = xmlhttp.responseText;
	        attdiv.parentNode.appendChild(attline);
		}
	}
	
	xmlhttp.open("POST","uploadfile.php",true);
	let data = new FormData(form);
	xmlhttp.send(data);
	xmlhttp.onload = () => {
            console.log(xmlhttp.responseText);
	}
    });

function delFile(hash) {
    console.log("deleting "+hash);
	if (window.XMLHttpRequest) {
	    // AJAX nutzen mit IE7+, Chrome, Firefox, Safari, Opera
	    xmlhttp=new XMLHttpRequest();
	}
	else {
	    // AJAX mit IE6, IE5
	    xmlhttp=new ActiveXObject("Microsoft.XMLHTTP");
	}
	xmlhttp.onreadystatechange=function() {
		if (xmlhttp.readyState==4 && xmlhttp.status==200) {
	        var attdiv = document.getElementById(hash);
            attdiv.remove();
		}
	}
	
	xmlhttp.open("GET","delfile.php?hash="+hash,true);
	xmlhttp.onload = () => {
        console.log(xmlhttp.responseText);
	}
    xmlhttp.send();
}
</script>

<div class="w3-row" id="attachments">
<?php
    $files = scandir("uploads/");
    foreach($files as $file) {
        if($file == "." || $file == ".." || $file == "README") continue;
        $hash = md5_file("uploads/".$file);
        echo "<div class=\"w3-row\" id=\"".$hash."\"><div class=\"w3-green w3-col l6 w3-padding\">".htmlspecialchars($file)."</div><button class=\"w3-text-red fas fa-times w3-col l1 w3-padding\" onclick=\"delFile('".$hash."')\"></button><div class=\"w3-col l5 w3-padding\">&nbsp;</div></div>\n";
    }
?>
</div>

</div>
<?php
 include "common/footer.php";
?>
