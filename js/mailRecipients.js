/**
 * MELD-60: chip picker — audience groups + registers + users.
 */
(function(global) {
  'use strict';

  var AUDIENCE_LABELS = {
    musicians: 'alle Musiker',
    members: 'Alle Vereinsmitglieder',
    users: 'alle User'
  };

  function parseCatalog(el) {
    if(!el) return {groups: [], registers: [], users: []};
    try {
      var c = JSON.parse(el.textContent || '{}');
      return {
        groups: Array.isArray(c.groups) ? c.groups : [],
        registers: Array.isArray(c.registers) ? c.registers : [],
        users: Array.isArray(c.users) ? c.users : []
      };
    } catch(e) {
      return {groups: [], registers: [], users: []};
    }
  }

  function parseSpec(el) {
    var fallback = {audience: 'musicians', registers: [], users: []};
    if(!el) return fallback;
    try {
      var s = JSON.parse(el.value || '{}');
      var aud = s.audience || 'musicians';
      if(['musicians', 'members', 'users'].indexOf(aud) === -1) aud = 'musicians';
      return {
        audience: aud,
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
      this.onChange = opts.onChange || function() {};
      this.spec = parseSpec(this.hiddenEl);
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
      if(!this.hiddenEl) return;
      this.hiddenEl.value = JSON.stringify({
        audience: this.spec.audience || 'musicians',
        registers: this.spec.registers.slice(),
        users: this.spec.users.slice()
      });
    },

    labelForRegister: function(id) {
      var list = this.catalog.registers;
      for(var i = 0; i < list.length; i++) {
        if(Number(list[i].id) === Number(id)) return list[i].label;
      }
      return 'Register #' + id;
    },

    labelForUser: function(id) {
      var list = this.catalog.users;
      for(var i = 0; i < list.length; i++) {
        if(Number(list[i].id) === Number(id)) {
          return list[i].label + (list[i].meta ? ' (' + list[i].meta + ')' : '');
        }
      }
      return 'User #' + id;
    },

    render: function() {
      if(!this.chipsEl) return;
      this.chipsEl.innerHTML = '';
      var self = this;
      var aud = this.spec.audience || 'musicians';
      this.chipsEl.appendChild(this.makeChip('audience', aud, AUDIENCE_LABELS[aud] || aud, true));
      this.spec.registers.forEach(function(id) {
        self.chipsEl.appendChild(self.makeChip('register', id, 'Register: ' + self.labelForRegister(id), false));
      });
      this.spec.users.forEach(function(id) {
        self.chipsEl.appendChild(self.makeChip('user', id, self.labelForUser(id), false));
      });
    },

    makeChip: function(type, id, label, locked) {
      var chip = document.createElement('span');
      chip.className = 'mail-recipient-chip mail-recipient-chip--' + type;
      chip.setAttribute('data-type', type);
      chip.setAttribute('data-id', String(id));
      var text = document.createElement('span');
      text.textContent = label;
      chip.appendChild(text);
      if(!locked) {
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'mail-recipient-chip-remove';
        btn.setAttribute('aria-label', 'Entfernen');
        btn.innerHTML = '&times;';
        var self = this;
        btn.addEventListener('click', function() {
          self.removeChip(type, id);
        });
        chip.appendChild(btn);
      }
      return chip;
    },

    removeChip: function(type, id) {
      if(type === 'audience') return;
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
      if(type === 'audience' || type === 'group') {
        var aud = String(id);
        if(['musicians', 'members', 'users'].indexOf(aud) === -1) return;
        this.spec.audience = aud;
        this.hideSuggest();
        if(this.inputEl) this.inputEl.value = '';
        this.render();
        this.syncHidden();
        this.onChange();
        return;
      }
      id = Number(id);
      if(!(id > 0)) return;
      if(type === 'register') {
        if(this.spec.registers.indexOf(id) === -1) this.spec.registers.push(id);
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
      var q = normalize(this.inputEl ? this.inputEl.value : '');
      var items = [];
      var self = this;
      var groups = this.catalog.groups.length
        ? this.catalog.groups
        : [
            {id: 'musicians', label: AUDIENCE_LABELS.musicians, meta: 'Gruppe'},
            {id: 'members', label: AUDIENCE_LABELS.members, meta: 'Gruppe'},
            {id: 'users', label: AUDIENCE_LABELS.users, meta: 'Gruppe'}
          ];

      groups.forEach(function(g) {
        if(String(g.id) === String(self.spec.audience)) return;
        if(q === '' || normalize(g.label).indexOf(q) !== -1) {
          items.push({type: 'group', id: g.id, label: g.label, meta: g.meta || 'Gruppe'});
        }
      });

      this.catalog.registers.forEach(function(r) {
        if(self.spec.registers.indexOf(Number(r.id)) !== -1) return;
        if(q !== '' && normalize(r.label).indexOf(q) === -1) return;
        if(q === '') return; // registers only after typing
        items.push({type: 'register', id: r.id, label: r.label, meta: 'Register'});
      });

      this.catalog.users.forEach(function(u) {
        if(self.spec.users.indexOf(Number(u.id)) !== -1) return;
        var hay = normalize(u.label + ' ' + (u.meta || ''));
        if(q === '' || hay.indexOf(q) === -1) {
          if(q === '') return;
          return;
        }
        items.push({type: 'user', id: u.id, label: u.label, meta: u.meta || 'Person'});
      });

      this.showSuggest(items.slice(0, 14));
    },

    showSuggest: function(items) {
      if(!this.suggestEl) return;
      this.suggestEl.innerHTML = '';
      if(!items.length) {
        this.hideSuggest();
        return;
      }
      var self = this;
      items.forEach(function(item) {
        var row = document.createElement('button');
        row.type = 'button';
        row.className = 'mail-recipient-suggest-item';
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
        else if(this.spec.registers.length) {
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
