<?php
/**
 * Security Tools - Plugin Controls Feature
 *
 * Disables plugin installation, activation, deactivation, and editing.
 *
 * @package    Security_Tools
 * @subpackage Features
 * @version    2.5
 * @author     Carlos Rodríguez
 *
 * CHANGELOG v1.3:
 * - Consolidated hide_add_button() CSS into hide_management_ui() (Issue #2)
 * - All inline CSS now uses wp_add_inline_style() for CSP compliance
 * - Removed separate hide_add_button() hook, merged into hide_management_ui()
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Class Security_Tools_Feature_Plugin_Controls
 *
 * Implements plugin control restrictions.
 *
 * @since 1.2
 */
class Security_Tools_Feature_Plugin_Controls {

    /**
     * Constructor - Register hooks if enabled
     *
     * @since 1.2
     */
    public function __construct() {
        add_action( 'init', array( $this, 'maybe_disable_controls' ) );
    }

    /**
     * Check if feature should be activated
     *
     * v1.3: Removed separate hide_add_button hook, merged into hide_management_ui.
     *
     * @since 1.2
     * @since 1.3 Consolidated CSS hooks
     * @return void
     */
    public function maybe_disable_controls() {
        if ( ! Security_Tools_Utils::get_bool_option( Security_Tools_Utils::OPTION_DISABLE_PLUGIN_CONTROLS ) ) {
            return;
        }

        // Menu and page restrictions
        add_action( 'admin_menu', array( $this, 'remove_menus' ), 999 );
        add_action( 'admin_init', array( $this, 'block_pages' ) );

        // UI modifications - v1.3: Consolidated into single hook
        add_action( 'admin_enqueue_scripts', array( $this, 'hide_management_ui' ) );

        // Block actions
        add_action( 'admin_init', array( $this, 'block_actions' ), 1 );
    }

    /**
     * Remove plugin-related menu items
     *
     * @since 1.2
     */
    public function remove_menus() {
        remove_submenu_page( 'plugins.php', 'plugin-install.php' );
        remove_submenu_page( 'plugins.php', 'plugin-editor.php' );
    }

    /**
     * Block access to plugin management pages
     *
     * @since 1.2
     */
    public function block_pages() {
        if ( Security_Tools_Utils::is_settings_page() ) {
            return;
        }

        global $pagenow;

        if ( in_array( $pagenow, array( 'plugin-install.php', 'plugin-editor.php' ), true ) ) {
            wp_safe_redirect( admin_url( 'plugins.php' ) );
            exit;
        }
    }

    /**
     * Hide plugin management UI elements
     *
     * v1.3: Consolidated all CSS into this method (previously split between
     * hide_add_button() and hide_management_ui()). Now uses wp_add_inline_style()
     * for all styling, improving CSP compliance.
     *
     * @since 1.2
     * @since 1.3 Consolidated hide_add_button() CSS, now handles all pages
     */
    public function hide_management_ui() {
        $screen = get_current_screen();
        
        if ( ! $screen ) {
            return;
        }

        $css = '';

        // CSS for plugins list page (previously in hide_add_button and hide_management_ui)
        if ( $screen->id === 'plugins' ) {
            $css = '
                /* Hide Add New button - previously in hide_add_button() */
                .page-title-action[href*="plugin-install.php"],
                a[href*="plugin-install.php"] { display: none !important; }
                
                /* Hide management UI elements - previously in hide_management_ui() */
                .plugin-action-buttons,
                .row-actions .activate, .row-actions .deactivate, .row-actions .delete,
                .row-actions .update-now, .row-actions .enable-auto-update, .row-actions .disable-auto-update,
                .bulkactions, #bulk-action-selector-top, #bulk-action-selector-bottom,
                .plugin-update-tr, .update-message, .plugin-autoupdate,
                .check-column input[type="checkbox"],
                #doaction, #doaction2, #cb-select-all-1, #cb-select-all-2,
                .column-auto-updates { display: none !important; }
                .check-column { display: none !important; }
            ';
        }

        // CSS for plugin install page (in case someone navigates there)
        if ( $screen->id === 'plugin-install' ) {
            $css = '
                .plugin-action-buttons,
                .install-now { display: none !important; }
            ';
        }

        if ( empty( $css ) ) {
            return;
        }

        wp_register_style( 'security-tools-hide-plugin-mgmt', false );
        wp_enqueue_style( 'security-tools-hide-plugin-mgmt' );
        wp_add_inline_style( 'security-tools-hide-plugin-mgmt', $css );
    }

    /**
     * Block plugin management actions
     *
     * @since 1.2
     */
    public function block_actions() {
        if ( Security_Tools_Utils::is_settings_page() ) {
            return;
        }

        // Block GET actions
        if ( isset( $_GET['action'] ) ) {
            $blocked = array( 'activate', 'deactivate', 'delete', 'update-plugin' );
            if ( in_array( $_GET['action'], $blocked, true ) ) {
                wp_die( __( 'Plugin management has been disabled by Security Tools.', 'security-tools' ), '', array( 'back_link' => true ) );
            }
        }

        // Block POST actions
        $blocked_actions = array(
            'activate-selected', 'deactivate-selected', 'delete-selected',
            'update-selected', 'enable-auto-update-selected', 'disable-auto-update-selected'
        );

        foreach ( array( 'action', 'action2' ) as $action_key ) {
            if ( isset( $_POST[ $action_key ] ) && in_array( $_POST[ $action_key ], $blocked_actions, true ) ) {
                wp_die( __( 'Plugin management has been disabled by Security Tools.', 'security-tools' ), '', array( 'back_link' => true ) );
            }
        }
    }
}
