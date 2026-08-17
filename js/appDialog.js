/**
 * Custom confirm/alert modals (MELD-187). Native window.confirm/alert
 * do not work in the Android WebView app.
 */
(function () {
  var resolvePending = null;
  var mode = 'confirm';
  var lastSubmitter = null;

  function el(id) {
    return document.getElementById(id);
  }

  function setVisible(node, on) {
    if (!node) return;
    node.style.display = on ? '' : 'none';
  }

  function finish(ok) {
    var modal = el('appConfirmModal');
    if (modal) modal.style.display = 'none';
    var fn = resolvePending;
    resolvePending = null;
    if (fn) fn(ok);
  }

  function openDialog(message, opts) {
    opts = opts || {};
    mode = opts.mode === 'alert' ? 'alert' : 'confirm';
    var modal = el('appConfirmModal');
    var msgEl = el('appConfirmMessage');
    var titleEl = el('appConfirmTitle');
    var kickerEl = el('appConfirmKicker');
    var okBtn = el('appConfirmOk');
    var cancelBtn = el('appConfirmCancel');
    if (!modal || !msgEl || !okBtn) {
      return Promise.resolve(mode === 'alert');
    }
    if (resolvePending) {
      finish(false);
    }
    msgEl.textContent = message || '';
    if (titleEl) {
      titleEl.textContent = opts.title || (mode === 'alert' ? 'Hinweis' : 'Bestätigen');
    }
    if (kickerEl) {
      kickerEl.textContent = opts.kicker || '';
      setVisible(kickerEl, !!opts.kicker);
    }
    okBtn.textContent = opts.okLabel || 'OK';
    okBtn.className = opts.okClass || 'w3-btn profile-btn-primary w3-border w3-mobile';
    setVisible(cancelBtn, mode === 'confirm' && !opts.hideCancel);
    if (cancelBtn) {
      cancelBtn.textContent = opts.cancelLabel || 'Abbrechen';
    }
    modal.style.display = 'block';
    try {
      okBtn.focus();
    } catch (e) {}
    return new Promise(function (resolve) {
      resolvePending = resolve;
    });
  }

  window.appConfirm = function (message, opts) {
    opts = opts || {};
    opts.mode = 'confirm';
    return openDialog(message, opts);
  };

  window.appAlert = function (message, opts) {
    opts = opts || {};
    opts.mode = 'alert';
    return openDialog(message, opts).then(function () {
      return undefined;
    });
  };

  function bindChrome() {
    var modal = el('appConfirmModal');
    if (!modal || modal.getAttribute('data-app-dialog-bound') === '1') return;
    modal.setAttribute('data-app-dialog-bound', '1');
    var okBtn = el('appConfirmOk');
    var cancelBtn = el('appConfirmCancel');
    var closeBtn = el('appConfirmClose');
    if (okBtn) {
      okBtn.addEventListener('click', function () {
        finish(true);
      });
    }
    if (cancelBtn) {
      cancelBtn.addEventListener('click', function () {
        finish(false);
      });
    }
    if (closeBtn) {
      closeBtn.addEventListener('click', function () {
        finish(false);
      });
    }
    modal.addEventListener('click', function (e) {
      if (e.target === modal) finish(false);
    });
    document.addEventListener('keydown', function (e) {
      if (e.key !== 'Escape') return;
      if (!modal || modal.style.display !== 'block') return;
      finish(false);
    });
  }

  function confirmAttr(node) {
    if (!node || !node.getAttribute) return '';
    return node.getAttribute('data-confirm') || '';
  }

  function confirmOptsFrom(node) {
    if (!node) return {};
    var opts = {};
    var ok = node.getAttribute('data-confirm-ok');
    var title = node.getAttribute('data-confirm-title');
    var okClass = node.getAttribute('data-confirm-ok-class');
    if (node.getAttribute('data-confirm-no-cancel') === '1') {
      opts.hideCancel = true;
    }
    if (ok) opts.okLabel = ok;
    if (title) opts.title = title;
    if (okClass) opts.okClass = okClass;
    return opts;
  }

  function resolveSubmitter(form, eventSubmitter) {
    if (eventSubmitter && form.contains(eventSubmitter)) return eventSubmitter;
    if (lastSubmitter && form.contains(lastSubmitter)) return lastSubmitter;
    return null;
  }

  function submitFormConfirmed(form, submitter) {
    form.setAttribute('data-confirm-pass', '1');
    if (submitter && typeof form.requestSubmit === 'function') {
      form.requestSubmit(submitter);
      return;
    }
    if (submitter && submitter.name) {
      var hidden = document.createElement('input');
      hidden.type = 'hidden';
      hidden.name = submitter.name;
      hidden.value = submitter.value;
      hidden.setAttribute('data-confirm-temp', '1');
      form.appendChild(hidden);
    }
    HTMLFormElement.prototype.submit.call(form);
  }

  document.addEventListener(
    'click',
    function (e) {
      var t = e.target && e.target.closest
        ? e.target.closest('button, input[type="submit"], input[type="image"]')
        : null;
      if (t) lastSubmitter = t;
    },
    true
  );

  document.addEventListener(
    'submit',
    function (e) {
      var form = e.target;
      if (!form || form.nodeName !== 'FORM') return;
      if (form.getAttribute('data-confirm-pass') === '1') {
        form.removeAttribute('data-confirm-pass');
        var temps = form.querySelectorAll('[data-confirm-temp="1"]');
        for (var i = 0; i < temps.length; i++) {
          temps[i].parentNode.removeChild(temps[i]);
        }
        return;
      }
      var submitter = resolveSubmitter(form, e.submitter || null);
      var msg = confirmAttr(form) || confirmAttr(submitter);
      if (!msg) return;
      e.preventDefault();
      e.stopPropagation();
      var optsNode = confirmAttr(submitter) ? submitter : form;
      window.appConfirm(msg, confirmOptsFrom(optsNode)).then(function (ok) {
        if (!ok) return;
        submitFormConfirmed(form, submitter);
      });
    },
    true
  );

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bindChrome);
  } else {
    bindChrome();
  }
})();
