document.getElementById('weather-form').addEventListener('submit', async (e) => {
  e.preventDefault();
  const location = document.getElementById('location-input').value;
  const resultDiv = document.getElementById('weather-result');
  
  try {
    const response = await fetch(`http://localhost:8000/api/weather?location=${encodeURIComponent(location)}`, {
      method: 'GET',
      headers: { 'Content-Type': 'application/json' }
    });
    const data = await response.json();
    
    if (response.ok) {
      resultDiv.innerHTML = `
        <h3>Weather in ${data.location}</h3>
        <p>Temperature: ${data.temperature}°C</p>
        <p>Condition: ${data.condition}</p>
        <p>Humidity: ${data.humidity}%</p>
        <p>Wind: ${data.wind_speed} km/h</p>
      `;
      gsap.from(resultDiv, { opacity: 0, y: 20, duration: 1 });
    } else {
      resultDiv.innerHTML = `<p class="error">Error: ${data.message}</p>`;
    }
  } catch (error) {
    resultDiv.innerHTML = `<p class="error">Failed to fetch weather data. Please try again.</p>`;
  }
});