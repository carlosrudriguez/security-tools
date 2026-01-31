<?php
/**
 * Security Tools - Hide Plugins Feature
 *
 * Hides selected plugins from the Plugins list.
 *
 * @package    Security_Tools
 * @subpackage Features
 * @version    2.5
 * @author     Carlos Rodríguez
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Class Security_Tools_Feature_Hide_Plugins
 *
 * Implements plugin hiding functionality.
 *
 * @since 1.2
 */
class Security_Tools_Feature_Hide_Plugins {

    /**
     * Constructor - Register hooks
     *
     * @since 1.2
     */
    public function __construct() {
        add_filter( 'all_plugins', array( $this, 'filter_plugins' ) );
    }

    /**
     * Filter hidden plugins from the plugins list
     *
     * @since 1.2
     * @param array $plugins All plugins
     * @return array Filtered plugins
     */
    public function filter_plugins( $plugins ) {
        // Don't filter on Security Tools settings page
        if ( Security_Tools_Utils::is_settings_page() ) {
            return $plugins;
        }

        $hidden = Security_Tools_Utils::get_array_option( Security_Tools_Utils::OPTION_HIDDEN_PLUGINS );

        if ( empty( $hidden ) ) {
            return $plugins;
        }

        foreach ( $hidden as $plugin_path ) {
            unset( $plugins[ $plugin_path ] );
        }

        return $plugins;
    }
}
