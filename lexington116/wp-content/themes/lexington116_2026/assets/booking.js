jQuery(function ($) {
  const form = document.querySelector('.booking-form');
  if (!form) return;

  const date = form.querySelector('#booking-date');
  const dateDisplay = form.querySelector('#booking-date-display');
  const time = form.querySelector('#booking-time');
  const timeDisplay = form.querySelector('#booking-time-display');
  const duration = form.querySelector('#booking-duration');
  const slots = form.querySelector('.booking-times');
  const scheduleError = form.querySelector('.booking-schedule-error');
  const calendar = $('#booking-calendar');
  const localDate = (value) => new Date(...value.split('-').map((n, i) => Number(n) - (i === 1 ? 1 : 0)));

  calendar.datepicker({
    minDate: localDate(calendar.data('min')),
    maxDate: localDate(calendar.data('max')),
    dateFormat: 'yy-mm-dd',
    defaultDate: localDate(calendar.data('min')),
    numberOfMonths: 1,
    prevText: 'Previous month',
    nextText: 'Next month',
    onSelect(value) {
      date.value = value;
      dateDisplay.textContent = localDate(value).toLocaleDateString('en-US', {
        weekday: 'long', year: 'numeric', month: 'long', day: 'numeric'
      });
      if (time.value) scheduleError.hidden = true;
    }
  });
  calendar.datepicker('setDate', null);

  const allOptions = [];
  for (let minute = 570; minute <= 1260; minute += 30) {
    const hours = Math.floor(minute / 60);
    const minutes = minute % 60;
    const value = `${String(hours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
    const label = `${hours % 12 || 12}:${String(minutes).padStart(2, '0')} ${hours < 12 ? 'AM' : 'PM'}`;
    allOptions.push({ value, label, minute });
  }

  const syncTimes = () => slots.querySelectorAll('button').forEach((button) => {
    button.setAttribute('aria-pressed', String(button.dataset.value === time.value));
  });

  const renderTimes = () => {
    const previous = time.value;
    const available = allOptions.filter(({ minute }) => minute + Number(duration.value) <= 1290);
    slots.replaceChildren();

    available.forEach(({ value, label }) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'booking-time';
      button.textContent = label;
      button.dataset.value = value;
      button.setAttribute('aria-pressed', 'false');
      button.addEventListener('click', () => {
        time.value = value;
        timeDisplay.textContent = label;
        syncTimes();
        if (date.value) scheduleError.hidden = true;
      });
      slots.append(button);
    });

    if (available.some(({ value }) => value === previous)) {
      time.value = previous;
      timeDisplay.textContent = available.find(({ value }) => value === previous).label;
    } else {
      time.value = '';
      timeDisplay.textContent = 'Choose a time above';
    }
    syncTimes();
  };

  duration.addEventListener('change', renderTimes);
  renderTimes();

  form.addEventListener('submit', (event) => {
    const error = form.querySelector('.booking-details .booking-error');
    if (!date.value || !time.value) {
      event.preventDefault();
      scheduleError.hidden = false;
      scheduleError.textContent = !date.value && !time.value
        ? 'Please choose a date and time.'
        : `Please choose ${!date.value ? 'a date' : 'a time'}.`;
      scheduleError.focus();
      return;
    }
    scheduleError.hidden = true;

    const digits = form.querySelector('#booking-phone').value.replace(/\D/g, '');
    if (digits.length < 10 || digits.length > 15) {
      event.preventDefault();
      error.hidden = false;
      error.textContent = 'Please enter a valid phone number, including the area code.';
      return;
    }
    error.hidden = true;
    const submit = form.querySelector('.booking-submit');
    submit.disabled = true;
    submit.textContent = 'Sending request…';
  });

  window.addEventListener('pageshow', () => {
    const submit = form.querySelector('.booking-submit');
    submit.disabled = false;
    submit.textContent = 'Request appointment';
  });
});
