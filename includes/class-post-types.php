<?php
/**
 * Custom Post Types Registration
 *
 * Handles registration of Map and Location custom post types.
 *
 * @package WP_Interactive_Maps
 * @since 1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Class WIM_Post_Types
 *
 * Registers and manages custom post types for the plugin.
 */
class WIM_Post_Types {

    /**
     * Initialize the class and register hooks.
     */
    public function __construct() {
        add_action( 'init', array( $this, 'register_post_types' ) );
        add_action( 'init', array( $this, 'register_meta_fields' ) );
    }

    /**
     * Register custom post types.
     */
    public function register_post_types() {
        $this->register_map_post_type();
        $this->register_location_post_type();
    }

    /**
     * Register the Map custom post type.
     *
     * Maps are the base images with configuration that locations are associated with.
     */
    private function register_map_post_type() {
        $labels = array(
            'name'                  => _x( 'Maps', 'Post type general name', 'wp-interactive-maps' ),
            'singular_name'         => _x( 'Map', 'Post type singular name', 'wp-interactive-maps' ),
            'menu_name'             => _x( 'Interactive Maps', 'Admin Menu text', 'wp-interactive-maps' ),
            'name_admin_bar'        => _x( 'Map', 'Add New on Toolbar', 'wp-interactive-maps' ),
            'add_new'               => __( 'Add New', 'wp-interactive-maps' ),
            'add_new_item'          => __( 'Add New Map', 'wp-interactive-maps' ),
            'new_item'              => __( 'New Map', 'wp-interactive-maps' ),
            'edit_item'             => __( 'Edit Map', 'wp-interactive-maps' ),
            'view_item'             => __( 'View Map', 'wp-interactive-maps' ),
            'all_items'             => __( 'All Maps', 'wp-interactive-maps' ),
            'search_items'          => __( 'Search Maps', 'wp-interactive-maps' ),
            'parent_item_colon'     => __( 'Parent Maps:', 'wp-interactive-maps' ),
            'not_found'             => __( 'No maps found.', 'wp-interactive-maps' ),
            'not_found_in_trash'    => __( 'No maps found in Trash.', 'wp-interactive-maps' ),
            'featured_image'        => _x( 'Map Image', 'Overrides the "Featured Image" phrase', 'wp-interactive-maps' ),
            'set_featured_image'    => _x( 'Set map image', 'Overrides the "Set featured image" phrase', 'wp-interactive-maps' ),
            'remove_featured_image' => _x( 'Remove map image', 'Overrides the "Remove featured image" phrase', 'wp-interactive-maps' ),
            'use_featured_image'    => _x( 'Use as map image', 'Overrides the "Use as featured image" phrase', 'wp-interactive-maps' ),
            'archives'              => _x( 'Map archives', 'The post type archive label', 'wp-interactive-maps' ),
            'insert_into_item'      => _x( 'Insert into map', 'Overrides the "Insert into post" phrase', 'wp-interactive-maps' ),
            'uploaded_to_this_item' => _x( 'Uploaded to this map', 'Overrides the "Uploaded to this post" phrase', 'wp-interactive-maps' ),
            'filter_items_list'     => _x( 'Filter maps list', 'Screen reader text for the filter links', 'wp-interactive-maps' ),
            'items_list_navigation' => _x( 'Maps list navigation', 'Screen reader text for the pagination', 'wp-interactive-maps' ),
            'items_list'            => _x( 'Maps list', 'Screen reader text for the items list', 'wp-interactive-maps' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => true,
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'interactive-map' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => 20,
            'menu_icon'          => 'dashicons-location-alt',
            'supports'           => array( 'title', 'editor', 'thumbnail' ),
            'show_in_rest'       => true,
            'rest_base'          => 'maps',
        );

        register_post_type( 'wim_map', $args );
    }

    /**
     * Register the Location custom post type.
     *
     * Locations are points or areas on a map with associated content.
     */
    private function register_location_post_type() {
        $labels = array(
            'name'                  => _x( 'Locations', 'Post type general name', 'wp-interactive-maps' ),
            'singular_name'         => _x( 'Location', 'Post type singular name', 'wp-interactive-maps' ),
            'menu_name'             => _x( 'Locations', 'Admin Menu text', 'wp-interactive-maps' ),
            'name_admin_bar'        => _x( 'Location', 'Add New on Toolbar', 'wp-interactive-maps' ),
            'add_new'               => __( 'Add New', 'wp-interactive-maps' ),
            'add_new_item'          => __( 'Add New Location', 'wp-interactive-maps' ),
            'new_item'              => __( 'New Location', 'wp-interactive-maps' ),
            'edit_item'             => __( 'Edit Location', 'wp-interactive-maps' ),
            'view_item'             => __( 'View Location', 'wp-interactive-maps' ),
            'all_items'             => __( 'All Locations', 'wp-interactive-maps' ),
            'search_items'          => __( 'Search Locations', 'wp-interactive-maps' ),
            'parent_item_colon'     => __( 'Parent Locations:', 'wp-interactive-maps' ),
            'not_found'             => __( 'No locations found.', 'wp-interactive-maps' ),
            'not_found_in_trash'    => __( 'No locations found in Trash.', 'wp-interactive-maps' ),
            'featured_image'        => _x( 'Location Image', 'Overrides the "Featured Image" phrase', 'wp-interactive-maps' ),
            'set_featured_image'    => _x( 'Set location image', 'Overrides the "Set featured image" phrase', 'wp-interactive-maps' ),
            'remove_featured_image' => _x( 'Remove location image', 'Overrides the "Remove featured image" phrase', 'wp-interactive-maps' ),
            'use_featured_image'    => _x( 'Use as location image', 'Overrides the "Use as featured image" phrase', 'wp-interactive-maps' ),
            'archives'              => _x( 'Location archives', 'The post type archive label', 'wp-interactive-maps' ),
            'insert_into_item'      => _x( 'Insert into location', 'Overrides the "Insert into post" phrase', 'wp-interactive-maps' ),
            'uploaded_to_this_item' => _x( 'Uploaded to this location', 'Overrides the "Uploaded to this post" phrase', 'wp-interactive-maps' ),
            'filter_items_list'     => _x( 'Filter locations list', 'Screen reader text for the filter links', 'wp-interactive-maps' ),
            'items_list_navigation' => _x( 'Locations list navigation', 'Screen reader text for the pagination', 'wp-interactive-maps' ),
            'items_list'            => _x( 'Locations list', 'Screen reader text for the items list', 'wp-interactive-maps' ),
        );

        $args = array(
            'labels'             => $labels,
            'public'             => true,
            'publicly_queryable' => true,
            'show_ui'            => true,
            'show_in_menu'       => 'edit.php?post_type=wim_map',
            'query_var'          => true,
            'rewrite'            => array( 'slug' => 'location' ),
            'capability_type'    => 'post',
            'has_archive'        => true,
            'hierarchical'       => false,
            'menu_position'      => null,
            'menu_icon'          => 'dashicons-location',
            'supports'           => array( 'title', 'editor', 'thumbnail' ),
            'show_in_rest'       => true,
            'rest_base'          => 'locations',
        );

        register_post_type( 'wim_location', $args );
    }

    /**
     * Register custom meta fields for REST API exposure.
     *
     * This allows meta fields to be accessed via the WordPress REST API.
     */
    public function register_meta_fields() {
        // Register Map meta fields
        register_post_meta(
            'wim_map',
            '_wim_map_image_id',
            array(
                'type'              => 'integer',
                'description'       => __( 'Map base image attachment ID', 'wp-interactive-maps' ),
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => 'absint',
                'auth_callback'     => array( 'WIM_Sanitization', 'user_can_edit_maps' ),
            )
        );

        register_post_meta(
            'wim_map',
            '_wim_map_width',
            array(
                'type'              => 'integer',
                'description'       => __( 'Map image width in pixels', 'wp-interactive-maps' ),
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => 'absint',
                'auth_callback'     => array( 'WIM_Sanitization', 'user_can_edit_maps' ),
            )
        );

        register_post_meta(
            'wim_map',
            '_wim_map_height',
            array(
                'type'              => 'integer',
                'description'       => __( 'Map image height in pixels', 'wp-interactive-maps' ),
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => 'absint',
                'auth_callback'     => array( 'WIM_Sanitization', 'user_can_edit_maps' ),
            )
        );

        // Register Location meta fields
        register_post_meta(
            'wim_location',
            '_wim_location_map_id',
            array(
                'type'              => 'integer',
                'description'       => __( 'Associated map post ID', 'wp-interactive-maps' ),
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => 'absint',
                'auth_callback'     => array( 'WIM_Sanitization', 'user_can_edit_maps' ),
            )
        );

        register_post_meta(
            'wim_location',
            '_wim_location_type',
            array(
                'type'              => 'string',
                'description'       => __( 'Location type: place or area', 'wp-interactive-maps' ),
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => array( 'WIM_Sanitization', 'sanitize_location_type' ),
                'auth_callback'     => array( 'WIM_Sanitization', 'user_can_edit_maps' ),
            )
        );

        register_post_meta(
            'wim_location',
            '_wim_location_coordinates',
            array(
                'type'              => 'string',
                'description'       => __( 'Location coordinates as JSON', 'wp-interactive-maps' ),
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => 'sanitize_text_field',
                'auth_callback'     => array( 'WIM_Sanitization', 'user_can_edit_maps' ),
            )
        );

        register_post_meta(
            'wim_location',
            '_wim_location_marker_color',
            array(
                'type'              => 'string',
                'description'       => __( 'Marker or polygon color (hex)', 'wp-interactive-maps' ),
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => 'sanitize_hex_color',
                'auth_callback'     => array( 'WIM_Sanitization', 'user_can_edit_maps' ),
            )
        );

        register_post_meta(
            'wim_location',
            '_wim_location_images',
            array(
                'type'              => 'string',
                'description'       => __( 'Location image attachment IDs as JSON array', 'wp-interactive-maps' ),
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => 'sanitize_text_field',
                'auth_callback'     => array( 'WIM_Sanitization', 'user_can_edit_maps' ),
            )
        );
    }
}

// Initialize the post types class
new WIM_Post_Types();
