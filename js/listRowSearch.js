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
