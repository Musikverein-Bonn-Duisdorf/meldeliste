(function() {
  'use strict';

  function byId(id) {
    return document.getElementById(id);
  }

  function postAction(kind, csrf, action, cb) {
    var body = 'kind=' + encodeURIComponent(kind)
      + '&action=' + encodeURIComponent(action)
      + '&csrf_token=' + encodeURIComponent(csrf);
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'ackIntro.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.onreadystatechange = function() {
      if(xhr.readyState !== 4) {
        return;
      }
      var data = null;
      try {
        data = JSON.parse(xhr.responseText || '{}');
      }
      catch(e) {
        data = null;
      }
      cb(xhr.status, data);
    };
    xhr.send(body);
  }

  function init() {
    var modal = byId('introModal');
    if(!modal) {
      return;
    }
    var kind = modal.getAttribute('data-intro-kind') || 'intro';
    var csrf = modal.getAttribute('data-intro-csrf') || '';
    var pages = [];
    try {
      pages = JSON.parse(modal.getAttribute('data-intro-pages') || '[]');
    }
    catch(e) {
      pages = [];
    }
    if(!pages.length) {
      modal.style.display = 'none';
      return;
    }

    var titleEl = byId('introModalTitle');
    var contentEl = byId('introModalContent');
    var progressEl = byId('introModalProgress');
    var ackWrap = byId('introModalAckWrap');
    var ackBox = byId('introModalAck');
    var btnPrev = byId('introModalPrev');
    var btnNext = byId('introModalNext');
    var btnConfirm = byId('introModalConfirm');
    var btnClose = byId('introModalClose');
    var idx = 0;
    var busy = false;

    function hideModal() {
      modal.style.display = 'none';
    }

    function dismissSession() {
      if(busy) {
        return;
      }
      busy = true;
      postAction(kind, csrf, 'dismiss', function(status, data) {
        busy = false;
        hideModal();
        if(!data || !data.ok) {
          if(typeof window.appAlert === 'function') {
            window.appAlert('Schließen fehlgeschlagen.', { title: 'Einführung' });
          }
        }
      });
    }

    function applyDemoMeldeRowTint(demo, wert) {
      var rowBase = demo.getAttribute('data-row-base') || 'intro-demo-melde melde-row w3-card-4';
      var extra = demo.getAttribute('data-row-extra-' + wert) || '';
      var style = demo.getAttribute('data-row-style-' + wert) || '';
      demo.className = (rowBase + (extra ? ' ' + extra : '')).replace(/\s+/g, ' ').trim();
      if(style) {
        demo.setAttribute('style', style);
      }
      else {
        demo.removeAttribute('style');
      }
    }

    function applyDemoMeldeWert(demo, wert) {
      var colors = {
        1: demo.getAttribute('data-color-yes') || 'w3-green',
        2: demo.getAttribute('data-color-no') || 'w3-red',
        3: demo.getAttribute('data-color-maybe') || 'w3-yellow'
      };
      var disabled = demo.getAttribute('data-color-disabled') || 'w3-light-grey';
      var base = 'melde-btn w3-btn w3-border w3-border-black w3-center';
      demo.setAttribute('data-wert', String(wert));
      applyDemoMeldeRowTint(demo, wert);
      var buttons = demo.querySelectorAll('.melde-btn[data-wert]');
      for(var i = 0; i < buttons.length; i++) {
        var b = buttons[i];
        var w = parseInt(b.getAttribute('data-wert'), 10);
        var color = (wert && wert !== w) ? disabled : (colors[w] || disabled);
        b.className = base + ' ' + color;
        b.setAttribute('aria-pressed', wert === w ? 'true' : 'false');
      }
    }

    function bindDemoMelde(root) {
      if(!root) {
        return;
      }
      var demos = root.querySelectorAll('.intro-demo-melde');
      for(var d = 0; d < demos.length; d++) {
        (function(demo) {
          if(demo.getAttribute('data-demo-bound') === '1') {
            return;
          }
          demo.setAttribute('data-demo-bound', '1');
          demo.addEventListener('click', function(ev) {
            var btn = ev.target.closest ? ev.target.closest('.melde-btn[data-wert]') : null;
            if(!btn || !demo.contains(btn)) {
              return;
            }
            ev.preventDefault();
            ev.stopPropagation();
            var wert = parseInt(btn.getAttribute('data-wert'), 10);
            var current = parseInt(demo.getAttribute('data-wert') || '0', 10);
            if(!wert || wert === current) {
              return;
            }
            applyDemoMeldeWert(demo, wert);
          });
        })(demos[d]);
      }
    }

    function render() {
      var page = pages[idx] || { title: '', html: '' };
      if(titleEl) {
        titleEl.textContent = page.title || '';
      }
      if(contentEl) {
        contentEl.innerHTML = page.html || '';
        contentEl.scrollTop = 0;
        bindDemoMelde(contentEl);
      }
      if(progressEl) {
        progressEl.textContent = (idx + 1) + ' / ' + pages.length;
      }
      var last = idx >= pages.length - 1;
      if(btnPrev) {
        btnPrev.hidden = idx === 0;
      }
      if(btnNext) {
        btnNext.hidden = last;
      }
      if(btnConfirm) {
        btnConfirm.hidden = !last;
      }
      if(ackWrap) {
        ackWrap.hidden = !last;
      }
      if(last && ackBox && btnConfirm) {
        btnConfirm.disabled = !ackBox.checked;
      }
    }

    if(btnClose) {
      btnClose.addEventListener('click', function(ev) {
        ev.preventDefault();
        dismissSession();
      });
    }

    if(btnPrev) {
      btnPrev.addEventListener('click', function() {
        if(idx > 0) {
          idx--;
          render();
        }
      });
    }
    if(btnNext) {
      btnNext.addEventListener('click', function() {
        if(idx < pages.length - 1) {
          idx++;
          render();
        }
      });
    }
    if(ackBox && btnConfirm) {
      ackBox.addEventListener('change', function() {
        btnConfirm.disabled = !ackBox.checked;
      });
    }
    if(btnConfirm) {
      btnConfirm.addEventListener('click', function() {
        if(busy || !ackBox || !ackBox.checked) {
          return;
        }
        busy = true;
        btnConfirm.disabled = true;
        postAction(kind, csrf, 'ack', function(status, data) {
          busy = false;
          if(!data || !data.ok) {
            btnConfirm.disabled = !ackBox.checked;
            if(typeof window.appAlert === 'function') {
              window.appAlert('Bestätigung fehlgeschlagen.', { title: 'Einführung' });
            }
            return;
          }
          if(data.next === 'changelog' || data.next === 'intro') {
            window.location.reload();
            return;
          }
          hideModal();
        });
      });
    }

    // Backdrop: same as X (session dismiss, no Ack)
    modal.addEventListener('click', function(ev) {
      if(ev.target === modal) {
        ev.preventDefault();
        dismissSession();
      }
    });

    render();
  }

  if(document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  }
  else {
    init();
  }
})();
