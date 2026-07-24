/**
 * Infinite scroll for long lists (MELD-55).
 * Expects #Liste and #listSentinel with data-list-type, data-cursor, data-has-more.
 * Optional data-filter-fn: name of global filter function to re-run after append.
 * Optional data-extra: query string fragment (e.g. user=123).
 * Optional data-limit: page size for getList.php (default 50; log uses config).
 * Optional data-sort / data-dir: server-side sort for user lists (MELD-96).
 * Exposes window.listInfiniteReload(sort, dir) to reset and reload from offset 0.
 *
 * MELD-164: With an active client-side filter, keep loading while the server reports
 * hasMore (sparse hits like „Adventskonzert“). No hard pause after empty chunks —
 * only stop on !hasMore or explicit user cancel. MELD-162 pause removed.
 */
(function() {
    var loading = false;
    var observer = null;
    var pausedByUser = false;
    var MSG_LOADING = 'Weitere Einträge werden geladen…';
    var MSG_FILTER_SCAN = 'Suche…';
    var MSG_END = 'Keine weiteren Einträge';
    var MSG_ERROR = 'Laden fehlgeschlagen. Bitte erneut versuchen.';
    var MSG_USER_PAUSE = 'Angehalten';

    function getSentinel() {
        return document.getElementById('listSentinel');
    }

    function getList() {
        return document.getElementById('Liste');
    }

    function filterActive() {
        var input = document.getElementById('filterString');
        return !!(input && String(input.value).trim() !== '');
    }

    function setBarVisible(visible) {
        var sentinel = getSentinel();
        if(!sentinel) return;
        if(visible) {
            sentinel.className = 'w3-panel w3-padding w3-center w3-margin-top w3-light-grey';
            sentinel.style.cssText = 'clear:both;';
        }
        else {
            sentinel.className = '';
            sentinel.style.cssText = 'clear:both;height:1px;padding:0;margin:0;';
            sentinel.textContent = '';
        }
    }

    function clearSentinelContent(sentinel) {
        while(sentinel.firstChild) {
            sentinel.removeChild(sentinel.firstChild);
        }
    }

    function appendActionButton(sentinel, label, onClick) {
        sentinel.appendChild(document.createTextNode(' '));
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'w3-button w3-small w3-border';
        btn.textContent = label;
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            onClick();
        });
        sentinel.appendChild(btn);
    }

    function setStatus(text, isLoading, action) {
        var sentinel = getSentinel();
        if(!sentinel) return;
        if(text) {
            setBarVisible(true);
            clearSentinelContent(sentinel);
            var span = document.createElement('span');
            span.textContent = text;
            sentinel.appendChild(span);
            if(action && action.label && typeof action.onClick === 'function') {
                appendActionButton(sentinel, action.label, action.onClick);
            }
        }
        else {
            setBarVisible(false);
        }
        if(isLoading) {
            sentinel.setAttribute('aria-busy', 'true');
        }
        else {
            sentinel.removeAttribute('aria-busy');
        }
    }

    function showEnd() {
        var sentinel = getSentinel();
        if(!sentinel) return;
        sentinel.setAttribute('data-has-more', '0');
        setStatus(MSG_END, false);
    }

    function showUserPaused() {
        setStatus(MSG_USER_PAUSE, false, {
            label: 'Weiter',
            onClick: resumeByUser
        });
    }

    function pauseByUser() {
        pausedByUser = true;
        var sentinel = getSentinel();
        if(observer && sentinel) observer.unobserve(sentinel);
        showUserPaused();
    }

    function resumeByUser() {
        pausedByUser = false;
        var sentinel = getSentinel();
        if(!sentinel || sentinel.getAttribute('data-has-more') !== '1') return;
        setStatus('', false);
        reobserveSoon();
    }

    function applyFilter(sentinel) {
        var filterFn = sentinel.getAttribute('data-filter-fn');
        if(!filterFn || typeof window[filterFn] !== 'function') return;
        var input = document.getElementById('filterString');
        if(input && input.value) {
            window[filterFn]();
        }
    }

    function countVisibleNodes(nodes) {
        var n = 0;
        var i;
        for(i = 0; i < nodes.length; i++) {
            var el = nodes[i];
            if(!el || el.nodeType !== 1) continue;
            if(el.classList && el.classList.contains('list-filtered-out')) continue;
            if(el.style && el.style.display === 'none') continue;
            n++;
        }
        return n;
    }

    function appendHtml(list, sentinel, html) {
        var appended = [];
        if(!html) return appended;
        var wrap = document.createElement('div');
        wrap.innerHTML = html;
        while(wrap.firstChild) {
            var node = wrap.firstChild;
            if(node.nodeType === 1 && node.id && node.id !== 'listSentinel' && document.getElementById(node.id)) {
                wrap.removeChild(node);
                continue;
            }
            list.insertBefore(node, sentinel);
            if(node.nodeType === 1) {
                appended.push(node);
            }
        }
        return appended;
    }

    function clearRows(list, sentinel) {
        var nodes = [];
        var i;
        for(i = 0; i < list.children.length; i++) {
            nodes.push(list.children[i]);
        }
        for(i = 0; i < nodes.length; i++) {
            if(nodes[i] !== sentinel) {
                list.removeChild(nodes[i]);
            }
        }
    }

    function pageLimit(sentinel) {
        var raw = sentinel.getAttribute('data-limit');
        var n = raw ? parseInt(raw, 10) : 50;
        if(!(n > 0)) n = 50;
        return n;
    }

    function buildUrl(sentinel, cursor) {
        var type = sentinel.getAttribute('data-list-type') || '';
        var url = 'getList.php?type=' + encodeURIComponent(type)
            + '&cursor=' + encodeURIComponent(cursor)
            + '&limit=' + encodeURIComponent(String(pageLimit(sentinel)));
        var extra = sentinel.getAttribute('data-extra');
        if(extra) url += '&' + extra;
        var sort = sentinel.getAttribute('data-sort');
        var dir = sentinel.getAttribute('data-dir');
        if(sort) url += '&sort=' + encodeURIComponent(sort);
        if(dir) url += '&dir=' + encodeURIComponent(dir);
        return url;
    }

    function reobserveSoon() {
        var sentinel = getSentinel();
        if(!observer || !sentinel) return;
        if(pausedByUser) return;
        if(sentinel.getAttribute('data-has-more') !== '1') return;
        setTimeout(function() {
            if(observer && !pausedByUser && sentinel.getAttribute('data-has-more') === '1') {
                observer.observe(sentinel);
            }
        }, 100);
    }

    function loadMore() {
        var sentinel = getSentinel();
        var list = getList();
        if(!sentinel || !list || loading) return;
        if(pausedByUser) return;
        if(sentinel.getAttribute('data-has-more') !== '1') return;

        var type = sentinel.getAttribute('data-list-type') || '';
        var cursor = sentinel.getAttribute('data-cursor') || '';
        if(!type || cursor === '') return;

        loading = true;
        if(observer) observer.unobserve(sentinel);
        if(filterActive()) {
            setStatus(MSG_FILTER_SCAN, true, {
                label: 'Stoppen',
                onClick: pauseByUser
            });
        }
        else {
            setStatus(MSG_LOADING, true);
        }

        var xhr;
        if(window.XMLHttpRequest) {
            xhr = new XMLHttpRequest();
        }
        else {
            xhr = new ActiveXObject('Microsoft.XMLHTTP');
        }
        xhr.onreadystatechange = function() {
            if(xhr.readyState !== 4) return;
            loading = false;
            if(xhr.status < 200 || xhr.status >= 300) {
                setStatus(MSG_ERROR, false);
                if(observer && sentinel.getAttribute('data-has-more') === '1') {
                    setTimeout(function() {
                        if(observer && !pausedByUser) observer.observe(sentinel);
                    }, 500);
                }
                return;
            }
            var hasMore = xhr.getResponseHeader('X-Has-More') === '1';
            var nextCursor = xhr.getResponseHeader('X-Next-Cursor');
            if(nextCursor === null || nextCursor === '') {
                nextCursor = cursor;
                hasMore = false;
            }
            if(nextCursor === cursor) {
                hasMore = false;
            }
            sentinel.setAttribute('data-has-more', hasMore ? '1' : '0');
            sentinel.setAttribute('data-cursor', nextCursor);

            var appended = appendHtml(list, sentinel, xhr.responseText);
            applyFilter(sentinel);

            if(!hasMore) {
                showEnd();
                return;
            }

            // User may have clicked Stop during this request — keep cursor progress
            if(pausedByUser) {
                showUserPaused();
                return;
            }

            if(filterActive()) {
                var visibleNew = countVisibleNodes(appended);
                if(visibleNew === 0) {
                    // Sparse filter: keep scanning — status stays „Suche…“ + Stoppen
                    setStatus(MSG_FILTER_SCAN, true, {
                        label: 'Stoppen',
                        onClick: pauseByUser
                    });
                    reobserveSoon();
                    return;
                }
            }

            setStatus('', false);
            reobserveSoon();
        };
        xhr.open('GET', buildUrl(sentinel, cursor), true);
        xhr.send();
    }

    function reloadFromStart(sort, dir) {
        var sentinel = getSentinel();
        var list = getList();
        if(!sentinel || !list) return;
        if(observer) observer.unobserve(sentinel);
        loading = false;
        pausedByUser = false;
        if(sort) sentinel.setAttribute('data-sort', sort);
        if(dir) sentinel.setAttribute('data-dir', dir);
        clearRows(list, sentinel);
        sentinel.setAttribute('data-cursor', '0');
        sentinel.setAttribute('data-has-more', '1');
        loadMore();
    }

    window.listInfiniteReload = reloadFromStart;

    function bindFilterResume() {
        var input = document.getElementById('filterString');
        if(!input || input.getAttribute('data-infinite-bound') === '1') return;
        input.setAttribute('data-infinite-bound', '1');
        input.addEventListener('input', function() {
            pausedByUser = false;
            var sentinel = getSentinel();
            if(!sentinel || sentinel.getAttribute('data-has-more') !== '1') return;
            if(!filterActive()) {
                setStatus('', false);
            }
            reobserveSoon();
        });
    }

    function init() {
        var sentinel = getSentinel();
        if(!sentinel) return;
        bindFilterResume();
        if(sentinel.getAttribute('data-has-more') !== '1') {
            showEnd();
            return;
        }
        if(!sentinel.getAttribute('data-cursor')) {
            showEnd();
            return;
        }

        if('IntersectionObserver' in window) {
            observer = new IntersectionObserver(function(entries) {
                for(var i = 0; i < entries.length; i++) {
                    if(entries[i].isIntersecting) loadMore();
                }
            }, { root: null, rootMargin: '80px', threshold: 0 });
            observer.observe(sentinel);
        }
        else {
            window.addEventListener('scroll', function() {
                if(pausedByUser) return;
                var rect = sentinel.getBoundingClientRect();
                if(rect.top < window.innerHeight + 80) loadMore();
            });
        }
    }

    if(document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    }
    else {
        init();
    }
})();
