/**
 * Infinite scroll for long lists (MELD-55).
 * Expects #Liste and #listSentinel with data-list-type, data-cursor, data-has-more.
 * Optional data-filter-fn: name of global filter function to re-run after append.
 * Optional data-extra: query string fragment (e.g. user=123).
 */
(function() {
    var loading = false;
    var observer = null;
    var MSG_LOADING = 'Weitere Einträge werden geladen…';
    var MSG_END = 'Keine weiteren Einträge';
    var MSG_ERROR = 'Laden fehlgeschlagen. Bitte erneut versuchen.';

    function getSentinel() {
        return document.getElementById('listSentinel');
    }

    function getList() {
        return document.getElementById('Liste');
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

    function setStatus(text, isLoading) {
        var sentinel = getSentinel();
        if(!sentinel) return;
        if(text) {
            setBarVisible(true);
            sentinel.textContent = text;
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

    function applyFilter(sentinel) {
        var filterFn = sentinel.getAttribute('data-filter-fn');
        if(!filterFn || typeof window[filterFn] !== 'function') return;
        var input = document.getElementById('filterString');
        if(input && input.value) {
            window[filterFn]();
        }
    }

    function appendHtml(list, sentinel, html) {
        if(!html) return;
        var wrap = document.createElement('div');
        wrap.innerHTML = html;
        while(wrap.firstChild) {
            var node = wrap.firstChild;
            if(node.nodeType === 1 && node.id && node.id !== 'listSentinel' && document.getElementById(node.id)) {
                wrap.removeChild(node);
                continue;
            }
            list.insertBefore(node, sentinel);
        }
    }

    function loadMore() {
        var sentinel = getSentinel();
        var list = getList();
        if(!sentinel || !list || loading) return;
        if(sentinel.getAttribute('data-has-more') !== '1') return;

        var type = sentinel.getAttribute('data-list-type') || '';
        var cursor = sentinel.getAttribute('data-cursor') || '';
        if(!type || cursor === '') return;

        loading = true;
        if(observer) observer.unobserve(sentinel);
        setStatus(MSG_LOADING, true);

        var url = 'getList.php?type=' + encodeURIComponent(type)
            + '&cursor=' + encodeURIComponent(cursor)
            + '&limit=50';
        var extra = sentinel.getAttribute('data-extra');
        if(extra) url += '&' + extra;

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
                        if(observer) observer.observe(sentinel);
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

            appendHtml(list, sentinel, xhr.responseText);
            applyFilter(sentinel);

            if(!hasMore) {
                showEnd();
                return;
            }
            setStatus('', false);
            if(observer) {
                setTimeout(function() {
                    if(sentinel.getAttribute('data-has-more') === '1') {
                        observer.observe(sentinel);
                    }
                }, 100);
            }
        };
        xhr.open('GET', url, true);
        xhr.send();
    }

    function init() {
        var sentinel = getSentinel();
        if(!sentinel) return;
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
