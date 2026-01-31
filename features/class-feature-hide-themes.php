<?php
/**
 * Security Tools - Hide Themes Feature
 *
 * Hides selected themes from the Themes list.
 *
 * @package    Security_Tools
 * @subpackage Features
 * @version    2.5
 * @author     Carlos Rodríguez
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Class Security_Tools_Feature_Hide_Themes
 *
 * Implements theme hiding functionality.
 *
 * @since 1.2
 */
class Security_Tools_Feature_Hide_Themes {

    /**
     * Constructor - Register hooks
     *
     * @since 1.2
     */
    public function __construct() {
        add_filter( 'wp_prepare_themes_for_js', array( $this, 'filter_themes' ) );
    }

    /**
     * Filter hidden themes from the themes list
     *
     * @since 1.2
     * @param array $themes Prepared themes array
     * @return array Filtered themes
     */
    public function filter_themes( $themes ) {
        // Don't filter on Security Tools settings page
        if ( Security_Tools_Utils::is_settings_page() ) {
            return $themes;
        }

        $hidden = Security_Tools_Utils::get_array_option( Security_Tools_Utils::OPTION_HIDDEN_THEMES );

        if ( empty( $hidden ) ) {
            return $themes;
        }

        foreach ( $hidden as $theme_slug ) {
            unset( $themes[ $theme_slug ] );
        }

        return $themes;
    }
}
