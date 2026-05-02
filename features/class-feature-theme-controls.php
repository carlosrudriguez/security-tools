<?php
/**
 * Security Tools - Theme Controls Feature
 *
 * Disables theme installation, activation, customization, and editing.
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
 * Class Security_Tools_Feature_Theme_Controls
 *
 * Implements theme control restrictions.
 *
 * @since 1.2
 */
class Security_Tools_Feature_Theme_Controls {

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
        if ( ! Security_Tools_Utils::get_bool_option( Security_Tools_Utils::OPTION_DISABLE_THEME_CONTROLS ) ) {
            return;
        }

        // Menu and page restrictions
        add_action( 'admin_menu', array( $this, 'remove_menus' ), 999 );
        add_action( 'admin_init', array( $this, 'block_pages' ) );

        // UI modifications - v1.3: Consolidated into single hook
        add_action( 'admin_enqueue_scripts', array( $this, 'hide_management_ui' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'hide_menus_js' ) );

        // Block actions
        add_action( 'admin_init', array( $this, 'block_actions' ), 1 );

        // Enforce the policy for code paths that check capabilities directly.
        add_filter( 'user_has_cap', array( $this, 'filter_theme_capabilities' ), 10, 4 );
    }

    /**
     * Remove theme-related menu items
     *
     * @since 1.2
     */
    public function remove_menus() {
        remove_submenu_page( 'themes.php', 'theme-install.php' );
        remove_submenu_page( 'themes.php', 'theme-editor.php' );
        remove_submenu_page( 'themes.php', 'customize.php' );
        remove_menu_page( 'site-editor.php' );

        // Remove customize from submenu
        global $submenu;
        if ( isset( $submenu['themes.php'] ) ) {
            foreach ( $submenu['themes.php'] as $key => $item ) {
                if ( isset( $item[2] ) && (
                    strpos( $item[2], 'site-editor' ) !== false ||
                    strpos( $item[2], 'customize' ) !== false
                ) && $item[2] !== 'widgets.php' ) {
                    unset( $submenu['themes.php'][ $key ] );
                }
            }
        }
    }

    /**
     * Block access to theme management pages
     *
     * @since 1.2
     */
    public function block_pages() {
        if ( Security_Tools_Utils::is_settings_page() ) {
            return;
        }

        global $pagenow;

        $blocked = array( 'theme-install.php', 'theme-editor.php', 'site-editor.php', 'customize.php' );

        if ( in_array( $pagenow, $blocked, true ) ) {
            wp_safe_redirect( admin_url( 'themes.php' ) );
            exit;
        }
    }

    /**
     * Hide theme management UI elements
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

        // CSS for themes list page (previously in hide_add_button and hide_management_ui)
        if ( $screen->id === 'themes' ) {
            $css = '
                /* Hide Add New button and related - previously in hide_add_button() */
                .page-title-action[href*="theme-install.php"],
                a[href*="theme-install.php"],
                .theme-browser .theme.add-new-theme { display: none !important; }
                
                /* Hide management UI elements - previously in hide_management_ui() */
                .theme-actions, .theme .theme-actions,
                .theme-overlay .theme-actions,
                .activate, .delete-theme, .load-customize, .preview,
                .theme-overlay .theme-actions .delete-theme { display: none !important; }
                .theme:not(.active) .theme-screenshot { pointer-events: none !important; }
            ';
        }

        // CSS for theme install page (in case someone navigates there)
        if ( $screen->id === 'theme-install' ) {
            $css = '
                .theme-install { display: none !important; }
            ';
        }

        if ( empty( $css ) ) {
            return;
        }

        wp_register_style( 'security-tools-hide-theme-mgmt', false );
        wp_enqueue_style( 'security-tools-hide-theme-mgmt' );
        wp_add_inline_style( 'security-tools-hide-theme-mgmt', $css );
    }

    /**
     * Hide theme menus with JavaScript
     *
     * @since 1.2
     */
    public function hide_menus_js() {
        if ( ! is_admin() ) {
            return;
        }

        wp_enqueue_script(
            'security-tools-hide-theme-menus',
            SECURITY_TOOLS_URL . 'assets/js/hide-theme-menus.js',
            array( 'jquery' ),
            SECURITY_TOOLS_VERSION,
            true
        );
    }

    /**
     * Block theme management actions
     *
     * @since 1.2
     */
    public function block_actions() {
        if ( Security_Tools_Utils::is_settings_page() ) {
            return;
        }

        global $pagenow;

        $theme_management_pages = array(
            'themes.php',
            'theme-install.php',
            'theme-editor.php',
            'customize.php',
            'site-editor.php',
            'update.php',
        );

        if ( ! in_array( $pagenow, $theme_management_pages, true ) ) {
            return;
        }

        $action  = $this->get_request_action( 'action' );
        $action2 = $this->get_request_action( 'action2' );

        $blocked_single_actions = array(
            'activate',
            'delete',
            'install-theme',
            'upload-theme',
        );

        if ( in_array( $action, $blocked_single_actions, true ) ) {
            wp_die( __( 'Theme management has been disabled by site policy.', 'security-tools' ), '', array( 'back_link' => true ) );
        }

        $blocked_bulk_actions = array(
            'delete-selected-themes',
        );

        if ( in_array( $action, $blocked_bulk_actions, true ) || in_array( $action2, $blocked_bulk_actions, true ) ) {
            wp_die( __( 'Theme management has been disabled by site policy.', 'security-tools' ), '', array( 'back_link' => true ) );
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
     * Remove theme-management capabilities while controls are disabled.
     *
     * @since 2.6
     * @param array $allcaps User capability map.
     * @param array $caps    Primitive capabilities being checked.
     * @param array $args    Requested capability arguments.
     * @param mixed $user    WP_User instance.
     * @return array
     */
    public function filter_theme_capabilities( $allcaps, $caps, $args, $user ) {
        $blocked_caps = array(
            'customize',
            'delete_themes',
            'edit_css',
            'edit_themes',
            'install_themes',
            'upload_themes',
        );

        foreach ( $blocked_caps as $cap ) {
            $allcaps[ $cap ] = false;
        }

        return $allcaps;
    }
}
