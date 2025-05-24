document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.usecase-card').forEach(card => {
    card.addEventListener('mouseenter', () => card.classList.add('hovered'));
    card.addEventListener('mouseleave', () => card.classList.remove('hovered'));
  });
});