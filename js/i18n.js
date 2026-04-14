/**
 * i18n Module — Client-side translation for DE/EN
 *
 * German is the source language (lives in HTML).
 * English translations are fetched from /locales/en.json on demand.
 * Language preference is persisted in localStorage.
 */

let translations = null;
let currentLang = localStorage.getItem('lang') || 'de';

// In-memory map of original DE values, keyed by attribute type → element
const deCache = new Map();

/**
 * Returns the current language code.
 */
export function getLang() {
  return currentLang;
}

/**
 * Translate a key. Returns the translated string for the current language,
 * or the fallback string (German default from HTML) if unavailable.
 * @param {string} key - Dot-separated key, e.g. 'common.nav.start'
 * @param {string} [fallback] - Fallback text (German) if no translation found
 * @returns {string}
 */
export function t(key, fallback = '') {
  if (currentLang === 'de' || !translations) return fallback;
  const val = key.split('.').reduce((obj, k) => obj?.[k], translations);
  return val || fallback;
}

/**
 * Cache the original DE value for a given element and attribute type,
 * then apply the EN translation.
 */
function cacheAndApply(el, attr, getter, setter) {
  const key = el.getAttribute(attr);
  const val = key.split('.').reduce((obj, k) => obj?.[k], translations);
  if (!val) return;
  const cacheKey = attr + '::' + key;
  if (!deCache.has(cacheKey)) {
    deCache.set(cacheKey, { elements: new Set(), original: getter(el) });
  }
  deCache.get(cacheKey).elements.add(el);
  setter(el, val);
}

/**
 * Apply translations to all [data-i18n] elements in the DOM.
 */
function applyTranslations() {
  if (!translations) return;

  document.querySelectorAll('[data-i18n]').forEach(el => {
    cacheAndApply(el, 'data-i18n', e => e.textContent, (e, v) => { e.textContent = v; });
  });

  document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
    cacheAndApply(el, 'data-i18n-placeholder', e => e.placeholder, (e, v) => { e.placeholder = v; });
  });

  document.querySelectorAll('[data-i18n-aria-label]').forEach(el => {
    cacheAndApply(el, 'data-i18n-aria-label', e => e.getAttribute('aria-label'), (e, v) => { e.setAttribute('aria-label', v); });
  });

  document.querySelectorAll('[data-i18n-title]').forEach(el => {
    cacheAndApply(el, 'data-i18n-title', e => e.title, (e, v) => { e.title = v; });
  });

  document.querySelectorAll('[data-i18n-alt]').forEach(el => {
    cacheAndApply(el, 'data-i18n-alt', e => e.alt, (e, v) => { e.alt = v; });
  });

  // Page title + meta description
  const pageKey = document.documentElement.getAttribute('data-i18n-page');
  if (pageKey && translations.meta?.[pageKey]) {
    const meta = translations.meta[pageKey];
    if (meta.title) {
      if (!deCache.has('meta::title')) deCache.set('meta::title', document.title);
      document.title = meta.title;
    }
    const desc = document.querySelector('meta[name="description"]');
    if (desc && meta.description) {
      if (!deCache.has('meta::description')) deCache.set('meta::description', desc.getAttribute('content'));
      desc.setAttribute('content', meta.description);
    }
  }
}

/**
 * Restore all elements to their original DE text from the in-memory cache.
 */
function restoreGerman() {
  const setters = {
    'data-i18n': (el, v) => { el.textContent = v; },
    'data-i18n-placeholder': (el, v) => { el.placeholder = v; },
    'data-i18n-aria-label': (el, v) => { el.setAttribute('aria-label', v); },
    'data-i18n-title': (el, v) => { el.title = v; },
    'data-i18n-alt': (el, v) => { el.alt = v; },
  };

  for (const [cacheKey, entry] of deCache) {
    if (cacheKey === 'meta::title') {
      document.title = entry;
      continue;
    }
    if (cacheKey === 'meta::description') {
      const desc = document.querySelector('meta[name="description"]');
      if (desc) desc.setAttribute('content', entry);
      continue;
    }
    const attr = cacheKey.split('::')[0];
    const setter = setters[attr];
    if (setter && entry.elements) {
      for (const el of entry.elements) {
        if (el.isConnected) setter(el, entry.original);
      }
    }
  }
}

/**
 * Set language, persist, and apply translations.
 * Switching to German reloads the page (restores HTML source text).
 */
export async function setLang(lang) {
  localStorage.setItem('lang', lang);
  if (lang === 'de') {
    currentLang = 'de';
    document.documentElement.lang = 'de';
    document.documentElement.dataset.lang = 'de';
    restoreGerman();
    return;
  }
  currentLang = lang;
  document.documentElement.lang = lang;
  document.documentElement.dataset.lang = lang;
  try {
    const res = await fetch('/locales/en.json');
    translations = await res.json();
  } catch (e) {
    console.warn('i18n: Failed to load translations', e);
    return;
  }
  applyTranslations();
  document.documentElement.classList.add('i18n-ready');
}

/**
 * Initialize i18n on page load. Called from main.js.
 */
export async function initI18n() {
  currentLang = localStorage.getItem('lang') || 'de';
  if (currentLang !== 'de') {
    document.documentElement.lang = currentLang;
    document.documentElement.dataset.lang = currentLang;
    try {
      const res = await fetch('/locales/en.json');
      translations = await res.json();
    } catch (e) {
      console.warn('i18n: Failed to load translations', e);
      currentLang = 'de';
      document.documentElement.lang = 'de';
      document.documentElement.dataset.lang = 'de';
      document.documentElement.classList.add('i18n-ready');
      return;
    }
    applyTranslations();
  }
  document.documentElement.classList.add('i18n-ready');
  initLangSwitcher();
}

/**
 * Wire up the language switcher buttons in the above-header bar.
 */
function initLangSwitcher() {
  const langBtns = document.querySelectorAll('.lang-btn');
  if (!langBtns.length) return;

  // Set initial aria-pressed state
  langBtns.forEach(btn => {
    const btnLang = btn.getAttribute('data-lang');
    btn.setAttribute('aria-pressed', btnLang === currentLang ? 'true' : 'false');
    btn.disabled = false;
    btn.classList.remove('lang-btn-disabled');
    btn.removeAttribute('title');
    btn.setAttribute('aria-label', btnLang === 'de' ? 'Deutsch' : 'English');
  });

  langBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const lang = btn.getAttribute('data-lang');
      if (lang === currentLang) return;
      langBtns.forEach(b => b.setAttribute('aria-pressed', b.getAttribute('data-lang') === lang ? 'true' : 'false'));
      setLang(lang);
    });
  });
}
