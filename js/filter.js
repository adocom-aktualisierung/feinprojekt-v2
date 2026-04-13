import { t } from './i18n.js';

/**
 * Generic filter-chip handler with WAI-ARIA Toolbar pattern.
 *
 * Expects:
 *   - .filter-bar[data-filter-group="id-of-container"] with .filter-chip buttons inside
 *   - Chips have data-filter="value" (or "all" for show-all) and aria-pressed
 *   - Container elements with [data-filter-target] and data-category matching filter
 *   - Optional: #filter-status (aria-live polite) announces result count
 *   - Optional: #filter-empty (hidden by default) displayed when 0 matches; may
 *     contain a [data-clear-filter] button to reset to "all"
 *   - Keyboard: Arrow Left/Right + Home/End navigate between chips (toolbar APG)
 */
export function initFilters() {
  document.querySelectorAll('.filter-bar').forEach(bar => {
    const chips = [...bar.querySelectorAll('.filter-chip')];
    const containerId = bar.dataset.filterGroup;
    const container = containerId ? document.getElementById(containerId) : bar.nextElementSibling;
    if (!container || !chips.length) return;

    const items = container.querySelectorAll('[data-filter-target]');
    const status = document.getElementById('filter-status');
    const emptyState = document.getElementById('filter-empty');

    function applyFilter(filter, activeChip) {
      chips.forEach(c => {
        const isActive = c === activeChip;
        c.classList.toggle('is-active', isActive);
        c.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      });

      let visible = 0;
      items.forEach(item => {
        const match = filter === 'all' || item.dataset.category === filter;
        item.classList.toggle('is-hidden', !match);
        if (match) visible++;
      });

      if (emptyState) emptyState.hidden = visible !== 0;

      if (status) {
        const msg = visible === 1
          ? t('workshops.filterStatusOne', '1 Workshop gefunden')
          : t('workshops.filterStatusMany', `${visible} Workshops gefunden`).replace('{count}', visible);
        status.textContent = msg;
      }
    }

    chips.forEach(chip => {
      chip.addEventListener('click', () => applyFilter(chip.dataset.filter, chip));
    });

    // Arrow-key navigation (WAI-ARIA Toolbar pattern)
    bar.addEventListener('keydown', (e) => {
      if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(e.key)) return;
      const idx = chips.indexOf(document.activeElement);
      if (idx === -1) return;
      e.preventDefault();
      let next = idx;
      if (e.key === 'ArrowRight') next = (idx + 1) % chips.length;
      if (e.key === 'ArrowLeft')  next = (idx - 1 + chips.length) % chips.length;
      if (e.key === 'Home') next = 0;
      if (e.key === 'End')  next = chips.length - 1;
      chips[next].focus();
    });

    // Clear-filter button in empty-state resets to "all"
    const clearBtn = document.querySelector('[data-clear-filter]');
    if (clearBtn) {
      clearBtn.addEventListener('click', () => {
        const allChip = chips.find(c => c.dataset.filter === 'all') || chips[0];
        applyFilter(allChip.dataset.filter, allChip);
        allChip.focus();
      });
    }
  });
}

// Auto-init when imported as module
initFilters();
