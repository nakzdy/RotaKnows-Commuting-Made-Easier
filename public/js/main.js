document.addEventListener('DOMContentLoaded', () => {
  // GSAP Animations
  gsap.registerPlugin(ScrollTrigger);

  // Hero Section Animations
  gsap.from('.hero-text', {
    opacity: 0,
    y: 50,
    duration: 1,
    ease: 'power2.out'
  });

  gsap.from('.hero-image img', {
    opacity: 0,
    x: 50,
    duration: 1,
    delay: 0.5,
    ease: 'power2.out'
  });

  // Services Section Fade-In
  gsap.utils.toArray('.service-card').forEach(card => {
    gsap.from(card, {
      opacity: 0,
      y: 50,
      duration: 0.8,
      scrollTrigger: {
        trigger: card,
        start: 'top 80%',
        toggleActions: 'play none none none'
      }
    });
  });

  // Team Section Fade-In
  gsap.utils.toArray('.team-member').forEach(member => {
    gsap.from(member, {
      opacity: 0,
      y: 50,
      duration: 0.8,
      scrollTrigger: {
        trigger: member,
        start: 'top 80%',
        toggleActions: 'play none none none'
      }
    });
  });

  // Contact Section Fade-In
  gsap.from('.contact-form', {
    opacity: 0,
    y: 50,
    duration: 1,
    scrollTrigger: {
      trigger: '.contact',
      start: 'top 80%',
      toggleActions: 'play none none none'
    }
  });

  // Accordion Functionality
  const accordionHeaders = document.querySelectorAll('.accordion-header');
  accordionHeaders.forEach(header => {
    header.addEventListener('click', () => {
      const content = header.nextElementSibling;
      const isActive = content.classList.contains('active');

      // Close all accordion items
      document.querySelectorAll('.accordion-content').forEach(item => {
        item.classList.remove('active');
      });

      // Toggle current item
      if (!isActive) {
        content.classList.add('active');
      }
    });
  });

  // Button Hover Effects
  const buttons = document.querySelectorAll('.cta-button, .submit-button');
  buttons.forEach(button => {
    button.addEventListener('mouseenter', () => {
      gsap.to(button, {
        scale: 1.05,
        boxShadow: '0 0 15px rgba(255, 111, 97, 0.7)',
        duration: 0.3
      });
    });
    button.addEventListener('mouseleave', () => {
      gsap.to(button, {
        scale: 1,
        boxShadow: 'none',
        duration: 0.3
      });
    });
  });

  // Card Hover Effects
  const cards = document.querySelectorAll('.service-card');
  cards.forEach(card => {
    card.addEventListener('mouseenter', () => {
      gsap.to(card, {
        scale: 1.05,
        rotateX: 10,
        rotateY: 10,
        duration: 0.3
      });
    });
    card.addEventListener('mouseleave', () => {
      gsap.to(card, {
        scale: 1,
        rotateX: 0,
        rotateY: 0,
        duration: 0.3
      });
    });
  });
});
