<?php
/**
 * Security Tools - Plugin Controls Feature
 *
 * Disables plugin installation, activation, deactivation, and editing.
 *
 * @package    Security_Tools
 * @subpackage Features
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

        // Enforce the policy for code paths that check capabilities directly.
        add_filter( 'user_has_cap', array( $this, 'filter_plugin_capabilities' ), 10, 4 );
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

        global $pagenow;

        if ( ! in_array( $pagenow, array( 'plugins.php', 'plugin-install.php', 'plugin-editor.php', 'update.php' ), true ) ) {
            return;
        }

        $action  = $this->get_request_action( 'action' );
        $action2 = $this->get_request_action( 'action2' );

        $blocked_single_actions = array(
            'activate',
            'deactivate',
            'delete',
            'update-plugin',
            'upgrade-plugin',
            'install-plugin',
            'upload-plugin',
        );

        if ( in_array( $action, $blocked_single_actions, true ) ) {
            wp_die( __( 'Plugin management has been disabled by site policy.', 'security-tools' ), '', array( 'back_link' => true ) );
        }

        $blocked_bulk_actions = array(
            'activate-selected', 'deactivate-selected', 'delete-selected',
            'update-selected', 'enable-auto-update-selected', 'disable-auto-update-selected'
        );

        if ( in_array( $action, $blocked_bulk_actions, true ) || in_array( $action2, $blocked_bulk_actions, true ) ) {
            wp_die( __( 'Plugin management has been disabled by site policy.', 'security-tools' ), '', array( 'back_link' => true ) );
        }
    }

    /**
     * Get a sanitized admin request action.
     *
     * @since 2.6
     * @param string $key Request key.
     * @return string
     */
    private function get_request_action( $key ) {
        if ( isset( $_POST[ $key ] ) ) {
            return sanitize_key( wp_unslash( $_POST[ $key ] ) );
        }

        if ( isset( $_GET[ $key ] ) ) {
            return sanitize_key( wp_unslash( $_GET[ $key ] ) );
        }

        return '';
    }

    /**
     * Remove plugin-management capabilities while controls are disabled.
     *
     * @since 2.6
     * @param array $allcaps User capability map.
     * @param array $caps    Primitive capabilities being checked.
     * @param array $args    Requested capability arguments.
     * @param mixed $user    WP_User instance.
     * @return array
     */
    public function filter_plugin_capabilities( $allcaps, $caps, $args, $user ) {
        $blocked_caps = array(
            'delete_plugins',
            'edit_plugins',
            'install_plugins',
            'upload_plugins',
            'update_plugins',
        );

        foreach ( $blocked_caps as $cap ) {
            $allcaps[ $cap ] = false;
        }

        return $allcaps;
    }
}
