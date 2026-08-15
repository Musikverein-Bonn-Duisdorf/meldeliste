/**
 * Search text for a list row: data-search, all data-sort-* attrs, then visible text.
 * Allows matching hidden context (e.g. loan recipient on instrument rows).
 */
function listRowSearchText(el) {
    var parts = [];
    var ds = el.getAttribute('data-search');
    if (ds) {
        parts.push(ds);
    }
    if (el.attributes) {
        for (var i = 0; i < el.attributes.length; i++) {
            var attr = el.attributes[i];
            if (attr.name.indexOf('data-sort-') === 0 && attr.value) {
                parts.push(attr.value);
            }
        }
    }
    parts.push(el.textContent || el.innerText || '');
    return parts.join(' ');
}

/**
 * Whitespace tokens for multi-term AND search (MELD-179).
 * @return {string[]}
 */
function listRowSearchTokens(query) {
    var q = String(query || '').trim();
    if (!q) {
        return [];
    }
    return q.split(/\s+/).filter(Boolean).slice(0, 8);
}

/**
 * True if haystack contains every whitespace token (case-insensitive).
 * Empty query matches everything.
 */
function listRowMatchesQuery(haystack, query) {
    var tokens = listRowSearchTokens(query);
    if (!tokens.length) {
        return true;
    }
    var h = String(haystack || '').toUpperCase();
    var i;
    for (i = 0; i < tokens.length; i++) {
        if (h.indexOf(String(tokens[i]).toUpperCase()) === -1) {
            return false;
        }
    }
    return true;
}
