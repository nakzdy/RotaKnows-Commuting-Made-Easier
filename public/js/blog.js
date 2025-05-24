document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.blog-card').forEach(card => {
    card.addEventListener('mouseenter', () => card.classList.add('highlight'));
    card.addEventListener('mouseleave', () => card.classList.remove('highlight'));
    card.querySelector('.cta-button')?.addEventListener('click', e => {
      e.preventDefault();
      card.classList.toggle('expanded');
    });
  });
});