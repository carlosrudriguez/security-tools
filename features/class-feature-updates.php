<?php
/**
 * Security Tools - Updates Feature
 *
 * Disables all WordPress update checks and automatic updates.
 *
 * @package    Security_Tools
 * @subpackage Features
 * @version    2.5
 * @author     Carlos Rodríguez
 *
 * CHANGELOG v1.3:
 * - Added complete transient object structure (Issue #3)
 * - disable_theme_plugin_check() now includes: response, translations, no_update
 * - disable_core_check() now includes: updates, translations
 * - Prevents PHP warnings from WordPress core and other plugins
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Class Security_Tools_Feature_Updates
 *
 * Implements update disabling functionality.
 *
 * @since 1.2
 */
class Security_Tools_Feature_Updates {

    /**
     * Constructor - Register hooks if enabled
     *
     * @since 1.2
     */
    public function __construct() {
        add_action( 'init', array( $this, 'maybe_disable_updates' ) );
    }

    /**
     * Check if feature should be activated
     *
     * @since 1.2
     * @return void
     */
    public function maybe_disable_updates() {
        if ( ! Security_Tools_Utils::get_bool_option( Security_Tools_Utils::OPTION_DISABLE_UPDATES ) ) {
            return;
        }

        $this->disable_update_checks();
        $this->disable_auto_updates();
        $this->disable_update_emails();
        $this->disable_update_crons();
        $this->disable_api_lookups();
    }

    /**
     * Disable update transient checks
     *
     * @since 1.2
     */
    private function disable_update_checks() {
        add_filter( 'pre_transient_update_themes', array( $this, 'disable_theme_plugin_check' ) );
        add_filter( 'pre_site_transient_update_themes', array( $this, 'disable_theme_plugin_check' ) );
        add_filter( 'pre_transient_update_plugins', array( $this, 'disable_theme_plugin_check' ) );
        add_filter( 'pre_site_transient_update_plugins', array( $this, 'disable_theme_plugin_check' ) );
        add_filter( 'pre_transient_update_core', array( $this, 'disable_core_check' ) );
        add_filter( 'pre_site_transient_update_core', array( $this, 'disable_core_check' ) );
    }

    /**
     * Disable automatic updates
     *
     * @since 1.2
     */
    private function disable_auto_updates() {
        add_filter( 'auto_update_core', '__return_false' );
        add_filter( 'wp_auto_update_core', '__return_false' );
        add_filter( 'allow_minor_auto_core_updates', '__return_false' );
        add_filter( 'allow_major_auto_core_updates', '__return_false' );
        add_filter( 'allow_dev_auto_core_updates', '__return_false' );
        add_filter( 'auto_update_plugin', '__return_false' );
        add_filter( 'auto_update_theme', '__return_false' );
        add_filter( 'auto_update_translation', '__return_false' );
    }

    /**
     * Disable update-related emails
     *
     * @since 1.2
     */
    private function disable_update_emails() {
        add_filter( 'auto_core_update_send_email', '__return_false' );
        add_filter( 'send_core_update_notification_email', '__return_false' );
        add_filter( 'automatic_updates_send_debug_email', '__return_false' );
    }

    /**
     * Disable scheduled update checks
     *
     * @since 1.2
     */
    private function disable_update_crons() {
        remove_action( 'init', 'wp_schedule_update_checks' );
        wp_clear_scheduled_hook( 'wp_update_plugins' );
        wp_clear_scheduled_hook( 'wp_update_themes' );
        wp_clear_scheduled_hook( 'wp_version_check' );
        wp_clear_scheduled_hook( 'wp_maybe_auto_update' );
    }

    /**
     * Disable plugin/theme API lookups
     *
     * @since 1.2
     */
    private function disable_api_lookups() {
        add_filter( 'plugins_api', array( $this, 'disable_plugins_api' ), 10, 3 );
        add_filter( 'themes_api', array( $this, 'disable_themes_api' ), 10, 3 );
    }

    /**
     * Return empty transient for theme/plugin checks
     *
     * v1.3: Now returns complete object structure with all expected properties
     * to prevent PHP warnings from WordPress core and other plugins.
     *
     * Expected properties for update_plugins transient:
     * - last_checked: Timestamp of last check
     * - checked: Array of plugin versions that were checked
     * - response: Array of plugins that need updates
     * - translations: Array of translation updates
     * - no_update: Array of plugins that are up to date
     *
     * Expected properties for update_themes transient:
     * - last_checked: Timestamp of last check
     * - checked: Array of theme versions that were checked
     * - response: Array of themes that need updates
     * - translations: Array of translation updates
     *
     * @since 1.2
     * @since 1.3 Added complete transient structure (response, translations, no_update)
     * @param mixed $transient Current transient value
     * @return object Modified transient with complete structure
     */
    public function disable_theme_plugin_check( $transient ) {
        if ( ! is_object( $transient ) ) {
            $transient = new stdClass();
        }

        // Core properties (existing in v1.2)
        $transient->last_checked = time();
        $transient->checked      = array();

        // Additional properties added in v1.3 for stability
        // These prevent "Undefined property" notices from WordPress core
        // and other plugins that expect these properties to exist
        $transient->response     = array();  // Plugins/themes that need updates
        $transient->translations = array();  // Translation updates available
        $transient->no_update    = array();  // Plugins/themes that are up to date

        return $transient;
    }

    /**
     * Return empty transient for core checks
     *
     * v1.3: Now returns complete object structure with all expected properties
     * to prevent PHP warnings from WordPress core and other plugins.
     *
     * Expected properties for update_core transient:
     * - last_checked: Timestamp of last check
     * - version_checked: WordPress version that was checked
     * - updates: Array of available core updates
     * - translations: Array of translation updates
     *
     * @since 1.2
     * @since 1.3 Added complete transient structure (updates, translations)
     * @param mixed $transient Current transient value
     * @return object Modified transient with complete structure
     */
    public function disable_core_check( $transient ) {
        if ( ! is_object( $transient ) ) {
            $transient = new stdClass();
        }

        // Core properties (existing in v1.2)
        $transient->last_checked     = time();
        $transient->version_checked  = get_bloginfo( 'version' );

        // Additional properties added in v1.3 for stability
        // These prevent "Undefined property" notices from WordPress core
        // and other plugins that expect these properties to exist
        $transient->updates      = array();  // Core updates available
        $transient->translations = array();  // Translation updates available

        return $transient;
    }

    /**
     * Disable plugins API
     *
     * @since 1.2
     */
    public function disable_plugins_api( $result, $action, $args ) {
        if ( in_array( $action, array( 'plugin_information', 'query_plugins' ), true ) ) {
            return new WP_Error( 'plugins_api_disabled', 'Plugin API disabled by Security Tools' );
        }
        return $result;
    }

    /**
     * Disable themes API
     *
     * @since 1.2
     */
    public function disable_themes_api( $result, $action, $args ) {
        if ( in_array( $action, array( 'theme_information', 'query_themes' ), true ) ) {
            return new WP_Error( 'themes_api_disabled', 'Theme API disabled by Security Tools' );
        }
        return $result;
    }
}
