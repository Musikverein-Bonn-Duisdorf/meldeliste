<?php
/**
 * MailJob detail modal (profile-shell).
 * Expects: $job (MailJob), $byId (int), $byName (string)
 */
$h = function ($s) {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
};
$subject = trim((string)$job->Subject);
$title = $subject !== '' ? $subject : '(ohne Betreff)';
$createdRaw = (string)$job->listTimestamp();
$createdView = (string)germanDate($createdRaw, true);
if(strlen($createdRaw) >= 16) {
    $createdView .= ' '.sql2timeRaw(substr($createdRaw, 11, 8));
}
$byHtml = ($byId > 0 && function_exists('entityOpenHtml'))
    ? entityOpenHtml('user', $byId, $byName)
    : $h($byName);
$body = formatMailBodyForDisplay((string)$job->BodyText);
$counts = ((string)$job->Status !== 'draft')
    ? MailJob::formatCounts($job->Sent, $job->Total, $job->Failed)
    : '—';
$btn = isset($GLOBALS['optionsDB']['colorBtnSubmit'])
    ? $GLOBALS['optionsDB']['colorBtnSubmit']
    : 'w3-blue';
?>
<div class="profile-shell modal-shell mail-modal" data-mail-id="<?php echo (int)$job->Index; ?>">
  <header class="profile-hero">
    <div class="profile-hero-text">
      <p class="profile-kicker">Email</p>
      <h2 class="profile-title"><?php echo $h($title); ?></h2>
    </div>
    <div class="profile-hero-actions">
      <div class="profile-actions">
        <div class="profile-actions-primary">
          <a class="w3-btn profile-btn-primary <?php echo $h($btn); ?> w3-border w3-mobile" href="mail.php?id=<?php echo (int)$job->Index; ?>">Öffnen</a>
        </div>
      </div>
      <button type="button" class="modal-close w3-button" onclick="closeModal()" aria-label="Schließen">&times;</button>
    </div>
  </header>

  <div class="profile-grid profile-grid--2">
    <section class="profile-col" aria-labelledby="mail-modal-meta">
      <h3 id="mail-modal-meta" class="profile-col-title">Versand</h3>
      <div class="profile-field">
        <span class="profile-label">Email-ID</span>
        <div class="profile-value"><?php echo (int)$job->Index; ?></div>
      </div>
      <div class="profile-field">
        <span class="profile-label">Status</span>
        <div class="profile-value"><span class="w3-tag <?php echo $h($job->statusClass()); ?>"><?php echo $h($job->statusLabel()); ?></span></div>
      </div>
      <div class="profile-field">
        <span class="profile-label">Zeit</span>
        <div class="profile-value"><?php echo $h($createdView); ?></div>
      </div>
      <div class="profile-field">
        <span class="profile-label">Von</span>
        <div class="profile-value"><?php echo $byHtml; ?></div>
      </div>
      <div class="profile-field">
        <span class="profile-label">Empfänger</span>
        <div class="profile-value"><?php echo $h($counts); ?></div>
      </div>
    </section>

    <section class="profile-col" aria-labelledby="mail-modal-body">
      <h3 id="mail-modal-body" class="profile-col-title">Text</h3>
      <div class="profile-field">
        <div class="profile-value mail-body-content"><?php echo $body !== '' ? $body : '<em>(kein Text)</em>'; ?></div>
      </div>
    </section>
  </div>
</div>
