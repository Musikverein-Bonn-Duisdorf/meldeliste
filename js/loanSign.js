(function () {
    function setupDurationClause() {
        var startInput = document.getElementById('loan-start-date');
        var endInput = document.getElementById('loan-end-date');
        var tplOpen = document.getElementById('loan-duration-tpl-open');
        var tplFixed = document.getElementById('loan-duration-tpl-fixed');
        if (!startInput || !endInput) {
            return;
        }

        function isoToDe(iso) {
            if (!iso || !/^\d{4}-\d{2}-\d{2}$/.test(iso)) {
                return '';
            }
            return iso.slice(8, 10) + '.' + iso.slice(5, 7) + '.' + iso.slice(0, 4);
        }

        function apply() {
            var startDe = isoToDe(startInput.value);
            var endDe = isoToDe(endInput.value);
            document.querySelectorAll('[data-loan-fill="start"]').forEach(function (el) {
                el.textContent = startDe;
            });
            var durationFill = document.querySelector('[data-loan-fill="duration"]');
            if (durationFill && tplOpen && tplFixed) {
                durationFill.innerHTML = (endDe ? tplFixed : tplOpen).innerHTML
                    .split('__END__').join(endDe);
            }
            var returnDueFill = document.querySelector('[data-loan-fill="returnDue"]');
            var tplReturnOpen = document.getElementById('loan-returndue-tpl-open');
            var tplReturnFixed = document.getElementById('loan-returndue-tpl-fixed');
            if (returnDueFill && tplReturnOpen && tplReturnFixed) {
                returnDueFill.innerHTML = (endDe ? tplReturnFixed : tplReturnOpen).innerHTML
                    .split('__END__').join(endDe);
            }
            var startPrint = document.querySelector('[data-loan-start-print]');
            var endPrint = document.querySelector('[data-loan-end-print]');
            if (startPrint && startDe) {
                startPrint.textContent = startDe;
            }
            if (endPrint) {
                endPrint.textContent = endDe ? endDe : 'unbefristet';
            }
        }

        startInput.addEventListener('input', apply);
        startInput.addEventListener('change', apply);
        endInput.addEventListener('input', apply);
        endInput.addEventListener('change', apply);
    }

    function parseAmountInput(raw) {
        var s = String(raw || '').replace(/€/g, '').replace(/\s/g, '');
        if (!s) {
            return 0;
        }
        if (s.indexOf(',') !== -1 && s.indexOf('.') !== -1) {
            s = s.replace(/\./g, '').replace(',', '.');
        } else if (s.indexOf(',') !== -1) {
            s = s.replace(',', '.');
        }
        var n = parseFloat(s);
        if (!isFinite(n) || n <= 0) {
            return 0;
        }
        return Math.round(n * 100) / 100;
    }

    function formatAmountDe(n) {
        var parts = n.toFixed(2).split('.');
        var intPart = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
        return intPart + ',' + parts[1] + ' €';
    }

    function bindMoneyClause(input, fillKey, printEl, row) {
        if (!input) {
            return;
        }
        function apply() {
            var amount = parseAmountInput(input.value);
            var fills = document.querySelectorAll('[data-loan-fill="' + fillKey + '"]');
            var lis = [];
            fills.forEach(function (el) {
                var li = el.closest('li');
                if (li && lis.indexOf(li) === -1) {
                    lis.push(li);
                }
            });
            if (amount <= 0) {
                fills.forEach(function (el) {
                    el.textContent = '';
                });
                lis.forEach(function (li) {
                    li.hidden = true;
                });
                if (printEl) {
                    printEl.textContent = '';
                }
                if (row) {
                    row.classList.add('no-print');
                }
                return;
            }
            var formatted = formatAmountDe(amount);
            fills.forEach(function (el) {
                el.textContent = formatted;
            });
            lis.forEach(function (li) {
                li.hidden = false;
            });
            if (printEl) {
                printEl.textContent = formatted;
            }
            if (row) {
                row.classList.remove('no-print');
            }
        }
        input.addEventListener('input', apply);
        input.addEventListener('change', apply);
        apply();
    }

    function setupMoneyClauses() {
        bindMoneyClause(
            document.getElementById('loan-leihgebuehr-form'),
            'fee',
            document.querySelector('[data-loan-leihgebuehr-print]'),
            document.getElementById('loan-leihgebuehr-row')
        );
        bindMoneyClause(
            document.getElementById('loan-kaution-form'),
            'kaution',
            document.querySelector('[data-loan-kaution-print]'),
            document.getElementById('loan-kaution-row')
        );
    }

    setupDurationClause();
    setupMoneyClauses();

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
        ['complete', 'mailed', 'saved', 'notified', 'notifyerr', 'signcleared', 'signerr', 'restarted', 'restarterr'].forEach(function (key) {
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
