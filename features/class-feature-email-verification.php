<?php
// Prevent direct access
defined( 'ABSPATH' ) || exit;

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
