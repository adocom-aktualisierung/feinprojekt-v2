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

// Language Switcher
document.querySelectorAll('.lang-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.lang-btn').forEach(b => b.setAttribute('aria-pressed', 'false'));
    btn.setAttribute('aria-pressed', 'true');
  });
});

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

  // Open dialog from workshop card buttons
  document.querySelectorAll('[data-workshop]').forEach(btn => {
    btn.addEventListener('click', () => {
      // Populate hidden fields with workshop data
      document.getElementById('reg-workshop').value = btn.dataset.workshop;
      document.getElementById('reg-date').value = btn.dataset.date;
      document.getElementById('reg-time').value = btn.dataset.time;
      document.getElementById('reg-location').value = btn.dataset.location;

      // Safely populate workshop info display (no innerHTML)
      workshopInfo.textContent = '';
      const strong = document.createElement('strong');
      strong.textContent = btn.dataset.workshop;
      workshopInfo.appendChild(strong);
      workshopInfo.appendChild(document.createTextNode(' · ' + btn.dataset.date + ', ' + btn.dataset.time + ' · ' + btn.dataset.location));
      regDialog.showModal();
    });
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
      showToast('Bitte stimmen Sie der Datenschutzerklärung zu.', 'error');
      return;
    }

    if (firstInvalid) {
      showErrorSummary(regForm, invalidFields);
      showToast('Bitte korrigieren Sie die markierten Felder.', 'error');
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
    submitBtn.textContent = 'Wird gesendet\u2026';

    try {
      const res = await fetch('/api/register.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });

      const result = await res.json();

      if (result.ok) {
        regDialog.close();
        showToast('Anmeldung erfolgreich! Wir melden uns bald bei Ihnen.');
      } else {
        showToast(result.error || 'Ein Fehler ist aufgetreten.', 'error');
      }
    } catch {
      // Network error — show fallback with phone number
      showToast('Verbindungsfehler. Bitte melden Sie sich telefonisch unter +49 163 7038724.', 'error');
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
    ? 'Es gibt 1 Fehler im Formular:'
    : `Es gibt ${invalidFields.length} Fehler im Formular:`;
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
  heading.textContent = 'Vielen Dank für Ihre Nachricht!';

  const paragraph = document.createElement('p');
  paragraph.textContent = 'Wir melden uns innerhalb von 2 Werktagen bei Ihnen.';

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
      showToast('Bitte stimmen Sie der Datenschutzerklärung zu.', 'error');
      return;
    }

    submitBtn.disabled = true;
    submitBtn.textContent = 'Wird gesendet…';

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
        showToast(result.message || 'Vielen Dank! Sie erhalten in Kürze eine Bestätigungs-E-Mail.');
        newsletterForm.reset();
      } else {
        showToast(result.error || 'Die Anmeldung konnte nicht durchgeführt werden.', 'error');
      }
    } catch {
      showToast('Verbindungsfehler – bitte versuchen Sie es später erneut.', 'error');
    } finally {
      submitBtn.disabled = false;
      submitBtn.textContent = 'Anmelden';
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
      showToast('Bitte stimmen Sie der Datenschutzerklärung zu.', 'error');
      return;
    }

    if (firstInvalid) {
      showErrorSummary(contactForm, invalidFields);
      showToast('Bitte korrigieren Sie die markierten Felder.', 'error');
      return;
    }
    clearErrorSummary(contactForm);

    const submitBtn = contactForm.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.textContent = 'Wird gesendet…';

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
        showToast('Nachricht erfolgreich gesendet!');
      } else {
        showToast(result.error || 'Die Nachricht konnte nicht gesendet werden.', 'error');
      }
    } catch {
      showToast('Verbindungsfehler – bitte versuchen Sie es telefonisch unter +49 163 7038724.', 'error');
    } finally {
      if (submitBtn.parentElement) {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Nachricht senden';
      }
    }
  });
}
