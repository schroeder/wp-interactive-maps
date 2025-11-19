# Design Document: WordPress Interactive Map Plugin

## Overview

The WordPress Interactive Map Plugin is a custom WordPress plugin that enables administrators to create interactive maps with clickable locations (points and areas). The plugin follows WordPress best practices and integrates seamlessly with the WordPress admin interface, custom post types, REST API, and Gutenberg block editor.

The plugin architecture consists of:
- Custom post types for Maps and Locations
- Admin meta boxes for coordinate input and visual map editing
- REST API endpoints for frontend data retrieval
- Frontend JavaScript component for interactive map rendering
- Shortcode and Gutenberg block for embedding maps

## Architecture

### Plugin Structure

```
wp-interactive-maps/
├── wp-interactive-maps.php          # Main plugin file
├── includes/
│   ├── class-post-types.php         # Register custom post types
│   ├── class-meta-boxes.php         # Admin meta boxes
│   ├── class-rest-api.php           # REST API endpoints
│   ├── class-shortcode.php          # Shortcode handler
│   ├── class-gutenberg-block.php    # Gutenberg block registration
│   └── class-settings.php           # Plugin settings page
├── admin/
│   ├── css/
│   │   └── admin-styles.css         # Admin interface styles
│   └── js/
│       └── map-editor.js            # Visual map coordinate editor
├── public/
│   ├── css/
│   │   └── map-display.css          # Frontend map styles
│   └── js/
│       └── map-display.js           # Frontend interactive map
└── blocks/
    └── map-block/
        ├── block.json               # Block metadata
        ├── edit.js                  # Block editor component
        └── save.js                  # Block save function
```

### Data Flow

1. **Admin Creation Flow:**
   - Administrator creates Map post with base image
   - Administrator creates Location posts, associates with Map
   - Visual editor allows clicking/drawing on map image to set coordinates
   - Meta data saved to post meta tables

2. **Frontend Display Flow:**
   - Page/post contains shortcode or Gutenberg block with map ID
   - Frontend JavaScript loads map data via REST API
   - SVG overlay renders locations on map image
   - Click events trigger content panel display

## Components and Interfaces

### 1. Custom Post Types

#### Map Post Type
```php
register_post_type('wim_map', [
    'labels' => [...],
    'public' => true,
    'show_in_rest' => true,
    'supports' => ['title', 'editor', 'thumbnail'],
    'menu_icon' => 'dashicons-location-alt'
]);
```

**Meta Fields:**
- `_wim_map_image_id` (int): Attachment ID of map image
- `_wim_map_width` (int): Original map image width
- `_wim_map_height` (int): Original map image height

#### Location Post Type
```php
register_post_type('wim_location', [
    'labels' => [...],
    'public' => true,
    'show_in_rest' => true,
    'supports' => ['title', 'editor', 'thumbnail'],
    'menu_icon' => 'dashicons-location'
]);
```

**Meta Fields:**
- `_wim_location_map_id` (int): Associated map post ID
- `_wim_location_type` (string): 'place' or 'area'
- `_wim_location_coordinates` (string): JSON-encoded coordinates
  - For place: `{"x": 250, "y": 150}`
  - For area: `{"points": [[200,150], [250,150], [250,200], [200,200]]}`
- `_wim_location_marker_color` (string): Hex color for marker/polygon
- `_wim_location_images` (string): JSON array of attachment IDs

### 2. Admin Meta Boxes

#### Map Meta Box
- Upload/select map base image
- Display image dimensions
- Preview map image

#### Location Meta Box
- Dropdown to select associated map
- Radio buttons for location type (Place/Area)
- Visual map editor:
  - Display associated map image
  - Click to set place coordinates
  - Click multiple points to draw area polygon
  - Display coordinate values
- Color picker for marker/polygon styling
- Image gallery selector for location images

### 3. REST API Endpoints

#### GET `/wp-json/wim/v1/maps/{id}`
Returns map data with all associated locations.

**Response:**
```json
{
  "id": 123,
  "title": "Campus Map",
  "description": "Interactive campus map",
  "image_url": "https://example.com/wp-content/uploads/map.jpg",
  "image_width": 1000,
  "image_height": 700,
  "locations": [
    {
      "id": 456,
      "title": "Main Building",
      "content": "<p>Description...</p>",
      "type": "area",
      "coordinates": {"points": [[200,150], [250,150], [250,200], [200,200]]},
      "color": "#ff6600",
      "images": [
        {"url": "https://...", "alt": "..."}
      ]
    }
  ]
}
```

#### GET `/wp-json/wim/v1/locations/{id}`
Returns single location data.

### 4. Frontend Map Display Component

**JavaScript Module Structure:**
```javascript
class InteractiveMap {
  constructor(containerId, mapId) {
    this.container = document.getElementById(containerId);
    this.mapId = mapId;
    this.mapData = null;
    this.svg = null;
    this.contentPanel = null;
  }

  async init() {
    await this.loadMapData();
    this.renderMap();
    this.renderLocations();
    this.setupEventListeners();
  }

  async loadMapData() {
    // Fetch from REST API
  }

  renderMap() {
    // Create img element and SVG overlay
  }

  renderLocations() {
    // Create SVG markers/polygons
  }

  showLocationContent(location) {
    // Display in side panel or popup
  }
}
```

**HTML Structure:**
```html
<div class="wim-container">
  <div class="wim-map-wrapper">
    <img src="map.jpg" class="wim-map-image">
    <svg class="wim-overlay" viewBox="0 0 1000 700">
      <!-- Markers and polygons rendered here -->
    </svg>
  </div>
  <div class="wim-content-panel">
    <!-- Location content displayed here -->
  </div>
</div>
```

### 5. Shortcode

**Usage:**
```
[interactive_map id="123" layout="side"]
```

**Attributes:**
- `id` (required): Map post ID
- `layout` (optional): 'side' (default) or 'popup'

**Implementation:**
```php
function wim_shortcode($atts) {
    $atts = shortcode_atts([
        'id' => 0,
        'layout' => 'side'
    ], $atts);
    
    // Enqueue scripts and styles
    // Return HTML container
}
```

### 6. Gutenberg Block

**Block Attributes:**
```json
{
  "mapId": {
    "type": "number",
    "default": 0
  },
  "layout": {
    "type": "string",
    "default": "side",
    "enum": ["side", "popup"]
  }
}
```

**Editor Component:**
- Map selector dropdown (ServerSideRender or custom)
- Layout toggle (side panel vs popup)
- Preview of selected map

## Data Models

### Map Entity
```
Map {
  id: int
  title: string
  description: string
  image_id: int
  image_url: string
  image_width: int
  image_height: int
  created_at: datetime
  updated_at: datetime
}
```

### Location Entity
```
Location {
  id: int
  map_id: int
  title: string
  content: string (HTML)
  type: enum('place', 'area')
  coordinates: object
  color: string (hex)
  images: array<Image>
  created_at: datetime
  updated_at: datetime
}
```

### Coordinate Formats

**Place Coordinates:**
```json
{
  "x": 250,
  "y": 150
}
```

**Area Coordinates:**
```json
{
  "points": [
    [200, 150],
    [250, 150],
    [250, 200],
    [200, 200]
  ]
}
```

Coordinates are stored as percentages or absolute pixels relative to the original map image dimensions, allowing responsive scaling.

## Error Handling

### Admin Interface
- Validate map image upload (file type, size)
- Validate coordinate values (numeric, within bounds)
- Display admin notices for validation errors
- Prevent location save without associated map

### REST API
- Return 404 for non-existent map/location IDs
- Return 400 for invalid parameters
- Use WordPress REST API error responses

### Frontend
- Display fallback message if map data fails to load
- Handle missing map images gracefully
- Validate coordinate data before rendering
- Console warnings for malformed data

### Security
- Nonce verification on all admin forms
- Capability checks (edit_posts, manage_options)
- Sanitize all user input:
  - `sanitize_text_field()` for text
  - `absint()` for IDs
  - `sanitize_hex_color()` for colors
  - JSON validation for coordinates
- Escape all output:
  - `esc_html()` for text
  - `esc_url()` for URLs
  - `wp_kses_post()` for HTML content

## Testing Strategy

### Unit Tests
- Test coordinate validation functions
- Test coordinate format conversions
- Test data sanitization functions
- Test REST API response formatting

### Integration Tests
- Test custom post type registration
- Test meta box save functionality
- Test REST API endpoints
- Test shortcode rendering
- Test Gutenberg block registration

### Manual Testing
- Create maps with various image sizes
- Create places and areas with different coordinate patterns
- Test visual map editor in admin
- Test frontend display on desktop and mobile
- Test with different WordPress themes
- Test with Gutenberg and Classic Editor

### Browser Testing
- Chrome, Firefox, Safari, Edge
- Mobile browsers (iOS Safari, Chrome Mobile)
- Test SVG rendering compatibility
- Test responsive layout breakpoints

## Plugin Settings

### Settings Page Location
WordPress Admin → Settings → Interactive Maps

### Configuration Options

1. **Default Marker Style**
   - Color picker for place markers
   - Size selector (small, medium, large)
   - Icon style (pin, circle, custom)

2. **Default Area Style**
   - Fill color picker
   - Fill opacity slider (0-100%)
   - Stroke color picker
   - Stroke width input

3. **Display Options**
   - Default layout (side panel or popup)
   - Content panel width (for side layout)
   - Animation enable/disable
   - Mobile breakpoint (px)

4. **Advanced**
   - Enable/disable REST API caching
   - Custom CSS textarea

Settings stored in WordPress options table as serialized array under key `wim_settings`.

## Performance Considerations

- Lazy load map images
- Cache REST API responses (transients)
- Minify and concatenate CSS/JS in production
- Use SVG for scalable graphics (no canvas)
- Debounce hover events
- Limit number of locations per map (recommend < 50)

## Accessibility

- Provide alt text for map images
- Keyboard navigation for locations (tab through)
- ARIA labels for interactive elements
- Focus indicators for keyboard users
- Screen reader announcements for content panel changes
- Semantic HTML structure

## Browser Compatibility

- Modern browsers (last 2 versions)
- IE11 support optional (requires polyfills)
- SVG support required
- JavaScript ES6+ (transpile if needed)

## Future Enhancements

- Search/filter locations
- Zoom and pan functionality
- Multiple map layers
- Import/export location data
- Analytics integration
- Custom marker icons upload
- Location categories/taxonomies
