# WordPress Interactive Map Plugin

## Overview

The WordPress Interactive Map Plugin is a custom WordPress plugin that enables administrators to create interactive maps with clickable locations (points and areas). The plugin follows WordPress best practices and integrates seamlessly with the WordPress admin interface, custom post types, REST API, and Gutenberg block editor.



## Installation

1. Copy the plugin folder to `wp-content/plugins/wp-interactive-maps/`
2. Navigate to WordPress Admin → Plugins
3. Activate "WP Interactive Maps"
4. Verify that activation completes without errors

## Usage 

### Create a Map with Image
1. Navigate to WordPress Admin → Interactive Maps → Add New
2. Enter a map title (e.g., "Campus Map")
3. Add a description in the editor
4. Click "Select Map Image" in the Map Details meta box
5. Upload or select an existing image (recommended: at least 800x600px)
6. Verify that:
   - Image preview appears
   - Image dimensions are displayed
   - "Remove Image" button appears
7. Click "Publish"
8. Verify the map is saved successfully


### Create Place-Type Locations
1. Navigate to WordPress Admin → Interactive Maps → Locations → Add New
2. Enter a location title (e.g., "Main Building")
3. Add description content in the editor
4. In Location Details meta box:
   - Select the map created in Test 1
   - Select "Place (Point)" as location type
   - Enter X coordinate (e.g., 250)
   - Enter Y coordinate (e.g., 150)
   - Select a marker color using the color picker
   - Click "Add Images" and select 1-2 images
5. Click "Publish"
6. Repeat for 2-3 different locations with different coordinates

### Create Area-Type Locations
1. Navigate to WordPress Admin → Interactive Maps → Locations → Add New
2. Enter a location title (e.g., "Parking Area")
3. Add description content
4. In Location Details meta box:
   - Select the map from Test 1
   - Select "Area (Polygon)" as location type
   - Enter polygon points in JSON format:
     ```json
     [[200,150],[250,150],[250,200],[200,200]]
     ```
   - Select a polygon color
   - Add images if desired
5. Click "Publish"
6. Create 1-2 more area locations

### Embed Map Using Shortcode
1. Create a new Page or Post
2. Add the shortcode with your map ID:
   ```
   [interactive_map id="123" layout="side"]
   ```
   (Replace 123 with your actual map ID)
3. Publish the page
4. View the page on the frontend
5. Verify that:
   - Map image displays correctly
   - Place markers appear at correct positions
   - Area polygons render correctly
   - Colors match the settings

### Embed Map Using Gutenberg Block
1. Create a new Page using the Block Editor
2. Add a new block and search for "Interactive Map"
3. Select the block
4. In block settings:
   - Select your map from the dropdown
   - Choose layout (side or popup)
5. Preview or publish the page
6. View on frontend

## Reporting Issues

If you encounter any issues during testing:

1. Note the exact steps to reproduce
2. Check browser console for errors
3. Verify WordPress and PHP versions
4. Check for plugin conflicts
5. Review server error logs
6. Document expected vs actual behavior

