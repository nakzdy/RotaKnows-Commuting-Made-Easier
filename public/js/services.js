document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.service-card').forEach(card => {
    card.addEventListener('mouseenter', () => card.classList.add('hovered'));
    card.addEventListener('mouseleave', () => card.classList.remove('hovered'));
    card.querySelector('.cta-button')?.addEventListener('click', e => {
      // Optionally show more info in a modal
    });
  });
});

document.getElementById('location-form').addEventListener('submit', async function(e) {
  e.preventDefault();
  const location = document.getElementById('location-input').value;
  document.getElementById('api-results').style.display = 'block';

  // Show loading states
  document.getElementById('weather-result').innerText = 'Loading weather...';
  document.getElementById('news-result').innerText = 'Loading news...';
  document.getElementById('traveltime-result').innerText = 'Loading travel time...';
  document.getElementById('foursquare-result').innerText = 'Loading places...';

  // Weather (calls your Laravel backend)
  try {
    const weatherRes = await fetch(`/api/weather?location=${encodeURIComponent(location)}`);
    const weatherData = await weatherRes.json();
    document.getElementById('weather-result').innerText = `Weather: ${weatherData.summary || 'No data'}`;
  } catch {
    document.getElementById('weather-result').innerText = 'Weather data unavailable.';
  }

  // News (calls your Laravel backend)
  try {
    const newsRes = await fetch(`/api/news?location=${encodeURIComponent(location)}`);
    const newsData = await newsRes.json();
    document.getElementById('news-result').innerText = `Top News: ${newsData.articles?.[0]?.title || 'No news found.'}`;
  } catch {
    document.getElementById('news-result').innerText = 'News data unavailable.';
  }

  // Travel Time (calls your Laravel backend)
  try {
    const travelRes = await fetch(`/api/traveltime?location=${encodeURIComponent(location)}`);
    const travelData = await travelRes.json();
    document.getElementById('traveltime-result').innerText = `Travel Time: ${travelData.time || 'No data'}`;
  } catch {
    document.getElementById('traveltime-result').innerText = 'Travel time data unavailable.';
  }

  // Foursquare (calls your Laravel backend)
  try {
    const fsqRes = await fetch(`/api/foursquare?location=${encodeURIComponent(location)}`);
    const fsqData = await fsqRes.json();
    document.getElementById('foursquare-result').innerText = `Nearby: ${fsqData.places?.[0]?.name || 'No places found.'}`;
  } catch {
    document.getElementById('foursquare-result').innerText = 'Nearby places data unavailable.';
  }
});

document.addEventListener('DOMContentLoaded', function () {
  const form = document.getElementById('location-form');
  const input = document.getElementById('location-input');
  const resultsDiv = document.getElementById('api-results');
  const weatherDiv = document.getElementById('weather-result');
  const newsDiv = document.getElementById('news-result');
  const travelDiv = document.getElementById('traveltime-result');
  const placesDiv = document.getElementById('foursquare-result');

  form.addEventListener('submit', async function (e) {
    e.preventDefault();
    const address = input.value.trim();
    if (!address) return;

    resultsDiv.style.display = 'block';
    weatherDiv.innerText = 'Loading weather...';
    newsDiv.innerText = 'Loading news...';
    travelDiv.innerText = 'Travel time feature coming soon.'; // No backend yet
    placesDiv.innerText = 'Loading places...';

    // 1. Geocode the address to get lat/lon
    let lat = '14.5995', lon = '120.9842'; // fallback: Manila
    try {
      const geoRes = await fetch(`/api/geocode?address=${encodeURIComponent(address)}`);
      const geoData = await geoRes.json();
      if (geoData && geoData.lat && geoData.lon) {
        lat = geoData.lat;
        lon = geoData.lon;
      }
    } catch (err) {
      // fallback to Manila
    }

    // 2. Weather (use lat/lon)
    try {
      const weatherRes = await fetch(`/api/weather?lat=${lat}&lon=${lon}`);
      const weatherData = await weatherRes.json();
      weatherDiv.innerText = `Weather: ${weatherData.summary || weatherData.weather?.[0]?.description || 'No data'}`;
    } catch {
      weatherDiv.innerText = 'Weather data unavailable.';
    }

    // 3. News (use /api/gnews)
    try {
      const newsRes = await fetch(`/api/gnews/${encodeURIComponent(address)}`);
      const newsData = await newsRes.json();
      if (newsData.articles && newsData.articles.length) {
        newsDiv.innerText = `Top News: ${newsData.articles[0].title}`;
      } else {
        newsDiv.innerText = 'No news found.';
      }
    } catch {
      newsDiv.innerText = 'News data unavailable.';
    }

    // 4. Foursquare Places (use /api/places)
    try {
      const placesRes = await fetch(`/api/places?query=mall&lat=${lat}&lon=${lon}`);
      const placesData = await placesRes.json();
      let html = '<h3>Nearby Places</h3>';
      if (placesData.results && placesData.results.length) {
        html += '<ul>';
        for (const place of placesData.results) {
          html += `<li>${place.name || 'Unknown Place'}</li>`;
        }
        html += '</ul>';
      } else {
        html += '<p>No places found.</p>';
      }
      placesDiv.innerHTML = html;
    } catch {
      placesDiv.innerHTML = '<p>Error fetching places.</p>';
    }
  });
});