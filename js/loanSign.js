(function () {
    var modal = document.getElementById('loanSignModal');
    var form = modal ? modal.querySelector('[data-loan-sign]') : null;
    if (!modal || !form) {
        return;
    }

    var canvas = form.querySelector('canvas');
    var wrap = form.querySelector('.loan-form-sign-canvas-wrap');
    var hidden = form.querySelector('input[name="signature"]');
    var loanInput = form.querySelector('input[name="loan"]');
    var kindInput = form.querySelector('input[name="kind"]');
    var roleInput = form.querySelector('input[name="role"]');
    var placeInput = form.querySelector('input[name="place"]');
    var titleEl = document.getElementById('loanSignTitle');
    var clearBtn = form.querySelector('[data-loan-sign-clear]');
    if (!canvas || !hidden || !wrap) {
        return;
    }

    var ctx = canvas.getContext('2d');
    if (!ctx) {
        return;
    }

    var drawing = false;
    var last = null;
    var dirty = false;

    function fitCanvas() {
        var dpr = window.devicePixelRatio || 1;
        var rect = wrap.getBoundingClientRect();
        var w = Math.max(240, Math.floor(rect.width));
        var h = Math.max(140, Math.floor(rect.height));
        canvas.style.width = w + 'px';
        canvas.style.height = h + 'px';
        canvas.width = Math.round(w * dpr);
        canvas.height = Math.round(h * dpr);
        ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';
        ctx.strokeStyle = '#1a1a1a';
        ctx.lineWidth = 2.4;
        dirty = false;
        hidden.value = '';
    }

    function pos(ev) {
        var rect = canvas.getBoundingClientRect();
        var src = ev.touches && ev.touches[0] ? ev.touches[0] : ev;
        return {
            x: src.clientX - rect.left,
            y: src.clientY - rect.top
        };
    }

    function start(ev) {
        ev.preventDefault();
        drawing = true;
        last = pos(ev);
        if (canvas.setPointerCapture && ev.pointerId != null) {
            try {
                canvas.setPointerCapture(ev.pointerId);
            } catch (err) { /* ignore */ }
        }
    }

    function move(ev) {
        if (!drawing) {
            return;
        }
        ev.preventDefault();
        var p = pos(ev);
        ctx.beginPath();
        ctx.moveTo(last.x, last.y);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
        last = p;
        dirty = true;
    }

    function end(ev) {
        if (!drawing) {
            return;
        }
        if (ev) {
            ev.preventDefault();
        }
        drawing = false;
        last = null;
    }

    if (window.PointerEvent) {
        canvas.addEventListener('pointerdown', start);
        canvas.addEventListener('pointermove', move);
        canvas.addEventListener('pointerup', end);
        canvas.addEventListener('pointercancel', end);
    } else {
        canvas.addEventListener('touchstart', start, { passive: false });
        canvas.addEventListener('touchmove', move, { passive: false });
        canvas.addEventListener('touchend', end, { passive: false });
        canvas.addEventListener('mousedown', start);
        canvas.addEventListener('mousemove', move);
        canvas.addEventListener('mouseup', end);
        canvas.addEventListener('mouseleave', end);
    }

    function openModal(btn) {
        loanInput.value = btn.getAttribute('data-loan') || '';
        kindInput.value = btn.getAttribute('data-kind') || '';
        roleInput.value = btn.getAttribute('data-role') || '';
        placeInput.value = btn.getAttribute('data-place') || '';
        if (titleEl) {
            titleEl.textContent = btn.getAttribute('data-role-label') || 'Unterschrift';
        }
        form.action = 'loan-form.php?loan=' + encodeURIComponent(loanInput.value)
            + '&kind=' + encodeURIComponent(kindInput.value);
        canvas.setAttribute('aria-label', 'Unterschrift ' + (titleEl ? titleEl.textContent : ''));
        modal.hidden = false;
        modal.classList.add('is-open');
        document.body.classList.add('loan-form-sign-open');
        window.requestAnimationFrame(function () {
            fitCanvas();
        });
    }

    window.addEventListener('resize', function () {
        if (modal.classList.contains('is-open')) {
            fitCanvas();
        }
    });

    function closeModal() {
        drawing = false;
        last = null;
        modal.classList.remove('is-open');
        modal.hidden = true;
        document.body.classList.remove('loan-form-sign-open');
        hidden.value = '';
        dirty = false;
    }

    document.querySelectorAll('[data-loan-sign-open]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            openModal(btn);
        });
    });

    modal.querySelectorAll('[data-loan-sign-close]').forEach(function (btn) {
        btn.addEventListener('click', closeModal);
    });

    modal.addEventListener('click', function (ev) {
        if (ev.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && modal.classList.contains('is-open')) {
            closeModal();
        }
    });

    if (clearBtn) {
        clearBtn.addEventListener('click', function () {
            fitCanvas();
        });
    }

    form.addEventListener('submit', function (ev) {
        if (!dirty) {
            ev.preventDefault();
            return;
        }
        hidden.value = canvas.toDataURL('image/png');
    });

    var completeHost = document.querySelector('[data-loan-complete-notice]');
    if (completeHost && window.history && window.history.replaceState) {
        var u = new URL(window.location.href);
        var strip = false;
        ['complete', 'mailed'].forEach(function (key) {
            if (u.searchParams.has(key)) {
                u.searchParams.delete(key);
                strip = true;
            }
        });
        if (strip) {
            var qs = u.searchParams.toString();
            window.history.replaceState({}, '', u.pathname + (qs ? '?' + qs : '') + u.hash);
        }
    }
})();
