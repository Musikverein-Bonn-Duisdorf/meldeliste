/**
 * MELD-191: inventory photo gallery (prev/next + AJAX upload/delete + lightbox).
 */
(function(global) {
  'use strict';

  var lightboxEl = null;
  var lightboxGallery = null;

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
    var primaryId = gallery.parentNode && gallery.parentNode.querySelector('.inv-photo-primary-id');
    if (primaryId) primaryId.value = String(ids[idx]);
    var primaryForm = gallery.parentNode && gallery.parentNode.querySelector('.inv-photo-primary');
    if (primaryForm) {
      if (idx === 0) primaryForm.setAttribute('hidden', 'hidden');
      else primaryForm.removeAttribute('hidden');
    }
  }

  function currentIndex(gallery) {
    var n = parseInt(gallery.getAttribute('data-photo-index') || '0', 10);
    return isNaN(n) ? 0 : n;
  }

  function lightboxSrc(gallery) {
    var ids = parseIds(gallery);
    var idx = currentIndex(gallery);
    if (ids.length && ids[idx]) {
      return 'inventory-photo.php?id=' + ids[idx];
    }
    var img = gallery.querySelector('.inv-photo-img');
    return img ? (img.getAttribute('src') || '') : '';
  }

  function isLightboxOpen() {
    return !!(lightboxEl && !lightboxEl.hasAttribute('hidden'));
  }

  function closeLightbox() {
    if (lightboxEl) lightboxEl.setAttribute('hidden', 'hidden');
    lightboxGallery = null;
  }

  function syncLightboxImg() {
    if (!lightboxGallery || !lightboxEl) return;
    var img = lightboxEl.querySelector('.inv-photo-lightbox-img');
    var src = lightboxSrc(lightboxGallery);
    if (img && src) img.src = src;
  }

  function stepLightbox(delta) {
    if (!lightboxGallery) return;
    showAt(lightboxGallery, currentIndex(lightboxGallery) + delta);
    syncLightboxImg();
  }

  function ensureLightbox() {
    if (lightboxEl) return lightboxEl;
    lightboxEl = document.createElement('div');
    lightboxEl.id = 'invPhotoLightbox';
    lightboxEl.className = 'inv-photo-lightbox';
    lightboxEl.setAttribute('hidden', 'hidden');
    lightboxEl.setAttribute('role', 'dialog');
    lightboxEl.setAttribute('aria-modal', 'true');
    lightboxEl.setAttribute('aria-label', 'Foto');
    lightboxEl.innerHTML =
      '<button type="button" class="inv-photo-lightbox-close" aria-label="Schließen">&times;</button>'
      + '<button type="button" class="inv-photo-lightbox-nav inv-photo-lightbox-nav--prev" aria-label="Vorheriges Foto">&lsaquo;</button>'
      + '<img class="inv-photo-lightbox-img" alt="">'
      + '<button type="button" class="inv-photo-lightbox-nav inv-photo-lightbox-nav--next" aria-label="Nächstes Foto">&rsaquo;</button>';
    document.body.appendChild(lightboxEl);
    lightboxEl.addEventListener('click', function(e) {
      if (e.target === lightboxEl) closeLightbox();
    });
    lightboxEl.querySelector('.inv-photo-lightbox-close').addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      closeLightbox();
    });
    lightboxEl.querySelector('.inv-photo-lightbox-nav--prev').addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      stepLightbox(-1);
    });
    lightboxEl.querySelector('.inv-photo-lightbox-nav--next').addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      stepLightbox(1);
    });
    return lightboxEl;
  }

  function openLightbox(gallery) {
    var src = lightboxSrc(gallery);
    if (!src) return;
    lightboxGallery = gallery;
    var box = ensureLightbox();
    var hideNav = parseIds(gallery).length < 2;
    box.querySelector('.inv-photo-lightbox-nav--prev').hidden = hideNav;
    box.querySelector('.inv-photo-lightbox-nav--next').hidden = hideNav;
    syncLightboxImg();
    box.removeAttribute('hidden');
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
        var zoom = gallery.querySelector('.inv-photo-zoom');
        if (zoom) {
          zoom.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            openLightbox(gallery);
          });
        }
      })(galleries[i]);
    }
  }

  document.addEventListener('keydown', function(e) {
    if (!isLightboxOpen()) return;
    if (e.key === 'Escape') {
      e.preventDefault();
      e.stopPropagation();
      closeLightbox();
      return;
    }
    if (e.key === 'ArrowLeft') {
      e.preventDefault();
      stepLightbox(-1);
    } else if (e.key === 'ArrowRight') {
      e.preventDefault();
      stepLightbox(1);
    }
  }, true);

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
    closeLightbox();
    if (typeof initLoanUserChipsInModal === 'function') {
      initLoanUserChipsInModal(content);
    }
    initGallery(content);
    if (typeof refreshInventarListRow === 'function') {
      refreshInventarListRow(invId, data.listRowHtml, data.action);
    }
  }

  document.addEventListener('submit', function(e) {
    var form = e.target;
    if (!form || !form.closest) return;
    if (!form.classList.contains('inv-photo-upload') && !form.classList.contains('inv-photo-delete') && !form.classList.contains('inv-photo-primary')) return;
    if (!form.closest('#ajaxModalContent .inventar-modal')) return;
    e.preventDefault();
    postForm(form, function(xhr, data) {
      applyModalHtml(data);
    });
  });

  global.initInventarPhotosInModal = initGallery;
  global.closeInventarPhotoLightbox = closeLightbox;
})(window);
