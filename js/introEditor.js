(function() {
  'use strict';

  function tinymceOptsFor(selector) {
    return {
      selector: selector,
      license_key: 'gpl',
      menubar: false,
      plugins: 'lists link table code',
      toolbar: 'undo redo | styles | bold italic underline | bullist numlist | link table | removeformat | code',
      entity_encoding: 'raw',
      branding: false,
      promotion: false,
      height: 280,
      convert_urls: false,
      relative_urls: false
    };
  }

  function initEditors(root) {
    if(typeof tinymce === 'undefined') {
      return;
    }
    var areas = (root || document).querySelectorAll('textarea.intro-page-html');
    for(var i = 0; i < areas.length; i++) {
      if(!areas[i].id) {
        areas[i].id = 'intro-html-' + Date.now() + '-' + i + '-' + Math.floor(Math.random() * 1000);
      }
      if(tinymce.get(areas[i].id)) {
        continue;
      }
      tinymce.init(tinymceOptsFor('#' + areas[i].id));
    }
  }

  function bindRemove(pageEl) {
    var btn = pageEl.querySelector('.intro-page-remove');
    if(!btn) {
      return;
    }
    btn.addEventListener('click', function() {
      var pages = document.querySelectorAll('#intro-pages .intro-editor-page');
      if(pages.length <= 1) {
        return;
      }
      var ta = pageEl.querySelector('textarea.intro-page-html');
      if(ta && ta.id && typeof tinymce !== 'undefined') {
        var ed = tinymce.get(ta.id);
        if(ed) {
          ed.remove();
        }
      }
      pageEl.parentNode.removeChild(pageEl);
    });
  }

  document.addEventListener('DOMContentLoaded', function() {
    var form = document.getElementById('introEditorForm');
    var list = document.getElementById('intro-pages');
    var addBtn = document.getElementById('introPageAdd');
    var tpl = document.getElementById('intro-page-template');
    if(!form || !list) {
      return;
    }

    var existing = list.querySelectorAll('.intro-editor-page');
    for(var i = 0; i < existing.length; i++) {
      bindRemove(existing[i]);
    }
    initEditors(list);

    if(addBtn && tpl) {
      addBtn.addEventListener('click', function() {
        list.appendChild(tpl.content.cloneNode(true));
        var last = list.querySelector('.intro-editor-page:last-child');
        bindRemove(last);
        initEditors(last);
      });
    }

    form.addEventListener('submit', function() {
      if(typeof tinymce !== 'undefined') {
        tinymce.triggerSave();
      }
    });
  });
})();
