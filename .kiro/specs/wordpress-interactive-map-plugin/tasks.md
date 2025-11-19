# Implementation Plan

- [x] 1. Set up plugin structure and core files
  - Create main plugin file with header comments and activation/deactivation hooks
  - Create directory structure (includes/, admin/, public/, blocks/)
  - Define plugin constants for paths and version
  - _Requirements: 6.1, 6.3_

- [x] 2. Implement custom post types
  - [x] 2.1 Create class-post-types.php with Map post type registration
    - Register 'wim_map' custom post type with REST API support
    - Configure post type labels, capabilities, and supports
    - _Requirements: 1.1, 1.2_

  - [x] 2.2 Add Location post type registration
    - Register 'wim_location' custom post type with REST API support
    - Configure post type labels and supports
    - _Requirements: 2.1_

  - [x] 2.3 Register custom post type meta fields for REST API
    - Register map meta fields (image_id, width, height) for REST exposure
    - Register location meta fields (map_id, type, coordinates, color, images) for REST exposure
    - _Requirements: 4.5_

- [x] 3. Create admin meta boxes for Maps
  - [x] 3.1 Implement Map meta box class
    - Create class-meta-boxes.php with meta box registration
    - Add meta box for map image upload using WordPress media library
    - Save and retrieve map image ID, width, and height
    - _Requirements: 1.3, 1.4_

  - [x] 3.2 Add meta box UI with nonce security
    - Implement nonce verification for form submissions
    - Add sanitization for all meta field inputs
    - Display map image preview in admin
    - _Requirements: 6.4, 6.5_

- [x] 4. Create admin meta boxes for Locations
  - [x] 4.1 Implement Location meta box with map selection
    - Add dropdown to select associated map (query all published maps)
    - Add radio buttons for location type (place/area)
    - Save map_id and location_type to post meta
    - _Requirements: 2.2, 2.4, 2.7_

  - [x] 4.2 Add coordinate input fields
    - Display X/Y input fields when type is "place"
    - Display polygon points textarea when type is "area"
    - Validate coordinate values are numeric
    - Save coordinates as JSON to post meta
    - _Requirements: 2.3, 2.5, 2.6_

  - [x] 4.3 Add color picker and image gallery fields
    - Integrate WordPress color picker for marker/polygon color
    - Add media library selector for location images
    - Save color and image IDs to post meta
    - _Requirements: 3.2, 3.3_

- [x] 5. Build visual map editor for admin
  - [x] 5.1 Create map-editor.js for coordinate selection
    - Load associated map image in meta box
    - Implement click handler to capture place coordinates
    - Display clicked coordinates in input fields
    - _Requirements: 2.8_

  - [x] 5.2 Add polygon drawing functionality
    - Implement multi-click polygon drawing on map image
    - Display polygon preview with SVG overlay
    - Store polygon points array in textarea
    - Add "Clear" and "Finish" buttons for polygon editing
    - _Requirements: 2.8_

  - [x] 5.3 Style admin map editor interface
    - Create admin-styles.css for meta box styling
    - Style map preview container and controls
    - Add responsive layout for admin editor
    - _Requirements: 2.8_

- [x] 6. Implement REST API endpoints
  - [x] 6.1 Create class-rest-api.php with map endpoint
    - Register GET /wp-json/wim/v1/maps/{id} endpoint
    - Query map post and all associated locations
    - Format response with map data and nested locations array
    - _Requirements: 4.4, 4.5_

  - [x] 6.2 Add location endpoint
    - Register GET /wp-json/wim/v1/locations/{id} endpoint
    - Query location post with all meta data
    - Format response with location data
    - _Requirements: 4.5_

  - [x] 6.3 Add error handling and validation
    - Return 404 for non-existent IDs
    - Return 400 for invalid parameters
    - Add permission checks for private posts
    - _Requirements: 4.5_

- [x] 7. Build frontend map display component
  - [x] 7.1 Create map-display.js with InteractiveMap class
    - Implement constructor and init method
    - Add loadMapData method to fetch from REST API
    - Create renderMap method to display image and SVG overlay
    - _Requirements: 4.3, 4.4, 5.4, 5.6_

  - [x] 7.2 Implement location rendering
    - Add renderLocations method to create SVG markers for places
    - Add polygon rendering for areas
    - Apply colors from location data
    - _Requirements: 5.4, 5.6_

  - [x] 7.3 Add interactivity and content display
    - Implement click handlers for markers and polygons
    - Create showLocationContent method to display info in panel
    - Add hover effects for areas
    - Implement close button for content panel
    - _Requirements: 5.1, 5.2, 5.3_

  - [x] 7.4 Create map-display.css for frontend styles
    - Style map container and SVG overlay
    - Style content panel (side layout)
    - Add hover and active states for locations
    - Implement responsive layout with mobile breakpoint
    - _Requirements: 5.7_

- [x] 8. Implement shortcode functionality
  - [x] 8.1 Create class-shortcode.php with shortcode handler
    - Register [interactive_map] shortcode
    - Parse id and layout attributes
    - Enqueue frontend CSS and JavaScript
    - Return HTML container with data attributes
    - _Requirements: 4.1, 4.3_

  - [x] 8.2 Add shortcode validation and error handling
    - Validate map ID exists
    - Display error message for invalid map ID
    - Handle missing attributes with defaults
    - _Requirements: 4.1_

- [x] 9. Create Gutenberg block
  - [x] 9.1 Set up block registration and files
    - Create blocks/map-block/ directory with block.json
    - Define block attributes (mapId, layout)
    - Register block in class-gutenberg-block.php
    - _Requirements: 4.2_

  - [x] 9.2 Build block editor component (edit.js)
    - Create map selector dropdown using useSelect
    - Add layout toggle control (side/popup)
    - Display map preview in editor
    - _Requirements: 4.2_

  - [x] 9.3 Implement block save function (save.js)
    - Save block attributes to block markup
    - Return container div with data attributes
    - Ensure frontend script initializes from block
    - _Requirements: 4.2, 4.3_

- [x] 10. Create plugin settings page
  - [x] 10.1 Implement class-settings.php with settings registration
    - Register settings page under Settings menu
    - Create settings sections for marker, area, and display options
    - Register settings fields with WordPress Settings API
    - _Requirements: 7.1_

  - [x] 10.2 Add settings fields and UI
    - Add color pickers for default marker and area colors
    - Add opacity slider for area fill
    - Add layout default selector
    - Add custom CSS textarea
    - _Requirements: 7.2, 7.3_

  - [x] 10.3 Implement settings save and retrieval
    - Save settings to options table
    - Add sanitization callbacks for all settings
    - Create helper function to get settings with defaults
    - Apply settings to frontend rendering
    - _Requirements: 7.4, 7.5_

- [x] 11. Add content validation and security
  - [x] 11.1 Implement input sanitization functions
    - Create sanitization helper for coordinates
    - Add validation for color values
    - Sanitize all text and HTML inputs
    - _Requirements: 6.5_

  - [x] 11.2 Add capability checks and nonces
    - Verify user capabilities on all admin actions
    - Add nonce verification to all forms
    - Implement permission checks in REST API
    - _Requirements: 6.4_

- [-] 12. Wire everything together and test
  - [x] 12.1 Initialize all plugin components in main file
    - Instantiate and initialize all classes
    - Hook activation/deactivation functions
    - Enqueue scripts and styles with proper dependencies
    - _Requirements: 6.3_

  - [ ] 12.2 Test complete workflow
    - Create test map with image
    - Create test locations (places and areas)
    - Embed map using shortcode
    - Embed map using Gutenberg block
    - Verify frontend interactivity
    - _Requirements: 1.5, 4.3, 5.1, 5.2, 5.3_

  - [ ]* 12.3 Write integration tests
    - Test post type registration
    - Test meta box save functionality
    - Test REST API endpoints
    - Test shortcode output
    - _Requirements: All_
