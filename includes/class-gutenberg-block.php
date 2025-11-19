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
        // Register the block using block.json
        register_block_type( WIM_PLUGIN_DIR . 'blocks/map-block' );
    }
}

// Initialize the Gutenberg block
new WIM_Gutenberg_Block();
