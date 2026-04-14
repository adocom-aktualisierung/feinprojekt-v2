import { initI18n, t } from './i18n.js';

// Inject Font-Size + Language switches into the mobile nav (they live in
// `.above-header`, which is hidden < 1024px). Runs before i18n + font/lang
// handlers so the existing `querySelectorAll` bindings pick up both instances.
// UX-Audit 2026-04-14, P0-2.
(function injectMobileUtilitySwitches() {
  const mobileNavEl = document.querySelector('.mobile-nav');
  if (!mobileNavEl) return;
  if (mobileNavEl.querySelector('.mobile-utility-switches')) return; // already injected

  const currentFontSize = localStorage.getItem('font-size') || 'normal';
  const currentLang = localStorage.getItem('lang') || document.documentElement.lang || 'de';

  // Build DOM safely — no innerHTML (XSS prevention, Design-Critique Fix #03)
  const wrap = document.createElement('div');
  wrap.className = 'mobile-utility-switches';

  // -- Font-size switch group --
  const fontGroup = document.createElement('div');
  fontGroup.className = 'mobile-switch-group';

  const fontLabel = document.createElement('span');
  fontLabel.className = 'mobile-switch-label';
  fontLabel.dataset.i18n = 'common.aboveHeader.fontSizeLabel';
  fontLabel.textContent = 'Schriftgröße wählen';
  fontGroup.appendChild(fontLabel);

  const fontSwitch = document.createElement('div');
  fontSwitch.className = 'font-size-switch';
  fontSwitch.setAttribute('role', 'group');
  fontSwitch.setAttribute('aria-label', 'Schriftgröße wählen');
  fontSwitch.dataset.i18nAriaLabel = 'common.aboveHeader.fontSizeLabel';

  const fontBtnNormal = document.createElement('button');
  fontBtnNormal.type = 'button';
  fontBtnNormal.className = 'font-btn';
  fontBtnNormal.dataset.size = 'normal';
  fontBtnNormal.setAttribute('aria-pressed', String(currentFontSize === 'normal'));
  fontBtnNormal.setAttribute('aria-label', 'Normale Schriftgröße');
  fontBtnNormal.dataset.i18nAriaLabel = 'common.aboveHeader.fontNormal';
  fontBtnNormal.textContent = 'A';

  const fontBtnLarge = document.createElement('button');
  fontBtnLarge.type = 'button';
  fontBtnLarge.className = 'font-btn';
  fontBtnLarge.dataset.size = 'large';
  fontBtnLarge.setAttribute('aria-pressed', String(currentFontSize === 'large'));
  fontBtnLarge.setAttribute('aria-label', 'Große Schriftgröße');
  fontBtnLarge.dataset.i18nAriaLabel = 'common.aboveHeader.fontLarge';
  fontBtnLarge.textContent = 'A+';

  fontSwitch.appendChild(fontBtnNormal);
  fontSwitch.appendChild(fontBtnLarge);
  fontGroup.appendChild(fontSwitch);
  wrap.appendChild(fontGroup);

  // -- Language switch group --
  const langGroup = document.createElement('div');
  langGroup.className = 'mobile-switch-group';

  const langLabel = document.createElement('span');
  langLabel.className = 'mobile-switch-label';
  langLabel.dataset.i18n = 'common.aboveHeader.langLabel';
  langLabel.textContent = 'Sprache wählen';
  langGroup.appendChild(langLabel);

  const langSwitch = document.createElement('div');
  langSwitch.className = 'lang-switch';
  langSwitch.setAttribute('role', 'group');
  langSwitch.setAttribute('aria-label', 'Sprache wählen');
  langSwitch.dataset.i18nAriaLabel = 'common.aboveHeader.langLabel';

  const langBtnDe = document.createElement('button');
  langBtnDe.type = 'button';
  langBtnDe.className = 'lang-btn';
  langBtnDe.dataset.lang = 'de';
  langBtnDe.setAttribute('aria-pressed', String(currentLang === 'de'));
  langBtnDe.setAttribute('aria-label', 'Deutsch');
  langBtnDe.textContent = 'DE';

  const langBtnEn = document.createElement('button');
  langBtnEn.type = 'button';
  langBtnEn.className = 'lang-btn';
  langBtnEn.dataset.lang = 'en';
  langBtnEn.setAttribute('aria-pressed', String(currentLang === 'en'));
  langBtnEn.setAttribute('aria-label', 'English');
  langBtnEn.textContent = 'EN';

  langSwitch.appendChild(langBtnDe);
  langSwitch.appendChild(langBtnEn);
  langGroup.appendChild(langSwitch);
  wrap.appendChild(langGroup);

  const contactBlock = mobileNavEl.querySelector('.mobile-contact');
  if (contactBlock) {
    mobileNavEl.insertBefore(wrap, contactBlock);
  } else {
    mobileNavEl.appendChild(wrap);
  }
})();

// Inject the Waitlist dialog markup if this page has any `.waitlist-btn`.
// The dialog itself is not in the HTML on purpose (DRY across 7 pages).
// UX-Audit 2026-04-14, P0-1.
(function injectWaitlistDialog() {
  if (!document.querySelector('.waitlist-btn')) return;
  if (document.getElementById('waitlist-dialog')) return;

  const dialog = document.createElement('dialog');
  dialog.id = 'waitlist-dialog';
  dialog.setAttribute('aria-labelledby', 'waitlist-title');
  dialog.setAttribute('aria-describedby', 'waitlist-workshop-info');
  dialog.innerHTML = `
    <div class="dialog-inner">
      <button type="button" class="dialog-close" aria-label="Dialog schließen" data-i18n-aria-label="common.dialog.closeLabel">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"/></svg>
      </button>
      <h2 id="waitlist-title" data-i18n="common.waitlist.title">Auf die Warteliste</h2>
      <p id="waitlist-workshop-info" class="dialog-workshop-info" aria-live="polite"></p>
      <p data-i18n="common.waitlist.intro">Wir informieren Sie, sobald ein Platz frei wird oder ein Zusatztermin feststeht.</p>
      <p class="form-required-hint" aria-hidden="true" data-i18n="common.form.requiredHint"><small>* = Pflichtangabe</small></p>
      <form class="contact-form" id="waitlist-form" aria-label="Warteliste-Formular" novalidate>
        <input type="hidden" name="workshop" id="waitlist-workshop">
        <div class="form-group">
          <label for="waitlist-name" data-i18n="common.form.name">Name *</label>
          <input type="text" id="waitlist-name" name="name" required autocomplete="name" class="form-input" aria-describedby="waitlist-name-error">
          <span id="waitlist-name-error" class="form-error-message" aria-live="polite" data-i18n="common.form.nameError">Bitte geben Sie Ihren Namen ein.</span>
        </div>
        <div class="form-group">
          <label for="waitlist-email" data-i18n="common.form.email">E-Mail *</label>
          <input type="email" id="waitlist-email" name="email" required autocomplete="email" class="form-input" aria-describedby="waitlist-email-error">
          <span id="waitlist-email-error" class="form-error-message" aria-live="polite" data-i18n="common.form.emailError">Bitte geben Sie eine gültige E-Mail-Adresse ein.</span>
        </div>
        <div class="form-group">
          <label for="waitlist-phone">Telefon <span class="label-optional" data-i18n="common.form.phoneOptional">(optional)</span></label>
          <input type="tel" id="waitlist-phone" name="phone" autocomplete="tel" class="form-input">
        </div>
        <div>
          <label class="form-consent">
            <input type="checkbox" id="waitlist-consent" required>
            Ich stimme der Verarbeitung meiner Daten gemäß der <a href="/datenschutz">Datenschutzerklärung</a> zu. *
          </label>
        </div>
        <div>
          <button type="submit" class="btn btn-green" data-i18n="common.waitlist.submit">Auf Warteliste eintragen</button>
        </div>
      </form>
    </div>
  `;
  document.body.appendChild(dialog);
})();

// Initialize i18n
initI18n();

// Mobile Navigation Toggle
const navToggle = document.querySelector('.nav-toggle');
const mobileNav = document.querySelector('.mobile-nav');

if (navToggle && mobileNav) {
  const focusableEls = () => mobileNav.querySelectorAll('a[href], button, input, textarea, select, [tabindex]:not([tabindex="-1"])');

  function openMobileNav() {
    navToggle.setAttribute('aria-expanded', 'true');
    mobileNav.classList.add('is-open');
    document.body.style.overflow = 'hidden';
    const els = focusableEls();
    if (els.length) els[0].focus();
  }

  function closeMobileNav() {
    navToggle.setAttribute('aria-expanded', 'false');
    mobileNav.classList.remove('is-open');
    document.body.style.overflow = '';
    navToggle.focus();
  }

  navToggle.addEventListener('click', () => {
    const isOpen = navToggle.getAttribute('aria-expanded') === 'true';
    isOpen ? closeMobileNav() : openMobileNav();
  });

  // Close mobile nav on link click
  mobileNav.querySelectorAll('a').forEach(link => {
    link.addEventListener('click', closeMobileNav);
  });

  // Escape key closes mobile nav
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && mobileNav.classList.contains('is-open')) {
      closeMobileNav();
    }
  });

  // Focus trap within mobile nav
  mobileNav.addEventListener('keydown', (e) => {
    if (e.key !== 'Tab') return;
    const els = focusableEls();
    if (!els.length) return;
    const first = els[0];
    const last = els[els.length - 1];

    if (e.shiftKey && document.activeElement === first) {
      e.preventDefault();
      last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault();
      first.focus();
    }
  });
}

// Language Switcher — handled by js/i18n.js (initLangSwitcher)

// Font-Size Switcher
const fontBtns = document.querySelectorAll('.font-btn');
if (fontBtns.length) {
  const savedSize = localStorage.getItem('font-size') || 'normal';
  document.documentElement.setAttribute('data-font-size', savedSize);
  fontBtns.forEach(btn => {
    btn.setAttribute('aria-pressed', btn.dataset.size === savedSize ? 'true' : 'false');
  });

  fontBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      const size = btn.dataset.size;
      document.documentElement.setAttribute('data-font-size', size);
      localStorage.setItem('font-size', size);
      fontBtns.forEach(b => b.setAttribute('aria-pressed', b.dataset.size === size ? 'true' : 'false'));
    });
  });
}

// Registration Dialog
const regDialog = document.getElementById('registration-dialog');
const regForm = document.getElementById('registration-form');

if (regDialog) {
  const regClose = regDialog.querySelector('.dialog-close');
  const workshopInfo = document.getElementById('dialog-workshop-info');

  // Focus trap fallback: native <dialog> traps focus in modern browsers via the
  // top-layer, but older browsers and polyfills leak focus. Mirrors the mobile-nav
  // pattern above.
  const dialogFocusables = () => regDialog.querySelectorAll(
    'a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])'
  );
  regDialog.addEventListener('keydown', (e) => {
    if (e.key !== 'Tab' || !regDialog.open) return;
    const els = dialogFocusables();
    if (!els.length) return;
    const first = els[0];
    const last = els[els.length - 1];
    if (e.shiftKey && document.activeElement === first) {
      e.preventDefault();
      last.focus();
    } else if (!e.shiftKey && document.activeElement === last) {
      e.preventDefault();
      first.focus();
    }
  });

  // Open dialog from workshop cards (entire card is clickable)
  function openWorkshopDialog(card) {
    document.getElementById('reg-workshop').value = card.dataset.workshop;
    document.getElementById('reg-date').value = card.dataset.date;
    document.getElementById('reg-time').value = card.dataset.time;
    document.getElementById('reg-location').value = card.dataset.location;

    // Safely populate workshop info display (no innerHTML)
    workshopInfo.textContent = '';
    const strong = document.createElement('strong');
    strong.textContent = card.dataset.workshop;
    workshopInfo.appendChild(strong);
    workshopInfo.appendChild(document.createTextNode(' · ' + card.dataset.date + ', ' + card.dataset.time + ' · ' + card.dataset.location));
    regDialog.showModal();
    // Move focus to first required input (not the close button, which is the browser default)
    requestAnimationFrame(() => {
      document.getElementById('reg-name')?.focus();
    });
  }

  // Workshop cards: if wrapped in a link, let the link navigate; otherwise open dialog
  document.querySelectorAll('.workshop-card[data-workshop]').forEach(card => {
    if (card.querySelector('.workshop-card-link')) return;
    card.addEventListener('click', () => openWorkshopDialog(card));
    card.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        openWorkshopDialog(card);
      }
    });
  });

  // Workshop detail page: CTA button opens dialog (skip if disabled/sold-out)
  document.querySelectorAll('.workshop-detail-cta[data-workshop]').forEach(btn => {
    if (btn.disabled) return;
    btn.addEventListener('click', () => openWorkshopDialog(btn));
  });

  // Close via × button
  if (regClose) {
    regClose.addEventListener('click', () => regDialog.close());
  }

  // Close on backdrop click
  regDialog.addEventListener('click', (e) => {
    if (e.target === regDialog) regDialog.close();
  });

  // Reset form on close
  regDialog.addEventListener('close', () => {
    if (regForm) regForm.reset();
    regForm?.querySelectorAll('.form-input').forEach(i => {
      i.classList.remove('is-invalid');
      i.setAttribute('aria-invalid', 'false');
    });
    regForm?.querySelectorAll('.form-error-message').forEach(el => { el.style.display = 'none'; });
  });
}

// Registration form submit
if (regForm) {
  regForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const requiredInputs = regForm.querySelectorAll('.form-input[required]');
    let firstInvalid = null;
    const invalidFields = [];

    requiredInputs.forEach(input => {
      if (!validateField(input)) {
        invalidFields.push(input);
        if (!firstInvalid) firstInvalid = input;
      }
    });

    const consent = regForm.querySelector('input[type="checkbox"]');
    if (consent && !consent.checked) {
      showToast(t('common.toast.consentRequired', 'Bitte stimmen Sie der Datenschutzerklärung zu.'), 'error');
      return;
    }

    if (firstInvalid) {
      showErrorSummary(regForm, invalidFields);
      showToast(t('common.toast.fixFields', 'Bitte korrigieren Sie die markierten Felder.'), 'error');
      return;
    }
    clearErrorSummary(regForm);

    // Collect form data
    const payload = {
      name:      regForm.querySelector('[name="name"]').value.trim(),
      phone:     regForm.querySelector('[name="phone"]').value.trim(),
      email:     regForm.querySelector('[name="email"]').value.trim(),
      companion: regForm.querySelector('[name="companion"]').value.trim(),
      workshop:  regForm.querySelector('[name="workshop"]').value,
      date:      regForm.querySelector('[name="date"]').value,
      time:      regForm.querySelector('[name="time"]').value,
      location:  regForm.querySelector('[name="location"]').value,
      consent:       true,
      photo_consent: regForm.querySelector('[name="photo_consent"]')?.checked ?? false
    };

    // Disable submit button during request
    const submitBtn = regForm.querySelector('[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = t('common.toast.sending', 'Wird gesendet\u2026');

    try {
      const res = await fetch('/api/register.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const result = await res.json();

      if (result.ok) {
        regDialog.close();
        showToast(result.message || t('common.toast.regSuccess', 'Anmeldung erfolgreich! Wir melden uns bald bei Ihnen.'));
      } else {
        showToast(result.error || t('common.toast.genericError', 'Ein Fehler ist aufgetreten.'), 'error');
      }
    } catch {
      showToast(t('common.toast.networkError', 'Verbindungsfehler. Bitte melden Sie sich telefonisch unter +49 163 7038724.'), 'error');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = originalText;
    }
  });
}

// Scroll-triggered fade-up animations
const observer = new IntersectionObserver((entries) => {
  entries.forEach(entry => {
    if (entry.isIntersecting) {
      entry.target.classList.add('is-visible');
      observer.unobserve(entry.target);
    }
  });
}, {
  threshold: 0.1,
  rootMargin: '0px 0px -40px 0px'
});

document.querySelectorAll('.fade-up').forEach(el => observer.observe(el));

// Toast notification system
const toast = document.getElementById('toast');
let toastTimer = null;

function showToast(message, type = 'success') {
  if (!toast) return;
  toast.textContent = message;
  toast.className = `toast toast--${type} is-visible`;
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => {
    toast.classList.remove('is-visible');
  }, 4000);
}

// Error Summary — WCAG pattern: list errors above form on submit
function showErrorSummary(form, invalidFields) {
  // Remove any existing summary
  clearErrorSummary(form);
  if (!invalidFields.length) return;

  const summary = document.createElement('div');
  summary.className = 'form-error-summary';
  summary.setAttribute('role', 'alert');
  summary.setAttribute('tabindex', '-1');

  const heading = document.createElement('strong');
  heading.textContent = invalidFields.length === 1
    ? t('common.toast.errorSummary1', 'Es gibt 1 Fehler im Formular:')
    : t('common.toast.errorSummaryN', 'Es gibt {count} Fehler im Formular:').replace('{count}', invalidFields.length);
  summary.appendChild(heading);

  const list = document.createElement('ul');
  invalidFields.forEach(input => {
    const li = document.createElement('li');
    const link = document.createElement('a');
    link.href = '#' + input.id;
    const label = form.querySelector(`label[for="${input.id}"]`);
    link.textContent = label ? label.textContent.replace(/\s*\*\s*$/, '') : input.name;
    link.addEventListener('click', (e) => {
      e.preventDefault();
      input.focus();
    });
    li.appendChild(link);
    list.appendChild(li);
  });
  summary.appendChild(list);

  form.insertBefore(summary, form.firstChild);
  summary.focus();
}

function clearErrorSummary(form) {
  const existing = form.querySelector('.form-error-summary');
  if (existing) existing.remove();
}

// Form handling — inline validation + toast feedback
function validateField(input) {
  if (!input.required) return true;
  const valid = input.validity.valid;
  input.classList.toggle('is-invalid', !valid);
  input.setAttribute('aria-invalid', !valid ? 'true' : 'false');
  // Show/hide the associated error message
  const errorId = input.getAttribute('aria-describedby');
  if (errorId) {
    const errorEl = document.getElementById(errorId);
    if (errorEl) errorEl.style.display = valid ? 'none' : 'block';
  }
  return valid;
}

// Hide error messages initially and set aria-invalid=false
document.querySelectorAll('.form-error-message').forEach(el => { el.style.display = 'none'; });
document.querySelectorAll('.form-input[required]').forEach(input => {
  input.setAttribute('aria-invalid', 'false');
});

// Real-time validation on blur
document.querySelectorAll('.form-input[required]').forEach(input => {
  input.addEventListener('blur', () => validateField(input));
  input.addEventListener('input', () => {
    if (input.classList.contains('is-invalid')) {
      validateField(input);
    }
  });
});

// Build success message using safe DOM methods
function createSuccessMessage() {
  const wrapper = document.createElement('div');
  wrapper.className = 'form-success';

  const iconWrap = document.createElement('div');
  iconWrap.className = 'form-success-icon';
  const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
  svg.setAttribute('viewBox', '0 0 24 24');
  svg.setAttribute('aria-hidden', 'true');
  const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
  path.setAttribute('d', 'M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z');
  svg.appendChild(path);
  iconWrap.appendChild(svg);

  const heading = document.createElement('h3');
  heading.textContent = t('common.contactSuccessHeading', 'Vielen Dank für Ihre Nachricht!');

  const paragraph = document.createElement('p');
  paragraph.textContent = t('common.contactSuccessText', 'Wir melden uns innerhalb von 2 Werktagen bei Ihnen.');

  wrapper.appendChild(iconWrap);
  wrapper.appendChild(heading);
  wrapper.appendChild(paragraph);
  return wrapper;
}

// Newsletter form
const newsletterForm = document.querySelector('[data-form="newsletter"]');
if (newsletterForm) {
  newsletterForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = newsletterForm.querySelector('input[type="email"]');
    const checkbox = newsletterForm.closest('.newsletter-card').querySelector('.newsletter-consent input[type="checkbox"]');
    const submitBtn = newsletterForm.querySelector('button[type="submit"]');

    if (!email.validity.valid) {
      email.focus();
      return;
    }
    if (checkbox && !checkbox.checked) {
      showToast(t('common.toast.consentRequired', 'Bitte stimmen Sie der Datenschutzerklärung zu.'), 'error');
      return;
    }

    submitBtn.disabled = true;
    submitBtn.textContent = t('common.toast.sending', 'Wird gesendet…');

    try {
      const res = await fetch('/api/newsletter.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          email: email.value.trim(),
          consent: checkbox.checked,
        }),
      });

      const result = await res.json();

      if (result.ok) {
        showToast(result.message || t('common.toast.newsletterSuccess', 'Bitte prüfen Sie Ihr Postfach – wir haben Ihnen eine Bestätigungs-E-Mail gesendet.'));
        newsletterForm.reset();
      } else {
        showToast(result.error || t('common.toast.newsletterError', 'Die Anmeldung konnte nicht durchgeführt werden.'), 'error');
      }
    } catch {
      showToast(t('common.toast.newsletterNetworkError', 'Verbindungsfehler – bitte versuchen Sie es später erneut.'), 'error');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = t('common.form.subscribe', 'Anmelden');
    }
  });
}

// Contact form
const contactForm = document.querySelector('[data-form="contact"]');
if (contactForm) {
  contactForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const requiredInputs = contactForm.querySelectorAll('.form-input[required]');
    let firstInvalid = null;
    const invalidFields = [];

    requiredInputs.forEach(input => {
      if (!validateField(input)) {
        invalidFields.push(input);
        if (!firstInvalid) firstInvalid = input;
      }
    });

    const checkbox = contactForm.querySelector('input[type="checkbox"]');
    if (checkbox && !checkbox.checked) {
      showToast(t('common.toast.consentRequired', 'Bitte stimmen Sie der Datenschutzerklärung zu.'), 'error');
      return;
    }

    if (firstInvalid) {
      showErrorSummary(contactForm, invalidFields);
      showToast(t('common.toast.fixFields', 'Bitte korrigieren Sie die markierten Felder.'), 'error');
      return;
    }
    clearErrorSummary(contactForm);

    const submitBtn = contactForm.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = t('common.toast.sending', 'Wird gesendet…');

    try {
      const res = await fetch('/api/contact.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          name: contactForm.querySelector('[name="name"]').value.trim(),
          email: contactForm.querySelector('[name="email"]').value.trim(),
          subject: contactForm.querySelector('[name="subject"]').value.trim(),
          message: contactForm.querySelector('[name="message"]').value.trim(),
          consent: checkbox.checked,
        }),
      });

      const result = await res.json();

      if (result.ok) {
        const formParent = contactForm.parentElement;
        contactForm.remove();
        formParent.appendChild(createSuccessMessage());
        showToast(t('common.toast.contactSuccess', 'Nachricht erfolgreich gesendet!'));
      } else {
        showToast(result.error || t('common.toast.contactError', 'Die Nachricht konnte nicht gesendet werden.'), 'error');
      }
    } catch {
      showToast(t('common.toast.contactNetworkError', 'Verbindungsfehler – bitte versuchen Sie es telefonisch unter +49 163 7038724.'), 'error');
    } finally {
      if (submitBtn.parentElement) {
        submitBtn.disabled = false;
        submitBtn.textContent = t('common.form.sendMessage', 'Nachricht senden');
      }
    }
  });
}

// ── Waitlist Dialog (UX-Audit 2026-04-14, P0-1) ─────────────────
const waitlistDialog = document.getElementById('waitlist-dialog');
const waitlistForm = document.getElementById('waitlist-form');

if (waitlistDialog && waitlistForm) {
  const waitlistWorkshopInfo = document.getElementById('waitlist-workshop-info');
  const waitlistClose = waitlistDialog.querySelector('.dialog-close');

  function openWaitlistDialog(btn) {
    const slug = btn.dataset.workshop || '';
    const title = btn.dataset.workshopTitle || slug;
    document.getElementById('waitlist-workshop').value = title || slug;
    waitlistWorkshopInfo.textContent = '';
    if (title) {
      const strong = document.createElement('strong');
      strong.textContent = title;
      waitlistWorkshopInfo.appendChild(strong);
    }
    waitlistDialog.showModal();
    requestAnimationFrame(() => document.getElementById('waitlist-name')?.focus());
  }

  document.querySelectorAll('.waitlist-btn').forEach(btn => {
    btn.addEventListener('click', (e) => {
      e.preventDefault();
      e.stopPropagation();
      openWaitlistDialog(btn);
    });
  });

  if (waitlistClose) {
    waitlistClose.addEventListener('click', () => waitlistDialog.close());
  }
  waitlistDialog.addEventListener('click', (e) => {
    if (e.target === waitlistDialog) waitlistDialog.close();
  });
  waitlistDialog.addEventListener('close', () => {
    waitlistForm.reset();
    waitlistForm.querySelectorAll('.form-input').forEach(i => {
      i.classList.remove('is-invalid');
      i.setAttribute('aria-invalid', 'false');
    });
    waitlistForm.querySelectorAll('.form-error-message').forEach(el => { el.style.display = 'none'; });
    clearErrorSummary(waitlistForm);
  });

  waitlistForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    const requiredInputs = waitlistForm.querySelectorAll('.form-input[required]');
    let firstInvalid = null;
    const invalidFields = [];
    requiredInputs.forEach(input => {
      if (!validateField(input)) {
        invalidFields.push(input);
        if (!firstInvalid) firstInvalid = input;
      }
    });
    const consent = waitlistForm.querySelector('#waitlist-consent');
    if (consent && !consent.checked) {
      showToast(t('common.toast.consentRequired', 'Bitte stimmen Sie der Datenschutzerklärung zu.'), 'error');
      return;
    }
    if (firstInvalid) {
      showErrorSummary(waitlistForm, invalidFields);
      showToast(t('common.toast.fixFields', 'Bitte korrigieren Sie die markierten Felder.'), 'error');
      return;
    }
    clearErrorSummary(waitlistForm);

    const payload = {
      name:     waitlistForm.querySelector('[name="name"]').value.trim(),
      email:    waitlistForm.querySelector('[name="email"]').value.trim(),
      phone:    waitlistForm.querySelector('[name="phone"]').value.trim(),
      workshop: waitlistForm.querySelector('[name="workshop"]').value,
      consent:  true,
    };

    const submitBtn = waitlistForm.querySelector('[type="submit"]');
    const originalText = submitBtn.textContent;
    submitBtn.disabled = true;
    submitBtn.textContent = t('common.toast.sending', 'Wird gesendet\u2026');

    try {
      const res = await fetch('/api/waitlist.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });
      const result = await res.json();
      if (result.ok) {
        waitlistDialog.close();
        showToast(t('common.waitlist.success', 'Danke! Wir melden uns, sobald ein Platz frei wird.'));
      } else {
        showToast(result.error || t('common.toast.genericError', 'Ein Fehler ist aufgetreten.'), 'error');
      }
    } catch {
      showToast(t('common.toast.networkError', 'Verbindungsfehler. Bitte melden Sie sich telefonisch unter +49 163 7038724.'), 'error');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = originalText;
    }
  });
}
