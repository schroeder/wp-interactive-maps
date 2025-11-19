<?php
/**
 * Sanitization and Validation Helpers
 *
 * Provides sanitization and validation functions for plugin data.
 *
 * @package WP_Interactive_Maps
 * @since 1.0.0
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

/**
 * Class WIM_Sanitization
 *
 * Handles sanitization and validation of plugin data.
 */
class WIM_Sanitization {

    /**
     * Sanitize and validate place coordinates.
     *
     * @param mixed $x X coordinate value.
     * @param mixed $y Y coordinate value.
     * @return array|false Array with sanitized coordinates or false if invalid.
     */
    public static function sanitize_place_coordinates( $x, $y ) {
        // Validate that both values are numeric
        if ( ! is_numeric( $x ) || ! is_numeric( $y ) ) {
            return false;
        }

        // Convert to float and validate range (must be positive)
        $x = floatval( $x );
        $y = floatval( $y );

        if ( $x < 0 || $y < 0 ) {
            return false;
        }

        return array(
            'x' => $x,
            'y' => $y,
        );
    }

    /**
     * Sanitize and validate area polygon coordinates.
     *
     * @param mixed $points Array of coordinate points or JSON string.
     * @return array|false Array with sanitized points or false if invalid.
     */
    public static function sanitize_area_coordinates( $points ) {
        // If it's a string, try to decode as JSON
        if ( is_string( $points ) ) {
            $points = json_decode( $points, true );
        }

        // Validate that points is an array
        if ( ! is_array( $points ) || empty( $points ) ) {
            return false;
        }

        // Validate minimum number of points (at least 3 for a polygon)
        if ( count( $points ) < 3 ) {
            return false;
        }

        // Validate and sanitize each point
        $sanitized_points = array();
        foreach ( $points as $point ) {
            // Each point must be an array with exactly 2 elements
            if ( ! is_array( $point ) || count( $point ) !== 2 ) {
                return false;
            }

            // Both coordinates must be numeric
            if ( ! is_numeric( $point[0] ) || ! is_numeric( $point[1] ) ) {
                return false;
            }

            // Convert to float and validate range
            $x = floatval( $point[0] );
            $y = floatval( $point[1] );

            if ( $x < 0 || $y < 0 ) {
                return false;
            }

            $sanitized_points[] = array( $x, $y );
        }

        return array( 'points' => $sanitized_points );
    }

    /**
     * Sanitize and validate color value.
     *
     * @param string $color Color value (hex format).
     * @return string|false Sanitized color or false if invalid.
     */
    public static function sanitize_color( $color ) {
        // Use WordPress built-in sanitize_hex_color function
        $sanitized = sanitize_hex_color( $color );

        // If sanitization failed, return false
        if ( null === $sanitized || '' === $sanitized ) {
            return false;
        }

        return $sanitized;
    }

    /**
     * Sanitize and validate image IDs array.
     *
     * @param mixed $images Array of image IDs or JSON string.
     * @return array Sanitized array of valid image IDs.
     */
    public static function sanitize_image_ids( $images ) {
        // If it's a string, try to decode as JSON
        if ( is_string( $images ) ) {
            $images = json_decode( $images, true );
        }

        // Ensure it's an array
        if ( ! is_array( $images ) ) {
            return array();
        }

        // Sanitize each ID and verify it's a valid attachment
        $sanitized_ids = array();
        foreach ( $images as $image_id ) {
            $id = absint( $image_id );
            
            // Verify the attachment exists and is an image
            if ( $id > 0 && wp_attachment_is_image( $id ) ) {
                $sanitized_ids[] = $id;
            }
        }

        return $sanitized_ids;
    }

    /**
     * Sanitize location type.
     *
     * @param string $type Location type value.
     * @return string|false Sanitized type or false if invalid.
     */
    public static function sanitize_location_type( $type ) {
        $type = sanitize_text_field( $type );
        
        // Only allow 'place' or 'area'
        if ( ! in_array( $type, array( 'place', 'area' ), true ) ) {
            return false;
        }

        return $type;
    }

    /**
     * Sanitize and validate map ID.
     *
     * @param mixed $map_id Map post ID.
     * @return int|false Sanitized map ID or false if invalid.
     */
    public static function sanitize_map_id( $map_id ) {
        $id = absint( $map_id );

        // Verify the post exists and is a map
        if ( $id <= 0 ) {
            return false;
        }

        $post = get_post( $id );
        if ( ! $post || 'wim_map' !== $post->post_type ) {
            return false;
        }

        return $id;
    }

    /**
     * Sanitize and validate attachment ID.
     *
     * @param mixed $attachment_id Attachment post ID.
     * @return int|false Sanitized attachment ID or false if invalid.
     */
    public static function sanitize_attachment_id( $attachment_id ) {
        $id = absint( $attachment_id );

        // Verify the attachment exists
        if ( $id <= 0 ) {
            return false;
        }

        $attachment = get_post( $id );
        if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
            return false;
        }

        return $id;
    }

    /**
     * Sanitize coordinates JSON string.
     *
     * @param string $coordinates_json JSON string of coordinates.
     * @param string $type Location type ('place' or 'area').
     * @return string|false Sanitized JSON string or false if invalid.
     */
    public static function sanitize_coordinates_json( $coordinates_json, $type ) {
        $coordinates = json_decode( $coordinates_json, true );

        if ( null === $coordinates ) {
            return false;
        }

        if ( 'place' === $type ) {
            if ( ! isset( $coordinates['x'] ) || ! isset( $coordinates['y'] ) ) {
                return false;
            }
            
            $sanitized = self::sanitize_place_coordinates( $coordinates['x'], $coordinates['y'] );
        } else {
            if ( ! isset( $coordinates['points'] ) ) {
                return false;
            }
            
            $sanitized = self::sanitize_area_coordinates( $coordinates['points'] );
        }

        if ( false === $sanitized ) {
            return false;
        }

        return wp_json_encode( $sanitized );
    }

    /**
     * Validate nonce for admin forms.
     *
     * @param string $nonce Nonce value.
     * @param string $action Nonce action.
     * @return bool True if valid, false otherwise.
     */
    public static function verify_nonce( $nonce, $action ) {
        return wp_verify_nonce( $nonce, $action );
    }

    /**
     * Check if user has capability to edit maps.
     *
     * @param int $post_id Optional post ID to check specific post permissions.
     * @return bool True if user has capability, false otherwise.
     */
    public static function user_can_edit_maps( $post_id = 0 ) {
        if ( $post_id > 0 ) {
            return current_user_can( 'edit_post', $post_id );
        }
        
        return current_user_can( 'edit_posts' );
    }

    /**
     * Check if user has capability to manage plugin settings.
     *
     * @return bool True if user has capability, false otherwise.
     */
    public static function user_can_manage_settings() {
        return current_user_can( 'manage_options' );
    }
}

