(function () {
  function resetAdminNavGroups() {
    var groups = document.querySelectorAll('.admin-nav-group.admin-nav-open');
    for (var i = 0; i < groups.length; i++) {
      groups[i].classList.remove('admin-nav-open');
    }
  }

  function isNarrow() {
    return window.matchMedia && window.matchMedia('(max-width: 600px)').matches;
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
      if (open) {
        backdrop.hidden = false;
      } else {
        backdrop.hidden = true;
      }
    }
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.classList.toggle('app-nav-more-open', !!open);
    if (!open) {
      resetAdminNavGroups();
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
      }
    });
  }

  document.addEventListener('click', function (ev) {
    var btn = ev.target.closest ? ev.target.closest('.admin-nav-group > .w3-button') : null;
    if (!btn) return;
    var group = btn.parentNode;
    if (!group || !group.classList || !group.classList.contains('admin-nav-group')) return;
    ev.preventDefault();
    ev.stopPropagation();
    var open = group.classList.contains('admin-nav-open');
    resetAdminNavGroups();
    if (!open) {
      group.classList.add('admin-nav-open');
    }
  });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initMore);
  } else {
    initMore();
  }
})();
