/**
 * Visual Map Editor for Location Meta Box
 *
 * Handles interactive coordinate selection and polygon drawing on map images.
 *
 * @package WP_Interactive_Maps
 * @since 1.0.0
 */

(function($) {
    'use strict';

    /**
     * Map Editor Class
     */
    var MapEditor = {
        mapId: null,
        mapImageUrl: null,
        mapWidth: null,
        mapHeight: null,
        locationType: 'place',
        polygonPoints: [],
        isDrawing: false,

        /**
         * Initialize the map editor
         */
        init: function() {
            this.bindEvents();
            this.loadMapImage();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;

            // Listen for map selection changes
            $('#wim_location_map_id').on('change', function() {
                console.log('WIM Map Editor: Map selection changed to:', $(this).val());
                self.loadMapImage();
            });

            // Listen for location type changes
            $('input[name="wim_location_type"]').on('change', function() {
                self.locationType = $(this).val();
                self.resetEditor();
            });

            // Handle clicks on the map image
            $(document).on('click', '#wim-map-editor-image', function(e) {
                self.handleMapClick(e);
            });

            // Clear polygon button
            $(document).on('click', '#wim-clear-polygon', function(e) {
                e.preventDefault();
                self.clearPolygon();
            });

            // Finish polygon button
            $(document).on('click', '#wim-finish-polygon', function(e) {
                e.preventDefault();
                self.finishPolygon();
            });
        },

        /**
         * Load the map image for the selected map
         */
        loadMapImage: function() {
            var self = this;
            var mapId = $('#wim_location_map_id').val();

            if (!mapId) {
                this.hideMapEditor();
                return;
            }

            // Fetch map data via AJAX
            $.ajax({
                url: wimMapEditor.ajaxUrl || ajaxurl,
                type: 'POST',
                data: {
                    action: 'wim_get_map_data',
                    map_id: mapId,
                    nonce: wimMapEditor.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        self.mapId = mapId;
                        self.mapImageUrl = response.data.image_url;
                        self.mapWidth = response.data.width;
                        self.mapHeight = response.data.height;
                        self.renderMapEditor();
                    } else {
                        console.error('Map data error:', response);
                        self.hideMapEditor();
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', status, error);
                    self.hideMapEditor();
                }
            });
        },

        /**
         * Render the map editor interface
         */
        renderMapEditor: function() {
            var self = this;
            var editorHtml = '<div id="wim-map-editor-container" style="margin-top: 20px; border: 1px solid #ddd; padding: 15px; background: #f9f9f9;">';
            editorHtml += '<h4>' + wimMapEditor.i18n.visualEditor + '</h4>';
            editorHtml += '<p class="description">' + this.getInstructionText() + '</p>';
            
            // Map image container with SVG overlay
            editorHtml += '<div id="wim-map-editor-wrapper" style="position: relative; display: inline-block; max-width: 100%;">';
            editorHtml += '<img id="wim-map-editor-image" src="' + this.mapImageUrl + '" style="display: block; max-width: 100%; height: auto; cursor: crosshair;" />';
            editorHtml += '<svg id="wim-map-editor-svg" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none;"></svg>';
            editorHtml += '</div>';

            // Polygon controls (only for area type)
            if (this.locationType === 'area') {
                editorHtml += '<div id="wim-polygon-controls" style="margin-top: 10px;">';
                editorHtml += '<button type="button" id="wim-clear-polygon" class="button">' + wimMapEditor.i18n.clearPolygon + '</button> ';
                editorHtml += '<button type="button" id="wim-finish-polygon" class="button button-primary">' + wimMapEditor.i18n.finishPolygon + '</button>';
                editorHtml += '</div>';
            }

            editorHtml += '</div>';

            // Remove existing editor if present
            $('#wim-map-editor-container').remove();

            // Insert editor after the appropriate coordinate section
            if (this.locationType === 'place') {
                $('#wim-place-coordinates').after(editorHtml);
            } else {
                $('#wim-area-coordinates').after(editorHtml);
            }

            // Load existing coordinates if any
            this.loadExistingCoordinates();
        },

        /**
         * Get instruction text based on location type
         */
        getInstructionText: function() {
            if (this.locationType === 'place') {
                return wimMapEditor.i18n.clickToSetPlace;
            } else {
                return wimMapEditor.i18n.clickToDrawArea;
            }
        },

        /**
         * Hide the map editor
         */
        hideMapEditor: function() {
            $('#wim-map-editor-container').remove();
        },

        /**
         * Reset the editor when location type changes
         */
        resetEditor: function() {
            this.polygonPoints = [];
            this.isDrawing = false;
            if (this.mapId) {
                this.renderMapEditor();
            }
        },

        /**
         * Handle clicks on the map image
         */
        handleMapClick: function(e) {
            var $img = $('#wim-map-editor-image');
            var offset = $img.offset();
            var imgWidth = $img.width();
            var imgHeight = $img.height();

            // Calculate click position relative to image
            var x = e.pageX - offset.left;
            var y = e.pageY - offset.top;

            // Convert to original image coordinates
            var scaleX = this.mapWidth / imgWidth;
            var scaleY = this.mapHeight / imgHeight;
            var actualX = Math.round(x * scaleX);
            var actualY = Math.round(y * scaleY);

            if (this.locationType === 'place') {
                this.setPlaceCoordinates(actualX, actualY);
                this.renderPlaceMarker(x, y);
            } else {
                this.addPolygonPoint(actualX, actualY, x, y);
            }
        },

        /**
         * Set place coordinates in the input fields
         */
        setPlaceCoordinates: function(x, y) {
            $('#wim_place_x').val(x);
            $('#wim_place_y').val(y);
        },

        /**
         * Render a marker for place location
         */
        renderPlaceMarker: function(x, y) {
            var svg = document.getElementById('wim-map-editor-svg');
            
            // Clear existing markers
            while (svg.firstChild) {
                svg.removeChild(svg.firstChild);
            }

            // Create marker circle
            var circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
            circle.setAttribute('cx', x);
            circle.setAttribute('cy', y);
            circle.setAttribute('r', '8');
            circle.setAttribute('fill', '#ff6600');
            circle.setAttribute('stroke', '#fff');
            circle.setAttribute('stroke-width', '2');
            svg.appendChild(circle);
        },

        /**
         * Add a point to the polygon
         */
        addPolygonPoint: function(actualX, actualY, displayX, displayY) {
            this.polygonPoints.push({
                actual: [actualX, actualY],
                display: [displayX, displayY]
            });

            this.isDrawing = true;
            this.renderPolygon();
            this.updatePolygonField();
        },

        /**
         * Render the polygon on the SVG overlay
         */
        renderPolygon: function() {
            var svg = document.getElementById('wim-map-editor-svg');
            
            // Clear existing shapes
            while (svg.firstChild) {
                svg.removeChild(svg.firstChild);
            }

            if (this.polygonPoints.length === 0) {
                return;
            }

            // Draw lines between points
            for (var i = 0; i < this.polygonPoints.length; i++) {
                var point = this.polygonPoints[i].display;
                
                // Draw point marker
                var circle = document.createElementNS('http://www.w3.org/2000/svg', 'circle');
                circle.setAttribute('cx', point[0]);
                circle.setAttribute('cy', point[1]);
                circle.setAttribute('r', '5');
                circle.setAttribute('fill', '#ff6600');
                circle.setAttribute('stroke', '#fff');
                circle.setAttribute('stroke-width', '2');
                svg.appendChild(circle);

                // Draw line to next point
                if (i < this.polygonPoints.length - 1) {
                    var nextPoint = this.polygonPoints[i + 1].display;
                    var line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
                    line.setAttribute('x1', point[0]);
                    line.setAttribute('y1', point[1]);
                    line.setAttribute('x2', nextPoint[0]);
                    line.setAttribute('y2', nextPoint[1]);
                    line.setAttribute('stroke', '#ff6600');
                    line.setAttribute('stroke-width', '2');
                    svg.appendChild(line);
                }
            }

            // If polygon is finished (3+ points), draw the polygon shape
            if (this.polygonPoints.length >= 3 && !this.isDrawing) {
                var points = this.polygonPoints.map(function(p) {
                    return p.display[0] + ',' + p.display[1];
                }).join(' ');

                var polygon = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
                polygon.setAttribute('points', points);
                polygon.setAttribute('fill', 'rgba(255, 102, 0, 0.3)');
                polygon.setAttribute('stroke', '#ff6600');
                polygon.setAttribute('stroke-width', '2');
                svg.insertBefore(polygon, svg.firstChild);
            }
        },

        /**
         * Update the polygon points textarea
         */
        updatePolygonField: function() {
            var actualPoints = this.polygonPoints.map(function(p) {
                return p.actual;
            });
            $('#wim_area_points').val(JSON.stringify(actualPoints));
        },

        /**
         * Clear the polygon
         */
        clearPolygon: function() {
            this.polygonPoints = [];
            this.isDrawing = true;
            this.renderPolygon();
            $('#wim_area_points').val('');
        },

        /**
         * Finish drawing the polygon
         */
        finishPolygon: function() {
            if (this.polygonPoints.length < 3) {
                alert(wimMapEditor.i18n.minThreePoints);
                return;
            }

            this.isDrawing = false;
            this.renderPolygon();
        },

        /**
         * Load existing coordinates from fields
         */
        loadExistingCoordinates: function() {
            var self = this;
            var $img = $('#wim-map-editor-image');

            // Wait for image to load before calculating display coordinates
            $img.on('load', function() {
                if (self.locationType === 'place') {
                    var x = $('#wim_place_x').val();
                    var y = $('#wim_place_y').val();

                    if (x && y) {
                        // Convert to display coordinates
                        var imgWidth = $img.width();
                        var imgHeight = $img.height();
                        
                        if (imgWidth > 0 && imgHeight > 0) {
                            var scaleX = imgWidth / self.mapWidth;
                            var scaleY = imgHeight / self.mapHeight;
                            var displayX = parseFloat(x) * scaleX;
                            var displayY = parseFloat(y) * scaleY;

                            self.renderPlaceMarker(displayX, displayY);
                        }
                    }
                } else {
                    var pointsJson = $('#wim_area_points').val();
                    if (pointsJson) {
                        try {
                            var actualPoints = JSON.parse(pointsJson);
                            if (Array.isArray(actualPoints) && actualPoints.length > 0) {
                                // Convert to display coordinates
                                var imgWidth = $img.width();
                                var imgHeight = $img.height();
                                
                                if (imgWidth > 0 && imgHeight > 0) {
                                    var scaleX = imgWidth / self.mapWidth;
                                    var scaleY = imgHeight / self.mapHeight;

                                    self.polygonPoints = actualPoints.map(function(point) {
                                        return {
                                            actual: point,
                                            display: [point[0] * scaleX, point[1] * scaleY]
                                        };
                                    });

                                    self.isDrawing = false;
                                    self.renderPolygon();
                                }
                            }
                        } catch (e) {
                            console.error('Error parsing polygon points:', e);
                        }
                    }
                }
            });

            // If image is already loaded (cached), trigger load event manually
            if ($img[0].complete) {
                $img.trigger('load');
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        // Only initialize if we're on the location edit screen
        if ($('#wim_location_map_id').length) {
            console.log('WIM Map Editor: Initializing...');
            MapEditor.init();
        } else {
            console.log('WIM Map Editor: Map select field not found');
        }
    });

})(jQuery);
