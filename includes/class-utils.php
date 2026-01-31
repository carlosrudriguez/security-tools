<?php
/**
 * Security Tools - Utility Functions
 *
 * Helper functions and utilities used throughout the plugin.
 * Contains option name constants and common helper methods.
 *
 * @package    Security_Tools
 * @subpackage Includes
 * @version    2.5
 * @author     Carlos Rodríguez
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Class Security_Tools_Utils
 *
 * Provides utility functions and constants used across the plugin.
 * All option names are centralized here for easy reference and maintenance.
 *
 * @since 1.2
 */
class Security_Tools_Utils {

    /**
     * ==========================================================================
     * OPTION NAME CONSTANTS
     * ==========================================================================
     * All WordPress option names used by the plugin.
     * Centralizing these prevents typos and makes refactoring easier.
     */

    // Branding
    const OPTION_CUSTOM_LEGEND    = 'security_tools_custom_legend';
    const OPTION_LOGIN_LOGO_ID    = 'security_tools_login_logo_id';    // Attachment ID for custom login logo
    const OPTION_LOGIN_LOGO_URL   = 'security_tools_login_logo_url';   // Custom URL for login logo link

    // General Settings (boolean toggles)
    const OPTION_AUTOHIDE_MENU = 'security_tools_autohide_menu';

    // System Controls (boolean toggles)
    const OPTION_DISABLE_UPDATES        = 'security_tools_disable_updates';
    const OPTION_DISABLE_EMAILS         = 'security_tools_disable_emails';
    const OPTION_DISABLE_EMAIL_CHECK    = 'security_tools_disable_email_check';
    const OPTION_HIDE_NOTICES           = 'security_tools_hide_notices';
    const OPTION_DISABLE_COMMENTS       = 'security_tools_disable_comments';
    const OPTION_DISABLE_PLUGIN_CONTROLS = 'security_tools_disable_plugin_controls';
    const OPTION_DISABLE_THEME_CONTROLS = 'security_tools_disable_theme_controls';
    const OPTION_DISABLE_FRONTEND_ADMIN_BAR = 'security_tools_disable_frontend_admin_bar';

    // Hide Login Page (string for slug, boolean for enabled state)
    const OPTION_HIDE_LOGIN_ENABLED = 'security_tools_hide_login_enabled';
    const OPTION_HIDE_LOGIN_SLUG    = 'security_tools_hide_login_slug';

    // Hide Features (array options)
    const OPTION_HIDDEN_ADMINS        = 'security_tools_hidden_admins';
    const OPTION_HIDDEN_PLUGINS       = 'security_tools_hidden_plugins';
    const OPTION_HIDDEN_THEMES        = 'security_tools_hidden_themes';
    const OPTION_HIDDEN_WIDGETS       = 'security_tools_hidden_widgets';
    const OPTION_HIDDEN_ADMIN_BAR     = 'security_tools_hidden_admin_bar';
    const OPTION_HIDDEN_ADMIN_BAR_CSS = 'security_tools_hidden_admin_bar_css'; // CSS-based hiding by element ID
    const OPTION_HIDDEN_METABOXES     = 'security_tools_hidden_metaboxes';

    // Discovered Metaboxes (array option for dynamic metabox detection)
    // Stores metaboxes discovered from post edit screens for the Metaboxes feature
    const OPTION_DISCOVERED_METABOXES = 'security_tools_discovered_metaboxes';

    // Change tracking options (used for admin notices)
    const OPTION_LEGEND_LAST_CHANGE              = 'security_tools_legend_last_change';
    const OPTION_ADMINS_LAST_CHANGE              = 'security_tools_last_changes';
    const OPTION_PLUGINS_LAST_CHANGE             = 'security_tools_plugins_last_changes';
    const OPTION_THEMES_LAST_CHANGE              = 'security_tools_themes_last_changes';
    const OPTION_WIDGETS_LAST_CHANGE             = 'security_tools_widgets_last_changes';
    const OPTION_HIDE_NOTICES_LAST_CHANGE        = 'security_tools_hide_notices_last_change';
    const OPTION_DISABLE_EMAIL_CHECK_LAST_CHANGE = 'security_tools_disable_email_check_last_change';
    const OPTION_DISABLE_UPDATES_LAST_CHANGE     = 'security_tools_disable_updates_last_change';
    const OPTION_DISABLE_EMAILS_LAST_CHANGE      = 'security_tools_disable_emails_last_change';
    const OPTION_DISABLE_COMMENTS_LAST_CHANGE    = 'security_tools_disable_comments_last_change';
    const OPTION_ADMIN_BAR_LAST_CHANGE           = 'security_tools_admin_bar_last_changes';
    const OPTION_ADMIN_BAR_CSS_LAST_CHANGE        = 'security_tools_admin_bar_css_last_changes';
    const OPTION_METABOXES_LAST_CHANGE           = 'security_tools_metaboxes_last_changes';
    const OPTION_DISABLE_FRONTEND_ADMIN_BAR_LAST_CHANGE = 'security_tools_disable_frontend_admin_bar_last_change';
    const OPTION_DISABLE_PLUGIN_CONTROLS_LAST_CHANGE = 'security_tools_disable_plugin_controls_last_change';
    const OPTION_DISABLE_THEME_CONTROLS_LAST_CHANGE  = 'security_tools_disable_theme_controls_last_change';
    const OPTION_AUTOHIDE_MENU_LAST_CHANGE           = 'security_tools_autohide_menu_last_change';
    const OPTION_HIDE_LOGIN_ENABLED_LAST_CHANGE      = 'security_tools_hide_login_enabled_last_change';
    const OPTION_HIDE_LOGIN_SLUG_LAST_CHANGE         = 'security_tools_hide_login_slug_last_change';
    const OPTION_LOGIN_LOGO_ID_LAST_CHANGE           = 'security_tools_login_logo_id_last_change';
    const OPTION_LOGIN_LOGO_URL_LAST_CHANGE          = 'security_tools_login_logo_url_last_change';
    const OPTION_RESET_SUCCESS                   = 'security_tools_reset_success';

    // Capability required for plugin settings
    const REQUIRED_CAPABILITY = 'manage_options';

    // Settings group name (legacy - kept for backwards compatibility)
    const SETTINGS_GROUP = 'security_tools_options_group';

    /**
     * ==========================================================================
     * SETTINGS GROUP CONSTANTS (Version 2.0)
     * ==========================================================================
     * Each subpage has its own settings group for independent saving.
     * @since 2.0
     */

    // Settings groups for each subpage
    const SETTINGS_GROUP_GENERAL         = 'security_tools_general_group';
    const SETTINGS_GROUP_BRANDING        = 'security_tools_branding_group';
    const SETTINGS_GROUP_SYSTEM_CONTROLS = 'security_tools_system_group';
    const SETTINGS_GROUP_ADMINS          = 'security_tools_admins_group';
    const SETTINGS_GROUP_PLUGINS         = 'security_tools_plugins_group';
    const SETTINGS_GROUP_THEMES          = 'security_tools_themes_group';
    const SETTINGS_GROUP_WIDGETS         = 'security_tools_widgets_group';
    const SETTINGS_GROUP_ADMIN_BAR       = 'security_tools_admin_bar_group';
    const SETTINGS_GROUP_METABOXES        = 'security_tools_metaboxes_group';

    /**
     * ==========================================================================
     * MENU AND PAGE SLUG CONSTANTS
     * ==========================================================================
     * Slugs for the top-level menu and all subpages.
     * @since 2.0
     */

    // Top-level menu slug
    const MENU_SLUG = 'security-tools';

    // Subpage slugs
    const PAGE_GENERAL         = 'security-tools';           // Default/main page
    const PAGE_BRANDING        = 'security-tools-branding';
    const PAGE_SYSTEM_CONTROLS = 'security-tools-system';
    const PAGE_ADMINS          = 'security-tools-admins';
    const PAGE_PLUGINS         = 'security-tools-plugins';
    const PAGE_THEMES          = 'security-tools-themes';
    const PAGE_WIDGETS         = 'security-tools-widgets';
    const PAGE_ADMIN_BAR       = 'security-tools-admin-bar';
    const PAGE_METABOXES        = 'security-tools-metaboxes';

    /**
     * ==========================================================================
     * HELPER METHODS
     * ==========================================================================
     */

    /**
     * Get all option names as an array
     *
     * Useful for bulk operations like resetting all settings.
     *
     * @since  1.2
     * @return array List of all main option names
     */
    public static function get_all_option_names() {
        return array(
            self::OPTION_CUSTOM_LEGEND,
            self::OPTION_DISABLE_UPDATES,
            self::OPTION_DISABLE_EMAILS,
            self::OPTION_DISABLE_EMAIL_CHECK,
            self::OPTION_HIDE_NOTICES,
            self::OPTION_DISABLE_COMMENTS,
            self::OPTION_DISABLE_PLUGIN_CONTROLS,
            self::OPTION_DISABLE_THEME_CONTROLS,
            self::OPTION_DISABLE_FRONTEND_ADMIN_BAR,
            self::OPTION_HIDDEN_ADMINS,
            self::OPTION_HIDDEN_PLUGINS,
            self::OPTION_HIDDEN_THEMES,
            self::OPTION_HIDDEN_WIDGETS,
            self::OPTION_HIDDEN_ADMIN_BAR,
            self::OPTION_HIDDEN_ADMIN_BAR_CSS,
            self::OPTION_HIDDEN_METABOXES,
            self::OPTION_DISCOVERED_METABOXES,
            self::OPTION_AUTOHIDE_MENU,
            self::OPTION_HIDE_LOGIN_ENABLED,
            self::OPTION_HIDE_LOGIN_SLUG,
            self::OPTION_LOGIN_LOGO_ID,
            self::OPTION_LOGIN_LOGO_URL,
        );
    }

    /**
     * Get all change tracking option names
     *
     * Used to clean up after saving settings.
     *
     * @since  1.2
     * @return array List of all change tracking option names
     */
    public static function get_all_change_tracking_options() {
        return array(
            self::OPTION_LEGEND_LAST_CHANGE,
            self::OPTION_ADMINS_LAST_CHANGE,
            self::OPTION_PLUGINS_LAST_CHANGE,
            self::OPTION_THEMES_LAST_CHANGE,
            self::OPTION_WIDGETS_LAST_CHANGE,
            self::OPTION_HIDE_NOTICES_LAST_CHANGE,
            self::OPTION_DISABLE_EMAIL_CHECK_LAST_CHANGE,
            self::OPTION_DISABLE_UPDATES_LAST_CHANGE,
            self::OPTION_DISABLE_EMAILS_LAST_CHANGE,
            self::OPTION_DISABLE_COMMENTS_LAST_CHANGE,
            self::OPTION_ADMIN_BAR_LAST_CHANGE,
            self::OPTION_ADMIN_BAR_CSS_LAST_CHANGE,
            self::OPTION_METABOXES_LAST_CHANGE,
            self::OPTION_DISABLE_FRONTEND_ADMIN_BAR_LAST_CHANGE,
            self::OPTION_DISABLE_PLUGIN_CONTROLS_LAST_CHANGE,
            self::OPTION_DISABLE_THEME_CONTROLS_LAST_CHANGE,
            self::OPTION_AUTOHIDE_MENU_LAST_CHANGE,
            self::OPTION_HIDE_LOGIN_ENABLED_LAST_CHANGE,
            self::OPTION_HIDE_LOGIN_SLUG_LAST_CHANGE,
            self::OPTION_LOGIN_LOGO_ID_LAST_CHANGE,
            self::OPTION_LOGIN_LOGO_URL_LAST_CHANGE,
        );
    }

    /**
     * Check if current user has required capability
     *
     * @since  1.2
     * @return bool True if user can manage plugin settings
     */
    public static function current_user_can_manage() {
        return current_user_can( self::REQUIRED_CAPABILITY );
    }

    /**
     * Get all page slugs as an array
     *
     * Useful for checking if we're on any Security Tools page.
     *
     * @since  2.0
     * @return array List of all page slugs
     */
    public static function get_all_page_slugs() {
        return array(
            self::PAGE_GENERAL,
            self::PAGE_BRANDING,
            self::PAGE_SYSTEM_CONTROLS,
            self::PAGE_ADMINS,
            self::PAGE_PLUGINS,
            self::PAGE_THEMES,
            self::PAGE_WIDGETS,
            self::PAGE_ADMIN_BAR,
            self::PAGE_METABOXES,
        );
    }

    /**
     * Check if we're on any Security Tools settings page
     *
     * @since  1.2
     * @since  2.0 Updated to check all subpages
     * @return bool True if on any settings page
     */
    public static function is_settings_page() {
        $screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
        
        // Check screen ID for any of our pages
        // Top-level pages use format: toplevel_page_{slug}
        // Subpages use format: {parent}_page_{slug}
        if ( $screen ) {
            $valid_screens = array(
                'toplevel_page_' . self::MENU_SLUG,
                'security-tools_page_' . self::PAGE_BRANDING,
                'security-tools_page_' . self::PAGE_SYSTEM_CONTROLS,
                'security-tools_page_' . self::PAGE_ADMINS,
                'security-tools_page_' . self::PAGE_PLUGINS,
                'security-tools_page_' . self::PAGE_THEMES,
                'security-tools_page_' . self::PAGE_WIDGETS,
                'security-tools_page_' . self::PAGE_ADMIN_BAR,
                'security-tools_page_' . self::PAGE_METABOXES,
            );
            
            if ( in_array( $screen->id, $valid_screens, true ) ) {
                return true;
            }
        }

        // Fallback check for early hooks where get_current_screen() isn't available
        if ( is_admin() && isset( $_GET['page'] ) ) {
            $page = sanitize_text_field( wp_unslash( $_GET['page'] ) );
            if ( in_array( $page, self::get_all_page_slugs(), true ) ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the current settings page slug
     *
     * @since  2.0
     * @return string|false Current page slug or false if not on a settings page
     */
    public static function get_current_page_slug() {
        if ( ! is_admin() || ! isset( $_GET['page'] ) ) {
            return false;
        }

        $page = sanitize_text_field( wp_unslash( $_GET['page'] ) );
        
        if ( in_array( $page, self::get_all_page_slugs(), true ) ) {
            return $page;
        }

        return false;
    }

    /**
     * Get a boolean option value
     *
     * @since  1.2
     * @param  string $option_name The option name constant
     * @return bool   The option value
     */
    public static function get_bool_option( $option_name ) {
        return (bool) get_option( $option_name, false );
    }

    /**
     * Get an array option value
     *
     * @since  1.2
     * @param  string $option_name The option name constant
     * @return array  The option value (empty array if not set)
     */
    public static function get_array_option( $option_name ) {
        $value = get_option( $option_name, array() );
        return is_array( $value ) ? $value : array();
    }

    /**
     * Get the settings group for a specific page slug
     *
     * Maps page slugs to their corresponding settings group constant.
     * Useful for forms that need to know which settings_fields() group to use.
     *
     * @since  2.0
     * @since  2.1 Added SETTINGS_GROUP_GENERAL mapping
     * @param  string $page_slug The page slug (use PAGE_* constants)
     * @return string|false The settings group name or false if not found
     */
    public static function get_settings_group_for_page( $page_slug ) {
        $map = array(
            self::PAGE_GENERAL         => self::SETTINGS_GROUP_GENERAL,
            self::PAGE_BRANDING        => self::SETTINGS_GROUP_BRANDING,
            self::PAGE_SYSTEM_CONTROLS => self::SETTINGS_GROUP_SYSTEM_CONTROLS,
            self::PAGE_ADMINS          => self::SETTINGS_GROUP_ADMINS,
            self::PAGE_PLUGINS         => self::SETTINGS_GROUP_PLUGINS,
            self::PAGE_THEMES          => self::SETTINGS_GROUP_THEMES,
            self::PAGE_WIDGETS         => self::SETTINGS_GROUP_WIDGETS,
            self::PAGE_ADMIN_BAR       => self::SETTINGS_GROUP_ADMIN_BAR,
            self::PAGE_METABOXES       => self::SETTINGS_GROUP_METABOXES,
        );

        return isset( $map[ $page_slug ] ) ? $map[ $page_slug ] : false;
    }

    /**
     * Get the display title for a specific page slug
     *
     * Used for contextual admin notices (e.g., "Hidden Plugins settings saved").
     *
     * @since  2.1
     * @param  string $page_slug The page slug (use PAGE_* constants)
     * @return string The display title for the page
     */
    public static function get_page_title( $page_slug ) {
        $titles = array(
            self::PAGE_GENERAL         => __( 'General', 'security-tools' ),
            self::PAGE_BRANDING        => __( 'Branding', 'security-tools' ),
            self::PAGE_SYSTEM_CONTROLS => __( 'System Controls', 'security-tools' ),
            self::PAGE_ADMINS          => __( 'Hidden Administrators', 'security-tools' ),
            self::PAGE_PLUGINS         => __( 'Hidden Plugins', 'security-tools' ),
            self::PAGE_THEMES          => __( 'Hidden Themes', 'security-tools' ),
            self::PAGE_WIDGETS         => __( 'Hidden Widgets', 'security-tools' ),
            self::PAGE_ADMIN_BAR       => __( 'Hidden Admin Bar Items', 'security-tools' ),
            self::PAGE_METABOXES       => __( 'Hidden Metaboxes', 'security-tools' ),
        );

        return isset( $titles[ $page_slug ] ) ? $titles[ $page_slug ] : __( 'Settings', 'security-tools' );
    }

    /**
     * Get all navigation items for tab navigation
     *
     * Returns an array of all subpages with their slugs, titles, and URLs
     * for rendering the internal tab navigation bar.
     *
     * @since  2.1
     * @return array Navigation items with slug, title, and url keys
     */
    public static function get_navigation_items() {
        return array(
            array(
                'slug'  => self::PAGE_GENERAL,
                'title' => __( 'General', 'security-tools' ),
                'url'   => admin_url( 'admin.php?page=' . self::PAGE_GENERAL ),
            ),
            array(
                'slug'  => self::PAGE_BRANDING,
                'title' => __( 'Branding', 'security-tools' ),
                'url'   => admin_url( 'admin.php?page=' . self::PAGE_BRANDING ),
            ),
            array(
                'slug'  => self::PAGE_SYSTEM_CONTROLS,
                'title' => __( 'System Controls', 'security-tools' ),
                'url'   => admin_url( 'admin.php?page=' . self::PAGE_SYSTEM_CONTROLS ),
            ),
            array(
                'slug'  => self::PAGE_ADMINS,
                'title' => __( 'Admins', 'security-tools' ),
                'url'   => admin_url( 'admin.php?page=' . self::PAGE_ADMINS ),
            ),
            array(
                'slug'  => self::PAGE_PLUGINS,
                'title' => __( 'Plugins', 'security-tools' ),
                'url'   => admin_url( 'admin.php?page=' . self::PAGE_PLUGINS ),
            ),
            array(
                'slug'  => self::PAGE_THEMES,
                'title' => __( 'Themes', 'security-tools' ),
                'url'   => admin_url( 'admin.php?page=' . self::PAGE_THEMES ),
            ),
            array(
                'slug'  => self::PAGE_WIDGETS,
                'title' => __( 'Widgets', 'security-tools' ),
                'url'   => admin_url( 'admin.php?page=' . self::PAGE_WIDGETS ),
            ),
            array(
                'slug'  => self::PAGE_ADMIN_BAR,
                'title' => __( 'Admin Bar', 'security-tools' ),
                'url'   => admin_url( 'admin.php?page=' . self::PAGE_ADMIN_BAR ),
            ),
            array(
                'slug'  => self::PAGE_METABOXES,
                'title' => __( 'Metaboxes', 'security-tools' ),
                'url'   => admin_url( 'admin.php?page=' . self::PAGE_METABOXES ),
            ),
        );
    }
}
