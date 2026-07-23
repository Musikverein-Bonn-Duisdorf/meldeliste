(function () {
  var form = document.querySelector('.shift-edit-form');
  if (!form) {
    return;
  }
  var tbody = form.querySelector('.shift-edit-table tbody');
  var template = document.getElementById('shift-edit-row-template');
  var addBtn = document.getElementById('shift-edit-add-row');
  if (!tbody || !template) {
    return;
  }

  function nextNewKey() {
    var n = parseInt(form.getAttribute('data-shift-new-counter') || '1', 10);
    if (!isFinite(n) || n < 1) {
      n = 1;
    }
    form.setAttribute('data-shift-new-counter', String(n + 1));
    return 'new_' + n;
  }

  function addRow() {
    var key = nextNewKey();
    var html = template.innerHTML.split('__KEY__').join(key);
    var wrap = document.createElement('tbody');
    wrap.innerHTML = html.trim();
    var row = wrap.firstElementChild;
    if (row) {
      tbody.appendChild(row);
      var nameInput = row.querySelector('input[name$="[Name]"]');
      if (nameInput) {
        nameInput.focus();
      }
    }
  }

  if (addBtn) {
    addBtn.addEventListener('click', function (e) {
      e.preventDefault();
      addRow();
    });
  }

  form.addEventListener('click', function (e) {
    var clearBtn = e.target.closest('.shift-edit-clear');
    if (clearBtn && form.contains(clearBtn)) {
      e.preventDefault();
      var wrap = clearBtn.closest('.shift-edit-time-wrap');
      var input = wrap ? wrap.querySelector('input') : null;
      if (input) {
        input.value = '';
        input.focus();
      }
      return;
    }
    var removeBtn = e.target.closest('.shift-edit-remove-row');
    if (removeBtn && form.contains(removeBtn)) {
      e.preventDefault();
      var row = removeBtn.closest('tr.shift-edit-row--new');
      if (row) {
        row.remove();
      }
    }
  });
})();
