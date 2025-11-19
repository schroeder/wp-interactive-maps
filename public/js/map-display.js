/**
 * Interactive Map Display Component
 * Handles frontend rendering and interaction for WordPress Interactive Maps
 */

class InteractiveMap {
  /**
   * Constructor
   * @param {string} containerId - ID of the container element
   * @param {number} mapId - WordPress post ID of the map
   * @param {object} options - Configuration options
   */
  constructor(containerId, mapId, options = {}) {
    this.container = document.getElementById(containerId);
    if (!this.container) {
      console.error(`Container element with ID "${containerId}" not found`);
      return;
    }

    this.mapId = mapId;
    this.mapData = null;
    this.svg = null;
    this.contentPanel = null;
    this.activeLocation = null;
    
    // Configuration options
    this.options = {
      layout: options.layout || 'side', // 'side' or 'popup'
      apiUrl: options.apiUrl || '/wp-json/wim/v1',
      ...options
    };
  }

  /**
   * Initialize the interactive map
   */
  async init() {
    try {
      await this.loadMapData();
      this.renderMap();
      this.renderLocations();
      this.setupEventListeners();
    } catch (error) {
      console.error('Failed to initialize interactive map:', error);
      this.showError('Failed to load map. Please try again later.');
    }
  }

  /**
   * Load map data from REST API
   */
  async loadMapData() {
    const url = `${this.options.apiUrl}/maps/${this.mapId}`;
    
    try {
      const response = await fetch(url);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }
      
      this.mapData = await response.json();
      
      // Validate map data
      if (!this.mapData || !this.mapData.image_url) {
        throw new Error('Invalid map data received');
      }
      
      return this.mapData;
    } catch (error) {
      console.error('Error loading map data:', error);
      throw error;
    }
  }

  /**
   * Render the map image and SVG overlay
   */
  renderMap() {
    if (!this.mapData) {
      console.error('No map data available');
      return;
    }

    // Create map wrapper
    const mapWrapper = document.createElement('div');
    mapWrapper.className = 'wim-map-wrapper';

    // Create map image
    const mapImage = document.createElement('img');
    mapImage.src = this.mapData.image_url;
    mapImage.alt = this.mapData.title || 'Interactive Map';
    mapImage.className = 'wim-map-image';
    
    // Wait for image to load to get dimensions
    mapImage.onload = () => {
      this.setupSVGOverlay(mapWrapper, mapImage);
    };

    mapWrapper.appendChild(mapImage);

    // Create content panel
    this.contentPanel = document.createElement('div');
    this.contentPanel.className = `wim-content-panel wim-layout-${this.options.layout}`;
    this.contentPanel.style.display = 'none';

    // Clear container and add elements
    this.container.innerHTML = '';
    this.container.className = `wim-container wim-layout-${this.options.layout}`;
    this.container.appendChild(mapWrapper);
    this.container.appendChild(this.contentPanel);
  }

  /**
   * Setup SVG overlay on the map
   */
  setupSVGOverlay(mapWrapper, mapImage) {
    // Create SVG overlay
    this.svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    this.svg.setAttribute('class', 'wim-overlay');
    this.svg.setAttribute('viewBox', `0 0 ${this.mapData.image_width} ${this.mapData.image_height}`);
    this.svg.setAttribute('preserveAspectRatio', 'xMidYMid meet');
    
    mapWrapper.appendChild(this.svg);
    
    // Render locations after SVG is ready
    this.renderLocations();
  }

  /**
   * Render all locations on the map
   */
  renderLocations() {
    if (!this.svg || !this.mapData || !this.mapData.locations) {
      return;
    }

    // Clear existing locations
    this.svg.innerHTML = '';

    // Render each location
    this.mapData.locations.forEach(location => {
      if (location.type === 'place') {
        this.renderPlaceMarker(location);
      } else if (location.type === 'area') {
        this.renderAreaPolygon(location);
      }
    });
  }

  /**
   * Render a place marker
   */
  renderPlaceMarker(location) {
    if (!location.coordinates || typeof location.coordinates.x === 'undefined' || typeof location.coordinates.y === 'undefined') {
      console.warn('Invalid place coordinates for location:', location);
      return;
    }

    const defaultColor = window.wimData?.settings?.markerColor || '#ff6600';
    const color = location.color || defaultColor;
    const x = location.coordinates.x;
    const y = location.coordinates.y;

    // Create marker group
    const group = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    group.setAttribute('class', 'wim-marker');
    group.setAttribute('data-location-id', location.id);
    group.style.cursor = 'pointer';

    // Create marker circle
    const circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    circle.setAttribute('cx', x);
    circle.setAttribute('cy', y);
    circle.setAttribute('r', '8');
    circle.setAttribute('fill', color);
    circle.setAttribute('stroke', '#ffffff');
    circle.setAttribute('stroke-width', '2');
    circle.setAttribute('class', 'wim-marker-circle');

    // Create outer circle for hover effect
    const outerCircle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
    outerCircle.setAttribute('cx', x);
    outerCircle.setAttribute('cy', y);
    outerCircle.setAttribute('r', '12');
    outerCircle.setAttribute('fill', 'transparent');
    outerCircle.setAttribute('class', 'wim-marker-hover');

    group.appendChild(outerCircle);
    group.appendChild(circle);
    
    // Store location data
    group.locationData = location;

    this.svg.appendChild(group);
  }

  /**
   * Render an area polygon
   */
  renderAreaPolygon(location) {
    if (!location.coordinates || !location.coordinates.points || !Array.isArray(location.coordinates.points)) {
      console.warn('Invalid area coordinates for location:', location);
      return;
    }

    const defaultFillColor = window.wimData?.settings?.areaFillColor || '#3388ff';
    const defaultStrokeColor = window.wimData?.settings?.areaStrokeColor || '#0055cc';
    const defaultOpacity = window.wimData?.settings?.areaFillOpacity || 0.3;
    
    const fillColor = location.color || defaultFillColor;
    const strokeColor = defaultStrokeColor;
    const points = location.coordinates.points;

    // Convert points array to SVG polygon points string
    const pointsString = points.map(point => `${point[0]},${point[1]}`).join(' ');

    // Create polygon group
    const group = document.createElementNS('http://www.w3.org/2000/svg', 'g');
    group.setAttribute('class', 'wim-area');
    group.setAttribute('data-location-id', location.id);
    group.style.cursor = 'pointer';

    // Create polygon
    const polygon = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
    polygon.setAttribute('points', pointsString);
    polygon.setAttribute('fill', fillColor);
    polygon.setAttribute('fill-opacity', defaultOpacity);
    polygon.setAttribute('stroke', strokeColor);
    polygon.setAttribute('stroke-width', '2');
    polygon.setAttribute('class', 'wim-area-polygon');

    group.appendChild(polygon);
    
    // Store location data
    group.locationData = location;

    this.svg.appendChild(group);
  }

  /**
   * Setup event listeners
   */
  setupEventListeners() {
    if (!this.svg) {
      return;
    }

    // Add click handlers to all markers and areas
    const markers = this.svg.querySelectorAll('.wim-marker');
    markers.forEach(marker => {
      marker.addEventListener('click', (e) => {
        e.stopPropagation();
        this.showLocationContent(marker.locationData);
      });
    });

    const areas = this.svg.querySelectorAll('.wim-area');
    areas.forEach(area => {
      // Click handler
      area.addEventListener('click', (e) => {
        e.stopPropagation();
        this.showLocationContent(area.locationData);
      });

      // Hover effect
      area.addEventListener('mouseenter', () => {
        const polygon = area.querySelector('.wim-area-polygon');
        if (polygon) {
          const currentOpacity = parseFloat(polygon.getAttribute('fill-opacity')) || 0.3;
          polygon.setAttribute('fill-opacity', Math.min(currentOpacity + 0.2, 1));
        }
      });

      area.addEventListener('mouseleave', () => {
        const polygon = area.querySelector('.wim-area-polygon');
        if (polygon) {
          const defaultOpacity = window.wimData?.settings?.areaFillOpacity || 0.3;
          polygon.setAttribute('fill-opacity', defaultOpacity);
        }
      });
    });
  }

  /**
   * Show location content in panel
   */
  showLocationContent(location) {
    if (!location || !this.contentPanel) {
      return;
    }

    this.activeLocation = location;

    // Build content HTML
    let contentHTML = `
      <div class="wim-content-header">
        <h3 class="wim-content-title">${this.escapeHtml(location.title)}</h3>
        <button class="wim-close-button" aria-label="Close">&times;</button>
      </div>
      <div class="wim-content-body">
    `;

    // Add description/content
    if (location.content) {
      contentHTML += `<div class="wim-content-description">${location.content}</div>`;
    }

    // Add images if available
    if (location.images && location.images.length > 0) {
      contentHTML += '<div class="wim-content-images">';
      location.images.forEach(image => {
        contentHTML += `
          <img src="${this.escapeHtml(image.url)}" 
               alt="${this.escapeHtml(image.alt || location.title)}" 
               class="wim-content-image">
        `;
      });
      contentHTML += '</div>';
    }

    contentHTML += '</div>';

    this.contentPanel.innerHTML = contentHTML;
    this.contentPanel.style.display = 'block';

    // Add close button handler
    const closeButton = this.contentPanel.querySelector('.wim-close-button');
    if (closeButton) {
      closeButton.addEventListener('click', () => {
        this.closeLocationContent();
      });
    }

    // Highlight active location
    this.highlightActiveLocation(location.id);
  }

  /**
   * Close location content panel
   */
  closeLocationContent() {
    if (this.contentPanel) {
      this.contentPanel.style.display = 'none';
      this.contentPanel.innerHTML = '';
    }

    this.activeLocation = null;
    this.removeLocationHighlight();
  }

  /**
   * Highlight the active location
   */
  highlightActiveLocation(locationId) {
    // Remove previous highlights
    this.removeLocationHighlight();

    // Add highlight to active location
    const locationElement = this.svg.querySelector(`[data-location-id="${locationId}"]`);
    if (locationElement) {
      locationElement.classList.add('wim-active');
    }
  }

  /**
   * Remove location highlight
   */
  removeLocationHighlight() {
    if (!this.svg) {
      return;
    }

    const activeElements = this.svg.querySelectorAll('.wim-active');
    activeElements.forEach(element => {
      element.classList.remove('wim-active');
    });
  }

  /**
   * Escape HTML to prevent XSS
   */
  escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  /**
   * Show error message
   */
  showError(message) {
    this.container.innerHTML = `
      <div class="wim-error">
        <p>${message}</p>
      </div>
    `;
  }
}

// Export for use in WordPress
if (typeof window !== 'undefined') {
  window.InteractiveMap = InteractiveMap;
}

/**
 * Auto-initialize maps from Gutenberg blocks and shortcodes
 */
document.addEventListener('DOMContentLoaded', function() {
  // Apply custom CSS if provided
  if (window.wimData?.settings?.customCss) {
    const style = document.createElement('style');
    style.textContent = window.wimData.settings.customCss;
    document.head.appendChild(style);
  }
  
  // Find all map containers with data attributes
  const mapContainers = document.querySelectorAll('.wim-map-container[data-map-id]');
  
  mapContainers.forEach((container, index) => {
    const mapId = parseInt(container.getAttribute('data-map-id'));
    const defaultLayout = window.wimData?.settings?.defaultLayout || 'side';
    const layout = container.getAttribute('data-layout') || defaultLayout;
    
    // Generate unique ID if not present
    if (!container.id) {
      container.id = `wim-map-${mapId}-${index}`;
    }
    
    // Initialize the map
    if (mapId > 0) {
      const map = new InteractiveMap(container.id, mapId, {
        layout: layout,
        apiUrl: window.wimData?.restUrl || '/wp-json/wim/v1'
      });
      map.init();
    }
  });
});
