<?php
require_once __DIR__.'/libs/sessionBootstrap.php';
meldeConfigureSession();
$_SESSION['page']='media';
$_SESSION['adminpage']=false;
include "common/header.php";

$mediaLinks = array(
    array(
        'url' => isset($GLOBALS['optionsDB']['MediaDiscordURL']) ? $GLOBALS['optionsDB']['MediaDiscordURL'] : '',
        'label' => 'Discord',
        'icon' => 'fa-brands fa-discord',
    ),
    array(
        'url' => isset($GLOBALS['optionsDB']['MediaYoutubeURL']) ? $GLOBALS['optionsDB']['MediaYoutubeURL'] : '',
        'label' => 'YouTube',
        'icon' => 'fa-brands fa-youtube',
    ),
    array(
        'url' => isset($GLOBALS['optionsDB']['MediaInstagramURL']) ? $GLOBALS['optionsDB']['MediaInstagramURL'] : '',
        'label' => 'Instagram',
        'icon' => 'fa-brands fa-instagram',
    ),
    array(
        'url' => isset($GLOBALS['optionsDB']['MediaFacebookURL']) ? $GLOBALS['optionsDB']['MediaFacebookURL'] : '',
        'label' => 'Facebook',
        'icon' => 'fa-brands fa-facebook',
    ),
    array(
        'url' => isset($GLOBALS['optionsDB']['MediaPhotosURL']) ? $GLOBALS['optionsDB']['MediaPhotosURL'] : '',
        'label' => 'Fotos',
        'icon' => 'fas fa-images',
    ),
    array(
        'url' => isset($GLOBALS['optionsDB']['MediaVideosURL']) ? $GLOBALS['optionsDB']['MediaVideosURL'] : '',
        'label' => 'Videos',
        'icon' => 'fas fa-film',
    ),
    array(
        'url' => isset($GLOBALS['optionsDB']['MediaAudioURL']) ? $GLOBALS['optionsDB']['MediaAudioURL'] : '',
        'label' => 'Audio',
        'icon' => 'fas fa-music',
    ),
);

$visibleLinks = array();
foreach($mediaLinks as $link) {
    $url = trim((string)$link['url']);
    if($url !== '') {
        $link['url'] = $url;
        $visibleLinks[] = $link;
    }
}
adminListPageBegin('Medien', 'Medien');
?>
<?php if($visibleLinks) { ?>
  <div class="w3-container w3-row-padding">
<?php foreach($visibleLinks as $link) { ?>
    <form class="w3-col s12 m6 l4 w3-margin-bottom" action="<?php echo htmlspecialchars($link['url'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" rel="noopener noreferrer">
	<button class="w3-btn w3-border w3-border-black w3-block <?php echo $GLOBALS['optionsDB']['colorBtnSubmit']; ?>" type="submit"><i class="<?php echo htmlspecialchars($link['icon'], ENT_QUOTES, 'UTF-8'); ?>"></i> <?php echo htmlspecialchars($link['label'], ENT_QUOTES, 'UTF-8'); ?></button>
    </form>
<?php } ?>
  </div>
<?php } else { ?>
<div class="w3-container w3-padding">
  <p class="w3-text-gray">Aktuell sind keine Medien-Links konfiguriert.</p>
</div>
<?php } ?>
<?php
adminListPageEnd();
include "common/footer.php";
?>
