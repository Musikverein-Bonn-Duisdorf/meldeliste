/**
 * MELD-191: inventory photo gallery (prev/next + AJAX upload/delete).
 */
(function(global) {
  'use strict';

  function parseIds(el) {
    try {
      var ids = JSON.parse(el.getAttribute('data-photo-ids') || '[]');
      return Array.isArray(ids) ? ids.map(Number).filter(function(n) { return n > 0; }) : [];
    } catch (e) {
      return [];
    }
  }

  function showAt(gallery, idx) {
    var ids = parseIds(gallery);
    if (!ids.length) return;
    idx = ((idx % ids.length) + ids.length) % ids.length;
    gallery.setAttribute('data-photo-index', String(idx));
    var img = gallery.querySelector('.inv-photo-img');
    if (img) {
      img.src = 'inventory-photo.php?id=' + ids[idx];
      img.setAttribute('data-photo-id', String(ids[idx]));
    }
    var pos = gallery.querySelector('.inv-photo-pos');
    if (pos) pos.textContent = String(idx + 1);
    var delId = gallery.parentNode && gallery.parentNode.querySelector('.inv-photo-delete-id');
    if (delId) delId.value = String(ids[idx]);
  }

  function currentIndex(gallery) {
    var n = parseInt(gallery.getAttribute('data-photo-index') || '0', 10);
    return isNaN(n) ? 0 : n;
  }

  function initGallery(root) {
    root = root || document;
    var galleries = root.querySelectorAll ? root.querySelectorAll('.inv-photo-gallery') : [];
    for (var i = 0; i < galleries.length; i++) {
      (function(gallery) {
        if (gallery.getAttribute('data-inv-photo-bound') === '1') return;
        gallery.setAttribute('data-inv-photo-bound', '1');
        gallery.setAttribute('data-photo-index', '0');
        var prev = gallery.querySelector('.inv-photo-nav--prev');
        var next = gallery.querySelector('.inv-photo-nav--next');
        if (prev) {
          prev.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            showAt(gallery, currentIndex(gallery) - 1);
          });
        }
        if (next) {
          next.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            showAt(gallery, currentIndex(gallery) + 1);
          });
        }
      })(galleries[i]);
    }
  }

  function postForm(form, done) {
    var fd = new FormData(form);
    fd.append('ajax', '1');
    var xhr = window.XMLHttpRequest ? new XMLHttpRequest() : new ActiveXObject('Microsoft.XMLHTTP');
    xhr.onreadystatechange = function() {
      if (xhr.readyState !== 4) return;
      var data = null;
      try { data = JSON.parse(xhr.responseText); } catch (e) { data = null; }
      done(xhr, data);
    };
    xhr.open('POST', 'inventory-photo.php', true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.send(fd);
  }

  function applyModalHtml(data) {
    var content = document.getElementById('ajaxModalContent');
    var host = document.getElementById('ajaxModalHost');
    if (!content || !data || !data.ok || !data.html) return;
    var invId = parseInt(data.inventoryId, 10) || 0;
    if (typeof invalidateInventarModalCache === 'function') {
      invalidateInventarModalCache(invId);
    }
    if (typeof modalCache !== 'undefined') {
      modalCache['inventar:' + invId] = data.html;
    }
    content.innerHTML = data.html;
    if (host) host.style.display = 'block';
    if (typeof initLoanUserChipsInModal === 'function') {
      initLoanUserChipsInModal(content);
    }
    initGallery(content);
  }

  document.addEventListener('submit', function(e) {
    var form = e.target;
    if (!form || !form.closest) return;
    if (!form.classList.contains('inv-photo-upload') && !form.classList.contains('inv-photo-delete')) return;
    if (!form.closest('#ajaxModalContent .inventar-modal')) return;
    e.preventDefault();
    postForm(form, function(xhr, data) {
      applyModalHtml(data);
    });
  });

  global.initInventarPhotosInModal = initGallery;
})(window);
