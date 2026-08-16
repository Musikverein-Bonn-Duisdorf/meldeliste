/**
 * MELD-199: single-user chip picker for inventar loan form.
 */
(function(global) {
  'use strict';

  function normalize(str) {
    return (str || '').toLowerCase().replace(/\s+/g, ' ').trim();
  }

  function parseCatalog(el) {
    if(!el) return [];
    try {
      var c = JSON.parse(el.textContent || '[]');
      if(!Array.isArray(c)) return [];
      return c.map(function(row) {
        return {
          id: Number(row.id),
          label: String(row.label || '').trim(),
          meta: String(row.meta || '').trim()
        };
      }).filter(function(row) {
        return row.label !== '' && !isNaN(row.id);
      });
    } catch(e) {
      return [];
    }
  }

  var LoanUserChips = {
    initIn: function(root) {
      root = root || document;
      var wrap = root.querySelector('.inventar-loan-new .loan-user-chip-field');
      if(!wrap || wrap.getAttribute('data-loan-user-chips') === '1') return;
      var chipsEl = wrap.querySelector('#loanUserChips') || root.querySelector('#loanUserChips');
      var inputEl = wrap.querySelector('#loanUserInput') || root.querySelector('#loanUserInput');
      var suggestEl = wrap.querySelector('#loanUserSuggest') || root.querySelector('#loanUserSuggest');
      var hiddenEl = wrap.querySelector('#loanUserId') || root.querySelector('#loanUserId');
      var catalogEl = wrap.querySelector('#loanUserCatalog') || root.querySelector('#loanUserCatalog');
      if(!chipsEl || !inputEl || !suggestEl || !hiddenEl) return;
      wrap.setAttribute('data-loan-user-chips', '1');

      var catalog = parseCatalog(catalogEl);
      var selectedId = parseInt(hiddenEl.value, 10);
      if(isNaN(selectedId)) selectedId = 0;
      var activeIndex = -1;

      function labelFor(id) {
        id = Number(id);
        for(var i = 0; i < catalog.length; i++) {
          if(catalog[i].id === id) return catalog[i].label;
        }
        return id > 0 ? ('#' + id) : '';
      }

      function syncHidden() {
        hiddenEl.value = String(selectedId);
      }

      function render() {
        chipsEl.innerHTML = '';
        syncHidden();
        if(!(selectedId > 0)) {
          return;
        }
        var chip = document.createElement('span');
        chip.className = 'mail-recipient-chip mail-recipient-chip--user';
        chip.setAttribute('role', 'listitem');
        var text = document.createElement('span');
        text.textContent = labelFor(selectedId) || ('#' + selectedId);
        chip.appendChild(text);
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'mail-recipient-chip-remove';
        btn.setAttribute('aria-label', 'Entfernen');
        btn.innerHTML = '&times;';
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          selectedId = 0;
          syncHidden();
          render();
          inputEl.focus();
        });
        chip.appendChild(btn);
        chipsEl.appendChild(chip);
      }

      function matches(q) {
        q = normalize(q);
        return catalog.filter(function(row) {
          if(row.id === selectedId && selectedId > 0) return false;
          // Verein (id 0) only when searching — empty default is already User=0
          if(row.id === 0 && q === '') return false;
          if(q === '') return row.id > 0;
          return normalize(row.label).indexOf(q) !== -1
            || (row.meta && normalize(row.meta).indexOf(q) !== -1);
        }).slice(0, 12);
      }

      function hideSuggest() {
        suggestEl.hidden = true;
        suggestEl.innerHTML = '';
        activeIndex = -1;
      }

      function showSuggest() {
        var items = matches(inputEl.value);
        suggestEl.innerHTML = '';
        if(!items.length) {
          hideSuggest();
          return;
        }
        items.forEach(function(row, idx) {
          var btn = document.createElement('button');
          btn.type = 'button';
          btn.className = 'mail-recipient-suggest-item'
            + (idx === activeIndex ? ' mail-recipient-suggest-item--active' : '');
          var label = document.createElement('span');
          label.textContent = row.label;
          btn.appendChild(label);
          if(row.meta) {
            var meta = document.createElement('span');
            meta.className = 'mail-recipient-suggest-meta';
            meta.textContent = row.meta;
            btn.appendChild(meta);
          }
          btn.addEventListener('mousedown', function(e) {
            e.preventDefault();
            selectUser(row.id);
          });
          suggestEl.appendChild(btn);
        });
        suggestEl.hidden = false;
      }

      function selectUser(id) {
        selectedId = Number(id);
        if(isNaN(selectedId)) selectedId = 0;
        inputEl.value = '';
        hideSuggest();
        render();
        inputEl.focus();
      }

      inputEl.addEventListener('input', function() {
        activeIndex = -1;
        showSuggest();
      });
      inputEl.addEventListener('focus', showSuggest);
      inputEl.addEventListener('blur', function() {
        window.setTimeout(hideSuggest, 150);
      });
      inputEl.addEventListener('keydown', function(e) {
        var items = matches(inputEl.value);
        if(e.key === 'ArrowDown') {
          e.preventDefault();
          if(!items.length) return;
          activeIndex = (activeIndex + 1) % items.length;
          showSuggest();
        } else if(e.key === 'ArrowUp') {
          e.preventDefault();
          if(!items.length) return;
          activeIndex = activeIndex <= 0 ? items.length - 1 : activeIndex - 1;
          showSuggest();
        } else if(e.key === 'Enter') {
          if(activeIndex >= 0 && items[activeIndex]) {
            e.preventDefault();
            selectUser(items[activeIndex].id);
          } else if(items.length === 1) {
            e.preventDefault();
            selectUser(items[0].id);
          }
        } else if(e.key === 'Backspace' && !inputEl.value) {
          selectedId = 0;
          render();
        } else if(e.key === 'Escape') {
          hideSuggest();
        }
      });

      render();
    }
  };

  global.LoanUserChips = LoanUserChips;
})(window);
