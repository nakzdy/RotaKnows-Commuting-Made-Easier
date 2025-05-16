document.addEventListener('DOMContentLoaded', function() {
  // Tab functionality
  const tabButtons = document.querySelectorAll('.tab-button');
  const tabContents = document.querySelectorAll('.tab-content');
  const routes = document.querySelectorAll('.route');
  
  // Initialize active route
  let activeRoute = null;
  
  // Tab click handler
  tabButtons.forEach(button => {
    button.addEventListener('click', () => {
      const tabId = button.getAttribute('data-tab');
      
      // Update tab buttons
      tabButtons.forEach(btn => btn.classList.remove('active'));
      button.classList.add('active');
      
      // Update tab contents
      tabContents.forEach(content => content.classList.remove('active'));
      document.getElementById(`tab-${tabId}`).classList.add('active');
      
      // Update active route on map
      setActiveRoute(tabId);
    });
  });
  
  // Route cards click handler
  const routeCards = document.querySelectorAll('.route-card');
  routeCards.forEach(card => {
    card.addEventListener('click', () => {
      // Remove active class from all cards
      routeCards.forEach(c => c.classList.remove('selected'));
      
      // Add active class to clicked card
      card.classList.add('selected');
      
      // Get the parent tab content to determine which route to highlight
      const parentTab = card.closest('.tab-content');
      const tabId = parentTab.id.replace('tab-', '');
      
      // Update active route on map
      setActiveRoute(tabId);
    });
  });
  
  // Function to set active route
  function setActiveRoute(routeId) {
    // Remove active class from all routes
    routes.forEach(route => route.classList.remove('active'));
    
    // Add active class to selected route
    const selectedRoute = document.getElementById(`route-${routeId}`);
    if (selectedRoute) {
      selectedRoute.classList.add('active');
      activeRoute = routeId;
    }
  }
  
  // View Route button click handler
  const viewRouteButton = document.querySelector('.view-route-button');
  viewRouteButton.addEventListener('click', () => {
    if (activeRoute) {
      alert(`Viewing detailed information for route to ${activeRoute.toUpperCase()}`);
      // In a real app, this would navigate to a detailed route view
    } else {
      alert('Please select a route first');
    }
  });
  
  // Map controls functionality
  const zoomInButton = document.querySelector('.map-controls button:nth-child(2)');
  const zoomOutButton = document.querySelector('.map-controls button:nth-child(3)');
  const navigateButton = document.querySelector('.map-controls button:nth-child(1)');
  
  let zoomLevel = 1;
  const map = document.querySelector('.map');
  
  zoomInButton.addEventListener('click', () => {
    if (zoomLevel < 1.5) {
      zoomLevel += 0.1;
      map.style.transform = `scale(${zoomLevel})`;
    }
  });
  
  zoomOutButton.addEventListener('click', () => {
    if (zoomLevel > 0.5) {
      zoomLevel -= 0.1;
      map.style.transform = `scale(${zoomLevel})`;
    }
  });
  
  navigateButton.addEventListener('click', () => {
    // Reset zoom and position
    zoomLevel = 1;
    map.style.transform = 'scale(1)';
    
    // In a real app, this would center the map on the user's location
    alert('Centering map on current location');
  });
  
  // Search functionality
  const searchInput = document.querySelector('.search-input');
  searchInput.addEventListener('keyup', (e) => {
    if (e.key === 'Enter') {
      const searchTerm = searchInput.value.toLowerCase();
      
      // Simple search implementation
      if (searchTerm.includes('sm')) {
        setActiveTab('sm');
      } else if (searchTerm.includes('gaisano')) {
        setActiveTab('gaisano');
      } else if (searchTerm.includes('centrio')) {
        setActiveTab('centrio');
      } else {
        alert(`Searching for: ${searchTerm}`);
      }
    }
  });
  
  function setActiveTab(tabId) {
    // Find and click the corresponding tab button
    const tabButton = document.querySelector(`.tab-button[data-tab="${tabId}"]`);
    if (tabButton) {
      tabButton.click();
    }
  }
  
  // Initialize with SM Mall tab active
  setActiveTab('sm');
});