document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.team-member').forEach(member => {
    member.addEventListener('mouseenter', () => member.classList.add('hovered'));
    member.addEventListener('mouseleave', () => member.classList.remove('hovered'));
    member.addEventListener('click', () => member.classList.toggle('expanded'));
  });
});