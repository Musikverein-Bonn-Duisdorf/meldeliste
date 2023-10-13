<?php
session_start();
$_SESSION['page']='updater';
$_SESSION['adminpage']=true;
include "common/header.php";
if(!requirePermission("perm_editConfig")) die();
?>
<div class="w3-container <?php echo $GLOBALS['optionsDB']['colorTitleBar']; ?>">
  <h2>Updater</h2>
</div>
<div class="w3-container w3-card w3-margin w3-padding <?php echo $GLOBALS['optionsDB']['colorWarning']; ?>">
  <div class="w3-col l3 m3 s2 w3-center">
    <i class="fas fa-exclamation-triangle"></i>
  </div>
  <div class="w3-col l6 m6 s8 w3-center">
    <b>Nur nutzen, wenn man weiß, was man tut!</b>
  </div>
  <div class="w3-col l3 m3 s2 w3-center">
    <i class="fas fa-exclamation-triangle"></i>
  </div>
</div>
<div class="w3-yellow w3-padding"><i class="fas fa-code-branch"></i>
  <?php echo "Aktueller Branch: <b>".getBranchName()."</b>"; ?>
</div>
    <?php
           if(isset($_POST['pull'])) {
?>      
      
      <div class=" w3-card-4 w3-margin">
  <div class="w3-container w3-teal"><h3>git pull</h3></div>
  <div class="w3-padding w3-code">
  <?php
               $vCurrent = shell_exec("git rev-parse --short HEAD 2>&1");
               $pull = explode("\n", shell_exec("git pull origin ".getBranchName()." 2>&1"));
               $vNew = shell_exec("git rev-parse --short HEAD 2>&1");
               foreach($pull as $line) {
                   echo "<div>".$line."</div>";
               }
  ?>
  </div>
<?php if($vCurrent != $vNew) {
                   $logentry = new Log;
                   $logentry->info("<b>Software Update</b> from version <b>".$vCurrent."</b> to <b>".$vNew."</b>");

?>
  <div class="w3-container w3-yellow w3-padding">updated <b><?php echo $vCurrent."</b> -> <b>".$vNew; ?></b></div>
<?php } ?>
</div>
    <?php
           }
?>      
      <div class=" w3-card-4 w3-margin">
  <div class="w3-container w3-teal"><h3>git status</h3></div>
  <div class="w3-padding w3-code">
    <?php
      $vCurrent = shell_exec("git rev-parse --short HEAD 2>&1");
      $status = explode("\n", shell_exec("git remote -v update origin 2>&1"));
      foreach($status as $line) {
          $found = strpos($line, "origin/".getBranchName());
          if($found) {
              echo "<div class=\"w3-yellow\"><b>".$line."</b></div>";
          }
          else {
              echo "<div>".$line."</div>";
          }
      }
    ?>
  </div>
</div>

<div class=" w3-card-4 w3-margin">
  <div class="w3-container w3-teal"><h3>git pull</h3></div>
  <form action="" method="post">
    <button class="w3-button w3-blue" type="submit" value="pull" name="pull">pull</button>
  </form>
  </div>
</div>

  <?php
include "common/footer.php";
?>
