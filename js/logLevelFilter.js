/**
 * MELD-251: Log level multi-select chips (server-side via levels= on getList/getLog).
 */
(function() {
  'use strict';

  function getSentinel() {
    return document.getElementById('listSentinel');
  }

  function selectedLevels() {
    var chips = document.querySelectorAll('#logLevelFilters [data-log-level]');
    var out = [];
    for(var i = 0; i < chips.length; i++) {
      if(chips[i].classList.contains('is-active')) {
        out.push(String(chips[i].getAttribute('data-log-level')));
      }
    }
    return out;
  }

  function syncSentinelLevels() {
    var sentinel = getSentinel();
    if(!sentinel) {
      return;
    }
    sentinel.setAttribute('data-levels', selectedLevels().join(','));
  }

  function onChipClick(ev) {
    var btn = ev.target.closest ? ev.target.closest('[data-log-level]') : null;
    if(!btn || !btn.closest('#logLevelFilters')) {
      return;
    }
    ev.preventDefault();
    var on = !btn.classList.contains('is-active');
    btn.classList.toggle('is-active', on);
    btn.setAttribute('aria-pressed', on ? 'true' : 'false');
    syncSentinelLevels();
    if(typeof window.listInfiniteFilterChanged === 'function') {
      window.listInfiniteFilterChanged();
    }
  }

  function init() {
    var bar = document.getElementById('logLevelFilters');
    if(!bar) {
      return;
    }
    syncSentinelLevels();
    bar.addEventListener('click', onChipClick);
  }

  window.logLevelFilterSelected = selectedLevels;

  if(document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  }
  else {
    init();
  }
})();
