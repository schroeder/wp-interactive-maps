# WP Interactive Maps - Testing Guide

This guide provides step-by-step instructions for testing the complete workflow of the WP Interactive Maps plugin.

## Prerequisites

- WordPress 5.8 or higher installed
- PHP 7.4 or higher
- Admin access to WordPress installation

## Installation

1. Copy the plugin folder to `wp-content/plugins/wp-interactive-maps/`
2. Navigate to WordPress Admin → Plugins
3. Activate "WP Interactive Maps"
4. Verify that activation completes without errors

## Test 1: Create a Map with Image

### Steps:
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

### Expected Results:
- Map post is created with status "Published"
- Map image is stored and displayed in meta box
- Image dimensions are saved correctly

## Test 2: Create Place-Type Locations

### Steps:
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

### Expected Results:
- Location posts are created successfully
- Coordinates are saved correctly
- Color and images are associated with the location
- Visual map editor displays the map image (if implemented)

## Test 3: Create Area-Type Locations

### Steps:
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

### Expected Results:
- Area locations are created successfully
- Polygon coordinates are saved as valid JSON
- Color is applied correctly

## Test 4: Embed Map Using Shortcode

### Steps:
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

### Expected Results:
- Map renders on the frontend
- All locations are visible
- No JavaScript errors in browser console

## Test 5: Embed Map Using Gutenberg Block

### Steps:
1. Create a new Page using the Block Editor
2. Add a new block and search for "Interactive Map"
3. Select the block
4. In block settings:
   - Select your map from the dropdown
   - Choose layout (side or popup)
5. Preview or publish the page
6. View on frontend

### Expected Results:
- Block appears in editor with map preview
- Block settings work correctly
- Frontend display matches shortcode implementation

## Test 6: Verify Frontend Interactivity

### Steps:
1. Navigate to a page with an embedded map (from Test 4 or 5)
2. Test place markers:
   - Click on a place marker
   - Verify content panel opens with location details
   - Verify title, description, and images display
   - Click close button to dismiss
3. Test area polygons:
   - Hover over an area polygon
   - Verify hover effect (highlight)
   - Click on the area
   - Verify content panel opens with area details
4. Test multiple locations:
   - Click different locations
   - Verify content updates correctly
5. Test responsive behavior:
   - Resize browser window
   - Verify layout adapts for mobile viewport
   - Test on actual mobile device if possible

### Expected Results:
- All interactive features work smoothly
- Content displays correctly
- No JavaScript errors
- Responsive layout works properly
- Images load correctly in content panel

## Test 7: REST API Endpoints

### Steps:
1. Get your map ID from the WordPress admin
2. Test the map endpoint in browser or API client:
   ```
   GET /wp-json/wim/v1/maps/{map_id}
   ```
3. Verify response includes:
   - Map title and description
   - Image URL and dimensions
   - Array of all associated locations
   - Each location has: id, title, content, type, coordinates, color, images
4. Test a location endpoint:
   ```
   GET /wp-json/wim/v1/locations/{location_id}
   ```
5. Verify response includes all location data

### Expected Results:
- Endpoints return valid JSON
- All data is properly formatted
- No 404 or 500 errors
- Published maps are publicly accessible
- Draft maps require authentication

## Test 8: Plugin Settings

### Steps:
1. Navigate to WordPress Admin → Settings → Interactive Maps
2. Modify default settings:
   - Change default marker color
   - Change default area fill color
   - Change default area stroke color
   - Adjust area fill opacity
   - Select default layout
   - Add custom CSS (optional)
3. Save settings
4. Create a new location without specifying colors
5. Verify default colors are applied
6. View a map on frontend
7. Verify custom CSS is applied (if added)

### Expected Results:
- Settings save successfully
- Default values are applied to new locations
- Custom CSS appears on frontend
- Settings persist across page loads

## Test 9: Security and Validation

### Steps:
1. Try to create a location without selecting a map
2. Try to enter invalid coordinates (non-numeric values)
3. Try to enter invalid polygon JSON
4. Try to access a draft map via REST API (logged out)
5. Verify nonce validation on form submissions

### Expected Results:
- Invalid data is rejected with appropriate error messages
- Security checks prevent unauthorized access
- Input sanitization prevents XSS attacks
- Nonce verification works correctly

## Test 10: Browser Compatibility

### Steps:
1. Test the plugin in multiple browsers:
   - Chrome (latest)
   - Firefox (latest)
   - Safari (latest)
   - Edge (latest)
2. Verify all features work in each browser
3. Check for console errors
4. Test on mobile browsers (iOS Safari, Chrome Mobile)

### Expected Results:
- Plugin works consistently across all browsers
- SVG rendering is correct
- No browser-specific errors
- Mobile experience is functional

## Common Issues and Troubleshooting

### Map doesn't display on frontend
- Check that map ID is correct in shortcode/block
- Verify map is published (not draft)
- Check browser console for JavaScript errors
- Verify REST API is accessible

### Locations don't appear
- Verify locations are published
- Check that locations are associated with correct map
- Verify coordinates are within map bounds
- Check REST API response for location data

### Visual editor doesn't work
- Verify map has an image uploaded
- Check browser console for JavaScript errors
- Ensure jQuery is loaded
- Verify AJAX endpoint is accessible

### Styles not applying
- Clear browser cache
- Check that CSS files are enqueued
- Verify file permissions
- Check for CSS conflicts with theme

## Performance Testing

### Steps:
1. Create a map with 20+ locations
2. Embed on a page and measure load time
3. Check browser performance tools
4. Verify no memory leaks
5. Test with slow network connection

### Expected Results:
- Page loads in reasonable time (< 3 seconds)
- No excessive memory usage
- Smooth interactions
- Graceful degradation on slow connections

## Accessibility Testing

### Steps:
1. Navigate map using keyboard only (Tab key)
2. Test with screen reader (NVDA, JAWS, or VoiceOver)
3. Verify ARIA labels are present
4. Check color contrast ratios
5. Verify focus indicators are visible

### Expected Results:
- All interactive elements are keyboard accessible
- Screen reader announces content correctly
- Color contrast meets WCAG AA standards
- Focus indicators are clearly visible

## Final Checklist

- [ ] Plugin activates without errors
- [ ] Maps can be created with images
- [ ] Place locations work correctly
- [ ] Area locations work correctly
- [ ] Shortcode embeds maps successfully
- [ ] Gutenberg block works properly
- [ ] Frontend interactivity functions
- [ ] REST API endpoints return correct data
- [ ] Plugin settings apply correctly
- [ ] Security validation works
- [ ] Cross-browser compatibility verified
- [ ] Mobile responsive design works
- [ ] Performance is acceptable
- [ ] Accessibility requirements met

## Reporting Issues

If you encounter any issues during testing:

1. Note the exact steps to reproduce
2. Check browser console for errors
3. Verify WordPress and PHP versions
4. Check for plugin conflicts
5. Review server error logs
6. Document expected vs actual behavior

## Next Steps

After completing all tests successfully:

1. Document any bugs or issues found
2. Create a list of potential improvements
3. Consider additional features for future versions
4. Prepare user documentation
5. Plan for production deployment
