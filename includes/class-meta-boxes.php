<?php
/**
 * Meta Boxes for Maps and Locations
 *
 * Handles admin meta boxes for Map and Location custom post types.
 *
 * @package WP_Interactive_Maps
 * @since 1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Class WIM_Meta_Boxes
 *
 * Registers and manages meta boxes for the plugin.
 */
class WIM_Meta_Boxes {

    /**
     * Initialize the class and register hooks.
     */
    public function __construct() {
        add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
        add_action( 'save_post', array( $this, 'save_map_meta_box' ), 10, 2 );
        add_action( 'save_post', array( $this, 'save_location_meta_box' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_media_uploader' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_location_scripts' ) );
        add_action( 'wp_ajax_wim_get_map_data', array( $this, 'ajax_get_map_data' ) );
    }

    /**
     * Register meta boxes for Map and Location post types.
     */
    public function add_meta_boxes() {
        // Map meta box
        add_meta_box(
            'wim_map_details',
            __( 'Map Details', 'wp-interactive-maps' ),
            array( $this, 'render_map_meta_box' ),
            'wim_map',
            'normal',
            'high'
        );

        // Location meta box
        add_meta_box(
            'wim_location_details',
            __( 'Location Details', 'wp-interactive-maps' ),
            array( $this, 'render_location_meta_box' ),
            'wim_location',
            'normal',
            'high'
        );
    }

    /**
     * Enqueue WordPress media uploader.
     *
     * @param string $hook The current admin page hook.
     */
    public function enqueue_media_uploader( $hook ) {
        // Only load on post edit screens
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
            return;
        }

        global $post_type;
        if ( 'wim_map' !== $post_type ) {
            return;
        }

        // Enqueue admin styles
        wp_enqueue_style(
            'wim-admin-styles',
            plugin_dir_url( dirname( __FILE__ ) ) . 'admin/css/admin-styles.css',
            array(),
            '1.0.0'
        );

        // Enqueue WordPress media uploader
        wp_enqueue_media();
    }

    /**
     * Enqueue scripts and styles for Location meta box.
     *
     * @param string $hook The current admin page hook.
     */
    public function enqueue_location_scripts( $hook ) {
        // Only load on post edit screens
        if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
            return;
        }

        global $post_type;
        if ( 'wim_location' !== $post_type ) {
            return;
        }

        // Enqueue admin styles
        wp_enqueue_style(
            'wim-admin-styles',
            plugin_dir_url( dirname( __FILE__ ) ) . 'admin/css/admin-styles.css',
            array(),
            '1.0.0'
        );

        // Enqueue WordPress media uploader
        wp_enqueue_media();

        // Enqueue WordPress color picker
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );

        // Enqueue map editor script
        wp_enqueue_script(
            'wim-map-editor',
            plugin_dir_url( dirname( __FILE__ ) ) . 'admin/js/map-editor.js',
            array( 'jquery' ),
            '1.0.0',
            true
        );

        // Localize script with translations and AJAX URL
        wp_localize_script(
            'wim-map-editor',
            'wimMapEditor',
            array(
                'ajaxUrl' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'wim_map_editor' ),
                'i18n' => array(
                    'visualEditor' => __( 'Visual Map Editor', 'wp-interactive-maps' ),
                    'clickToSetPlace' => __( 'Click on the map to set the place coordinates.', 'wp-interactive-maps' ),
                    'clickToDrawArea' => __( 'Click on the map to draw polygon points. Click "Finish" when done.', 'wp-interactive-maps' ),
                    'clearPolygon' => __( 'Clear Polygon', 'wp-interactive-maps' ),
                    'finishPolygon' => __( 'Finish Polygon', 'wp-interactive-maps' ),
                    'minThreePoints' => __( 'A polygon must have at least 3 points.', 'wp-interactive-maps' ),
                ),
            )
        );
    }

    /**
     * Render the Map meta box.
     *
     * @param WP_Post $post The current post object.
     */
    public function render_map_meta_box( $post ) {
        // Add nonce for security
        wp_nonce_field( 'wim_save_map_meta', 'wim_map_meta_nonce' );

        // Get current meta values
        $image_id = get_post_meta( $post->ID, '_wim_map_image_id', true );
        $width = get_post_meta( $post->ID, '_wim_map_width', true );
        $height = get_post_meta( $post->ID, '_wim_map_height', true );

        // Get image URL if image ID exists
        $image_url = '';
        if ( $image_id ) {
            $image_url = wp_get_attachment_url( $image_id );
        }

        ?>
        <div class="wim-map-meta-box">
            <p>
                <label for="wim_map_image_id">
                    <strong><?php esc_html_e( 'Map Base Image', 'wp-interactive-maps' ); ?></strong>
                </label>
            </p>
            
            <div class="wim-map-image-container">
                <?php if ( $image_url ) : ?>
                    <img src="<?php echo esc_url( $image_url ); ?>" 
                         id="wim-map-image-preview" 
                         style="max-width: 100%; height: auto; display: block; margin-bottom: 10px;" 
                         alt="<?php esc_attr_e( 'Map preview', 'wp-interactive-maps' ); ?>" />
                <?php else : ?>
                    <img src="" 
                         id="wim-map-image-preview" 
                         style="max-width: 100%; height: auto; display: none; margin-bottom: 10px;" 
                         alt="<?php esc_attr_e( 'Map preview', 'wp-interactive-maps' ); ?>" />
                <?php endif; ?>
            </div>

            <p>
                <input type="hidden" 
                       id="wim_map_image_id" 
                       name="wim_map_image_id" 
                       value="<?php echo esc_attr( $image_id ); ?>" />
                
                <button type="button" 
                        class="button button-primary" 
                        id="wim-upload-map-image">
                    <?php esc_html_e( 'Select Map Image', 'wp-interactive-maps' ); ?>
                </button>
                
                <button type="button" 
                        class="button" 
                        id="wim-remove-map-image"
                        <?php echo empty( $image_id ) ? 'style="display:none;"' : ''; ?>>
                    <?php esc_html_e( 'Remove Image', 'wp-interactive-maps' ); ?>
                </button>
            </p>

            <p class="description">
                <?php esc_html_e( 'Upload or select the base map image. This will be used as the background for placing locations.', 'wp-interactive-maps' ); ?>
            </p>

            <?php if ( $width && $height ) : ?>
                <p>
                    <strong><?php esc_html_e( 'Image Dimensions:', 'wp-interactive-maps' ); ?></strong>
                    <?php echo esc_html( $width ); ?> × <?php echo esc_html( $height ); ?> px
                </p>
            <?php endif; ?>

            <input type="hidden" 
                   id="wim_map_width" 
                   name="wim_map_width" 
                   value="<?php echo esc_attr( $width ); ?>" />
            
            <input type="hidden" 
                   id="wim_map_height" 
                   name="wim_map_height" 
                   value="<?php echo esc_attr( $height ); ?>" />
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var mediaUploader;

            $('#wim-upload-map-image').on('click', function(e) {
                e.preventDefault();

                // If the uploader object has already been created, reopen the dialog
                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }

                // Create the media uploader
                mediaUploader = wp.media({
                    title: '<?php esc_html_e( 'Select Map Image', 'wp-interactive-maps' ); ?>',
                    button: {
                        text: '<?php esc_html_e( 'Use this image', 'wp-interactive-maps' ); ?>'
                    },
                    multiple: false,
                    library: {
                        type: 'image'
                    }
                });

                // When an image is selected, run a callback
                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    
                    // Set the image ID
                    $('#wim_map_image_id').val(attachment.id);
                    
                    // Set the image dimensions
                    $('#wim_map_width').val(attachment.width);
                    $('#wim_map_height').val(attachment.height);
                    
                    // Display the image preview
                    $('#wim-map-image-preview').attr('src', attachment.url).show();
                    
                    // Show the remove button
                    $('#wim-remove-map-image').show();
                });

                // Open the uploader dialog
                mediaUploader.open();
            });

            $('#wim-remove-map-image').on('click', function(e) {
                e.preventDefault();
                
                // Clear the image ID and dimensions
                $('#wim_map_image_id').val('');
                $('#wim_map_width').val('');
                $('#wim_map_height').val('');
                
                // Hide the image preview
                $('#wim-map-image-preview').attr('src', '').hide();
                
                // Hide the remove button
                $(this).hide();
            });
        });
        </script>
        <?php
    }

    /**
     * Save the Map meta box data.
     *
     * @param int     $post_id The post ID.
     * @param WP_Post $post    The post object.
     */
    public function save_map_meta_box( $post_id, $post ) {
        // Check if this is the Map post type
        if ( 'wim_map' !== $post->post_type ) {
            return;
        }

        // Verify nonce
        if ( ! isset( $_POST['wim_map_meta_nonce'] ) || 
             ! WIM_Sanitization::verify_nonce( $_POST['wim_map_meta_nonce'], 'wim_save_map_meta' ) ) {
            return;
        }

        // Check if this is an autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check user permissions
        if ( ! WIM_Sanitization::user_can_edit_maps( $post_id ) ) {
            return;
        }

        // Save map image ID
        if ( isset( $_POST['wim_map_image_id'] ) ) {
            $image_id = WIM_Sanitization::sanitize_attachment_id( $_POST['wim_map_image_id'] );
            if ( false !== $image_id ) {
                update_post_meta( $post_id, '_wim_map_image_id', $image_id );
            }
        } else {
            delete_post_meta( $post_id, '_wim_map_image_id' );
        }

        // Save map width
        if ( isset( $_POST['wim_map_width'] ) ) {
            $width = absint( $_POST['wim_map_width'] );
            if ( $width > 0 ) {
                update_post_meta( $post_id, '_wim_map_width', $width );
            }
        } else {
            delete_post_meta( $post_id, '_wim_map_width' );
        }

        // Save map height
        if ( isset( $_POST['wim_map_height'] ) ) {
            $height = absint( $_POST['wim_map_height'] );
            if ( $height > 0 ) {
                update_post_meta( $post_id, '_wim_map_height', $height );
            }
        } else {
            delete_post_meta( $post_id, '_wim_map_height' );
        }
    }

    /**
     * Render the Location meta box.
     *
     * @param WP_Post $post The current post object.
     */
    public function render_location_meta_box( $post ) {
        // Add nonce for security
        wp_nonce_field( 'wim_save_location_meta', 'wim_location_meta_nonce' );

        // Get current meta values
        $map_id = get_post_meta( $post->ID, '_wim_location_map_id', true );
        $location_type = get_post_meta( $post->ID, '_wim_location_type', true );
        $coordinates = get_post_meta( $post->ID, '_wim_location_coordinates', true );
        $marker_color = get_post_meta( $post->ID, '_wim_location_marker_color', true );
        $images = get_post_meta( $post->ID, '_wim_location_images', true );

        // Set defaults
        if ( empty( $location_type ) ) {
            $location_type = 'place';
        }
        if ( empty( $marker_color ) ) {
            $marker_color = '#ff6600';
        }

        // Parse coordinates
        $coordinates_data = json_decode( $coordinates, true );
        $place_x = isset( $coordinates_data['x'] ) ? $coordinates_data['x'] : '';
        $place_y = isset( $coordinates_data['y'] ) ? $coordinates_data['y'] : '';
        $area_points = isset( $coordinates_data['points'] ) ? json_encode( $coordinates_data['points'] ) : '';

        // Parse images
        $image_ids = array();
        if ( ! empty( $images ) ) {
            $image_ids = json_decode( $images, true );
            if ( ! is_array( $image_ids ) ) {
                $image_ids = array();
            }
        }

        // Query all published maps
        $maps = get_posts( array(
            'post_type' => 'wim_map',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC'
        ) );

        ?>
        <div class="wim-location-meta-box">
            <!-- Map Selection -->
            <p>
                <label for="wim_location_map_id">
                    <strong><?php esc_html_e( 'Associated Map', 'wp-interactive-maps' ); ?></strong>
                </label>
            </p>
            <p>
                <select id="wim_location_map_id" name="wim_location_map_id" style="width: 100%;">
                    <option value=""><?php esc_html_e( '-- Select a Map --', 'wp-interactive-maps' ); ?></option>
                    <?php foreach ( $maps as $map ) : ?>
                        <option value="<?php echo esc_attr( $map->ID ); ?>" 
                                <?php selected( $map_id, $map->ID ); ?>>
                            <?php echo esc_html( $map->post_title ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </p>

            <!-- Location Type -->
            <p>
                <label>
                    <strong><?php esc_html_e( 'Location Type', 'wp-interactive-maps' ); ?></strong>
                </label>
            </p>
            <p>
                <label>
                    <input type="radio" 
                           name="wim_location_type" 
                           value="place" 
                           <?php checked( $location_type, 'place' ); ?> />
                    <?php esc_html_e( 'Place (Point)', 'wp-interactive-maps' ); ?>
                </label>
                <br>
                <label>
                    <input type="radio" 
                           name="wim_location_type" 
                           value="area" 
                           <?php checked( $location_type, 'area' ); ?> />
                    <?php esc_html_e( 'Area (Polygon)', 'wp-interactive-maps' ); ?>
                </label>
            </p>

            <!-- Place Coordinates -->
            <div id="wim-place-coordinates" style="<?php echo ( $location_type === 'area' ) ? 'display:none;' : ''; ?>">
                <p>
                    <label for="wim_place_x">
                        <strong><?php esc_html_e( 'Place Coordinates', 'wp-interactive-maps' ); ?></strong>
                    </label>
                </p>
                <p>
                    <label for="wim_place_x">
                        <?php esc_html_e( 'X Position:', 'wp-interactive-maps' ); ?>
                    </label>
                    <input type="number" 
                           id="wim_place_x" 
                           name="wim_place_x" 
                           value="<?php echo esc_attr( $place_x ); ?>" 
                           step="0.01" 
                           style="width: 100px;" />
                </p>
                <p>
                    <label for="wim_place_y">
                        <?php esc_html_e( 'Y Position:', 'wp-interactive-maps' ); ?>
                    </label>
                    <input type="number" 
                           id="wim_place_y" 
                           name="wim_place_y" 
                           value="<?php echo esc_attr( $place_y ); ?>" 
                           step="0.01" 
                           style="width: 100px;" />
                </p>
            </div>

            <!-- Area Coordinates -->
            <div id="wim-area-coordinates" style="<?php echo ( $location_type === 'place' ) ? 'display:none;' : ''; ?>">
                <p>
                    <label for="wim_area_points">
                        <strong><?php esc_html_e( 'Polygon Points', 'wp-interactive-maps' ); ?></strong>
                    </label>
                </p>
                <p>
                    <textarea id="wim_area_points" 
                              name="wim_area_points" 
                              rows="5" 
                              style="width: 100%;"><?php echo esc_textarea( $area_points ); ?></textarea>
                </p>
                <p class="description">
                    <?php esc_html_e( 'Enter polygon points as JSON array: [[x1,y1],[x2,y2],[x3,y3],...]', 'wp-interactive-maps' ); ?>
                </p>
            </div>

            <!-- Color Picker -->
            <p>
                <label for="wim_location_marker_color">
                    <strong><?php esc_html_e( 'Marker/Polygon Color', 'wp-interactive-maps' ); ?></strong>
                </label>
            </p>
            <p>
                <input type="text" 
                       id="wim_location_marker_color" 
                       name="wim_location_marker_color" 
                       value="<?php echo esc_attr( $marker_color ); ?>" 
                       class="wim-color-picker" />
            </p>

            <!-- Image Gallery -->
            <p>
                <label>
                    <strong><?php esc_html_e( 'Location Images', 'wp-interactive-maps' ); ?></strong>
                </label>
            </p>
            <div id="wim-location-images-container">
                <?php foreach ( $image_ids as $image_id ) : 
                    $image_url = wp_get_attachment_image_url( $image_id, 'thumbnail' );
                    if ( $image_url ) :
                ?>
                    <div class="wim-location-image" data-image-id="<?php echo esc_attr( $image_id ); ?>" style="display: inline-block; margin: 5px; position: relative;">
                        <img src="<?php echo esc_url( $image_url ); ?>" style="width: 100px; height: 100px; object-fit: cover;" />
                        <button type="button" class="wim-remove-location-image" style="position: absolute; top: 0; right: 0; background: red; color: white; border: none; cursor: pointer; padding: 2px 6px;">×</button>
                    </div>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
            <p>
                <button type="button" 
                        class="button" 
                        id="wim-add-location-images">
                    <?php esc_html_e( 'Add Images', 'wp-interactive-maps' ); ?>
                </button>
            </p>
            <input type="hidden" 
                   id="wim_location_images" 
                   name="wim_location_images" 
                   value="<?php echo esc_attr( $images ); ?>" />
        </div>

        <script type="text/javascript">
        jQuery(document).ready(function($) {
            // Toggle coordinate fields based on location type
            $('input[name="wim_location_type"]').on('change', function() {
                if ($(this).val() === 'place') {
                    $('#wim-place-coordinates').show();
                    $('#wim-area-coordinates').hide();
                } else {
                    $('#wim-place-coordinates').hide();
                    $('#wim-area-coordinates').show();
                }
            });

            // Initialize color picker
            $('.wim-color-picker').wpColorPicker();

            // Image gallery functionality
            var mediaUploader;
            var imageIds = <?php echo ! empty( $images ) ? $images : '[]'; ?>;

            $('#wim-add-location-images').on('click', function(e) {
                e.preventDefault();

                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }

                mediaUploader = wp.media({
                    title: '<?php esc_html_e( 'Select Location Images', 'wp-interactive-maps' ); ?>',
                    button: {
                        text: '<?php esc_html_e( 'Add Images', 'wp-interactive-maps' ); ?>'
                    },
                    multiple: true,
                    library: {
                        type: 'image'
                    }
                });

                mediaUploader.on('select', function() {
                    var attachments = mediaUploader.state().get('selection').toJSON();
                    
                    attachments.forEach(function(attachment) {
                        if (imageIds.indexOf(attachment.id) === -1) {
                            imageIds.push(attachment.id);
                            
                            var imageHtml = '<div class="wim-location-image" data-image-id="' + attachment.id + '" style="display: inline-block; margin: 5px; position: relative;">' +
                                '<img src="' + attachment.sizes.thumbnail.url + '" style="width: 100px; height: 100px; object-fit: cover;" />' +
                                '<button type="button" class="wim-remove-location-image" style="position: absolute; top: 0; right: 0; background: red; color: white; border: none; cursor: pointer; padding: 2px 6px;">×</button>' +
                                '</div>';
                            
                            $('#wim-location-images-container').append(imageHtml);
                        }
                    });
                    
                    $('#wim_location_images').val(JSON.stringify(imageIds));
                });

                mediaUploader.open();
            });

            // Remove image
            $(document).on('click', '.wim-remove-location-image', function(e) {
                e.preventDefault();
                var imageDiv = $(this).closest('.wim-location-image');
                var imageId = imageDiv.data('image-id');
                
                imageIds = imageIds.filter(function(id) {
                    return id !== imageId;
                });
                
                imageDiv.remove();
                $('#wim_location_images').val(JSON.stringify(imageIds));
            });
        });
        </script>
        <?php
    }

    /**
     * Save the Location meta box data.
     *
     * @param int     $post_id The post ID.
     * @param WP_Post $post    The post object.
     */
    public function save_location_meta_box( $post_id, $post ) {
        // Check if this is the Location post type
        if ( 'wim_location' !== $post->post_type ) {
            return;
        }

        // Verify nonce
        if ( ! isset( $_POST['wim_location_meta_nonce'] ) || 
             ! WIM_Sanitization::verify_nonce( $_POST['wim_location_meta_nonce'], 'wim_save_location_meta' ) ) {
            return;
        }

        // Check if this is an autosave
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return;
        }

        // Check user permissions
        if ( ! WIM_Sanitization::user_can_edit_maps( $post_id ) ) {
            return;
        }

        // Save map ID
        if ( isset( $_POST['wim_location_map_id'] ) ) {
            $map_id = WIM_Sanitization::sanitize_map_id( $_POST['wim_location_map_id'] );
            if ( false !== $map_id ) {
                update_post_meta( $post_id, '_wim_location_map_id', $map_id );
            }
        } else {
            delete_post_meta( $post_id, '_wim_location_map_id' );
        }

        // Save location type
        if ( isset( $_POST['wim_location_type'] ) ) {
            $location_type = WIM_Sanitization::sanitize_location_type( $_POST['wim_location_type'] );
            if ( false !== $location_type ) {
                update_post_meta( $post_id, '_wim_location_type', $location_type );
            }
        }

        // Save coordinates based on type
        $location_type = isset( $_POST['wim_location_type'] ) ? $_POST['wim_location_type'] : 'place';
        
        if ( $location_type === 'place' ) {
            // Validate and save place coordinates
            $place_x = isset( $_POST['wim_place_x'] ) ? $_POST['wim_place_x'] : '';
            $place_y = isset( $_POST['wim_place_y'] ) ? $_POST['wim_place_y'] : '';
            
            $coordinates = WIM_Sanitization::sanitize_place_coordinates( $place_x, $place_y );
            if ( false !== $coordinates ) {
                update_post_meta( $post_id, '_wim_location_coordinates', wp_json_encode( $coordinates ) );
            }
        } else {
            // Validate and save area coordinates
            if ( isset( $_POST['wim_area_points'] ) ) {
                $area_points = stripslashes( $_POST['wim_area_points'] );
                $coordinates = WIM_Sanitization::sanitize_area_coordinates( $area_points );
                
                if ( false !== $coordinates ) {
                    update_post_meta( $post_id, '_wim_location_coordinates', wp_json_encode( $coordinates ) );
                }
            }
        }

        // Save marker color
        if ( isset( $_POST['wim_location_marker_color'] ) ) {
            $color = WIM_Sanitization::sanitize_color( $_POST['wim_location_marker_color'] );
            if ( false !== $color ) {
                update_post_meta( $post_id, '_wim_location_marker_color', $color );
            }
        }

        // Save location images
        if ( isset( $_POST['wim_location_images'] ) ) {
            $image_ids = WIM_Sanitization::sanitize_image_ids( $_POST['wim_location_images'] );
            update_post_meta( $post_id, '_wim_location_images', wp_json_encode( $image_ids ) );
        } else {
            update_post_meta( $post_id, '_wim_location_images', wp_json_encode( array() ) );
        }
    }

    /**
     * AJAX handler to get map data for the visual editor.
     */
    public function ajax_get_map_data() {
        // Verify nonce
        if ( ! isset( $_POST['nonce'] ) || ! WIM_Sanitization::verify_nonce( $_POST['nonce'], 'wim_map_editor' ) ) {
            wp_send_json_error( array( 
                'message' => __( 'Invalid nonce', 'wp-interactive-maps' ),
                'debug' => 'Nonce verification failed'
            ) );
            return;
        }

        // Check user permissions
        if ( ! WIM_Sanitization::user_can_edit_maps() ) {
            wp_send_json_error( array( 
                'message' => __( 'Permission denied', 'wp-interactive-maps' ),
                'debug' => 'User lacks edit_posts capability'
            ) );
            return;
        }

        // Get map ID
        $map_id = isset( $_POST['map_id'] ) ? absint( $_POST['map_id'] ) : 0;

        if ( ! $map_id ) {
            wp_send_json_error( array( 
                'message' => __( 'Invalid map ID', 'wp-interactive-maps' ),
                'debug' => 'Map ID is 0 or not provided'
            ) );
            return;
        }

        // Get map post
        $map = get_post( $map_id );

        if ( ! $map || 'wim_map' !== $map->post_type ) {
            wp_send_json_error( array( 
                'message' => __( 'Map not found', 'wp-interactive-maps' ),
                'debug' => 'Map post not found or wrong post type',
                'map_id' => $map_id,
                'post_type' => $map ? $map->post_type : 'null'
            ) );
            return;
        }

        // Get map meta data
        $image_id = get_post_meta( $map_id, '_wim_map_image_id', true );
        $width = get_post_meta( $map_id, '_wim_map_width', true );
        $height = get_post_meta( $map_id, '_wim_map_height', true );

        if ( ! $image_id ) {
            wp_send_json_error( array( 
                'message' => __( 'Map has no image', 'wp-interactive-maps' ),
                'debug' => 'No image ID found in post meta',
                'map_id' => $map_id
            ) );
            return;
        }

        $image_url = wp_get_attachment_url( $image_id );

        if ( ! $image_url ) {
            wp_send_json_error( array( 
                'message' => __( 'Map image not found', 'wp-interactive-maps' ),
                'debug' => 'Attachment URL not found',
                'image_id' => $image_id
            ) );
            return;
        }

        // Return map data
        wp_send_json_success( array(
            'image_url' => esc_url( $image_url ),
            'width' => absint( $width ),
            'height' => absint( $height ),
        ) );
    }
}

// Initialize the meta boxes class
new WIM_Meta_Boxes();
