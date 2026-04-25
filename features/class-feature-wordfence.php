<?php
/**
 * Security Tools - Wordfence Integration Feature
 *
 * Hides Wordfence Login Security 2FA filter sections from the Users list.
 *
 * @package    Security_Tools
 * @subpackage Features
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
        add_action( 'admin_enqueue_scripts', array( $this, 'hide_2fa_js' ) );
    }

    /**
     * Check if Wordfence is active
     *
     * @since 1.2
     * @return bool
     */
    private function is_wordfence_active() {
        $plugins = array(
            'wordfence/wordfence.php',
            'wordfence-login-security/wordfence-login-security.php',
        );

        foreach ( $plugins as $plugin ) {
            if ( function_exists( 'is_plugin_active' ) && is_plugin_active( $plugin ) ) {
                return true;
            }

            if ( function_exists( 'is_plugin_active_for_network' ) && is_plugin_active_for_network( $plugin ) ) {
                return true;
            }
        }

        return class_exists( 'wordfence', false ) ||
               class_exists( 'WordfenceLS\\Controller_Users', false ) ||
               defined( 'WORDFENCE_VERSION' ) ||
               defined( 'WORDFENCE_LS_VERSION' );
    }

    /**
     * Check whether current screen is Users (site or network admin)
     *
     * @since 2.6
     * @param object|null $screen Current screen object.
     * @return bool
     */
    private function is_users_screen( $screen ) {
        if ( ! $screen ) {
            return false;
        }

        if ( isset( $screen->id ) && in_array( $screen->id, array( 'users', 'users-network' ), true ) ) {
            return true;
        }

        return isset( $screen->base ) && 'users' === $screen->base;
    }

    /**
     * Hide 2FA sections using CSS
     *
     * @since 1.2
     */
    public function hide_2fa_css() {
        $screen = get_current_screen();

        if ( ! $this->is_users_screen( $screen ) ) {
            return;
        }

        if ( ! $this->is_wordfence_active() ) {
            return;
        }

        $css = '
            .subsubsub li.wfls-active,
            .subsubsub li.wfls-inactive,
            .subsubsub li a[href*="2fa-active"],
            .subsubsub li a[href*="2fa-inactive"],
            .subsubsub li a[href*="wf2fa=active"],
            .subsubsub li a[href*="wf2fa=inactive"],
            .subsubsub li a[href*="wf-2fa-active"],
            .subsubsub li a[href*="wf-2fa-inactive"],
            .subsubsub li a[href*="wfls-active"],
            .subsubsub li a[href*="wfls-inactive"],
            .subsubsub li:has(a[href*="2fa-active"]),
            .subsubsub li:has(a[href*="2fa-inactive"]),
            .subsubsub li:has(a[href*="wf2fa=active"]),
            .subsubsub li:has(a[href*="wf2fa=inactive"]),
            .subsubsub li:has(a[href*="wf-2fa-active"]),
            .subsubsub li:has(a[href*="wf-2fa-inactive"]),
            .subsubsub li:has(a[href*="wfls-active"]),
            .subsubsub li:has(a[href*="wfls-inactive"]) {
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

        if ( ! $this->is_users_screen( $screen ) ) {
            return;
        }

        if ( ! $this->is_wordfence_active() ) {
            return;
        }

        wp_enqueue_script(
            'security-tools-hide-wordfence-2fa',
            SECURITY_TOOLS_URL . 'assets/js/hide-wordfence-2fa.js',
            array( 'jquery' ),
            SECURITY_TOOLS_VERSION,
            true
        );
    }
}
