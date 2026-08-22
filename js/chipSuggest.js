/**
 * Shared keyboard navigation for mail-recipient chip suggestion lists.
 */
(function(global) {
    'use strict';

    var ACTIVE = 'mail-recipient-suggest-item--active';

    function highlight(suggestEl, activeIndex, scrollIntoView) {
        if(!suggestEl) {
            return;
        }
        var rows = suggestEl.querySelectorAll('.mail-recipient-suggest-item');
        for(var i = 0; i < rows.length; i++) {
            var on = i === activeIndex;
            if(on) {
                rows[i].classList.add(ACTIVE);
                if(scrollIntoView) {
                    rows[i].scrollIntoView({ block: 'nearest' });
                }
            } else {
                rows[i].classList.remove(ACTIVE);
            }
        }
    }

    function itemClassName(index, activeIndex) {
        return 'mail-recipient-suggest-item'
            + (index === activeIndex ? ' mail-recipient-suggest-item--active' : '');
    }

    function stepIndex(key, activeIndex, length, wrap) {
        if(length <= 0) {
            return null;
        }
        if(key === 'ArrowDown') {
            if(wrap) {
                return activeIndex < 0 ? 0 : (activeIndex + 1) % length;
            }
            return activeIndex < 0 ? 0 : Math.min(activeIndex + 1, length - 1);
        }
        if(key === 'ArrowUp') {
            if(wrap) {
                return activeIndex <= 0 ? length - 1 : activeIndex - 1;
            }
            return activeIndex <= 0 ? 0 : activeIndex - 1;
        }
        return null;
    }

    global.ChipSuggest = {
        ACTIVE_CLASS: ACTIVE,
        highlight: highlight,
        itemClassName: itemClassName,
        stepIndex: stepIndex
    };
})(window);
