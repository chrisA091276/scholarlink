(() => {
  const form = document.getElementById('applicationWizard');
  if (!form) return;
  const assistance = document.getElementById('adfc-other_assistance');
  const detail = document.getElementById('adfc-other_assistance_details');
  const alternative = document.getElementById('indigencyUnavailable');
  function update() {
    detail.required = assistance.value === 'yes';
    detail.closest('[data-assistance-details]').hidden = assistance.value !== 'yes';
    document.querySelectorAll('[data-alternative-document]').forEach(card => { card.hidden = !alternative.checked; });
    const review = document.getElementById('adfcReview');
    review.replaceChildren();
    form.querySelectorAll('[data-adfc-section]').forEach(section => {
      const box = document.createElement('section'); box.className = 'adfc-review-box';
      const heading = document.createElement('h3');
      heading.textContent = section.previousElementSibling.querySelector('h3').textContent;
      const answers = document.createElement('dl'); answers.className = 'adfc-answer-grid';
      section.querySelectorAll('[data-adfc-label]').forEach(input => {
        if (input === detail && assistance.value !== 'yes') return;
        const row = document.createElement('div'), label = document.createElement('dt'), value = document.createElement('dd');
        label.textContent = input.dataset.adfcLabel;
        value.textContent = input.value ? (input.tagName === 'SELECT' ? input.selectedOptions[0].textContent : input.value) : 'Not provided';
        if (input.type === 'date' && input.value) value.textContent = input.value.split('-').reverse().join('/');
        value.dataset.empty = String(!input.value);
        row.append(label, value); answers.append(row);
      });
      box.append(heading, answers); review.append(box);
    });
  }
  form.addEventListener('input', update);
  form.addEventListener('change', update);
  update();
})();
