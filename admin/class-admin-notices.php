<?php
/**
 * Security Tools - Admin Notices
 *
 * Handles display of admin notices for save confirmations and reset alerts.
 * In version 2.0, notices work across all subpages of the plugin.
 *
 * @package    Security_Tools
 * @subpackage Admin
 * @version    2.5
 * @author     Carlos Rodríguez
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Class Security_Tools_Admin_Notices
 *
 * Manages admin notifications specific to the Security Tools settings pages.
 * Displays save confirmations and reset notifications on any plugin subpage.
 *
 * @since 1.2
 * @since 2.0 Updated to support multiple subpages
 */
class Security_Tools_Admin_Notices {

    /**
     * Constructor - Register hooks
     *
     * @since 1.2
     */
    public function __construct() {
        add_action( 'admin_notices', array( $this, 'display_notices' ) );
    }

    /**
     * Display admin notices on Security Tools pages
     *
     * Shows save confirmation or reset notification based on tracked changes.
     * Works on all Security Tools subpages.
     *
     * @since 1.2
     * @since 2.0 Updated to work on all subpages
     * @return void
     */
    public function display_notices() {
        // Only show on our settings pages
        if ( ! Security_Tools_Utils::is_settings_page() ) {
            return;
        }

        // Check for reset success first (highest priority)
        if ( $this->display_reset_notice() ) {
            return;
        }

        // Check for WordPress Settings API success message
        // This is triggered by the 'settings-updated' query parameter
        if ( $this->display_settings_updated_notice() ) {
            return;
        }

        // Check for save success via change tracking (fallback)
        $this->display_save_notice();
    }

    /**
     * Display reset success notice
     *
     * Shows a warning notice when all settings have been reset.
     *
     * @since  1.2
     * @return bool True if notice was displayed
     */
    private function display_reset_notice() {
        $reset_success = get_option( Security_Tools_Utils::OPTION_RESET_SUCCESS );

        if ( ! $reset_success ) {
            return false;
        }

        ?>
        <div class="notice notice-warning is-dismissible security-tools-notice">
            <p><strong><?php esc_html_e( 'All settings have been reset to default values.', 'security-tools' ); ?></strong></p>
        </div>
        <?php

        delete_option( Security_Tools_Utils::OPTION_RESET_SUCCESS );
        return true;
    }

    /**
     * Display settings updated notice
     *
     * Shows a success notice when WordPress Settings API confirms save.
     * This is triggered when redirected back from options.php with
     * the 'settings-updated=true' query parameter.
     *
     * In version 2.1, the notice now shows which specific settings were saved
     * based on the current page (e.g., "Hidden Plugins settings saved successfully.").
     *
     * @since  2.0
     * @since  2.1 Added specific page-based notice messages
     * @since  2.1 Now cleans up tracking options to prevent duplicate notices
     * @return bool True if notice was displayed
     */
    private function display_settings_updated_notice() {
        // Check for WordPress Settings API success parameter
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( ! isset( $_GET['settings-updated'] ) || $_GET['settings-updated'] !== 'true' ) {
            return false;
        }

        // Get current page slug to determine the specific message
        $current_page = Security_Tools_Utils::get_current_page_slug();
        $page_title   = Security_Tools_Utils::get_page_title( $current_page );

        // Build the specific notice message
        /* translators: %s: The name of the settings section that was saved (e.g., "Hidden Plugins", "Branding") */
        $message = sprintf(
            __( '%s settings saved successfully.', 'security-tools' ),
            $page_title
        );

        ?>
        <div class="notice notice-success is-dismissible security-tools-notice">
            <p><?php echo esc_html( $message ); ?></p>
        </div>
        <?php

        // Clean up tracking options for this page to prevent duplicate notices
        // when returning to this page later
        $this->cleanup_tracking_options_for_page( $current_page );

        return true;
    }

    /**
     * Display save success notice
     *
     * Checks all change tracking options and displays a notice if any were changed.
     * This is a fallback for when the Settings API doesn't provide feedback.
     *
     * In version 2.1, this method only shows a notice if the change tracking options
     * correspond to the current page. This prevents stale notices from appearing
     * when navigating between sections.
     *
     * @since 1.2
     * @since 2.1 Added page-specific filtering to prevent cross-page notice persistence
     * @return void
     */
    private function display_save_notice() {
        // Get current page to filter relevant tracking options
        $current_page = Security_Tools_Utils::get_current_page_slug();
        
        // Get tracking options relevant to the current page only
        $relevant_options = $this->get_tracking_options_for_page( $current_page );
        
        if ( empty( $relevant_options ) ) {
            return;
        }

        // Check if any relevant tracking options are set
        $has_changes = false;
        foreach ( $relevant_options as $option ) {
            if ( get_option( $option ) ) {
                $has_changes = true;
                break;
            }
        }

        if ( ! $has_changes ) {
            return;
        }

        // Get page title for contextual message
        $page_title = Security_Tools_Utils::get_page_title( $current_page );
        
        /* translators: %s: The name of the settings section that was saved (e.g., "Hidden Plugins", "Branding") */
        $message = sprintf(
            __( '%s settings saved successfully.', 'security-tools' ),
            $page_title
        );

        ?>
        <div class="notice notice-success is-dismissible security-tools-notice">
            <p><?php echo esc_html( $message ); ?></p>
        </div>
        <?php

        // Clean up the relevant tracking options for this page
        $this->cleanup_tracking_options_for_page( $current_page );
    }

    /**
     * Clean up change tracking options for a specific page
     *
     * Deletes all change tracking options associated with a page slug.
     * This prevents notices from reappearing when returning to a page.
     *
     * @since 2.1
     * @param string $page_slug The page slug to clean up tracking options for
     * @return void
     */
    private function cleanup_tracking_options_for_page( $page_slug ) {
        $relevant_options = $this->get_tracking_options_for_page( $page_slug );
        
        foreach ( $relevant_options as $option ) {
            delete_option( $option );
        }
    }

    /**
     * Get change tracking options relevant to a specific page
     *
     * Maps page slugs to their corresponding change tracking option(s).
     * This allows us to only show notices for changes made on the current page.
     *
     * @since  2.1
     * @param  string $page_slug The current page slug
     * @return array Array of tracking option names for this page
     */
    private function get_tracking_options_for_page( $page_slug ) {
        $map = array(
            Security_Tools_Utils::PAGE_GENERAL => array(
                Security_Tools_Utils::OPTION_AUTOHIDE_MENU_LAST_CHANGE,
            ),
            Security_Tools_Utils::PAGE_BRANDING => array(
                Security_Tools_Utils::OPTION_LEGEND_LAST_CHANGE,
            ),
            Security_Tools_Utils::PAGE_SYSTEM_CONTROLS => array(
                Security_Tools_Utils::OPTION_DISABLE_UPDATES_LAST_CHANGE,
                Security_Tools_Utils::OPTION_DISABLE_EMAILS_LAST_CHANGE,
                Security_Tools_Utils::OPTION_DISABLE_EMAIL_CHECK_LAST_CHANGE,
                Security_Tools_Utils::OPTION_HIDE_NOTICES_LAST_CHANGE,
                Security_Tools_Utils::OPTION_DISABLE_COMMENTS_LAST_CHANGE,
                Security_Tools_Utils::OPTION_DISABLE_PLUGIN_CONTROLS_LAST_CHANGE,
                Security_Tools_Utils::OPTION_DISABLE_THEME_CONTROLS_LAST_CHANGE,
                Security_Tools_Utils::OPTION_DISABLE_FRONTEND_ADMIN_BAR_LAST_CHANGE,
            ),
            Security_Tools_Utils::PAGE_ADMINS => array(
                Security_Tools_Utils::OPTION_ADMINS_LAST_CHANGE,
            ),
            Security_Tools_Utils::PAGE_PLUGINS => array(
                Security_Tools_Utils::OPTION_PLUGINS_LAST_CHANGE,
            ),
            Security_Tools_Utils::PAGE_THEMES => array(
                Security_Tools_Utils::OPTION_THEMES_LAST_CHANGE,
            ),
            Security_Tools_Utils::PAGE_WIDGETS => array(
                Security_Tools_Utils::OPTION_WIDGETS_LAST_CHANGE,
            ),
            Security_Tools_Utils::PAGE_ADMIN_BAR => array(
                Security_Tools_Utils::OPTION_ADMIN_BAR_LAST_CHANGE,
            ),
            Security_Tools_Utils::PAGE_METABOXES => array(
                Security_Tools_Utils::OPTION_METABOXES_LAST_CHANGE,
            ),
        );

        return isset( $map[ $page_slug ] ) ? $map[ $page_slug ] : array();
    }
}
