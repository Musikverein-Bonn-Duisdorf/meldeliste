/**
 * MELD-222: warn before upload when a selected file exceeds 2 MB.
 */
(function () {
  'use strict';

  var LIMIT = 2 * 1024 * 1024;

  function oversizeFiles(form) {
    var out = [];
    if (!form || !form.querySelectorAll) return out;
    var inputs = form.querySelectorAll('input[type="file"]');
    for (var i = 0; i < inputs.length; i++) {
      var input = inputs[i];
      if (!input.files) continue;
      for (var j = 0; j < input.files.length; j++) {
        var file = input.files[j];
        if (file && file.size > LIMIT) {
          out.push(file);
        }
      }
    }
    return out;
  }

  function formatMb(bytes) {
    return (bytes / (1024 * 1024)).toFixed(1).replace('.', ',');
  }

  document.addEventListener('submit', function (e) {
    var form = e.target;
    if (!form || form.tagName !== 'FORM') return;
    if (form.getAttribute('data-upload-warn-pass') === '1') {
      form.removeAttribute('data-upload-warn-pass');
      return;
    }
    var big = oversizeFiles(form);
    if (!big.length) return;
    if (e.defaultPrevented) return;
    e.preventDefault();
    var names = big.map(function (f) {
      return f.name + ' (' + formatMb(f.size) + ' MB)';
    }).join(', ');
    var msg = big.length === 1
      ? 'Die Datei „' + names + '“ ist größer als 2 MB. Trotzdem hochladen?'
      : 'Mehrere Dateien sind größer als 2 MB (' + names + '). Trotzdem hochladen?';
    var confirmFn = window.appConfirm;
    if (typeof confirmFn !== 'function') {
      form.setAttribute('data-upload-warn-pass', '1');
      form.requestSubmit(e.submitter || undefined);
      return;
    }
    confirmFn(msg, { okLabel: 'Hochladen', title: 'Große Datei' }).then(function (ok) {
      if (!ok) return;
      form.setAttribute('data-upload-warn-pass', '1');
      if (typeof form.requestSubmit === 'function') {
        form.requestSubmit(e.submitter || undefined);
      } else {
        form.submit();
      }
    });
  });
})();
