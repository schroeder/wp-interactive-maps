<?php
/**
 * Plugin Name: WP Interactive Maps
 * Plugin URI: https://example.com/wp-interactive-maps
 * Description: Create interactive maps with clickable locations (points and areas) for WordPress sites
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-interactive-maps
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Plugin version.
 */
define( 'WIM_VERSION', '1.0.0' );

/**
 * Plugin root directory path.
 */
define( 'WIM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * Plugin root directory URL.
 */
define( 'WIM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin basename.
 */
define( 'WIM_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Activation hook.
 * Runs when the plugin is activated.
 */
function wim_activate() {
    // Load required files for activation
    require_once WIM_PLUGIN_DIR . 'includes/class-sanitization.php';
    require_once WIM_PLUGIN_DIR . 'includes/class-post-types.php';
    
    // Trigger post type registration
    $post_types = new WIM_Post_Types();
    $post_types->register_post_types();
    $post_types->register_meta_fields();
    
    // Flush rewrite rules to register custom post types
    flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'wim_activate' );

/**
 * Deactivation hook.
 * Runs when the plugin is deactivated.
 */
function wim_deactivate() {
    // Flush rewrite rules to clean up custom post types
    flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'wim_deactivate' );

/**
 * Initialize the plugin.
 * Load all required files and initialize components.
 */
function wim_init() {
    // Load plugin classes
    require_once WIM_PLUGIN_DIR . 'includes/class-sanitization.php';
    require_once WIM_PLUGIN_DIR . 'includes/class-post-types.php';
    require_once WIM_PLUGIN_DIR . 'includes/class-meta-boxes.php';
    require_once WIM_PLUGIN_DIR . 'includes/class-rest-api.php';
    require_once WIM_PLUGIN_DIR . 'includes/class-shortcode.php';
    require_once WIM_PLUGIN_DIR . 'includes/class-gutenberg-block.php';
    require_once WIM_PLUGIN_DIR . 'includes/class-settings.php';
    
    // Note: All classes are instantiated within their respective files
    // This ensures proper initialization order and hook registration
}
add_action( 'plugins_loaded', 'wim_init' );

/**
 * Enqueue admin scripts and styles.
 */
function wim_enqueue_admin_assets( $hook ) {
    // Only load on our post type edit screens
    global $post_type;
    if ( ! in_array( $post_type, array( 'wim_map', 'wim_location' ) ) ) {
        return;
    }
    
    wp_enqueue_style(
        'wim-admin-styles',
        WIM_PLUGIN_URL . 'admin/css/admin-styles.css',
        array(),
        WIM_VERSION
    );
    
    wp_enqueue_script(
        'wim-map-editor',
        WIM_PLUGIN_URL . 'admin/js/map-editor.js',
        array( 'jquery' ),
        WIM_VERSION,
        true
    );
}
add_action( 'admin_enqueue_scripts', 'wim_enqueue_admin_assets' );

/**
 * Enqueue frontend scripts and styles.
 * Only enqueues when needed (on pages with maps).
 */
function wim_enqueue_frontend_assets() {
    // Check if we're on a page that might have maps
    // This will be called by shortcode/block when needed
    if ( ! is_admin() ) {
        wp_register_style(
            'wim-map-display',
            WIM_PLUGIN_URL . 'public/css/map-display.css',
            array(),
            WIM_VERSION
        );
        
        wp_register_script(
            'wim-map-display',
            WIM_PLUGIN_URL . 'public/js/map-display.js',
            array(),
            WIM_VERSION,
            true
        );
        
        // Get plugin settings
        $settings = WIM_Settings::get_settings();
        
        // Localize script with REST API URL and settings
        wp_localize_script(
            'wim-map-display',
            'wimData',
            array(
                'restUrl' => rest_url( 'wim/v1/' ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
                'settings' => array(
                    'markerColor' => $settings['marker_color'],
                    'areaFillColor' => $settings['area_fill_color'],
                    'areaStrokeColor' => $settings['area_stroke_color'],
                    'areaFillOpacity' => $settings['area_fill_opacity'] / 100,
                    'defaultLayout' => $settings['default_layout'],
                )
            )
        );
        
        // Add custom CSS if provided
        if ( ! empty( $settings['custom_css'] ) ) {
            wp_add_inline_style( 'wim-map-display', $settings['custom_css'] );
        }
    }
}
add_action( 'wp_enqueue_scripts', 'wim_enqueue_frontend_assets' );
