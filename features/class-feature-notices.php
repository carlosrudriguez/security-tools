<?php
/**
 * Security Tools - Admin Notices Feature
 *
 * Hides all admin notices throughout the WordPress dashboard.
 * Uses PHP, CSS, and JavaScript methods for comprehensive coverage.
 *
 * @package    Security_Tools
 * @subpackage Features
 * @version    2.5
 * @author     Carlos Rodríguez
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Class Security_Tools_Feature_Notices
 *
 * Implements admin notices hiding functionality.
 *
 * @since 1.2
 * @since 2.0 Updated screen ID checks for new multi-page structure
 */
class Security_Tools_Feature_Notices {

    /**
     * Constructor - Register hooks
     *
     * @since 1.2
     */
    public function __construct() {
        add_action( 'current_screen', array( $this, 'hide_notices_php' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'hide_notices_css' ) );
        add_action( 'admin_footer', array( $this, 'hide_notices_js' ) );
    }

    /**
     * Check if feature is enabled
     *
     * @since 1.2
     * @return bool
     */
    private function is_enabled() {
        return Security_Tools_Utils::get_bool_option( Security_Tools_Utils::OPTION_HIDE_NOTICES );
    }

    /**
     * Hide notices using PHP (primary method)
     *
     * Removes notice filter arrays before they're displayed.
     *
     * @since 1.2
     * @return void
     */
    public function hide_notices_php() {
        if ( ! $this->is_enabled() ) {
            return;
        }

        // Don't hide on Security Tools pages - preserve our own notices
        if ( Security_Tools_Utils::is_settings_page() ) {
            return;
        }

        global $wp_filter;

        $notice_hooks = array(
            'admin_notices',
            'user_admin_notices',
            'network_admin_notices',
            'all_admin_notices',
        );

        foreach ( $notice_hooks as $hook ) {
            if ( isset( $wp_filter[ $hook ] ) ) {
                unset( $wp_filter[ $hook ] );
            }
        }
    }

    /**
     * Hide notices using CSS (backup method)
     *
     * On Security Tools pages, hides all notices except our own.
     * On other pages, hides all notices completely.
     *
     * @since 1.2
     * @since 2.0 Updated to use is_settings_page() for multi-page support
     * @return void
     */
    public function hide_notices_css() {
        if ( ! $this->is_enabled() ) {
            return;
        }

        // On Security Tools pages, hide all notices except our own
        // Uses the utility method which checks all subpage screen IDs
        if ( Security_Tools_Utils::is_settings_page() ) {
            $css = '
                .notice:not(.security-tools-notice),
                .error:not(.security-tools-notice),
                .updated:not(.security-tools-notice),
                .update-nag:not(.security-tools-notice) {
                    display: none !important;
                }
            ';
        } else {
            // On other pages, hide all notices
            $css = '
                .notice, .error, .updated, .update-nag,
                .notice-error, .notice-warning, .notice-success, .notice-info {
                    display: none !important;
                }
            ';
        }

        wp_register_style( 'security-tools-hide-notices', false );
        wp_enqueue_style( 'security-tools-hide-notices' );
        wp_add_inline_style( 'security-tools-hide-notices', $css );
    }

    /**
     * Hide notices using JavaScript (for dynamically added notices)
     *
     * Uses MutationObserver to catch notices added after page load.
     *
     * @since 1.2
     * @return void
     */
    public function hide_notices_js() {
        if ( ! $this->is_enabled() ) {
            return;
        }

        $is_settings_page = Security_Tools_Utils::is_settings_page();
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            function hideNotices() {
                <?php if ( $is_settings_page ) : ?>
                    $('.notice, .error, .updated, .update-nag').not('.security-tools-notice').hide();
                <?php else : ?>
                    $('.notice, .error, .updated, .update-nag').hide();
                <?php endif; ?>
            }

            hideNotices();

            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.type === 'childList') {
                        hideNotices();
                    }
                });
            });

            observer.observe(document.body, { childList: true, subtree: true });
        });
        </script>
        <?php
    }
}
