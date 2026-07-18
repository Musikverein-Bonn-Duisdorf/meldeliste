/**
 * MELD-60 POC: chip picker for mail registers + users.
 */
(function(global) {
  'use strict';

  function parseCatalog(el) {
    if(!el) return {registers: [], users: []};
    try {
      return JSON.parse(el.textContent || '{}');
    } catch(e) {
      return {registers: [], users: []};
    }
  }

  function parseSpec(el) {
    var fallback = {allRegisters: true, registers: [], users: []};
    if(!el) return fallback;
    try {
      var s = JSON.parse(el.value || '{}');
      return {
        allRegisters: !!s.allRegisters,
        registers: Array.isArray(s.registers) ? s.registers.map(Number).filter(function(n) { return n > 0; }) : [],
        users: Array.isArray(s.users) ? s.users.map(Number).filter(function(n) { return n > 0; }) : []
      };
    } catch(e) {
      return fallback;
    }
  }

  function normalize(str) {
    return (str || '').toLowerCase().replace(/\s+/g, ' ').trim();
  }

  var MailRecipientChips = {
    init: function(opts) {
      this.catalog = parseCatalog(opts.catalogEl);
      this.chipsEl = opts.chipsEl;
      this.inputEl = opts.inputEl;
      this.suggestEl = opts.suggestEl;
      this.hiddenEl = opts.hiddenEl;
      this.allRegistersEl = opts.allRegistersEl;
      this.onChange = opts.onChange || function() {};
      this.spec = parseSpec(this.hiddenEl);
      if(this.allRegistersEl) {
        this.allRegistersEl.checked = !!this.spec.allRegisters;
        this.allRegistersEl.addEventListener('change', this.onAllRegistersToggle.bind(this));
      }
      if(this.inputEl) {
        this.inputEl.addEventListener('input', this.onInput.bind(this));
        this.inputEl.addEventListener('keydown', this.onKeydown.bind(this));
        this.inputEl.addEventListener('blur', this.onBlur.bind(this));
      }
      this.render();
      this.syncHidden();
    },

    onAllRegistersToggle: function() {
      this.spec.allRegisters = !!(this.allRegistersEl && this.allRegistersEl.checked);
      if(this.spec.allRegisters) {
        this.spec.registers = [];
      }
      this.render();
      this.syncHidden();
      this.onChange();
    },

    syncHidden: function() {
      if(!this.hiddenEl) return;
      var payload = {
        allRegisters: !!this.spec.allRegisters,
        registers: this.spec.allRegisters ? [] : this.spec.registers.slice(),
        users: this.spec.users.slice()
      };
      this.hiddenEl.value = JSON.stringify(payload);
      if(this.inputEl) {
        this.inputEl.placeholder = this.spec.allRegisters
          ? 'Person tippen (Register = alle)…'
          : 'Register oder Person tippen…';
      }
    },

    labelFor: function(type, id) {
      var list = type === 'register' ? this.catalog.registers : this.catalog.users;
      for(var i = 0; i < list.length; i++) {
        if(Number(list[i].id) === Number(id)) {
          return list[i].label + (list[i].meta ? ' (' + list[i].meta + ')' : '');
        }
      }
      return type + ' #' + id;
    },

    render: function() {
      if(!this.chipsEl) return;
      this.chipsEl.innerHTML = '';
      var self = this;
      if(!this.spec.allRegisters) {
        this.spec.registers.forEach(function(id) {
          self.chipsEl.appendChild(self.makeChip('register', id));
        });
      }
      this.spec.users.forEach(function(id) {
        self.chipsEl.appendChild(self.makeChip('user', id));
      });
    },

    makeChip: function(type, id) {
      var chip = document.createElement('span');
      chip.className = 'mail-recipient-chip mail-recipient-chip--' + type;
      chip.setAttribute('data-type', type);
      chip.setAttribute('data-id', String(id));
      var text = document.createElement('span');
      text.textContent = (type === 'register' ? 'Register: ' : '') + this.labelFor(type, id);
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'mail-recipient-chip-remove';
      btn.setAttribute('aria-label', 'Entfernen');
      btn.innerHTML = '&times;';
      var self = this;
      btn.addEventListener('click', function() {
        self.removeChip(type, id);
      });
      chip.appendChild(text);
      chip.appendChild(btn);
      return chip;
    },

    removeChip: function(type, id) {
      id = Number(id);
      if(type === 'register') {
        this.spec.registers = this.spec.registers.filter(function(x) { return x !== id; });
      }
      else {
        this.spec.users = this.spec.users.filter(function(x) { return x !== id; });
      }
      this.render();
      this.syncHidden();
      this.onChange();
    },

    addChip: function(type, id) {
      id = Number(id);
      if(!(id > 0)) return;
      if(type === 'register') {
        if(this.spec.allRegisters) {
          this.spec.allRegisters = false;
          if(this.allRegistersEl) this.allRegistersEl.checked = false;
        }
        if(this.spec.registers.indexOf(id) === -1) {
          this.spec.registers.push(id);
        }
      }
      else if(this.spec.users.indexOf(id) === -1) {
        this.spec.users.push(id);
      }
      this.hideSuggest();
      if(this.inputEl) this.inputEl.value = '';
      this.render();
      this.syncHidden();
      this.onChange();
    },

    onInput: function() {
      var q = normalize(this.inputEl.value);
      if(q.length < 1) {
        this.hideSuggest();
        return;
      }
      var items = [];
      var self = this;
      if(!this.spec.allRegisters) {
        this.catalog.registers.forEach(function(r) {
          if(normalize(r.label).indexOf(q) !== -1 && self.spec.registers.indexOf(Number(r.id)) === -1) {
            items.push({type: 'register', id: r.id, label: r.label, meta: 'Register'});
          }
        });
      }
      this.catalog.users.forEach(function(u) {
        var hay = normalize(u.label + ' ' + (u.meta || ''));
        if(hay.indexOf(q) !== -1 && self.spec.users.indexOf(Number(u.id)) === -1) {
          items.push({type: 'user', id: u.id, label: u.label, meta: u.meta || 'Person'});
        }
      });
      this.showSuggest(items.slice(0, 12));
    },

    showSuggest: function(items) {
      if(!this.suggestEl) return;
      this.suggestEl.innerHTML = '';
      if(!items.length) {
        this.hideSuggest();
        return;
      }
      var self = this;
      items.forEach(function(item, idx) {
        var row = document.createElement('button');
        row.type = 'button';
        row.className = 'mail-recipient-suggest-item';
        row.setAttribute('data-idx', String(idx));
        row.innerHTML = '<span class="mail-recipient-suggest-label"></span>'
          + '<span class="mail-recipient-suggest-meta"></span>';
        row.querySelector('.mail-recipient-suggest-label').textContent = item.label;
        row.querySelector('.mail-recipient-suggest-meta').textContent = item.meta;
        row.addEventListener('mousedown', function(e) {
          e.preventDefault();
          self.addChip(item.type, item.id);
        });
        self.suggestEl.appendChild(row);
      });
      this._suggestItems = items;
      this.suggestEl.hidden = false;
    },

    hideSuggest: function() {
      if(this.suggestEl) {
        this.suggestEl.hidden = true;
        this.suggestEl.innerHTML = '';
      }
      this._suggestItems = [];
    },

    onKeydown: function(e) {
      if(e.key === 'Enter') {
        e.preventDefault();
        if(this._suggestItems && this._suggestItems.length) {
          var first = this._suggestItems[0];
          this.addChip(first.type, first.id);
        }
      }
      else if(e.key === 'Escape') {
        this.hideSuggest();
      }
      else if(e.key === 'Backspace' && this.inputEl && this.inputEl.value === '') {
        if(this.spec.users.length) {
          this.removeChip('user', this.spec.users[this.spec.users.length - 1]);
        }
        else if(!this.spec.allRegisters && this.spec.registers.length) {
          this.removeChip('register', this.spec.registers[this.spec.registers.length - 1]);
        }
      }
    },

    onBlur: function() {
      var self = this;
      setTimeout(function() { self.hideSuggest(); }, 150);
    }
  };

  global.MailRecipientChips = MailRecipientChips;
})(window);
