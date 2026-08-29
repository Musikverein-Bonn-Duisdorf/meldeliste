(function () {
  function isIosWebKit() {
    var ua = navigator.userAgent || '';
    if (/iPhone|iPad|iPod/i.test(ua)) {
      return true;
    }
    return navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1;
  }

  if (isIosWebKit()) {
    document.documentElement.classList.add('ios-webkit-nav');
  }

  function isNarrow() {
    return window.matchMedia && window.matchMedia('(max-width: 992px)').matches;
  }

  /** Close open admin accordion groups; on desktop keep the active-page section open. */
  function resetAdminNavGroups() {
    var groups = document.querySelectorAll('.admin-nav-group.admin-nav-open');
    for (var i = 0; i < groups.length; i++) {
      var g = groups[i];
      if (!isNarrow() && g.classList.contains('admin-nav-current-group')) {
        continue;
      }
      g.classList.remove('admin-nav-open');
    }
  }

  function ensureCurrentAdminNavOpen() {
    if (isNarrow()) return;
    var cur = document.querySelector('.admin-nav-group.admin-nav-current-group');
    if (cur) {
      cur.classList.add('admin-nav-open');
    }
  }

  function setMoreOpen(open) {
    var panel = document.getElementById('appNavMorePanel');
    var backdrop = document.getElementById('appNavMoreBackdrop');
    var toggle = document.getElementById('appNavMoreToggle');
    if (!panel || !toggle) return;
    if (!isNarrow()) {
      open = false;
    }
    panel.classList.toggle('is-open', !!open);
    if (backdrop) {
      backdrop.hidden = !open;
    }
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.classList.toggle('app-nav-more-open', !!open);
    if (open && panel) {
      panel.scrollTop = 0;
    }
    if (!open) {
      resetAdminNavGroups();
      ensureCurrentAdminNavOpen();
    }
  }

  function initMore() {
    var toggle = document.getElementById('appNavMoreToggle');
    var closeBtn = document.getElementById('appNavMoreClose');
    var backdrop = document.getElementById('appNavMoreBackdrop');
    if (toggle) {
      toggle.addEventListener('click', function (e) {
        e.preventDefault();
        var open = toggle.getAttribute('aria-expanded') === 'true';
        setMoreOpen(!open);
      });
    }
    if (closeBtn) {
      closeBtn.addEventListener('click', function () {
        setMoreOpen(false);
      });
    }
    if (backdrop) {
      backdrop.addEventListener('click', function () {
        setMoreOpen(false);
      });
    }
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') {
        setMoreOpen(false);
      }
    });
    window.addEventListener('resize', function () {
      if (!isNarrow()) {
        setMoreOpen(false);
        ensureCurrentAdminNavOpen();
      }
    });
    ensureCurrentAdminNavOpen();
  }

  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest ? ev.target.closest('.admin-nav-group > .w3-button') : null;
    if (!btn) return;
    var group = btn.parentNode;
    if (!group || !group.classList || !group.classList.contains('admin-nav-group')) return;
    ev.preventDefault();
    ev.stopPropagation();

    var willOpen = !group.classList.contains('admin-nav-open');
    var others = document.querySelectorAll('.admin-nav-group.admin-nav-open');
    for (var i = 0; i < others.length; i++) {
      var g = others[i];
      if (g === group) continue;
      if (!isNarrow() && g.classList.contains('admin-nav-current-group')) continue;
      g.classList.remove('admin-nav-open');
    }
    if (willOpen) {
      group.classList.add('admin-nav-open');
    } else {
      group.classList.remove('admin-nav-open');
    }
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMore);
  } else {
    initMore();
  }

  function updateBrowserUiBottom() {
    if (!isNarrow() || !isIosWebKit()) {
      document.documentElement.style.removeProperty('--browser-ui-bottom');
      return;
    }

    var bottom = 0;
    var innerH = window.innerHeight || 0;

    if (window.visualViewport) {
      var vv = window.visualViewport;
      var fromVv = innerH - vv.height - vv.offsetTop;
      var fromPage = innerH - vv.height - (vv.pageTop || 0);
      bottom = Math.max(bottom, fromVv, fromPage, 0);
    }

    // Safari tab bar: innerHeight shrinks while toolbar visible; track peak height.
    if (!updateBrowserUiBottom._peakInner) {
      updateBrowserUiBottom._peakInner = innerH;
    }
    if (innerH > updateBrowserUiBottom._peakInner) {
      updateBrowserUiBottom._peakInner = innerH;
    }
    bottom = Math.max(bottom, updateBrowserUiBottom._peakInner - innerH, 0);
    bottom = Math.min(bottom, Math.round(innerH * 0.3));

    document.documentElement.style.setProperty('--browser-ui-bottom', bottom.toFixed(1) + 'px');
  }

  function scheduleBrowserUiBottomUpdate() {
    if (updateBrowserUiBottom._raf) return;
    updateBrowserUiBottom._raf = window.requestAnimationFrame(function () {
      updateBrowserUiBottom._raf = 0;
      updateBrowserUiBottom();
    });
  }

  if (isIosWebKit()) {
    if (window.visualViewport) {
      window.visualViewport.addEventListener('resize', scheduleBrowserUiBottomUpdate);
      window.visualViewport.addEventListener('scroll', scheduleBrowserUiBottomUpdate);
    }
    window.addEventListener('resize', scheduleBrowserUiBottomUpdate);
    window.addEventListener('scroll', scheduleBrowserUiBottomUpdate, { passive: true });
    window.addEventListener('orientationchange', function () {
      updateBrowserUiBottom._peakInner = 0;
      scheduleBrowserUiBottomUpdate();
    });
    window.addEventListener('pageshow', scheduleBrowserUiBottomUpdate);
    if (document.readyState === 'loading') {
      document.addEventListener('DOMContentLoaded', scheduleBrowserUiBottomUpdate);
    }
    scheduleBrowserUiBottomUpdate();
    window.setTimeout(scheduleBrowserUiBottomUpdate, 100);
    window.setTimeout(scheduleBrowserUiBottomUpdate, 500);
  }
})();
