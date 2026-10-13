document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.datatable').forEach(el => {
    if (window.DataTable) new DataTable(el, {pageLength: 25, order: [], language: {url: 'https://cdn.datatables.net/plug-ins/2.1.8/i18n/es-ES.json'}});
  });
  document.querySelectorAll('[data-confirm]').forEach(el => el.addEventListener('click', ev => {
    if (!confirm(el.dataset.confirm || '¿Confirma esta acción?')) ev.preventDefault();
  }));
  document.querySelectorAll('input[type=file][data-max-bytes]').forEach(input => input.addEventListener('change', () => {
    const file = input.files[0]; const max = Number(input.dataset.maxBytes);
    if (file && file.size > max) { alert('El archivo supera el tamaño máximo permitido.'); input.value=''; }
  }));
});