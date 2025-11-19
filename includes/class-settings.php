<?php
/**
 * Plugin Settings Page
 *
 * @package WP_Interactive_Maps
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Class WIM_Settings
 * Handles plugin settings page and options.
 */
class WIM_Settings {
    
    /**
     * Settings option name.
     */
    const OPTION_NAME = 'wim_settings';
    
    /**
     * Initialize the class.
     */
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
    }
    
    /**
     * Add settings page to WordPress admin menu.
     */
    public function add_settings_page() {
        add_options_page(
            __( 'Interactive Maps Settings', 'wp-interactive-maps' ),
            __( 'Interactive Maps', 'wp-interactive-maps' ),
            'manage_options',
            'wim-settings',
            array( $this, 'render_settings_page' )
        );
    }
    
    /**
     * Register settings, sections, and fields.
     */
    public function register_settings() {
        // Register the main settings option
        register_setting(
            'wim_settings_group',
            self::OPTION_NAME,
            array( $this, 'sanitize_settings' )
        );
        
        // Marker Settings Section
        add_settings_section(
            'wim_marker_section',
            __( 'Default Marker Settings', 'wp-interactive-maps' ),
            array( $this, 'render_marker_section' ),
            'wim-settings'
        );
        
        add_settings_field(
            'marker_color',
            __( 'Default Marker Color', 'wp-interactive-maps' ),
            array( $this, 'render_color_field' ),
            'wim-settings',
            'wim_marker_section',
            array( 'field' => 'marker_color' )
        );
        
        // Area Settings Section
        add_settings_section(
            'wim_area_section',
            __( 'Default Area Settings', 'wp-interactive-maps' ),
            array( $this, 'render_area_section' ),
            'wim-settings'
        );
        
        add_settings_field(
            'area_fill_color',
            __( 'Area Fill Color', 'wp-interactive-maps' ),
            array( $this, 'render_color_field' ),
            'wim-settings',
            'wim_area_section',
            array( 'field' => 'area_fill_color' )
        );
        
        add_settings_field(
            'area_fill_opacity',
            __( 'Area Fill Opacity', 'wp-interactive-maps' ),
            array( $this, 'render_opacity_field' ),
            'wim-settings',
            'wim_area_section',
            array( 'field' => 'area_fill_opacity' )
        );
        
        add_settings_field(
            'area_stroke_color',
            __( 'Area Stroke Color', 'wp-interactive-maps' ),
            array( $this, 'render_color_field' ),
            'wim-settings',
            'wim_area_section',
            array( 'field' => 'area_stroke_color' )
        );
        
        // Display Settings Section
        add_settings_section(
            'wim_display_section',
            __( 'Display Settings', 'wp-interactive-maps' ),
            array( $this, 'render_display_section' ),
            'wim-settings'
        );
        
        add_settings_field(
            'default_layout',
            __( 'Default Layout', 'wp-interactive-maps' ),
            array( $this, 'render_layout_field' ),
            'wim-settings',
            'wim_display_section',
            array( 'field' => 'default_layout' )
        );
        
        add_settings_field(
            'custom_css',
            __( 'Custom CSS', 'wp-interactive-maps' ),
            array( $this, 'render_textarea_field' ),
            'wim-settings',
            'wim_display_section',
            array( 'field' => 'custom_css' )
        );
    }
    
    /**
     * Enqueue admin assets for settings page.
     */
    public function enqueue_admin_assets( $hook ) {
        // Only load on our settings page
        if ( 'settings_page_wim-settings' !== $hook ) {
            return;
        }
        
        // Enqueue WordPress color picker
        wp_enqueue_style( 'wp-color-picker' );
        wp_enqueue_script( 'wp-color-picker' );
        
        // Enqueue custom script for color picker initialization
        wp_add_inline_script(
            'wp-color-picker',
            'jQuery(document).ready(function($) { $(".wim-color-picker").wpColorPicker(); });'
        );
    }
    
    /**
     * Render the settings page.
     */
    public function render_settings_page() {
        // Check user permissions
        if ( ! WIM_Sanitization::user_can_manage_settings() ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wp-interactive-maps' ) );
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
            <form action="options.php" method="post">
                <?php
                settings_fields( 'wim_settings_group' );
                do_settings_sections( 'wim-settings' );
                submit_button( __( 'Save Settings', 'wp-interactive-maps' ) );
                ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Render marker section description.
     */
    public function render_marker_section() {
        echo '<p>' . esc_html__( 'Configure default styling for place-type locations (markers).', 'wp-interactive-maps' ) . '</p>';
    }
    
    /**
     * Render area section description.
     */
    public function render_area_section() {
        echo '<p>' . esc_html__( 'Configure default styling for area-type locations (polygons).', 'wp-interactive-maps' ) . '</p>';
    }
    
    /**
     * Render display section description.
     */
    public function render_display_section() {
        echo '<p>' . esc_html__( 'Configure default display options for maps.', 'wp-interactive-maps' ) . '</p>';
    }
    
    /**
     * Render color picker field.
     */
    public function render_color_field( $args ) {
        $settings = $this->get_settings();
        $field = $args['field'];
        $value = isset( $settings[ $field ] ) ? $settings[ $field ] : $this->get_default_value( $field );
        ?>
        <input 
            type="text" 
            name="<?php echo esc_attr( self::OPTION_NAME . '[' . $field . ']' ); ?>" 
            value="<?php echo esc_attr( $value ); ?>" 
            class="wim-color-picker" 
        />
        <?php
    }
    
    /**
     * Render opacity slider field.
     */
    public function render_opacity_field( $args ) {
        $settings = $this->get_settings();
        $field = $args['field'];
        $value = isset( $settings[ $field ] ) ? $settings[ $field ] : $this->get_default_value( $field );
        ?>
        <input 
            type="range" 
            name="<?php echo esc_attr( self::OPTION_NAME . '[' . $field . ']' ); ?>" 
            value="<?php echo esc_attr( $value ); ?>" 
            min="0" 
            max="100" 
            step="5"
            oninput="this.nextElementSibling.value = this.value + '%'"
        />
        <output><?php echo esc_html( $value ); ?>%</output>
        <?php
    }
    
    /**
     * Render layout selector field.
     */
    public function render_layout_field( $args ) {
        $settings = $this->get_settings();
        $field = $args['field'];
        $value = isset( $settings[ $field ] ) ? $settings[ $field ] : $this->get_default_value( $field );
        ?>
        <select name="<?php echo esc_attr( self::OPTION_NAME . '[' . $field . ']' ); ?>">
            <option value="side" <?php selected( $value, 'side' ); ?>>
                <?php esc_html_e( 'Side Panel', 'wp-interactive-maps' ); ?>
            </option>
            <option value="popup" <?php selected( $value, 'popup' ); ?>>
                <?php esc_html_e( 'Popup Overlay', 'wp-interactive-maps' ); ?>
            </option>
        </select>
        <?php
    }
    
    /**
     * Render textarea field.
     */
    public function render_textarea_field( $args ) {
        $settings = $this->get_settings();
        $field = $args['field'];
        $value = isset( $settings[ $field ] ) ? $settings[ $field ] : $this->get_default_value( $field );
        ?>
        <textarea 
            name="<?php echo esc_attr( self::OPTION_NAME . '[' . $field . ']' ); ?>" 
            rows="10" 
            cols="50" 
            class="large-text code"
        ><?php echo esc_textarea( $value ); ?></textarea>
        <p class="description">
            <?php esc_html_e( 'Add custom CSS to style your interactive maps.', 'wp-interactive-maps' ); ?>
        </p>
        <?php
    }
    
    /**
     * Sanitize settings before saving.
     */
    public function sanitize_settings( $input ) {
        // Check user permissions
        if ( ! WIM_Sanitization::user_can_manage_settings() ) {
            add_settings_error(
                self::OPTION_NAME,
                'permission_denied',
                __( 'You do not have permission to modify these settings.', 'wp-interactive-maps' ),
                'error'
            );
            return get_option( self::OPTION_NAME, array() );
        }
        
        $sanitized = array();
        $defaults = self::get_default_settings();
        
        // Sanitize marker color
        if ( isset( $input['marker_color'] ) ) {
            $color = WIM_Sanitization::sanitize_color( $input['marker_color'] );
            $sanitized['marker_color'] = ( false !== $color ) ? $color : $defaults['marker_color'];
        }
        
        // Sanitize area colors
        if ( isset( $input['area_fill_color'] ) ) {
            $color = WIM_Sanitization::sanitize_color( $input['area_fill_color'] );
            $sanitized['area_fill_color'] = ( false !== $color ) ? $color : $defaults['area_fill_color'];
        }
        
        if ( isset( $input['area_stroke_color'] ) ) {
            $color = WIM_Sanitization::sanitize_color( $input['area_stroke_color'] );
            $sanitized['area_stroke_color'] = ( false !== $color ) ? $color : $defaults['area_stroke_color'];
        }
        
        // Sanitize opacity (ensure it's between 0-100)
        if ( isset( $input['area_fill_opacity'] ) ) {
            $opacity = absint( $input['area_fill_opacity'] );
            $sanitized['area_fill_opacity'] = min( 100, max( 0, $opacity ) );
        }
        
        // Sanitize layout
        if ( isset( $input['default_layout'] ) ) {
            $layout = sanitize_text_field( $input['default_layout'] );
            $sanitized['default_layout'] = in_array( $layout, array( 'side', 'popup' ), true ) 
                ? $layout 
                : $defaults['default_layout'];
        }
        
        // Sanitize custom CSS
        if ( isset( $input['custom_css'] ) ) {
            $sanitized['custom_css'] = wp_strip_all_tags( $input['custom_css'] );
        }
        
        return $sanitized;
    }
    
    /**
     * Get settings with defaults.
     */
    public static function get_settings() {
        $defaults = self::get_default_settings();
        $settings = get_option( self::OPTION_NAME, array() );
        
        return wp_parse_args( $settings, $defaults );
    }
    
    /**
     * Get default settings values.
     */
    public static function get_default_settings() {
        return array(
            'marker_color'       => '#ff6600',
            'area_fill_color'    => '#3388ff',
            'area_stroke_color'  => '#0055cc',
            'area_fill_opacity'  => 30,
            'default_layout'     => 'side',
            'custom_css'         => '',
        );
    }
    
    /**
     * Get default value for a specific field.
     */
    private function get_default_value( $field ) {
        $defaults = self::get_default_settings();
        return isset( $defaults[ $field ] ) ? $defaults[ $field ] : '';
    }
    
    /**
     * Get a specific setting value.
     */
    public static function get_setting( $key, $default = '' ) {
        $settings = self::get_settings();
        return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
    }
}
