/**
 * Infinite scroll for long lists (MELD-55).
 * Expects #Liste and #listSentinel with data-list-type, data-cursor, data-has-more.
 * Optional data-filter-fn: name of global filter function to re-run after append.
 * Optional data-extra: query string fragment (e.g. user=123).
 */
(function() {
    var loading = false;
    var observer = null;

    function getSentinel() {
        return document.getElementById('listSentinel');
    }

    function getList() {
        return document.getElementById('Liste');
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
                if(observer && sentinel.getAttribute('data-has-more') === '1') {
                    observer.observe(sentinel);
                }
                return;
            }
            var hasMore = xhr.getResponseHeader('X-Has-More') === '1';
            var nextCursor = xhr.getResponseHeader('X-Next-Cursor');
            if(nextCursor === null || nextCursor === '') {
                nextCursor = cursor;
                hasMore = false;
            }
            // Prevent tight loops that re-request the same cursor
            if(nextCursor === cursor) {
                hasMore = false;
            }
            sentinel.setAttribute('data-has-more', hasMore ? '1' : '0');
            sentinel.setAttribute('data-cursor', nextCursor);

            appendHtml(list, sentinel, xhr.responseText);
            applyFilter(sentinel);

            if(!hasMore) {
                sentinel.style.display = 'none';
                return;
            }
            if(observer) {
                // Re-observe after layout so we only load again if still in view / user scrolls
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
            sentinel.style.display = 'none';
            return;
        }
        // Initial page already loaded first chunk; require a cursor for next page
        if(!sentinel.getAttribute('data-cursor')) {
            sentinel.setAttribute('data-has-more', '0');
            sentinel.style.display = 'none';
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
