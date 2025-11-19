<?php
/**
 * REST API Endpoints
 *
 * Handles custom REST API endpoints for maps and locations.
 *
 * @package WP_Interactive_Maps
 * @since 1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Class WIM_REST_API
 *
 * Registers and manages custom REST API endpoints.
 */
class WIM_REST_API {

    /**
     * API namespace.
     *
     * @var string
     */
    private $namespace = 'wim/v1';

    /**
     * Initialize the class and register hooks.
     */
    public function __construct() {
        add_action( 'rest_api_init', array( $this, 'register_routes' ) );
    }

    /**
     * Register REST API routes.
     */
    public function register_routes() {
        // Register map endpoint
        register_rest_route(
            $this->namespace,
            '/maps/(?P<id>\d+)',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_map' ),
                'permission_callback' => array( $this, 'get_map_permissions_check' ),
                'args'                => array(
                    'id' => array(
                        'validate_callback' => function( $param, $request, $key ) {
                            return is_numeric( $param );
                        },
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );

        // Register location endpoint
        register_rest_route(
            $this->namespace,
            '/locations/(?P<id>\d+)',
            array(
                'methods'             => 'GET',
                'callback'            => array( $this, 'get_location' ),
                'permission_callback' => array( $this, 'get_location_permissions_check' ),
                'args'                => array(
                    'id' => array(
                        'validate_callback' => function( $param, $request, $key ) {
                            return is_numeric( $param );
                        },
                        'sanitize_callback' => 'absint',
                    ),
                ),
            )
        );
    }

    /**
     * Get map data with all associated locations.
     *
     * @param WP_REST_Request $request Full request data.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_map( $request ) {
        $map_id = $request->get_param( 'id' );

        // Validate map ID
        if ( empty( $map_id ) || $map_id <= 0 ) {
            return new WP_Error(
                'invalid_map_id',
                __( 'Invalid map ID provided.', 'wp-interactive-maps' ),
                array( 'status' => 400 )
            );
        }

        // Get map post
        $map_post = get_post( $map_id );

        // Check if map exists
        if ( ! $map_post || $map_post->post_type !== 'wim_map' ) {
            return new WP_Error(
                'map_not_found',
                __( 'Map not found.', 'wp-interactive-maps' ),
                array( 'status' => 404 )
            );
        }

        // Check if map is published or user has permission to view
        if ( $map_post->post_status !== 'publish' && ! current_user_can( 'edit_post', $map_id ) ) {
            return new WP_Error(
                'map_not_accessible',
                __( 'You do not have permission to access this map.', 'wp-interactive-maps' ),
                array( 'status' => 403 )
            );
        }

        // Get map meta data
        $image_id = get_post_meta( $map_id, '_wim_map_image_id', true );
        $image_url = $image_id ? wp_get_attachment_url( $image_id ) : '';
        $image_width = get_post_meta( $map_id, '_wim_map_width', true );
        $image_height = get_post_meta( $map_id, '_wim_map_height', true );

        // Query all locations associated with this map
        $locations_query = new WP_Query(
            array(
                'post_type'      => 'wim_location',
                'post_status'    => 'publish',
                'posts_per_page' => -1,
                'meta_query'     => array(
                    array(
                        'key'     => '_wim_location_map_id',
                        'value'   => $map_id,
                        'compare' => '=',
                    ),
                ),
            )
        );

        $locations = array();
        if ( $locations_query->have_posts() ) {
            while ( $locations_query->have_posts() ) {
                $locations_query->the_post();
                $location_id = get_the_ID();

                // Get location meta data
                $coordinates_json = get_post_meta( $location_id, '_wim_location_coordinates', true );
                $coordinates = ! empty( $coordinates_json ) ? json_decode( $coordinates_json, true ) : null;

                $images_json = get_post_meta( $location_id, '_wim_location_images', true );
                $image_ids = ! empty( $images_json ) ? json_decode( $images_json, true ) : array();

                // Format images array
                $images = array();
                if ( is_array( $image_ids ) ) {
                    foreach ( $image_ids as $img_id ) {
                        $img_url = wp_get_attachment_url( $img_id );
                        $img_alt = get_post_meta( $img_id, '_wp_attachment_image_alt', true );
                        if ( $img_url ) {
                            $images[] = array(
                                'url' => $img_url,
                                'alt' => $img_alt ? $img_alt : get_the_title( $img_id ),
                            );
                        }
                    }
                }

                $locations[] = array(
                    'id'          => $location_id,
                    'title'       => get_the_title(),
                    'content'     => apply_filters( 'the_content', get_the_content() ),
                    'type'        => get_post_meta( $location_id, '_wim_location_type', true ),
                    'coordinates' => $coordinates,
                    'color'       => get_post_meta( $location_id, '_wim_location_marker_color', true ),
                    'images'      => $images,
                );
            }
            wp_reset_postdata();
        }

        // Format response
        $response = array(
            'id'           => $map_id,
            'title'        => $map_post->post_title,
            'description'  => apply_filters( 'the_content', $map_post->post_content ),
            'image_url'    => $image_url,
            'image_width'  => absint( $image_width ),
            'image_height' => absint( $image_height ),
            'locations'    => $locations,
        );

        return rest_ensure_response( $response );
    }

    /**
     * Get single location data.
     *
     * @param WP_REST_Request $request Full request data.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function get_location( $request ) {
        $location_id = $request->get_param( 'id' );

        // Validate location ID
        if ( empty( $location_id ) || $location_id <= 0 ) {
            return new WP_Error(
                'invalid_location_id',
                __( 'Invalid location ID provided.', 'wp-interactive-maps' ),
                array( 'status' => 400 )
            );
        }

        // Get location post
        $location_post = get_post( $location_id );

        // Check if location exists
        if ( ! $location_post || $location_post->post_type !== 'wim_location' ) {
            return new WP_Error(
                'location_not_found',
                __( 'Location not found.', 'wp-interactive-maps' ),
                array( 'status' => 404 )
            );
        }

        // Check if location is published or user has permission to view
        if ( $location_post->post_status !== 'publish' && ! current_user_can( 'edit_post', $location_id ) ) {
            return new WP_Error(
                'location_not_accessible',
                __( 'You do not have permission to access this location.', 'wp-interactive-maps' ),
                array( 'status' => 403 )
            );
        }

        // Get location meta data
        $coordinates_json = get_post_meta( $location_id, '_wim_location_coordinates', true );
        $coordinates = ! empty( $coordinates_json ) ? json_decode( $coordinates_json, true ) : null;

        $images_json = get_post_meta( $location_id, '_wim_location_images', true );
        $image_ids = ! empty( $images_json ) ? json_decode( $images_json, true ) : array();

        // Format images array
        $images = array();
        if ( is_array( $image_ids ) ) {
            foreach ( $image_ids as $img_id ) {
                $img_url = wp_get_attachment_url( $img_id );
                $img_alt = get_post_meta( $img_id, '_wp_attachment_image_alt', true );
                if ( $img_url ) {
                    $images[] = array(
                        'url' => $img_url,
                        'alt' => $img_alt ? $img_alt : get_the_title( $img_id ),
                    );
                }
            }
        }

        // Format response
        $response = array(
            'id'          => $location_id,
            'map_id'      => absint( get_post_meta( $location_id, '_wim_location_map_id', true ) ),
            'title'       => $location_post->post_title,
            'content'     => apply_filters( 'the_content', $location_post->post_content ),
            'type'        => get_post_meta( $location_id, '_wim_location_type', true ),
            'coordinates' => $coordinates,
            'color'       => get_post_meta( $location_id, '_wim_location_marker_color', true ),
            'images'      => $images,
        );

        return rest_ensure_response( $response );
    }

    /**
     * Check permissions for map endpoint.
     *
     * @param WP_REST_Request $request Full request data.
     * @return bool|WP_Error True if the request has permission, WP_Error otherwise.
     */
    public function get_map_permissions_check( $request ) {
        $map_id = $request->get_param( 'id' );
        
        // Validate map ID
        if ( ! $map_id || $map_id <= 0 ) {
            return new WP_Error(
                'invalid_map_id',
                __( 'Invalid map ID provided.', 'wp-interactive-maps' ),
                array( 'status' => 400 )
            );
        }
        
        // Get the post
        $post = get_post( $map_id );
        
        // If post doesn't exist, allow the request to proceed to get proper 404 error
        if ( ! $post ) {
            return true;
        }
        
        // Verify it's a map post type
        if ( 'wim_map' !== $post->post_type ) {
            return new WP_Error(
                'invalid_post_type',
                __( 'The specified ID is not a map.', 'wp-interactive-maps' ),
                array( 'status' => 400 )
            );
        }
        
        // If post is published, allow public access
        if ( 'publish' === $post->post_status ) {
            return true;
        }
        
        // For non-published posts, check if user can edit
        if ( ! WIM_Sanitization::user_can_edit_maps( $map_id ) ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'You do not have permission to access this map.', 'wp-interactive-maps' ),
                array( 'status' => 403 )
            );
        }
        
        return true;
    }

    /**
     * Check permissions for location endpoint.
     *
     * @param WP_REST_Request $request Full request data.
     * @return bool|WP_Error True if the request has permission, WP_Error otherwise.
     */
    public function get_location_permissions_check( $request ) {
        $location_id = $request->get_param( 'id' );
        
        // Validate location ID
        if ( ! $location_id || $location_id <= 0 ) {
            return new WP_Error(
                'invalid_location_id',
                __( 'Invalid location ID provided.', 'wp-interactive-maps' ),
                array( 'status' => 400 )
            );
        }
        
        // Get the post
        $post = get_post( $location_id );
        
        // If post doesn't exist, allow the request to proceed to get proper 404 error
        if ( ! $post ) {
            return true;
        }
        
        // Verify it's a location post type
        if ( 'wim_location' !== $post->post_type ) {
            return new WP_Error(
                'invalid_post_type',
                __( 'The specified ID is not a location.', 'wp-interactive-maps' ),
                array( 'status' => 400 )
            );
        }
        
        // If post is published, allow public access
        if ( 'publish' === $post->post_status ) {
            return true;
        }
        
        // For non-published posts, check if user can edit
        if ( ! WIM_Sanitization::user_can_edit_maps( $location_id ) ) {
            return new WP_Error(
                'rest_forbidden',
                __( 'You do not have permission to access this location.', 'wp-interactive-maps' ),
                array( 'status' => 403 )
            );
        }
        
        return true;
    }
}

// Initialize the REST API class
new WIM_REST_API();
