<?php
/**
 * Shortcode Handler Class
 *
 * Handles the [interactive_map] shortcode for embedding maps.
 *
 * @package WP_Interactive_Maps
 * @since 1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Class WIM_Shortcode
 *
 * Registers and handles the interactive map shortcode.
 */
class WIM_Shortcode {

    /**
     * Initialize the shortcode.
     */
    public function __construct() {
        add_shortcode( 'interactive_map', array( $this, 'render_shortcode' ) );
    }

    /**
     * Render the interactive map shortcode.
     *
     * @param array $atts Shortcode attributes.
     * @return string HTML output for the map.
     */
    public function render_shortcode( $atts ) {
        // Parse shortcode attributes with defaults
        $atts = shortcode_atts(
            array(
                'id'     => 0,
                'layout' => 'side',
            ),
            $atts,
            'interactive_map'
        );

        // Sanitize attributes
        $map_id = absint( $atts['id'] );
        $layout = sanitize_text_field( $atts['layout'] );

        // Validate layout value
        if ( ! in_array( $layout, array( 'side', 'popup' ), true ) ) {
            $layout = 'side';
        }

        // Validate map ID exists
        if ( $map_id === 0 ) {
            return $this->render_error( __( 'Map ID is required. Please provide a valid map ID.', 'wp-interactive-maps' ) );
        }

        // Validate map ID using sanitization helper
        $validated_map_id = WIM_Sanitization::sanitize_map_id( $map_id );
        if ( false === $validated_map_id ) {
            return $this->render_error( __( 'Invalid map ID. The specified map does not exist.', 'wp-interactive-maps' ) );
        }

        // Check if map post is published (or user has permission to view)
        $map_post = get_post( $validated_map_id );
        if ( 'publish' !== $map_post->post_status && ! WIM_Sanitization::user_can_edit_maps( $validated_map_id ) ) {
            return $this->render_error( __( 'The specified map is not published.', 'wp-interactive-maps' ) );
        }

        // Enqueue frontend assets
        $this->enqueue_assets();

        // Generate unique container ID
        $container_id = 'wim-map-' . $validated_map_id . '-' . uniqid();

        // Build HTML output
        $output = sprintf(
            '<div id="%s" class="wim-container" data-map-id="%d" data-layout="%s"></div>',
            esc_attr( $container_id ),
            esc_attr( $validated_map_id ),
            esc_attr( $layout )
        );

        // Add initialization script
        $output .= sprintf(
            '<script>
            document.addEventListener("DOMContentLoaded", function() {
                if (typeof InteractiveMap !== "undefined" && typeof wimData !== "undefined") {
                    new InteractiveMap("%s", %d, {
                        layout: "%s",
                        apiUrl: wimData.restUrl
                    }).init();
                }
            });
            </script>',
            esc_js( $container_id ),
            esc_js( $validated_map_id ),
            esc_js( $layout )
        );

        return $output;
    }

    /**
     * Enqueue frontend CSS and JavaScript.
     */
    private function enqueue_assets() {
        wp_enqueue_style(
            'wim-map-display',
            WIM_PLUGIN_URL . 'public/css/map-display.css',
            array(),
            WIM_VERSION
        );

        wp_enqueue_script(
            'wim-map-display',
            WIM_PLUGIN_URL . 'public/js/map-display.js',
            array(),
            WIM_VERSION,
            true
        );

        // Localize script with REST API URL
        wp_localize_script(
            'wim-map-display',
            'wimData',
            array(
                'restUrl' => rest_url( 'wim/v1' ),
                'nonce'   => wp_create_nonce( 'wp_rest' ),
            )
        );
    }

    /**
     * Render error message.
     *
     * @param string $message Error message to display.
     * @return string HTML error output.
     */
    private function render_error( $message ) {
        return sprintf(
            '<div class="wim-error" style="padding: 15px; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 4px; color: #721c24;">
                <strong>%s:</strong> %s
            </div>',
            esc_html__( 'Interactive Map Error', 'wp-interactive-maps' ),
            esc_html( $message )
        );
    }
}

// Initialize the shortcode
new WIM_Shortcode();
