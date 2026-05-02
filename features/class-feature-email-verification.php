<?php
/**
 * Security Tools - Email Verification Feature
 *
 * Suppresses WordPress admin email confirmation redirects and screens.
 * Depends on WordPress login hooks and Security Tools option helpers.
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Class Security_Tools_Feature_Email_Verification
 *
 * Implements admin email confirmation prompt suppression.
 *
 * @since 2.5
 */
class Security_Tools_Feature_Email_Verification {

    /**
     * Constructor - Register hooks if enabled
     *
     * @since 2.5
     */
    public function __construct() {
        add_action( 'init', array( $this, 'maybe_disable_email_verification' ) );
    }

    /**
     * Disable admin email verification checks when enabled
     *
     * @since 2.5
     * @return void
     */
    public function maybe_disable_email_verification() {
        if ( ! Security_Tools_Utils::get_bool_option( Security_Tools_Utils::OPTION_DISABLE_EMAIL_CHECK ) ) {
            return;
        }

        add_filter( 'admin_email_check_interval', array( $this, 'force_zero_interval' ), PHP_INT_MAX );
        add_filter( 'admin_email_remind_interval', array( $this, 'force_zero_interval' ), PHP_INT_MAX );
        add_action( 'login_form_confirm_admin_email', array( $this, 'redirect_admin_email_confirmation' ), 0 );
    }

    /**
     * Redirect logged-in administrators away from the confirmation screen.
     *
     * @since  2.6.1
     * @return void
     */
    public function redirect_admin_email_confirmation() {
        if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $redirect_to = admin_url();

        if ( ! empty( $_REQUEST['redirect_to'] ) ) {
            $redirect_to = esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) );
        }

        wp_safe_redirect( $redirect_to );
        exit;
    }

    /**
     * Force the interval to zero
     *
     * @since 2.5
     * @param int $interval Current interval.
     * @return int
     */
    public function force_zero_interval( $interval ) {
        return 0;
    }
}
