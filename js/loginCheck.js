(function() {
  'use strict';

  function byId(id) {
    return document.getElementById(id);
  }

  function qs(sel, root) {
    return (root || document).querySelector(sel);
  }

  function initLoginCheck() {
    var loginInput = byId('profile-login');
    if(!loginInput || loginInput.disabled) {
      return;
    }

    var form = loginInput.form || qs('form');
    var vornameInput = byId('profile-vorname') || (form && qs('input[name="Vorname"]', form));
    var nachnameInput = byId('profile-nachname') || (form && qs('input[name="Nachname"]', form));
    var excludeId = 0;
    var indexInput = form && qs('input[name="Index"]', form);
    if(indexInput && indexInput.value) {
      excludeId = parseInt(indexInput.value, 10) || 0;
    }

    var loginTouched = loginInput.value !== '';
    var checkTimer = null;
    var suggestTimer = null;
    var checkSeq = 0;
    var suggestSeq = 0;
    var loginOk = true;
    var checkPending = false;

    function setLoginValidity(message, showTip) {
      var msg = message || '';
      loginInput.setCustomValidity(msg);
      if(msg) {
        loginInput.setAttribute('title', msg);
        loginInput.setAttribute('aria-invalid', 'true');
        loginOk = false;
        if(showTip) {
          // Native Browser-Hinweis sofort nach der Live-Prüfung (ohne Speichern/Hover).
          try {
            loginInput.reportValidity();
          }
          catch(e) { /* ignore */ }
        }
      }
      else {
        loginInput.removeAttribute('title');
        loginInput.removeAttribute('aria-invalid');
        loginOk = true;
      }
    }

    function postForm(params, cb) {
      var body = Object.keys(params).map(function(k) {
        return encodeURIComponent(k) + '=' + encodeURIComponent(params[k] == null ? '' : params[k]);
      }).join('&');
      var xhr = new XMLHttpRequest();
      xhr.open('POST', 'checkLogin.php', true);
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

    function runCheck() {
      var seq = ++checkSeq;
      var val = loginInput.value;
      if(!val) {
        // Leer: Server vergibt Login beim Speichern.
        setLoginValidity('');
        checkPending = false;
        return;
      }
      checkPending = true;
      postForm({
        action: 'check',
        login: val,
        excludeUserId: excludeId
      }, function(status, data) {
        if(seq !== checkSeq) {
          return;
        }
        checkPending = false;
        if(!data || !data.ok) {
          setLoginValidity('Login konnte nicht geprüft werden.', true);
          return;
        }
        if(!data.valid) {
          setLoginValidity(data.message || 'Login ungültig.', true);
          return;
        }
        if(!data.available) {
          setLoginValidity(data.message || 'Login bereits vergeben.', true);
          return;
        }
        setLoginValidity('');
      });
    }

    function runSuggest() {
      if(loginTouched && loginInput.value !== '') {
        return;
      }
      var vor = vornameInput ? vornameInput.value : '';
      var nach = nachnameInput ? nachnameInput.value : '';
      if(!vor && !nach) {
        return;
      }
      var seq = ++suggestSeq;
      postForm({
        action: 'suggest',
        vorname: vor,
        nachname: nach,
        excludeUserId: excludeId
      }, function(status, data) {
        if(seq !== suggestSeq) {
          return;
        }
        if(!data || !data.ok || !data.suggest) {
          return;
        }
        if(loginTouched && loginInput.value !== '') {
          return;
        }
        loginInput.value = data.suggest;
        setLoginValidity(data.available === false ? 'Login bereits vergeben.' : '');
      });
    }

    loginInput.addEventListener('input', function() {
      loginTouched = true;
      if(checkTimer) {
        clearTimeout(checkTimer);
      }
      checkTimer = setTimeout(runCheck, 250);
    });

    function onNameInput() {
      if(loginTouched && loginInput.value !== '') {
        return;
      }
      if(suggestTimer) {
        clearTimeout(suggestTimer);
      }
      suggestTimer = setTimeout(runSuggest, 250);
    }

    if(vornameInput) {
      vornameInput.addEventListener('input', onNameInput);
    }
    if(nachnameInput) {
      nachnameInput.addEventListener('input', onNameInput);
    }

    if(form) {
      form.addEventListener('submit', function(ev) {
        if(checkPending) {
          ev.preventDefault();
          loginInput.setCustomValidity('Login wird noch geprüft.');
          loginInput.reportValidity();
          return;
        }
        if(!loginOk) {
          ev.preventDefault();
          loginInput.reportValidity();
        }
      });
    }

    if(loginInput.value) {
      runCheck();
    }
    else if(excludeId <= 0) {
      runSuggest();
    }
  }

  if(document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initLoginCheck);
  }
  else {
    initLoginCheck();
  }
})();
