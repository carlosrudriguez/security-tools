<?php
/**
 * Security Tools - Comments Feature
 *
 * Completely disables WordPress comment functionality.
 *
 * @package    Security_Tools
 * @subpackage Features
 * @version    2.5
 * @author     Carlos Rodríguez
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Class Security_Tools_Feature_Comments
 *
 * Implements comment disabling functionality.
 *
 * @since 1.2
 */
class Security_Tools_Feature_Comments {

    /**
     * Constructor - Register hooks if enabled
     *
     * @since 1.2
     */
    public function __construct() {
        add_action( 'init', array( $this, 'maybe_disable_comments' ) );
    }

    /**
     * Check if feature should be activated
     *
     * @since 1.2
     * @return void
     */
    public function maybe_disable_comments() {
        if ( ! Security_Tools_Utils::get_bool_option( Security_Tools_Utils::OPTION_DISABLE_COMMENTS ) ) {
            return;
        }

        // Core filters
        add_filter( 'comments_open', '__return_false', 20, 2 );
        add_filter( 'pings_open', '__return_false', 20, 2 );
        add_filter( 'comments_array', '__return_empty_array', 10, 2 );

        // Admin modifications
        add_action( 'admin_init', array( $this, 'disable_admin_comments' ) );
        add_action( 'admin_init', array( $this, 'redirect_comment_pages' ) );

        // Frontend modifications
        add_action( 'template_redirect', array( $this, 'disable_comment_feeds' ), 9 );
        add_filter( 'wp_headers', array( $this, 'remove_pingback_header' ) );

        // API modifications
        add_filter( 'xmlrpc_methods', array( $this, 'disable_xmlrpc_pingbacks' ) );
        add_filter( 'rest_endpoints', array( $this, 'disable_rest_comments' ) );

        // Remove widgets
        add_action( 'widgets_init', array( $this, 'disable_widgets' ), 1 );

        // Close comments on all existing posts
        add_action( 'wp_loaded', array( $this, 'close_all_comments' ) );
    }

    /**
     * Disable comments in admin
     *
     * @since 1.2
     */
    public function disable_admin_comments() {
        // Remove comment support from all post types
        foreach ( get_post_types() as $post_type ) {
            if ( post_type_supports( $post_type, 'comments' ) ) {
                remove_post_type_support( $post_type, 'comments' );
                remove_post_type_support( $post_type, 'trackbacks' );
            }
        }

        // Remove menu items
        remove_menu_page( 'edit-comments.php' );
        remove_submenu_page( 'options-general.php', 'options-discussion.php' );

        // Remove from admin bar
        add_action( 'wp_before_admin_bar_render', array( $this, 'remove_admin_bar_comments' ) );

        // Remove dashboard widget
        add_action( 'wp_dashboard_setup', array( $this, 'remove_dashboard_widget' ), 999 );
    }

    /**
     * Redirect comment-related admin pages
     *
     * @since 1.2
     */
    public function redirect_comment_pages() {
        global $pagenow;

        $blocked_pages = array( 'edit-comments.php', 'comment.php', 'options-discussion.php' );

        if ( in_array( $pagenow, $blocked_pages, true ) ) {
            wp_safe_redirect( admin_url( 'index.php' ) );
            exit;
        }
    }

    /**
     * Remove comments from admin bar
     *
     * @since 1.2
     */
    public function remove_admin_bar_comments() {
        global $wp_admin_bar;
        $wp_admin_bar->remove_menu( 'comments' );
        $wp_admin_bar->remove_menu( 'new-comment' );
    }

    /**
     * Remove recent comments dashboard widget
     *
     * @since 1.2
     */
    public function remove_dashboard_widget() {
        remove_meta_box( 'dashboard_recent_comments', 'dashboard', 'normal' );
    }

    /**
     * Disable comment feeds
     *
     * @since 1.2
     */
    public function disable_comment_feeds() {
        if ( is_comment_feed() ) {
            wp_safe_redirect( home_url(), 301 );
            exit;
        }
    }

    /**
     * Remove X-Pingback header
     *
     * @since 1.2
     * @param array $headers HTTP headers
     * @return array Modified headers
     */
    public function remove_pingback_header( $headers ) {
        unset( $headers['X-Pingback'] );
        return $headers;
    }

    /**
     * Disable XML-RPC pingback methods
     *
     * @since 1.2
     * @param array $methods XML-RPC methods
     * @return array Modified methods
     */
    public function disable_xmlrpc_pingbacks( $methods ) {
        unset( $methods['pingback.ping'] );
        unset( $methods['pingback.extensions.getPingbacks'] );
        return $methods;
    }

    /**
     * Disable REST API comment endpoints
     *
     * @since 1.2
     * @param array $endpoints REST endpoints
     * @return array Modified endpoints
     */
    public function disable_rest_comments( $endpoints ) {
        unset( $endpoints['/wp/v2/comments'] );
        unset( $endpoints['/wp/v2/comments/(?P<id>[\d]+)'] );
        return $endpoints;
    }

    /**
     * Unregister comment widgets
     *
     * @since 1.2
     */
    public function disable_widgets() {
        unregister_widget( 'WP_Widget_Recent_Comments' );
    }

    /**
     * Close comments on all existing posts
     *
     * @since 1.2
     */
    public function close_all_comments() {
        global $wpdb;
        $wpdb->query(
            "UPDATE {$wpdb->posts} SET comment_status = 'closed', ping_status = 'closed' WHERE post_status = 'publish'"
        );
    }
}
