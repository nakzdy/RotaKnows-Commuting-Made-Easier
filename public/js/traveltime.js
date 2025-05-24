document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('traveltime-form');
  if (!form) return;
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const start = document.getElementById('start-location').value;
    const end = document.getElementById('end-location').value;
    const resultDiv = document.getElementById('traveltime-result');
    try {
      const response = await fetch(`/api/traveltime?start=${encodeURIComponent(start)}&end=${encodeURIComponent(end)}`, {
        method: 'GET',
        headers: { 'Content-Type': 'application/json' }
      });
      const data = await response.json();
      if (response.ok) {
        resultDiv.innerHTML = `
          <h3>Travel Time</h3>
          <p>From: ${data.start}</p>
          <p>To: ${data.end}</p>
          <p>Duration: ${data.duration} minutes</p>
          <p>Distance: ${data.distance} km</p>
        `;
        if (window.gsap) {
          gsap.from(resultDiv, { opacity: 0, y: 20, duration: 1 });
        }
      } else {
        resultDiv.innerHTML = `<p class="error">Error: ${data.message}</p>`;
      }
    } catch (error) {
      resultDiv.innerHTML = `<p class="error">Failed to calculate travel time. Please try again.</p>`;
    }
  });
});