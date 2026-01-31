<?php
/**
 * Security Tools - Emails Feature
 *
 * Disables all WordPress email sending functionality.
 *
 * @package    Security_Tools
 * @subpackage Features
 * @version    2.5
 * @author     Carlos Rodríguez
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Class Security_Tools_Feature_Emails
 *
 * Implements email disabling functionality.
 *
 * @since 1.2
 */
class Security_Tools_Feature_Emails {

    /**
     * Constructor - Register hooks if enabled
     *
     * @since 1.2
     */
    public function __construct() {
        add_action( 'init', array( $this, 'maybe_disable_emails' ) );
    }

    /**
     * Check if feature should be activated
     *
     * @since 1.2
     * @return void
     */
    public function maybe_disable_emails() {
        if ( ! Security_Tools_Utils::get_bool_option( Security_Tools_Utils::OPTION_DISABLE_EMAILS ) ) {
            return;
        }

        // Use pre_wp_mail filter to suppress all emails
        add_filter( 'pre_wp_mail', array( $this, 'suppress_mail' ), 10, 2 );

        // Also hook into PHPMailer as backup
        add_action( 'phpmailer_init', array( $this, 'disable_phpmailer' ) );
    }

    /**
     * Suppress wp_mail function
     *
     * Intercepts all wp_mail calls and prevents actual sending.
     *
     * @since 1.2
     * @param null|bool $null     Null to send, true to short-circuit
     * @param array     $atts     Email attributes
     * @return bool True to prevent sending
     */
    public function suppress_mail( $null, $atts ) {
        global $phpmailer;

        // Ensure PHPMailer is available for compatibility
        if ( ! class_exists( 'PHPMailer\\PHPMailer\\PHPMailer' ) ) {
            require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
            require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
            require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';
        }

        if ( ! ( $phpmailer instanceof PHPMailer\PHPMailer\PHPMailer ) ) {
            $phpmailer = new PHPMailer\PHPMailer\PHPMailer( true );
        }

        // Fire action for compatibility with plugins expecting it
        do_action_ref_array( 'phpmailer_init', array( &$phpmailer ) );

        // Return true to indicate "success" without actually sending
        return true;
    }

    /**
     * Disable PHPMailer sending
     *
     * Clears recipients to prevent any actual mail sending.
     *
     * @since 1.2
     * @param PHPMailer $phpmailer PHPMailer instance
     * @return void
     */
    public function disable_phpmailer( $phpmailer ) {
        $phpmailer->clearAllRecipients();
        $phpmailer->clearAttachments();
        $phpmailer->clearCustomHeaders();
    }
}
