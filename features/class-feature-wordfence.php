<?php
/**
 * Security Tools - Wordfence Integration Feature
 *
 * Hides Wordfence Login Security 2FA filter sections from the Users list.
 *
 * @package    Security_Tools
 * @subpackage Features
 * @version    2.5
 * @author     Carlos Rodríguez
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Class Security_Tools_Feature_Wordfence
 *
 * Implements Wordfence 2FA UI hiding functionality.
 *
 * @since 1.2
 */
class Security_Tools_Feature_Wordfence {

    /**
     * Constructor - Register hooks
     *
     * @since 1.2
     */
    public function __construct() {
        add_action( 'admin_enqueue_scripts', array( $this, 'hide_2fa_css' ) );
        add_action( 'admin_footer-users.php', array( $this, 'hide_2fa_js' ) );
    }

    /**
     * Check if Wordfence is active
     *
     * @since 1.2
     * @return bool
     */
    private function is_wordfence_active() {
        return is_plugin_active( 'wordfence-login-security/wordfence-login-security.php' ) ||
               class_exists( 'wordfence' ) ||
               defined( 'WORDFENCE_VERSION' );
    }

    /**
     * Hide 2FA sections using CSS
     *
     * @since 1.2
     */
    public function hide_2fa_css() {
        $screen = get_current_screen();

        if ( ! $screen || $screen->id !== 'users' ) {
            return;
        }

        if ( ! $this->is_wordfence_active() ) {
            return;
        }

        $css = '
            .subsubsub li a[href*="2fa-active"],
            .subsubsub li a[href*="2fa-inactive"],
            .subsubsub li a[href*="wf-2fa-active"],
            .subsubsub li a[href*="wf-2fa-inactive"],
            .subsubsub li:has(a[href*="2fa-active"]),
            .subsubsub li:has(a[href*="2fa-inactive"]) {
                display: none !important;
            }
        ';

        wp_register_style( 'security-tools-hide-wf-2fa', false );
        wp_enqueue_style( 'security-tools-hide-wf-2fa' );
        wp_add_inline_style( 'security-tools-hide-wf-2fa', $css );
    }

    /**
     * Hide 2FA sections using JavaScript
     *
     * @since 1.2
     */
    public function hide_2fa_js() {
        $screen = get_current_screen();

        if ( ! $screen || $screen->id !== 'users' ) {
            return;
        }

        if ( ! $this->is_wordfence_active() ) {
            return;
        }

        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            $('.subsubsub li a').each(function() {
                var href = $(this).attr('href') || '';
                var text = $(this).text();

                if (href.includes('2fa-active') ||
                    href.includes('2fa-inactive') ||
                    text.includes('2FA Active') ||
                    text.includes('2FA Inactive')) {
                    $(this).closest('li').hide();
                }
            });
        });
        </script>
        <?php
    }
}
