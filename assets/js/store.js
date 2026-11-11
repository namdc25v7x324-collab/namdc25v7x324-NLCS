(() => {
  const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if ('IntersectionObserver' in window && !reduce) {
    const observer = new IntersectionObserver(entries => entries.forEach(entry => {
      if (entry.isIntersecting) { entry.target.classList.add('is-visible'); observer.unobserve(entry.target); }
    }), { threshold: .08 });
    document.querySelectorAll('.reveal').forEach(el => { el.classList.add('reveal-ready'); observer.observe(el); });
  }
  document.querySelectorAll('input[type="file"][data-preview]').forEach(input => input.addEventListener('change', () => {
    const target = document.getElementById(input.dataset.preview);
    if (target && input.files[0]) { if(target.dataset.blob) URL.revokeObjectURL(target.dataset.blob); const url = URL.createObjectURL(input.files[0]); target.src = url;target.dataset.blob=url;target.hidden=false; }
  }));
  document.querySelectorAll('[data-confirm]').forEach(form => form.addEventListener('submit', e => { if (!confirm(form.dataset.confirm)) e.preventDefault(); }));
})();
