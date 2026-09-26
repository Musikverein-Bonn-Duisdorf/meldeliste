<?php
/**
 * Shared TinyMCE init for mail compose / welcome template (MELD-214 / MELD-240).
 * Expects: $tinymceSelector (CSS selector, default #mail-body)
 */
$tinymceSelector = isset($tinymceSelector) ? (string)$tinymceSelector : '#mail-body';
$hash = isset($GLOBALS['version']['Hash']) ? htmlspecialchars((string)$GLOBALS['version']['Hash'], ENT_QUOTES, 'UTF-8') : '0';
$mtime = @filemtime(__DIR__.'/../../js/tinymce/tinymce.min.js');
$selJs = json_encode($tinymceSelector);
?>
  <script src="js/tinymce/tinymce.min.js?<?php echo $hash; ?>-<?php echo (int)$mtime; ?>"></script>
  <script>
  (function() {
    var isNarrow = window.matchMedia && window.matchMedia('(max-width: 900px)').matches;
    tinymce.init({
    selector: <?php echo $selJs; ?>,
    license_key: 'gpl',
    menubar: false,
    branding: false,
    promotion: false,
    height: isNarrow ? 280 : 400,
    plugins: 'lists link autolink table searchreplace code charmap nonbreaking',
    toolbar: isNarrow
      ? 'undo redo | bold italic underline | bullist numlist | link | removeformat'
      : 'undo redo | blocks | fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor | alignleft aligncenter alignright | bullist numlist | outdent indent | blockquote | table | hr | link | charmap | searchreplace | code | removeformat',
    toolbar_mode: isNarrow ? 'scrolling' : 'wrap',
    block_formats: 'Absatz=p; Überschrift 2=h2; Überschrift 3=h3; Überschrift 4=h4',
    table_toolbar: 'tableprops tabledelete | tableinsertrowbefore tableinsertrowafter tabledeleterow | tableinsertcolbefore tableinsertcolafter tabledeletecol',
    table_appearance_options: false,
    table_default_attributes: { border: '1' },
    table_default_styles: { 'border-collapse': 'collapse', width: '100%' },
    font_family_formats: 'Arial=arial,helvetica,sans-serif; Georgia=georgia,serif; Times New Roman=times new roman,times,serif; Verdana=verdana,geneva,sans-serif; Courier New=courier new,courier,monospace',
    font_size_formats: '10pt 12pt 14pt 16pt 18pt 24pt 36pt',
    color_map: [
      '000000', 'Schwarz',
      '333333', 'Dunkelgrau',
      'FFFFFF', 'Weiß',
      'E53935', 'Rot',
      'FB8C00', 'Orange',
      'FDD835', 'Gelb',
      '43A047', 'Grün',
      '1E88E5', 'Blau',
      '8E24AA', 'Violett',
      '6D4C41', 'Braun'
    ],
    link_default_target: '_blank',
    link_assume_external_targets: true,
    convert_urls: false,
    relative_urls: false,
    entity_encoding: 'raw',
    content_style: 'body { font-family: Arial, Helvetica, sans-serif; font-size: 14pt; } img, table { max-width: 100%; }',
    setup: function (editor) {
      var form = editor.getElement() && editor.getElement().form;
      if (form) {
        form.addEventListener('submit', function () {
          editor.save();
        });
      }
    }
  });
  })();
  </script>
