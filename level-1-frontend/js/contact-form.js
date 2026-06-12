(function () {
  const form = document.querySelector('.contact-form');
  if (!form) return;

  const API_URL = window.CONTACT_API_URL || 'http://localhost:5000';
  const NAME_RE = /^[A-Za-zА-Яа-яІіЇїЄєҐґ'\-\s]{2,50}$/;
  const UA_OPERATORS = ['50', '63', '66', '67', '68', '73', '91', '92', '93', '94', '95', '96', '97', '98', '99'];

  const statusBox = document.createElement('p');
  statusBox.className = 'form-status';
  statusBox.setAttribute('aria-live', 'polite');
  form.appendChild(statusBox);

  const formLoadedAt = Math.floor(Date.now() / 1000);
  const phoneInput = form.querySelector('#phone');

  function showFieldError(id, message) {
    const box = form.querySelector('#' + id + '-error');
    if (box) box.textContent = message || '';
  }

  function normalizePhone(value) {
    const digits = value.replace(/\D/g, '');
    if (digits.startsWith('380')) return digits;
    if (digits.startsWith('0') && digits.length === 10) return '38' + digits;
    if (digits.length === 9) return '380' + digits;
    return digits;
  }

  function isValidUaPhone(value) {
    const digits = normalizePhone(value);
    if (digits.length !== 12 || !digits.startsWith('380')) return false;
    return UA_OPERATORS.includes(digits.slice(3, 5));
  }

  function formatPhoneInput(value) {
    let digits = value.replace(/\D/g, '');
    if (digits.startsWith('380')) digits = digits.slice(3);
    if (digits.startsWith('0')) digits = digits.slice(1);
    digits = digits.slice(0, 9);

    let formatted = '+380';
    if (digits.length > 0) formatted += ' (' + digits.slice(0, 2);
    if (digits.length >= 2) formatted += ') ' + digits.slice(2, 5);
    if (digits.length >= 5) formatted += '-' + digits.slice(5, 7);
    if (digits.length >= 7) formatted += '-' + digits.slice(7, 9);
    return formatted;
  }

  function validateForm() {
    let valid = true;
    const firstName = form.querySelector('#first-name').value.trim();
    const lastName = form.querySelector('#last-name').value.trim();
    const phone = form.querySelector('#phone').value.trim();
    const email = form.querySelector('#email').value.trim();

    showFieldError('first-name', '');
    showFieldError('last-name', '');
    showFieldError('phone', '');
    showFieldError('email', '');

    if (!NAME_RE.test(firstName)) {
      showFieldError('first-name', 'Тільки літери');
      valid = false;
    }
    if (lastName && !NAME_RE.test(lastName)) {
      showFieldError('last-name', 'Тільки літери');
      valid = false;
    }
    if (!isValidUaPhone(phone)) {
      showFieldError('phone', 'Формат: +380 (XX) XXX-XX-XX');
      valid = false;
    }
    if (!email) {
      showFieldError('email', 'Вкажіть email');
      valid = false;
    }

    return valid;
  }

  if (phoneInput) {
    phoneInput.addEventListener('input', function () {
      phoneInput.value = formatPhoneInput(phoneInput.value);
    });
    phoneInput.placeholder = '+380 (50) 123-45-67';
  }

  ['#first-name', '#last-name'].forEach(function (selector) {
    const input = form.querySelector(selector);
    if (!input) return;
    input.addEventListener('input', function () {
      input.value = input.value.replace(/[^A-Za-zА-Яа-яІіЇїЄєҐґ'\-\s]/g, '');
    });
  });

  form.addEventListener('submit', async function (event) {
    event.preventDefault();
    statusBox.textContent = '';

    if (!validateForm()) {
      statusBox.textContent = 'Перевірте поля форми';
      statusBox.className = 'form-status form-status--error';
      return;
    }

    statusBox.textContent = 'Надсилаємо...';
    statusBox.className = 'form-status form-status--loading';

    const payload = {
      first_name: form.querySelector('#first-name').value.trim(),
      last_name: form.querySelector('#last-name').value.trim(),
      email: form.querySelector('#email').value.trim(),
      phone: form.querySelector('#phone').value.trim(),
      message: form.querySelector('#message').value.trim(),
      website: form.querySelector('[name="website"]')?.value || '',
      form_loaded_at: formLoadedAt,
    };

    try {
      const response = await fetch(API_URL + '/api/contact', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
      });

      const result = await response.json();

      if (response.ok && result.ok) {
        statusBox.textContent = 'Надіслано, дякуємо!';
        statusBox.className = 'form-status form-status--success';
        form.reset();
        return;
      }

      statusBox.textContent = result.error || 'Помилка відправки';
      statusBox.className = 'form-status form-status--error';
    } catch (error) {
      statusBox.textContent = 'API недоступний. Запусти бекенд.';
      statusBox.className = 'form-status form-status--error';
    }
  });
})();
