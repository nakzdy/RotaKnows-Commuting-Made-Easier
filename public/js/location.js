document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('location-form');
  if (!form) return;
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const location = document.getElementById('location-input').value;
    const resultDiv = document.getElementById('location-result');
    try {
      const response = await fetch(`/api/location?query=${encodeURIComponent(location)}`, {
        method: 'GET',
        headers: { 'Content-Type': 'application/json' }
      });
      const data = await response.json();
      if (response.ok) {
        resultDiv.innerHTML = `
          <h3>Location: ${data.name}</h3>
          <p>Latitude: ${data.latitude}</p>
          <p>Longitude: ${data.longitude}</p>
          <p>Address: ${data.address}</p>
        `;
        if (window.gsap) {
          gsap.from(resultDiv, { opacity: 0, y: 20, duration: 1 });
        }
      } else {
        resultDiv.innerHTML = `<p class="error">Error: ${data.message}</p>`;
      }
    } catch (error) {
      resultDiv.innerHTML = `<p class="error">Failed to fetch location data. Please try again.</p>`;
    }
  });
});