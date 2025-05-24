// Handles validation for signup and login forms

document.addEventListener('DOMContentLoaded', () => {
  // Signup form validation
  const signupForm = document.querySelector('.signup-form');
  if (signupForm) {
    signupForm.addEventListener('submit', e => {
      const email = signupForm.querySelector('input[type="email"]').value.trim();
      const password = signupForm.querySelector('input[type="password"]').value;
      if (!email.includes('@') || password.length < 6) {
        e.preventDefault();
        alert('Please enter a valid email and a password with at least 6 characters.');
      }
    });
  }

  // Login form validation
  const loginForm = document.querySelector('.login-form');
  if (loginForm) {
    loginForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const email = loginForm.email.value.trim();
      const password = loginForm.password.value;

      try {
        const response = await fetch('http://localhost:8000/api/login', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
          body: JSON.stringify({ email, password })
        });
        const data = await response.json();
        if (response.ok) {
          alert('Login successful!');
          // Optionally: Save token, redirect, etc.
        } else {
          alert(data.message || 'Login failed.');
        }
      } catch (error) {
        alert('Error connecting to server.');
      }
    });
  }
});