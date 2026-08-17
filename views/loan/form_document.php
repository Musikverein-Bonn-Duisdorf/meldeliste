<?php
/**
 * Read-only loan/return document (snapshot / print layout).
 * Expects: $ctx, $kind, $h, $brandBar, $logoSrc, $lenderRep, $lenderWhen, $borrowerWhen,
 *          $lenderImg, $borrowerImg, $checklist (array)
 */
$showAddress = !empty($ctx['needAddressField']);
$contractNotes = isset($ctx['contractNotes']) ? (string)$ctx['contractNotes'] : '';
$checkReturned = !empty($checklist['returned']);
$checkDeposit = !empty($checklist['depositReturned']);
$checkDeductions = isset($checklist['deductions']) ? (string)$checklist['deductions'] : '';
$checkNotes = isset($checklist['notes']) ? (string)$checklist['notes'] : '';
?>
<article class="loan-form-doc" style="--loan-brand: <?php echo $h($brandBar); ?>;">
  <header class="loan-form-header">
    <div class="loan-form-brand">
<?php if($logoSrc !== '') { ?>
      <img class="loan-form-logo" src="<?php echo $logoSrc; ?>" alt="">
<?php } ?>
      <div class="loan-form-brand-text">
        <p class="loan-form-org"><?php echo $h($ctx['orgName']); ?></p>
        <h1><?php echo $h($ctx['title']); ?></h1>
      </div>
    </div>
    <p class="loan-form-meta">Leihe Nr. <?php echo (int)$ctx['loanId']; ?></p>
  </header>

  <section class="loan-form-section loan-form-panel">
    <h2>Parteien</h2>
    <div class="loan-form-parties">
      <div class="loan-form-party">
        <p class="loan-form-party-role">Verleiher</p>
        <p class="loan-form-party-name"><strong class="loan-form-em"><?php echo $h($ctx['orgName']); ?></strong></p>
        <div class="loan-form-field-row loan-form-field-row--stack">
          <span class="loan-form-field-label">Adresse</span>
<?php if($ctx['orgAddress'] !== '') { ?>
          <p class="loan-form-address-value"><?php echo $h($ctx['orgAddress']); ?></p>
<?php } else { ?>
          <span class="loan-form-blank loan-form-blank--address"></span>
<?php } ?>
        </div>
      </div>
      <div class="loan-form-party">
        <p class="loan-form-party-role">Entleiher</p>
        <p class="loan-form-party-name"><strong class="loan-form-em"><?php echo $h($ctx['borrowerName']); ?></strong></p>
<?php if($ctx['borrowerEmail'] !== '') { ?>
        <p class="loan-form-muted"><?php echo $h($ctx['borrowerEmail']); ?></p>
<?php } ?>
<?php if($showAddress) { ?>
        <div class="loan-form-field-row loan-form-field-row--stack">
          <span class="loan-form-field-label">Adresse</span>
<?php   if($ctx['borrowerAddress'] !== '') { ?>
          <p class="loan-form-address-value"><?php echo $h($ctx['borrowerAddress']); ?></p>
<?php   } else { ?>
          <span class="loan-form-blank loan-form-blank--address"></span>
<?php   } ?>
        </div>
<?php } ?>
      </div>
    </div>
  </section>

  <section class="loan-form-section loan-form-panel loan-form-leihgut">
    <h2>Leihgut</h2>
    <p class="loan-form-item-title">
      <span class="loan-form-item-label">Leihgegenstand</span>
      <span class="loan-form-item-value"><?php echo $h($ctx['itemLabel']); ?></span>
    </p>
    <dl class="loan-form-dl loan-form-dl--2col">
<?php foreach($ctx['itemDetails'] as $row) { ?>
      <div><dt><?php echo $h($row['label']); ?></dt><dd><?php echo $h($row['value']); ?></dd></div>
<?php } ?>
      <div>
        <dt>Leihbeginn</dt>
        <dd><?php echo $h($ctx['startDateDe']); ?></dd>
      </div>
      <div>
        <dt><?php echo $ctx['hasFixedEnd'] ? 'Leihende' : 'Dauer'; ?></dt>
        <dd><?php echo $ctx['hasFixedEnd'] ? $h($ctx['endDateDe']) : 'unbefristet'; ?></dd>
      </div>
<?php if($ctx['hasKaution']) { ?>
      <div>
        <dt>Kaution</dt>
        <dd><?php echo $h($ctx['kautionFormatted']); ?></dd>
      </div>
<?php } ?>
<?php if(!empty($ctx['hasLeihgebuehr'])) { ?>
      <div>
        <dt>Leihgebühr</dt>
        <dd><?php echo $h($ctx['leihgebuehrFormatted']); ?></dd>
      </div>
<?php } ?>
    </dl>
  </section>

  <div class="loan-form-body">
    <section class="loan-form-section loan-form-panel loan-form-terms">
      <h2><?php echo $kind === LoanForm::KIND_RETURN ? 'Protokoll' : 'Vertragsbedingungen'; ?></h2>
      <ol class="loan-form-clauses">
<?php foreach($ctx['clauses'] as $clause) { ?>
        <li><?php echo $clause; ?></li>
<?php } ?>
      </ol>
    </section>

    <section class="loan-form-section loan-form-panel">
      <h2>Zusätzliche Vereinbarungen</h2>
      <div class="loan-form-field-row loan-form-field-row--stack">
        <span class="loan-form-field-label">Bemerkungen</span>
<?php if($contractNotes !== '') { ?>
        <p class="loan-form-address-value"><?php echo nl2br($h($contractNotes)); ?></p>
<?php } else { ?>
        <span class="loan-form-blank loan-form-blank--address"></span>
<?php } ?>
      </div>
    </section>

<?php if($kind === LoanForm::KIND_RETURN) { ?>
    <section class="loan-form-section loan-form-panel">
      <h2>Checkliste</h2>
      <ul class="loan-form-checks">
        <li>
          <span class="loan-form-box<?php echo $checkReturned ? ' loan-form-box--on' : ''; ?>" aria-hidden="true"></span>
          <span>Leihgut zurückgegeben</span>
        </li>
<?php   if($ctx['hasKaution']) { ?>
        <li>
          <span class="loan-form-box<?php echo $checkDeposit ? ' loan-form-box--on' : ''; ?>" aria-hidden="true"></span>
          <span><strong class="loan-form-em">Kaution</strong> zurückgezahlt (<strong class="loan-form-em"><?php echo $h($ctx['kautionFormatted']); ?></strong>)</span>
        </li>
        <li class="loan-form-check-note-row">
          <span class="loan-form-field-label">Abzüge</span>
<?php     if($checkDeductions !== '') { ?>
          <span class="loan-form-field-value"><?php echo $h($checkDeductions); ?></span>
<?php     } else { ?>
          <span class="loan-form-blank loan-form-blank--line"></span>
<?php     } ?>
        </li>
<?php   } ?>
        <li class="loan-form-check-note-row">
          <span class="loan-form-field-label">Bemerkungen</span>
<?php   if($checkNotes !== '') { ?>
          <span class="loan-form-field-value"><?php echo $h($checkNotes); ?></span>
<?php   } else { ?>
          <span class="loan-form-blank loan-form-blank--line"></span>
<?php   } ?>
        </li>
      </ul>
    </section>
<?php } ?>

    <section class="loan-form-section loan-form-signatures">
      <h2>Unterschriften</h2>
      <div class="loan-form-sign-grid">
        <div class="loan-form-sign">
<?php if($lenderWhen !== '') { ?>
          <p class="loan-form-sign-caption"><?php echo $h($lenderWhen); ?></p>
<?php } ?>
<?php if($lenderImg !== '') { ?>
          <img class="loan-form-sign-img" src="<?php echo $lenderImg; ?>" alt="Unterschrift Verleiher">
<?php } ?>
          <p class="loan-form-sign-caption"><strong class="loan-form-em"><?php echo $h($ctx['orgName']); ?></strong><?php
if($lenderRep !== '') {
    echo '<span class="loan-form-sign-iv">'.$h($lenderRep).'</span>';
}
?><span class="loan-form-sign-role">Verleiher</span></p>
        </div>
        <div class="loan-form-sign">
<?php if($borrowerWhen !== '') { ?>
          <p class="loan-form-sign-caption"><?php echo $h($borrowerWhen); ?></p>
<?php } ?>
<?php if($borrowerImg !== '') { ?>
          <img class="loan-form-sign-img" src="<?php echo $borrowerImg; ?>" alt="Unterschrift Entleiher">
<?php } ?>
          <p class="loan-form-sign-caption"><strong class="loan-form-em"><?php echo $h($ctx['borrowerName']); ?></strong><span class="loan-form-sign-role">Entleiher</span></p>
        </div>
      </div>
    </section>
  </div>
</article>
