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
        $this->maybe_disable_email_verification();
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
        add_filter( 'pre_option_admin_email_lifespan', array( $this, 'force_future_lifespan' ), PHP_INT_MAX );
        add_action( 'login_init', array( $this, 'redirect_admin_email_confirmation' ), 0 );
        add_action( 'login_form_confirm_admin_email', array( $this, 'redirect_admin_email_confirmation' ), 0 );
    }

    /**
     * Redirect confirmation-screen requests away from the login form.
     *
     * @since  2.6.1
     * @return void
     */
    public function redirect_admin_email_confirmation() {
        if ( ! $this->is_admin_email_confirmation_request() ) {
            return;
        }

        wp_safe_redirect( $this->get_confirmation_redirect_url() );
        exit;
    }

    /**
     * Determine whether the current login request is the admin email screen.
     *
     * @since  2.6.2
     * @return bool
     */
    private function is_admin_email_confirmation_request() {
        if ( empty( $_REQUEST['action'] ) || ! is_string( $_REQUEST['action'] ) ) {
            return false;
        }

        return 'confirm_admin_email' === sanitize_key( wp_unslash( $_REQUEST['action'] ) );
    }

    /**
     * Resolve the post-confirmation destination.
     *
     * @since  2.6.2
     * @return string
     */
    private function get_confirmation_redirect_url() {
        $redirect_to = admin_url();

        if ( empty( $_REQUEST['redirect_to'] ) || ! is_string( $_REQUEST['redirect_to'] ) ) {
            return $redirect_to;
        }

        $requested_redirect = esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) );

        return wp_validate_redirect( $requested_redirect, $redirect_to );
    }

    /**
     * Keep WordPress from seeing an expired admin email reminder window.
     *
     * @since  2.6.2
     * @param  mixed $pre_option Existing short-circuit value.
     * @return int
     */
    public function force_future_lifespan( $pre_option ) {
        return time() + YEAR_IN_SECONDS;
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
