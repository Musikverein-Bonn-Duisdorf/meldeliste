/**
 * MELD-60/61: chip picker — roles + registers + users + named mailGroups.
 */
(function(global) {
  'use strict';

  var GROUP_LABELS = {
    musicians: 'Alle Musiker',
    members: 'Alle Vereinsmitglieder',
    nonmembers: 'Alle Nicht-Mitglieder',
    users: 'Alle User'
  };
  var GROUP_IDS = ['musicians', 'members', 'nonmembers', 'users'];

  function parseCatalog(el) {
    if(!el) return {groups: [], registers: [], users: [], mailGroups: []};
    try {
      var c = JSON.parse(el.textContent || '{}');
      return {
        groups: Array.isArray(c.groups) ? c.groups : [],
        registers: Array.isArray(c.registers) ? c.registers : [],
        users: Array.isArray(c.users) ? c.users : [],
        mailGroups: Array.isArray(c.mailGroups) ? c.mailGroups : []
      };
    } catch(e) {
      return {groups: [], registers: [], users: [], mailGroups: []};
    }
  }

  function normalizeGroups(list) {
    var out = [];
    if(!Array.isArray(list)) return out;
    list.forEach(function(g) {
      g = String(g);
      if(GROUP_IDS.indexOf(g) !== -1 && out.indexOf(g) === -1) out.push(g);
    });
    return out;
  }

  function normalizeIdList(list) {
    if(!Array.isArray(list)) return [];
    return list.map(Number).filter(function(n) { return n > 0; }).filter(function(n, i, a) {
      return a.indexOf(n) === i;
    });
  }

  function emptySpec() {
    return {groups: [], registers: [], users: [], mailGroups: []};
  }

  function parseSpec(el, opts) {
    opts = opts || {};
    var allowEmpty = !!opts.allowEmpty;
    var defaultGroups = Array.isArray(opts.defaultGroups) ? opts.defaultGroups.slice() : ['musicians'];
    var fallback = emptySpec();
    if(!allowEmpty && defaultGroups.length) {
      fallback.groups = normalizeGroups(defaultGroups);
    }
    if(!el) return fallback;
    try {
      var s = JSON.parse(el.value || '{}');
      var groups = normalizeGroups(s.groups);
      if(!groups.length && s.audience && GROUP_IDS.indexOf(String(s.audience)) !== -1) {
        groups = [String(s.audience)];
      }
      var registers = normalizeIdList(s.registers);
      var users = normalizeIdList(s.users);
      var mailGroups = normalizeIdList(s.mailGroups);
      if(!groups.length && !registers.length && !users.length && !mailGroups.length) {
        if(!allowEmpty && defaultGroups.length) {
          groups = normalizeGroups(defaultGroups);
        }
      }
      return {
        groups: groups,
        registers: registers,
        users: users,
        mailGroups: mailGroups
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
      this.countEl = opts.countEl || null;
      this.countUrl = opts.countUrl || 'mailRecipientCount.php';
      this.countLabel = opts.countLabel || 'Empfänger';
      this.jobId = opts.jobId ? Number(opts.jobId) : 0;
      this.saveUrl = opts.saveUrl || 'mailSaveRecipients.php';
      this.allowEmpty = !!opts.allowEmpty;
      this.defaultGroups = Array.isArray(opts.defaultGroups) ? opts.defaultGroups : ['musicians'];
      this.onChange = opts.onChange || function() {};
      this._countSeq = 0;
      this._saveSeq = 0;
      this.spec = parseSpec(this.hiddenEl, {
        allowEmpty: this.allowEmpty,
        defaultGroups: this.defaultGroups
      });
      if(this.inputEl) {
        this.inputEl.addEventListener('input', this.onInput.bind(this));
        this.inputEl.addEventListener('keydown', this.onKeydown.bind(this));
        this.inputEl.addEventListener('blur', this.onBlur.bind(this));
        this.inputEl.addEventListener('focus', this.onInput.bind(this));
      }
      this.render();
      this.syncHidden();
      this.scheduleCountRefresh();
      this.scheduleSave();
    },

    syncHidden: function() {
      if(!this.hiddenEl) return;
      this.hiddenEl.value = JSON.stringify({
        groups: this.spec.groups.slice(),
        registers: this.spec.registers.slice(),
        users: this.spec.users.slice(),
        mailGroups: this.spec.mailGroups.slice()
      });
    },

    scheduleCountRefresh: function() {
      if(!this.countEl) return;
      var self = this;
      if(this._countTimer) clearTimeout(this._countTimer);
      this._countTimer = setTimeout(function() {
        self.refreshCount();
      }, 180);
    },

    scheduleSave: function() {
      if(!(this.jobId > 0) || !this.hiddenEl) return;
      var self = this;
      if(this._saveTimer) clearTimeout(this._saveTimer);
      this._saveTimer = setTimeout(function() {
        self.saveToDraft();
      }, 250);
    },

    saveToDraft: function() {
      if(!(this.jobId > 0) || !this.hiddenEl) return;
      var self = this;
      var seq = ++this._saveSeq;
      var body = 'id=' + encodeURIComponent(String(this.jobId))
        + '&recipientSpec=' + encodeURIComponent(this.hiddenEl.value || '{}');
      var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
      xhr.onreadystatechange = function() {
        if(xhr.readyState !== 4) return;
        if(seq !== self._saveSeq) return;
      };
      xhr.open('POST', this.saveUrl, true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
      xhr.send(body);
    },

    refreshCount: function() {
      if(!this.countEl) return;
      var self = this;
      var seq = ++this._countSeq;
      this.countEl.classList.add('mail-recipient-count--loading');
      var body = 'recipientSpec=' + encodeURIComponent(this.hiddenEl ? this.hiddenEl.value : '{}');
      if(this.allowEmpty) {
        body += '&requireMail=0';
      }
      var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
      xhr.onreadystatechange = function() {
        if(xhr.readyState !== 4) return;
        if(seq !== self._countSeq) return;
        self.countEl.classList.remove('mail-recipient-count--loading');
        if(xhr.status !== 200) {
          self.countEl.textContent = self.countLabel + ': ?';
          return;
        }
        try {
          var data = JSON.parse(xhr.responseText);
          var n = data && typeof data.count === 'number' ? data.count : 0;
          var label = self.countLabel;
          self.countEl.textContent = n === 1 ? ('1 ' + label.replace(/en$/, '').replace(/er$/, '')) : (n + ' ' + label);
          if(label === 'Empfänger') {
            self.countEl.textContent = n === 1 ? '1 Empfänger' : (n + ' Empfänger');
          }
          else if(label === 'Mitglieder') {
            self.countEl.textContent = n === 1 ? '1 Mitglied' : (n + ' Mitglieder');
          }
          else if(label === 'sichtbar für') {
            var emptySpec = self.allowEmpty
              && !(self.spec.groups && self.spec.groups.length)
              && !(self.spec.registers && self.spec.registers.length)
              && !(self.spec.users && self.spec.users.length)
              && !(self.spec.mailGroups && self.spec.mailGroups.length);
            self.countEl.textContent = emptySpec
              ? '—'
              : (n === 1 ? 'sichtbar für 1 Person' : ('sichtbar für ' + n + ' Personen'));
          }
        } catch(e) {
          self.countEl.textContent = self.countLabel + ': ?';
        }
      };
      xhr.open('POST', this.countUrl, true);
      xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
      xhr.send(body);
    },

    notifyChanged: function() {
      this.render();
      this.syncHidden();
      this.scheduleCountRefresh();
      this.scheduleSave();
      this.onChange();
    },

    labelForGroup: function(id) {
      var list = this.catalog.groups;
      for(var i = 0; i < list.length; i++) {
        if(String(list[i].id) === String(id)) return list[i].label;
      }
      return GROUP_LABELS[id] || id;
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

    labelForMailGroup: function(id) {
      var list = this.catalog.mailGroups || [];
      for(var i = 0; i < list.length; i++) {
        if(Number(list[i].id) === Number(id)) return list[i].label;
      }
      return 'Gruppe #' + id;
    },

    render: function() {
      if(!this.chipsEl) return;
      this.chipsEl.innerHTML = '';
      var self = this;
      (this.spec.mailGroups || []).forEach(function(id) {
        self.chipsEl.appendChild(self.makeChip('mailGroup', id, 'Gruppe: ' + self.labelForMailGroup(id)));
      });
      this.spec.groups.forEach(function(id) {
        self.chipsEl.appendChild(self.makeChip('group', id, self.labelForGroup(id)));
      });
      this.spec.registers.forEach(function(id) {
        self.chipsEl.appendChild(self.makeChip('register', id, 'Register: ' + self.labelForRegister(id)));
      });
      this.spec.users.forEach(function(id) {
        self.chipsEl.appendChild(self.makeChip('user', id, self.labelForUser(id)));
      });
    },

    makeChip: function(type, id, label) {
      var chip = document.createElement('span');
      chip.className = 'mail-recipient-chip mail-recipient-chip--' + type;
      chip.setAttribute('data-type', type);
      chip.setAttribute('data-id', String(id));
      var text = document.createElement('span');
      text.textContent = label;
      chip.appendChild(text);
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
      return chip;
    },

    removeChip: function(type, id) {
      if(type === 'group' || type === 'audience') {
        var gid = String(id);
        this.spec.groups = this.spec.groups.filter(function(x) { return x !== gid; });
      }
      else if(type === 'register') {
        id = Number(id);
        this.spec.registers = this.spec.registers.filter(function(x) { return x !== id; });
      }
      else if(type === 'mailGroup') {
        id = Number(id);
        this.spec.mailGroups = (this.spec.mailGroups || []).filter(function(x) { return x !== id; });
      }
      else {
        id = Number(id);
        this.spec.users = this.spec.users.filter(function(x) { return x !== id; });
      }
      this.notifyChanged();
    },

    addChip: function(type, id) {
      if(type === 'group' || type === 'audience') {
        var aud = String(id);
        if(GROUP_IDS.indexOf(aud) === -1) return;
        if(this.spec.groups.indexOf(aud) === -1) this.spec.groups.push(aud);
        this.hideSuggest();
        if(this.inputEl) this.inputEl.value = '';
        this.notifyChanged();
        return;
      }
      id = Number(id);
      if(!(id > 0)) return;
      if(type === 'register') {
        if(this.spec.registers.indexOf(id) === -1) this.spec.registers.push(id);
      }
      else if(type === 'mailGroup') {
        if(!this.spec.mailGroups) this.spec.mailGroups = [];
        if(this.spec.mailGroups.indexOf(id) === -1) this.spec.mailGroups.push(id);
      }
      else if(this.spec.users.indexOf(id) === -1) {
        this.spec.users.push(id);
      }
      this.hideSuggest();
      if(this.inputEl) this.inputEl.value = '';
      this.notifyChanged();
    },

    onInput: function() {
      var q = normalize(this.inputEl ? this.inputEl.value : '');
      var items = [];
      var self = this;
      var groups = this.catalog.groups.length
        ? this.catalog.groups
        : [
            {id: 'musicians', label: GROUP_LABELS.musicians, meta: 'Rolle'},
            {id: 'members', label: GROUP_LABELS.members, meta: 'Rolle'},
            {id: 'nonmembers', label: GROUP_LABELS.nonmembers, meta: 'Rolle'},
            {id: 'users', label: GROUP_LABELS.users, meta: 'Rolle'}
          ];

      (this.catalog.mailGroups || []).forEach(function(g) {
        if((self.spec.mailGroups || []).indexOf(Number(g.id)) !== -1) return;
        if(q === '' || normalize(g.label).indexOf(q) !== -1) {
          items.push({type: 'mailGroup', id: g.id, label: g.label, meta: g.meta || 'Gruppe'});
        }
      });

      groups.forEach(function(g) {
        if(self.spec.groups.indexOf(String(g.id)) !== -1) return;
        if(q === '' || normalize(g.label).indexOf(q) !== -1) {
          items.push({type: 'group', id: g.id, label: g.label, meta: g.meta || 'Rolle'});
        }
      });

      this.catalog.registers.forEach(function(r) {
        if(self.spec.registers.indexOf(Number(r.id)) !== -1) return;
        if(q !== '' && normalize(r.label).indexOf(q) === -1) return;
        if(q === '') return;
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
      items.forEach(function(item, idx) {
        var row = document.createElement('button');
        row.type = 'button';
        row.className = 'mail-recipient-suggest-item';
        row.setAttribute('data-suggest-index', String(idx));
        row.innerHTML = '<span class="mail-recipient-suggest-label"></span>'
          + '<span class="mail-recipient-suggest-meta"></span>';
        row.querySelector('.mail-recipient-suggest-label').textContent = item.label;
        row.querySelector('.mail-recipient-suggest-meta').textContent = item.meta;
        row.addEventListener('mousedown', function(e) {
          e.preventDefault();
          self.addChip(item.type, item.id);
        });
        row.addEventListener('mouseenter', function() {
          self._suggestIndex = idx;
          self.updateSuggestHighlight(false);
        });
        self.suggestEl.appendChild(row);
      });
      this._suggestItems = items;
      this._suggestIndex = 0;
      this.suggestEl.hidden = false;
      this.updateSuggestHighlight(false);
    },

    hideSuggest: function() {
      if(this.suggestEl) {
        this.suggestEl.hidden = true;
        this.suggestEl.innerHTML = '';
      }
      this._suggestItems = [];
      this._suggestIndex = -1;
    },

    updateSuggestHighlight: function(scrollIntoView) {
      if(!this.suggestEl) return;
      var rows = this.suggestEl.querySelectorAll('.mail-recipient-suggest-item');
      for(var i = 0; i < rows.length; i++) {
        var active = i === this._suggestIndex;
        if(active) {
          rows[i].classList.add('mail-recipient-suggest-item--active');
          if(scrollIntoView) {
            rows[i].scrollIntoView({block: 'nearest'});
          }
        }
        else {
          rows[i].classList.remove('mail-recipient-suggest-item--active');
        }
      }
    },

    onKeydown: function(e) {
      var items = this._suggestItems || [];
      var open = this.suggestEl && !this.suggestEl.hidden && items.length > 0;

      if(e.key === 'ArrowDown') {
        if(!open) return;
        e.preventDefault();
        this._suggestIndex = this._suggestIndex < 0
          ? 0
          : Math.min(items.length - 1, this._suggestIndex + 1);
        this.updateSuggestHighlight(true);
      }
      else if(e.key === 'ArrowUp') {
        if(!open) return;
        e.preventDefault();
        this._suggestIndex = this._suggestIndex <= 0
          ? 0
          : this._suggestIndex - 1;
        this.updateSuggestHighlight(true);
      }
      else if(e.key === 'Enter') {
        e.preventDefault();
        if(open) {
          var idx = this._suggestIndex >= 0 ? this._suggestIndex : 0;
          var pick = items[idx];
          if(pick) this.addChip(pick.type, pick.id);
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
        else if(this.spec.groups.length) {
          this.removeChip('group', this.spec.groups[this.spec.groups.length - 1]);
        }
        else if(this.spec.mailGroups && this.spec.mailGroups.length) {
          this.removeChip('mailGroup', this.spec.mailGroups[this.spec.mailGroups.length - 1]);
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
