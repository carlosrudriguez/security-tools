<?php
/**
 * Security Tools - Hide Login Page Feature
 *
 * Hides the default WordPress login page (wp-login.php) and allows
 * administrators to set a custom login URL slug. Unauthorized access
 * to wp-login.php or wp-admin (when not logged in) results in a 404 page.
 *
 * MECHANISM:
 * - Intercepts requests early via 'init' hook
 * - Custom slug renders the WordPress login form
 * - Standard wp-login.php and wp-admin redirect to 404 for non-logged users
 * - No core file modifications, no .htaccess changes
 * - Fully reversible when disabled
 *
 * SECURITY CONSIDERATIONS:
 * - Only affects non-authenticated users
 * - Logged-in users can access wp-admin normally
 * - Password reset, registration, and other login actions are supported
 * - AJAX and REST API endpoints remain unaffected
 *
 * @package    Security_Tools
 * @subpackage Features
 * @version    2.5
 * @author     Carlos Rodríguez
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Class Security_Tools_Feature_Hide_Login
 *
 * Implements the custom login URL functionality by intercepting page requests.
 *
 * @since 2.2
 */
class Security_Tools_Feature_Hide_Login {

    /**
     * The custom login slug set by the administrator
     *
     * @var string
     */
    private $custom_slug = '';

    /**
     * Whether the feature is enabled
     *
     * @var bool
     */
    private $is_enabled = false;

    /**
     * Constructor - Register hooks if feature is potentially active
     *
     * Hooks are registered early (init with priority 1) to intercept requests
     * before WordPress processes them normally.
     *
     * @since 2.2
     */
    public function __construct() {
        // Load settings
        $this->is_enabled  = Security_Tools_Utils::get_bool_option( Security_Tools_Utils::OPTION_HIDE_LOGIN_ENABLED );
        $this->custom_slug = $this->get_custom_slug();

        // Only proceed if feature is enabled and slug is set
        if ( ! $this->is_enabled || empty( $this->custom_slug ) ) {
            return;
        }

        // Hook early to intercept requests before WordPress processes them
        // Priority 1 ensures we run before most other plugins
        add_action( 'init', array( $this, 'handle_custom_login_request' ), 1 );

        // Block access to default login URLs for non-authenticated users
        add_action( 'init', array( $this, 'block_default_login_access' ), 1 );

        // CRITICAL: Hook into login_init to catch /login URL access
        // This fires at the very beginning of wp-login.php, before any output
        // This catches cases where /login directly loads wp-login.php via rewrite rules
        add_action( 'login_init', array( $this, 'block_login_init_access' ), 1 );

        // Filter login URL to return our custom URL
        add_filter( 'login_url', array( $this, 'filter_login_url' ), 10, 3 );

        // Filter logout URL to ensure proper redirect
        add_filter( 'logout_url', array( $this, 'filter_logout_url' ), 10, 2 );

        // Filter lostpassword URL
        add_filter( 'lostpassword_url', array( $this, 'filter_lostpassword_url' ), 10, 2 );

        // Filter register URL (if registration is enabled)
        add_filter( 'register_url', array( $this, 'filter_register_url' ), 10, 1 );

        // Filter site URL for login-related paths
        add_filter( 'site_url', array( $this, 'filter_site_url' ), 10, 4 );

        // Filter network site URL for multisite compatibility
        add_filter( 'network_site_url', array( $this, 'filter_network_site_url' ), 10, 3 );

        // Filter wp_redirect to catch any remaining login redirects
        add_filter( 'wp_redirect', array( $this, 'filter_wp_redirect' ), 10, 2 );
    }

    /**
     * Get the custom login slug from options
     *
     * Sanitizes the slug to ensure it's URL-safe.
     *
     * @since  2.2
     * @return string The sanitized custom login slug
     */
    private function get_custom_slug() {
        $slug = get_option( Security_Tools_Utils::OPTION_HIDE_LOGIN_SLUG, '' );
        return sanitize_title( $slug );
    }

    /**
     * Handle requests to the custom login URL
     *
     * When a user visits the custom login slug, we load the WordPress
     * login functionality without exposing the actual wp-login.php file.
     *
     * @since 2.2
     * @return void
     */
    public function handle_custom_login_request() {
        // Get the current request path
        $request_path = $this->get_request_path();

        // Check if this is a request to our custom login slug
        if ( $request_path !== $this->custom_slug ) {
            return;
        }

        // Load the login page
        $this->load_login_page();
    }

    /**
     * Block access to default WordPress login URLs
     *
     * Non-authenticated users attempting to access wp-login.php or wp-admin
     * will be shown a 404 error page.
     *
     * @since 2.2
     * @return void
     */
    public function block_default_login_access() {
        // Don't block if user is already logged in
        if ( is_user_logged_in() ) {
            return;
        }

        // Don't block AJAX requests
        if ( wp_doing_ajax() ) {
            return;
        }

        // Don't block REST API requests
        if ( $this->is_rest_request() ) {
            return;
        }

        // Don't block WP-CLI requests
        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            return;
        }

        // Don't block cron requests
        if ( wp_doing_cron() ) {
            return;
        }

        // Check if accessing wp-login.php
        if ( $this->is_login_page_request() ) {
            $this->trigger_404();
        }

        // Check if accessing wp-admin (but not admin-ajax.php or admin-post.php)
        if ( $this->is_admin_request() && ! $this->is_allowed_admin_request() ) {
            $this->trigger_404();
        }
    }

    /**
     * Block access via login_init hook
     *
     * This method fires at the very beginning of wp-login.php, catching cases
     * where the login page is accessed via:
     * - /login (WordPress rewrite rule)
     * - Direct wp-login.php access that bypassed earlier checks
     *
     * IMPORTANT: This is our last line of defense. If someone reaches
     * wp-login.php through any method (rewrite rules, direct access, etc.),
     * this hook will catch it.
     *
     * We allow access if:
     * - User is already logged in
     * - Request came through our custom login slug (indicated by our constant)
     *
     * @since 2.3.1
     * @return void
     */
    public function block_login_init_access() {
        // Allow if user is already logged in
        if ( is_user_logged_in() ) {
            return;
        }

        // Allow if accessed via our custom login slug
        // Our handle_custom_login_request() method defines this constant
        if ( defined( 'DOING_SECURITY_TOOLS_LOGIN' ) && DOING_SECURITY_TOOLS_LOGIN ) {
            return;
        }

        // Block all other access to wp-login.php for non-authenticated users
        // This catches /login, direct wp-login.php access, and any other method
        $this->trigger_404();
    }

    /**
     * Get the current request path relative to WordPress installation
     *
     * @since  2.2
     * @return string The request path without query string
     */
    private function get_request_path() {
        // Get the request URI
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

        // Remove query string if present
        $request_uri = strtok( $request_uri, '?' );

        // Get WordPress home path
        $home_path = wp_parse_url( home_url(), PHP_URL_PATH );
        if ( empty( $home_path ) ) {
            $home_path = '/';
        }

        // Remove home path from request URI
        if ( strpos( $request_uri, $home_path ) === 0 ) {
            $request_uri = substr( $request_uri, strlen( $home_path ) );
        }

        // Remove leading and trailing slashes
        $request_uri = trim( $request_uri, '/' );

        return $request_uri;
    }

    /**
     * Check if current request is for the WordPress login page
     *
     * Detects access attempts to:
     * - /wp-login.php (direct access)
     * - /login (WordPress rewrite rule since WP 3.0)
     *
     * Note: WordPress has a built-in rewrite rule that serves wp-login.php
     * when users visit /login. This is handled via the Rewrite API, not
     * via a redirect, so the REQUEST_URI shows /login, not wp-login.php.
     *
     * We use multiple detection methods because WordPress can load wp-login.php
     * in different ways depending on server configuration and rewrite rules.
     *
     * @since  2.2
     * @since  2.3.1 Added detection for /login rewrite rule and $pagenow check
     * @return bool True if this is a login page request
     */
    private function is_login_page_request() {
        // Method 1: Check the global $pagenow variable
        // WordPress sets this very early to indicate the current page
        global $pagenow;
        if ( isset( $pagenow ) && 'wp-login.php' === $pagenow ) {
            return true;
        }

        // Method 2: Check the script name for direct wp-login.php access
        $script_name = isset( $_SERVER['SCRIPT_NAME'] ) ? sanitize_text_field( wp_unslash( $_SERVER['SCRIPT_NAME'] ) ) : '';

        if ( strpos( $script_name, 'wp-login.php' ) !== false ) {
            return true;
        }

        // Method 3: Check REQUEST_URI for wp-login.php
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

        if ( strpos( $request_uri, 'wp-login.php' ) !== false ) {
            return true;
        }

        // Method 4: Check PHP_SELF for wp-login.php
        // Some server configurations set this differently
        $php_self = isset( $_SERVER['PHP_SELF'] ) ? sanitize_text_field( wp_unslash( $_SERVER['PHP_SELF'] ) ) : '';

        if ( strpos( $php_self, 'wp-login.php' ) !== false ) {
            return true;
        }

        // Method 5: Check for /login path (WordPress rewrite rule since version 3.0)
        // WordPress serves wp-login.php via a rewrite rule when visiting /login
        // The URL doesn't change, so we need to check the request path directly
        $request_path = $this->get_request_path();

        // Block /login and /login/ (with optional trailing slash)
        if ( 'login' === $request_path ) {
            return true;
        }

        return false;
    }

    /**
     * Check if current request is for the WordPress admin area
     *
     * @since  2.2
     * @return bool True if this is an admin request
     */
    private function is_admin_request() {
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

        // Check if the request contains wp-admin
        if ( strpos( $request_uri, 'wp-admin' ) !== false ) {
            return true;
        }

        return false;
    }

    /**
     * Check if this is an allowed admin request (AJAX, admin-post, etc.)
     *
     * These endpoints should remain accessible even for non-logged-in users
     * as they may be used by frontend forms.
     *
     * @since  2.2
     * @return bool True if this is an allowed admin request
     */
    private function is_allowed_admin_request() {
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';

        // Allow admin-ajax.php (frontend AJAX)
        if ( strpos( $request_uri, 'admin-ajax.php' ) !== false ) {
            return true;
        }

        // Allow admin-post.php (frontend form submissions)
        if ( strpos( $request_uri, 'admin-post.php' ) !== false ) {
            return true;
        }

        return false;
    }

    /**
     * Check if this is a REST API request
     *
     * @since  2.2
     * @return bool True if this is a REST request
     */
    private function is_rest_request() {
        // Check if REST_REQUEST constant is defined
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return true;
        }

        // Check the request URI for REST API path
        $request_uri = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
        $rest_prefix = rest_get_url_prefix();

        if ( strpos( $request_uri, '/' . $rest_prefix . '/' ) !== false ) {
            return true;
        }

        return false;
    }

    /**
     * Trigger a 404 error response
     *
     * Sets the WordPress query to 404 status and loads the theme's
     * 404 template. This ensures the 404 looks consistent with the
     * site's design.
     *
     * @since 2.2
     * @return void
     */
    private function trigger_404() {
        global $wp_query;

        // Set 404 status
        status_header( 404 );
        nocache_headers();

        // If wp_query is available, set it to 404
        if ( $wp_query ) {
            $wp_query->set_404();
        }

        // Try to load the theme's 404 template
        $template_404 = get_404_template();

        if ( $template_404 ) {
            include $template_404;
            exit;
        }

        // Fallback: simple 404 response
        wp_die(
            esc_html__( 'Page not found.', 'security-tools' ),
            esc_html__( '404 Not Found', 'security-tools' ),
            array( 'response' => 404 )
        );
    }

    /**
     * Load the WordPress login page
     *
     * This method includes the wp-login.php file to render the login form
     * while hiding the actual file location.
     *
     * @since 2.2
     * @return void
     */
    private function load_login_page() {
        // Define that we're on the login page
        // This constant helps other parts of WordPress know we're on the login screen
        if ( ! defined( 'DOING_SECURITY_TOOLS_LOGIN' ) ) {
            define( 'DOING_SECURITY_TOOLS_LOGIN', true );
        }

        // Get the path to wp-login.php
        $login_path = ABSPATH . 'wp-login.php';

        if ( file_exists( $login_path ) ) {
            // Include the login file
            // We need to use require since wp-login.php contains functions and exit calls
            require $login_path;
            exit;
        }

        // Fallback if wp-login.php doesn't exist (shouldn't happen)
        wp_die(
            esc_html__( 'Login page not available.', 'security-tools' ),
            esc_html__( 'Error', 'security-tools' ),
            array( 'response' => 500 )
        );
    }

    /**
     * Filter the login URL to return our custom URL
     *
     * @since  2.2
     * @param  string $login_url    The original login URL
     * @param  string $redirect     The redirect URL after login
     * @param  bool   $force_reauth Whether to force reauthentication
     * @return string The filtered login URL
     */
    public function filter_login_url( $login_url, $redirect = '', $force_reauth = false ) {
        // Build custom login URL
        $custom_login_url = home_url( $this->custom_slug );

        // Add redirect parameter if specified
        if ( ! empty( $redirect ) ) {
            $custom_login_url = add_query_arg( 'redirect_to', urlencode( $redirect ), $custom_login_url );
        }

        // Add reauth parameter if forcing reauthentication
        if ( $force_reauth ) {
            $custom_login_url = add_query_arg( 'reauth', '1', $custom_login_url );
        }

        return $custom_login_url;
    }

    /**
     * Filter the logout URL
     *
     * @since  2.2
     * @param  string $logout_url The original logout URL
     * @param  string $redirect   The redirect URL after logout
     * @return string The filtered logout URL
     */
    public function filter_logout_url( $logout_url, $redirect = '' ) {
        // Build custom logout URL
        $custom_logout_url = home_url( $this->custom_slug );
        $custom_logout_url = add_query_arg( 'action', 'logout', $custom_logout_url );

        // Add nonce
        $custom_logout_url = wp_nonce_url( $custom_logout_url, 'log-out' );

        // Add redirect parameter if specified
        if ( ! empty( $redirect ) ) {
            $custom_logout_url = add_query_arg( 'redirect_to', urlencode( $redirect ), $custom_logout_url );
        }

        return $custom_logout_url;
    }

    /**
     * Filter the lost password URL
     *
     * @since  2.2
     * @param  string $lostpassword_url The original lost password URL
     * @param  string $redirect         The redirect URL
     * @return string The filtered lost password URL
     */
    public function filter_lostpassword_url( $lostpassword_url, $redirect = '' ) {
        $custom_url = home_url( $this->custom_slug );
        $custom_url = add_query_arg( 'action', 'lostpassword', $custom_url );

        if ( ! empty( $redirect ) ) {
            $custom_url = add_query_arg( 'redirect_to', urlencode( $redirect ), $custom_url );
        }

        return $custom_url;
    }

    /**
     * Filter the registration URL
     *
     * @since  2.2
     * @param  string $register_url The original registration URL
     * @return string The filtered registration URL
     */
    public function filter_register_url( $register_url ) {
        $custom_url = home_url( $this->custom_slug );
        $custom_url = add_query_arg( 'action', 'register', $custom_url );

        return $custom_url;
    }

    /**
     * Filter site_url for login-related paths
     *
     * This catches calls like site_url('wp-login.php') that bypass
     * the login_url filter.
     *
     * @since  2.2
     * @param  string      $url     The complete site URL
     * @param  string      $path    The requested path
     * @param  string|null $scheme  The URL scheme
     * @param  int|null    $blog_id Blog ID (multisite)
     * @return string The filtered URL
     */
    public function filter_site_url( $url, $path, $scheme, $blog_id ) {
        return $this->maybe_replace_login_url( $url, $path );
    }

    /**
     * Filter network_site_url for multisite compatibility
     *
     * @since  2.2
     * @param  string      $url    The complete network site URL
     * @param  string      $path   The requested path
     * @param  string|null $scheme The URL scheme
     * @return string The filtered URL
     */
    public function filter_network_site_url( $url, $path, $scheme ) {
        return $this->maybe_replace_login_url( $url, $path );
    }

    /**
     * Replace wp-login.php in URL with custom slug if applicable
     *
     * @since  2.2
     * @param  string $url  The URL to filter
     * @param  string $path The path component
     * @return string The potentially modified URL
     */
    private function maybe_replace_login_url( $url, $path ) {
        // Only modify URLs that reference wp-login.php
        if ( strpos( $path, 'wp-login.php' ) === false ) {
            return $url;
        }

        // Extract query string from path if present
        $query_string = '';
        if ( strpos( $path, '?' ) !== false ) {
            list( $path_only, $query_string ) = explode( '?', $path, 2 );
        }

        // Build new URL with custom slug
        $new_url = home_url( $this->custom_slug );

        // Re-add query string if present
        if ( ! empty( $query_string ) ) {
            $new_url .= '?' . $query_string;
        }

        return $new_url;
    }

    /**
     * Filter wp_redirect to catch login redirects
     *
     * This method handles redirects to wp-login.php differently based on
     * authentication status:
     *
     * - Logged-in users: Redirect to custom login URL (preserves logout,
     *   password change, and other authenticated actions)
     * - Non-logged-in users: Trigger 404 to prevent login page exposure
     *   through canonical redirects (e.g., /login, /admin slugs)
     *
     * SECURITY FIX (v2.3.1): Previously, canonical redirects from URLs like
     * /login and /admin would expose the custom login page to non-authenticated
     * users. Now these attempts result in a 404 error.
     *
     * @since  2.2
     * @since  2.3.1 Added 404 response for non-authenticated redirect attempts
     * @param  string $location The redirect location
     * @param  int    $status   The HTTP status code
     * @return string The filtered redirect location
     */
    public function filter_wp_redirect( $location, $status ) {
        // Check if redirecting to wp-login.php
        if ( strpos( $location, 'wp-login.php' ) !== false ) {

            // For non-logged-in users, trigger 404 instead of exposing login page
            // This prevents /login, /admin, and other canonical redirects from
            // revealing the custom login URL
            if ( ! is_user_logged_in() ) {
                $this->trigger_404();
                // Note: trigger_404() calls exit, so this line won't execute
            }

            // For logged-in users, redirect to custom login URL
            // This preserves logout, password change, and other authenticated flows
            $parsed = wp_parse_url( $location );
            $query  = isset( $parsed['query'] ) ? $parsed['query'] : '';

            // Build new location with custom slug
            $new_location = home_url( $this->custom_slug );

            if ( ! empty( $query ) ) {
                $new_location .= '?' . $query;
            }

            return $new_location;
        }

        return $location;
    }

    /**
     * Check if the Hide Login feature is currently enabled
     *
     * Static method for external checks.
     *
     * @since  2.2
     * @return bool True if feature is enabled and slug is set
     */
    public static function is_feature_active() {
        $enabled = Security_Tools_Utils::get_bool_option( Security_Tools_Utils::OPTION_HIDE_LOGIN_ENABLED );
        $slug    = get_option( Security_Tools_Utils::OPTION_HIDE_LOGIN_SLUG, '' );

        return $enabled && ! empty( $slug );
    }

    /**
     * Get the current custom login URL
     *
     * Static method for external use.
     *
     * @since  2.2
     * @return string|false The custom login URL or false if not configured
     */
    public static function get_custom_login_url() {
        if ( ! self::is_feature_active() ) {
            return false;
        }

        $slug = get_option( Security_Tools_Utils::OPTION_HIDE_LOGIN_SLUG, '' );
        $slug = sanitize_title( $slug );

        if ( empty( $slug ) ) {
            return false;
        }

        return home_url( $slug );
    }
}
