/**
 * Flash toasts: success auto-dismisses; errors require close.
 */
(function () {
  function dismissToast(el) {
    if (!el || el.classList.contains('app-toast--out')) {
      return;
    }
    el.classList.add('app-toast--out');
    window.setTimeout(function () {
      if (el.parentNode) {
        el.parentNode.removeChild(el);
      }
      var host = document.querySelector('.app-toast-host');
      if (host && !host.querySelector('.app-toast')) {
        host.parentNode && host.parentNode.removeChild(host);
      }
    }, 280);
  }

  function bindToast(el) {
    var closeBtn = el.querySelector('.app-toast-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', function (e) {
        e.preventDefault();
        dismissToast(el);
      });
    }
    var ms = el.getAttribute('data-autodismiss');
    if (ms) {
      var delay = parseInt(ms, 10);
      if (delay > 0) {
        window.setTimeout(function () {
          dismissToast(el);
        }, delay);
      }
    }
  }

  function init() {
    var toasts = document.querySelectorAll('.app-toast-host .app-toast');
    for (var i = 0; i < toasts.length; i++) {
      bindToast(toasts[i]);
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
