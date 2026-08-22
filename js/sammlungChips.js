/**
 * MELD-197: chip picker for Archiv-Sammlungen (id list in hidden JSON).
 */
(function(global) {
  'use strict';

  function normalizeIdList(list) {
    if(!Array.isArray(list)) return [];
    return list.map(Number).filter(function(n) { return n > 0; }).filter(function(n, i, a) {
      return a.indexOf(n) === i;
    });
  }

  function parseCatalog(el) {
    if(!el) return [];
    try {
      var c = JSON.parse(el.textContent || '[]');
      if(!Array.isArray(c)) return [];
      return c.map(function(row) {
        return {
          id: Number(row.id) || 0,
          label: String(row.label || row.name || '').trim()
        };
      }).filter(function(row) { return row.id > 0 && row.label !== ''; });
    } catch(e) {
      return [];
    }
  }

  function parseIds(el) {
    if(!el) return [];
    try {
      return normalizeIdList(JSON.parse(el.value || '[]'));
    } catch(e) {
      return [];
    }
  }

  function normalize(str) {
    return (str || '').toLowerCase().replace(/\s+/g, ' ').trim();
  }

  var SammlungChips = {
    init: function(opts) {
      this.catalog = parseCatalog(opts.catalogEl);
      this.chipsEl = opts.chipsEl;
      this.inputEl = opts.inputEl;
      this.suggestEl = opts.suggestEl;
      this.hiddenEl = opts.hiddenEl;
      this.ids = parseIds(this.hiddenEl);
      this._active = -1;
      this._chipSuggest = global.ChipSuggest;
      if(this.inputEl) {
        this.inputEl.addEventListener('input', this.onInput.bind(this));
        this.inputEl.addEventListener('keydown', this.onKeydown.bind(this));
        this.inputEl.addEventListener('blur', this.onBlur.bind(this));
        this.inputEl.addEventListener('focus', this.onInput.bind(this));
      }
      this.render();
      this.syncHidden();
    },

    syncHidden: function() {
      if(this.hiddenEl) {
        this.hiddenEl.value = JSON.stringify(this.ids.slice());
      }
    },

    labelFor: function(id) {
      id = Number(id);
      for(var i = 0; i < this.catalog.length; i++) {
        if(this.catalog[i].id === id) return this.catalog[i].label;
      }
      return 'Sammlung #' + id;
    },

    render: function() {
      if(!this.chipsEl) return;
      var self = this;
      this.chipsEl.innerHTML = '';
      this.ids.forEach(function(id) {
        var chip = document.createElement('span');
        chip.className = 'mail-recipient-chip mail-recipient-chip--instrument';
        chip.setAttribute('role', 'listitem');
        chip.textContent = self.labelFor(id) + ' ';
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'mail-recipient-chip-remove';
        btn.setAttribute('aria-label', 'Entfernen');
        btn.textContent = '×';
        btn.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation();
          self.removeId(id);
        });
        chip.appendChild(btn);
        self.chipsEl.appendChild(chip);
      });
    },

    addId: function(id) {
      id = Number(id);
      if(id < 1 || this.ids.indexOf(id) !== -1) return;
      this.ids.push(id);
      this.syncHidden();
      this.render();
      if(this.inputEl) this.inputEl.value = '';
      this.hideSuggest();
    },

    removeId: function(id) {
      id = Number(id);
      this.ids = this.ids.filter(function(n) { return n !== id; });
      this.syncHidden();
      this.render();
    },

    matches: function(q) {
      q = normalize(q);
      var self = this;
      var selected = {};
      this.ids.forEach(function(id) { selected[id] = true; });
      return this.catalog.filter(function(row) {
        if(selected[row.id]) return false;
        if(q === '') return true;
        return normalize(row.label).indexOf(q) !== -1;
      }).slice(0, 12);
    },

    hideSuggest: function() {
      if(!this.suggestEl) return;
      this.suggestEl.hidden = true;
      this.suggestEl.innerHTML = '';
      this._active = -1;
    },

    showSuggest: function(items) {
      if(!this.suggestEl) return;
      var self = this;
      this.suggestEl.innerHTML = '';
      if(!items.length) {
        this.hideSuggest();
        return;
      }
      items.forEach(function(row, idx) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = self._chipSuggest
          ? self._chipSuggest.itemClassName(idx, self._active)
          : 'mail-recipient-suggest-item';
        btn.textContent = row.label;
        btn.addEventListener('mousedown', function(e) {
          e.preventDefault();
          self.addId(row.id);
        });
        self.suggestEl.appendChild(btn);
      });
      this.suggestEl.hidden = false;
      if(this._chipSuggest && this._active >= 0) {
        this._chipSuggest.highlight(this.suggestEl, this._active, false);
      }
    },

    onInput: function() {
      var q = this.inputEl ? this.inputEl.value : '';
      this._active = -1;
      this.showSuggest(this.matches(q));
    },

    onBlur: function() {
      var self = this;
      setTimeout(function() { self.hideSuggest(); }, 150);
    },

    onKeydown: function(e) {
      var list = this.matches(this.inputEl ? this.inputEl.value : '');
      if(e.key === 'ArrowDown' || e.key === 'ArrowUp') {
        if(!list.length) return;
        e.preventDefault();
        var next = this._chipSuggest
          ? this._chipSuggest.stepIndex(e.key, this._active, list.length, true)
          : this._active;
        if(next === null) return;
        this._active = next;
        this.showSuggest(list);
        if(this._chipSuggest) {
          this._chipSuggest.highlight(this.suggestEl, this._active, true);
        }
      }
      else if(e.key === 'Enter') {
        if(list.length) {
          e.preventDefault();
          var idx = this._active >= 0 ? this._active : 0;
          if(list[idx]) this.addId(list[idx].id);
        }
      }
      else if(e.key === 'Backspace' && this.inputEl && this.inputEl.value === '' && this.ids.length) {
        this.removeId(this.ids[this.ids.length - 1]);
      }
      else if(e.key === 'Escape') {
        this.hideSuggest();
      }
    }
  };

  global.SammlungChips = SammlungChips;
})(window);
