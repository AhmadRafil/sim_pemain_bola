// Sidebar mobile
document.getElementById('toggleSidebar')?.addEventListener('click', () => document.getElementById('sidebar').classList.toggle('open'));
document.addEventListener('click', e => {
  const sb = document.getElementById('sidebar');
  if (sb && sb.classList.contains('open') && !sb.contains(e.target) && !e.target.closest('#toggleSidebar')) sb.classList.remove('open');
});
// Konfirmasi sebelum hapus
document.querySelectorAll('form[data-confirm]').forEach(f =>
  f.addEventListener('submit', e => { if (!confirm(f.dataset.confirm)) e.preventDefault(); }));
// Filter langsung terkirim saat dipilih
document.querySelectorAll('select[data-autosubmit]').forEach(s =>
  s.addEventListener('change', () => s.form.submit()));
