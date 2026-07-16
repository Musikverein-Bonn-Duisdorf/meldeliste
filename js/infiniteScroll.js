/**
 * Infinite scroll for long lists (MELD-55).
 * Expects #Liste and #listSentinel with data-list-type, data-cursor, data-has-more.
 * Optional data-filter-fn: name of global filter function to re-run after append.
 * Optional data-extra: query string fragment (e.g. user=123).
 */
(function() {
    var loading = false;

    function getSentinel() {
        return document.getElementById('listSentinel');
    }

    function getList() {
        return document.getElementById('Liste');
    }

    function loadMore() {
        var sentinel = getSentinel();
        var list = getList();
        if(!sentinel || !list || loading) return;
        if(sentinel.getAttribute('data-has-more') !== '1') return;

        var type = sentinel.getAttribute('data-list-type') || '';
        var cursor = sentinel.getAttribute('data-cursor') || '';
        if(!type) return;

        loading = true;
        sentinel.textContent = 'Lade…';

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
                sentinel.textContent = '';
                return;
            }
            var hasMore = xhr.getResponseHeader('X-Has-More') === '1';
            var nextCursor = xhr.getResponseHeader('X-Next-Cursor') || cursor;
            sentinel.setAttribute('data-has-more', hasMore ? '1' : '0');
            sentinel.setAttribute('data-cursor', nextCursor || '');

            if(xhr.responseText) {
                var wrap = document.createElement('div');
                wrap.innerHTML = xhr.responseText;
                while(wrap.firstChild) {
                    list.insertBefore(wrap.firstChild, sentinel);
                }
            }

            sentinel.textContent = '';
            if(!hasMore) {
                sentinel.style.display = 'none';
            }

            var filterFn = sentinel.getAttribute('data-filter-fn');
            if(filterFn && typeof window[filterFn] === 'function') {
                window[filterFn]();
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

        if('IntersectionObserver' in window) {
            var obs = new IntersectionObserver(function(entries) {
                for(var i = 0; i < entries.length; i++) {
                    if(entries[i].isIntersecting) loadMore();
                }
            }, { root: null, rootMargin: '200px', threshold: 0 });
            obs.observe(sentinel);
        }
        else {
            window.addEventListener('scroll', function() {
                var rect = sentinel.getBoundingClientRect();
                if(rect.top < window.innerHeight + 200) loadMore();
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
