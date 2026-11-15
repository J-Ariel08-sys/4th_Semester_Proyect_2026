document.querySelectorAll('form[data-confirm]').forEach(form => {
  form.addEventListener('submit', event => {
    if (!window.confirm(form.dataset.confirm)) event.preventDefault();
  });
});
document.querySelectorAll('[data-print]').forEach(button => button.addEventListener('click', () => window.print()));
const rows = document.querySelector('#shift-rows');
function renumberShifts() {
  rows.querySelectorAll('.shift-row').forEach((row, index) => {
    row.querySelectorAll('[name]').forEach(input => { input.name = input.name.replace(/slots\[\d+\]/, `slots[${index}]`); });
  });
}
document.querySelector('#add-shift')?.addEventListener('click', () => {
  if (rows.children.length >= 100) return;
  const row = rows.firstElementChild.cloneNode(true);
  row.querySelectorAll('input,select').forEach(input => { input.value = ''; });
  rows.append(row);
  renumberShifts();
});
rows?.addEventListener('click', event => {
  if (event.target.matches('.remove-shift') && rows.children.length > 1) {
    event.target.closest('.shift-row').remove();
    renumberShifts();
  }
});

document.querySelectorAll('[data-rubric-toggle]').forEach(toggle => {
  toggle.addEventListener('change', () => {
    toggle.closest('.full').querySelectorAll('.rubric-fields input').forEach(input => {
      input.disabled = !toggle.checked;
      input.required = toggle.checked;
    });
  });
});
