<?php
/**
 * Gutenberg Block Registration
 *
 * @package WP_Interactive_Maps
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Class WIM_Gutenberg_Block
 * Handles Gutenberg block registration for interactive maps.
 */
class WIM_Gutenberg_Block {
    
    /**
     * Initialize the class.
     */
    public function __construct() {
        add_action( 'init', array( $this, 'register_block' ) );
    }
    
    /**
     * Register the interactive map Gutenberg block.
     */
    public function register_block() {
        // Register the block using block.json with render callback
        register_block_type( WIM_PLUGIN_DIR . 'blocks/map-block', array(
            'render_callback' => array( $this, 'render_block' ),
        ) );
    }
    
    /**
     * Render the block on the frontend.
     *
     * @param array $attributes Block attributes.
     * @return string Block HTML output.
     */
    public function render_block( $attributes ) {
        $map_id = isset( $attributes['mapId'] ) ? absint( $attributes['mapId'] ) : 0;
        $layout = isset( $attributes['layout'] ) ? sanitize_text_field( $attributes['layout'] ) : 'side';
        
        // Don't render if no map is selected
        if ( ! $map_id ) {
            return '';
        }
        
        // Use WordPress do_shortcode to render the map
        return do_shortcode( sprintf(
            '[interactive_map id="%d" layout="%s"]',
            $map_id,
            $layout
        ) );
    }
}

// Initialize the Gutenberg block
new WIM_Gutenberg_Block();
