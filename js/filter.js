/**
 * Generic filter-chip handler.
 * Usage: call initFilters() after DOM is ready.
 * Expects:
 *   - .filter-chip buttons with data-filter="value" (or "all" for show-all)
 *   - [data-filter-target] elements with data-category matching the filter value
 */
export function initFilters() {
  document.querySelectorAll('.filter-bar').forEach(bar => {
    const chips = bar.querySelectorAll('.filter-chip');
    const containerId = bar.dataset.filterGroup;
    const container = containerId ? document.getElementById(containerId) : bar.nextElementSibling;
    if (!container) return;

    const items = container.querySelectorAll('[data-filter-target]');

    chips.forEach(chip => {
      chip.addEventListener('click', () => {
        const filter = chip.dataset.filter;

        // Update active chip
        chips.forEach(c => c.classList.remove('is-active'));
        chip.classList.add('is-active');

        // Filter items
        items.forEach(item => {
          if (filter === 'all' || item.dataset.category === filter) {
            item.classList.remove('is-hidden');
          } else {
            item.classList.add('is-hidden');
          }
        });
      });
    });
  });
}

// Auto-init when imported as module
initFilters();
