document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('news-form');
  if (!form) return;
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const query = document.getElementById('news-query').value;
    const resultDiv = document.getElementById('news-result');
    try {
      const response = await fetch(`/api/news?query=${encodeURIComponent(query)}`, {
        method: 'GET',
        headers: { 'Content-Type': 'application/json' }
      });
      const data = await response.json();
      if (response.ok) {
        resultDiv.innerHTML = data.articles.map(article => `
          <div class="news-card">
            <h3>${article.title}</h3>
            <p>${article.description}</p>
            <a href="${article.url}" target="_blank">Read More</a>
          </div>
        `).join('');
        if (window.gsap) {
          gsap.from('.news-card', { opacity: 0, y: 20, duration: 1, stagger: 0.2 });
        }
      } else {
        resultDiv.innerHTML = `<p class="error">Error: ${data.message}</p>`;
      }
    } catch (error) {
      resultDiv.innerHTML = `<p class="error">Failed to fetch news. Please try again.</p>`;
    }
  });
});