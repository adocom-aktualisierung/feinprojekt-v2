/**
 * i18n Module — Client-side translation for DE/EN
 *
 * German is the source language (lives in HTML).
 * English translations are fetched from /locales/en.json on demand.
 * Language preference is persisted in localStorage.
 */

let translations = null;
let currentLang = localStorage.getItem('lang') || 'de';

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
 * Apply translations to all [data-i18n] elements in the DOM.
 */
function applyTranslations() {
  if (!translations) return;

  // textContent
  document.querySelectorAll('[data-i18n]').forEach(el => {
    const key = el.getAttribute('data-i18n');
    const val = key.split('.').reduce((obj, k) => obj?.[k], translations);
    if (val) el.textContent = val;
  });

  // placeholder
  document.querySelectorAll('[data-i18n-placeholder]').forEach(el => {
    const key = el.getAttribute('data-i18n-placeholder');
    const val = key.split('.').reduce((obj, k) => obj?.[k], translations);
    if (val) el.placeholder = val;
  });

  // aria-label
  document.querySelectorAll('[data-i18n-aria-label]').forEach(el => {
    const key = el.getAttribute('data-i18n-aria-label');
    const val = key.split('.').reduce((obj, k) => obj?.[k], translations);
    if (val) el.setAttribute('aria-label', val);
  });

  // title attribute
  document.querySelectorAll('[data-i18n-title]').forEach(el => {
    const key = el.getAttribute('data-i18n-title');
    const val = key.split('.').reduce((obj, k) => obj?.[k], translations);
    if (val) el.title = val;
  });

  // alt text on images
  document.querySelectorAll('[data-i18n-alt]').forEach(el => {
    const key = el.getAttribute('data-i18n-alt');
    const val = key.split('.').reduce((obj, k) => obj?.[k], translations);
    if (val) el.alt = val;
  });

  // Page title + meta description
  const pageKey = document.documentElement.getAttribute('data-i18n-page');
  if (pageKey && translations.meta?.[pageKey]) {
    const meta = translations.meta[pageKey];
    if (meta.title) document.title = meta.title;
    const desc = document.querySelector('meta[name="description"]');
    if (desc && meta.description) desc.setAttribute('content', meta.description);
  }
}

/**
 * Set language, persist, and apply translations.
 * Switching to German reloads the page (restores HTML source text).
 */
export async function setLang(lang) {
  localStorage.setItem('lang', lang);
  if (lang === 'de') {
    location.reload();
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
