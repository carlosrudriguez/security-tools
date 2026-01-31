<?php
/**
 * Security Tools - Admin Initialization
 *
 * Handles admin-specific initialization including menu registration,
 * asset enqueueing, and coordinating admin components.
 *
 * @package    Security_Tools
 * @subpackage Includes
 * @version    2.5
 * @author     Carlos Rodríguez
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Class Security_Tools_Admin
 *
 * Coordinates all admin-side functionality.
 * Uses singleton pattern to ensure only one instance exists.
 *
 * @since 1.2
 */
class Security_Tools_Admin {

    /**
     * Single instance of this class
     *
     * @var Security_Tools_Admin
     */
    private static $instance = null;

    /**
     * Admin page instance
     *
     * @var Security_Tools_Admin_Page
     */
    private $admin_page;

    /**
     * Admin settings instance
     *
     * @var Security_Tools_Admin_Settings
     */
    private $admin_settings;

    /**
     * Admin notices instance
     *
     * @var Security_Tools_Admin_Notices
     */
    private $admin_notices;

    /**
     * Get the singleton instance
     *
     * @since  1.2
     * @return Security_Tools_Admin
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - Initialize admin components
     *
     * Private to enforce singleton pattern.
     *
     * @since 1.2
     */
    private function __construct() {
        $this->init_components();
        $this->register_hooks();
    }

    /**
     * Initialize admin component classes
     *
     * @since 1.2
     * @return void
     */
    private function init_components() {
        $this->admin_settings = new Security_Tools_Admin_Settings();
        $this->admin_notices  = new Security_Tools_Admin_Notices();
        $this->admin_page     = new Security_Tools_Admin_Page();
    }

    /**
     * Register WordPress hooks
     *
     * @since 1.2
     * @since 2.1 Added autohide menu functionality
     * @since 2.4 Added pre-processing for empty array options
     * @return void
     */
    private function register_hooks() {
        // Add admin menu
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );

        // Hide menu if autohide is enabled (runs after menu is registered)
        add_action( 'admin_menu', array( $this, 'maybe_hide_admin_menu' ), 9999 );

        // Enqueue admin assets
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );

        // Handle settings reset
        add_action( 'admin_init', array( $this, 'handle_reset' ) );

        // Pre-process empty array options before Settings API runs
        // Priority 5 ensures this runs before register_settings (priority 10)
        add_action( 'admin_init', array( $this, 'preprocess_empty_array_options' ), 5 );
    }

    /**
     * Add the Security Tools top-level menu and subpages
     *
     * Creates a top-level menu positioned as the first item in the dashboard,
     * with 9 subpages for different settings categories.
     *
     * @since 1.2
     * @since 2.0 Changed from submenu to top-level menu with subpages
     * @since 2.1 Changed position to 1 (first menu item)
     * @return void
     */
    public function add_admin_menu() {
        // Add top-level menu
        // Position 1 places it as the first menu item (after Dashboard at 2)
        // Using 1.1 to ensure it appears before Dashboard
        add_menu_page(
            __( 'Security Tools', 'security-tools' ),       // Page title
            __( 'Security Tools', 'security-tools' ),       // Menu title
            Security_Tools_Utils::REQUIRED_CAPABILITY,      // Capability required
            Security_Tools_Utils::MENU_SLUG,                // Menu slug
            array( $this->admin_page, 'render_general' ),   // Callback for default page
            'dashicons-shield',                             // Menu icon
            '1.1'                                           // Position (first item)
        );

        // Add subpages
        // Note: First submenu replaces the top-level menu link
        $subpages = $this->get_subpages();

        foreach ( $subpages as $slug => $config ) {
            add_submenu_page(
                Security_Tools_Utils::MENU_SLUG,            // Parent menu slug
                $config['title'],                           // Page title
                $config['menu_title'],                      // Menu title
                Security_Tools_Utils::REQUIRED_CAPABILITY,  // Capability required
                $slug,                                      // Submenu slug
                $config['callback']                         // Callback function
            );
        }
    }

    /**
     * Get configuration for all subpages
     *
     * Returns an array of subpage configurations including title,
     * menu title, and render callback for each page.
     *
     * @since  2.0
     * @return array Subpage configurations keyed by slug
     */
    private function get_subpages() {
        return array(
            Security_Tools_Utils::PAGE_GENERAL => array(
                'title'      => __( 'General Settings', 'security-tools' ),
                'menu_title' => __( 'General', 'security-tools' ),
                'callback'   => array( $this->admin_page, 'render_general' ),
            ),
            Security_Tools_Utils::PAGE_BRANDING => array(
                'title'      => __( 'Branding Settings', 'security-tools' ),
                'menu_title' => __( 'Branding', 'security-tools' ),
                'callback'   => array( $this->admin_page, 'render_branding' ),
            ),
            Security_Tools_Utils::PAGE_SYSTEM_CONTROLS => array(
                'title'      => __( 'System Controls', 'security-tools' ),
                'menu_title' => __( 'System Controls', 'security-tools' ),
                'callback'   => array( $this->admin_page, 'render_system_controls' ),
            ),
            Security_Tools_Utils::PAGE_ADMINS => array(
                'title'      => __( 'Hidden Administrators', 'security-tools' ),
                'menu_title' => __( 'Admins', 'security-tools' ),
                'callback'   => array( $this->admin_page, 'render_admins' ),
            ),
            Security_Tools_Utils::PAGE_PLUGINS => array(
                'title'      => __( 'Hidden Plugins', 'security-tools' ),
                'menu_title' => __( 'Plugins', 'security-tools' ),
                'callback'   => array( $this->admin_page, 'render_plugins' ),
            ),
            Security_Tools_Utils::PAGE_THEMES => array(
                'title'      => __( 'Hidden Themes', 'security-tools' ),
                'menu_title' => __( 'Themes', 'security-tools' ),
                'callback'   => array( $this->admin_page, 'render_themes' ),
            ),
            Security_Tools_Utils::PAGE_WIDGETS => array(
                'title'      => __( 'Hidden Widgets', 'security-tools' ),
                'menu_title' => __( 'Widgets', 'security-tools' ),
                'callback'   => array( $this->admin_page, 'render_widgets' ),
            ),
            Security_Tools_Utils::PAGE_ADMIN_BAR => array(
                'title'      => __( 'Hidden Admin Bar Items', 'security-tools' ),
                'menu_title' => __( 'Admin Bar', 'security-tools' ),
                'callback'   => array( $this->admin_page, 'render_admin_bar' ),
            ),
            Security_Tools_Utils::PAGE_METABOXES => array(
                'title'      => __( 'Hidden Metaboxes', 'security-tools' ),
                'menu_title' => __( 'Metaboxes', 'security-tools' ),
                'callback'   => array( $this->admin_page, 'render_metaboxes' ),
            ),
        );
    }

    /**
     * Enqueue admin CSS and JavaScript
     *
     * Only loads on Security Tools settings pages.
     * Enqueues Media Library scripts on Branding page for logo uploader.
     *
     * @since 1.2
     * @since 2.0 Updated to support multiple subpages
     * @since 2.3 Added Media Library support for Branding page
     * @param string $hook_suffix The current admin page hook
     * @return void
     */
    public function enqueue_assets( $hook_suffix ) {
        // Build list of valid hook suffixes for our pages
        // Top-level page: toplevel_page_{slug}
        // Subpages: security-tools_page_{slug}
        $valid_hooks = array(
            'toplevel_page_' . Security_Tools_Utils::MENU_SLUG,
        );

        // Add all subpage hooks
        foreach ( Security_Tools_Utils::get_all_page_slugs() as $slug ) {
            // Skip the main page as it's already added above
            if ( $slug === Security_Tools_Utils::MENU_SLUG ) {
                continue;
            }
            $valid_hooks[] = 'security-tools_page_' . $slug;
        }

        // Only load on our settings pages
        if ( ! in_array( $hook_suffix, $valid_hooks, true ) ) {
            return;
        }

        // Enqueue CSS
        wp_enqueue_style(
            'security-tools-admin',
            SECURITY_TOOLS_URL . 'assets/css/admin-styles.css',
            array(),
            SECURITY_TOOLS_VERSION
        );

        // Check if we're on the Branding page for Media Library
        $is_branding_page = ( 'security-tools_page_' . Security_Tools_Utils::PAGE_BRANDING === $hook_suffix );

        // Determine script dependencies
        $script_deps = array();
        if ( $is_branding_page ) {
            // Enqueue WordPress Media Library scripts for logo uploader
            wp_enqueue_media();
            $script_deps[] = 'media-upload';
        }

        // Enqueue JavaScript
        wp_enqueue_script(
            'security-tools-admin',
            SECURITY_TOOLS_URL . 'assets/js/admin-scripts.js',
            $script_deps,
            SECURITY_TOOLS_VERSION,
            true // Load in footer
        );

        // Localize script with translatable strings for Media Library
        if ( $is_branding_page ) {
            wp_localize_script(
                'security-tools-admin',
                'securityToolsMedia',
                array(
                    'title'        => __( 'Select Login Logo', 'security-tools' ),
                    'button'       => __( 'Use as Login Logo', 'security-tools' ),
                    'selectButton' => __( 'Select Logo', 'security-tools' ),
                    'changeButton' => __( 'Change Logo', 'security-tools' ),
                )
            );
        }
    }

    /**
     * Handle settings reset request
     *
     * Processes the reset form submission and deletes all plugin options.
     * After reset, redirects back to the General settings page.
     *
     * @since 1.2
     * @since 2.0 Added redirect to General page after reset
     * @return void
     */
    public function handle_reset() {
        // Check if reset was requested
        if ( ! isset( $_POST['security_tools_reset'] ) ) {
            return;
        }

        // Verify nonce
        if ( ! isset( $_POST['security_tools_reset_nonce'] ) ||
             ! wp_verify_nonce( $_POST['security_tools_reset_nonce'], 'security_tools_reset_action' ) ) {
            wp_die( __( 'Security check failed', 'security-tools' ) );
        }

        // Check capabilities
        if ( ! Security_Tools_Utils::current_user_can_manage() ) {
            wp_die( __( 'Insufficient permissions', 'security-tools' ) );
        }

        // Delete all main options
        foreach ( Security_Tools_Utils::get_all_option_names() as $option_name ) {
            delete_option( $option_name );
        }

        // Delete all change tracking options
        foreach ( Security_Tools_Utils::get_all_change_tracking_options() as $option_name ) {
            delete_option( $option_name );
        }

        // Set reset success flag
        update_option( Security_Tools_Utils::OPTION_RESET_SUCCESS, true );

        // Redirect back to General page to show the reset notice
        wp_safe_redirect( admin_url( 'admin.php?page=' . Security_Tools_Utils::PAGE_GENERAL ) );
        exit;
    }

    /**
     * Pre-process empty array options before Settings API runs
     *
     * WordPress Settings API only triggers sanitize callbacks when an option
     * key exists in $_POST. For array options stored via checkbox arrays or
     * tokenfield inputs, if all items are unchecked/removed, the key won't
     * exist in $_POST and the option won't be updated (stays at old value).
     *
     * This method detects when a form section was rendered (via marker fields)
     * but the array option is missing from $_POST, and initializes it as an
     * empty array so the sanitize callback will be triggered.
     *
     * @since 2.4
     * @return void
     */
    public function preprocess_empty_array_options() {
        // Only process during settings form submissions to options.php
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by Settings API
        if ( ! isset( $_POST['option_page'] ) ) {
            return;
        }

        // Check if this is an Admin Bar settings submission
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by Settings API
        if ( $_POST['option_page'] !== Security_Tools_Utils::SETTINGS_GROUP_ADMIN_BAR ) {
            return;
        }

        // Check if the CSS tokenfield section was rendered (marker field present)
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by Settings API
        if ( ! isset( $_POST['security_tools_admin_bar_css_rendered'] ) ) {
            return;
        }

        // If the marker is present but the array option is missing, initialize it as empty
        // This ensures the sanitize callback will be triggered with an empty array
        $option_key = Security_Tools_Utils::OPTION_HIDDEN_ADMIN_BAR_CSS;

        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by Settings API
        if ( ! isset( $_POST[ $option_key ] ) ) {
            $_POST[ $option_key ] = array();
        }
    }

    /**
     * Get admin page instance
     *
     * @since  1.2
     * @return Security_Tools_Admin_Page
     */
    public function get_admin_page() {
        return $this->admin_page;
    }

    /**
     * Get admin settings instance
     *
     * @since  1.2
     * @return Security_Tools_Admin_Settings
     */
    public function get_admin_settings() {
        return $this->admin_settings;
    }

    /**
     * Get admin notices instance
     *
     * @since  1.2
     * @return Security_Tools_Admin_Notices
     */
    public function get_admin_notices() {
        return $this->admin_notices;
    }

    /**
     * Hide admin menu if autohide feature is enabled
     *
     * Hides the Security Tools menu and all submenus from the admin sidebar
     * using CSS, while keeping the pages accessible via direct URL or tab navigation.
     *
     * Note: We use CSS to hide the menu instead of remove_menu_page() because
     * removing menu pages also removes capability checks, making pages inaccessible.
     *
     * @since 2.1
     * @return void
     */
    public function maybe_hide_admin_menu() {
        // Check if autohide is enabled
        if ( ! Security_Tools_Utils::get_bool_option( Security_Tools_Utils::OPTION_AUTOHIDE_MENU ) ) {
            return;
        }

        // Hide the menu using CSS instead of removing it
        // This keeps the pages accessible while hiding the menu visually
        add_action( 'admin_head', array( $this, 'output_autohide_css' ) );
    }

    /**
     * Output CSS to hide the Security Tools menu from the admin sidebar
     *
     * Uses the menu item's unique ID to target only the Security Tools menu.
     * The CSS is output in the admin head to ensure it loads on all admin pages.
     *
     * @since 2.1
     * @return void
     */
    public function output_autohide_css() {
        ?>
        <style type="text/css">
            /* Hide Security Tools menu from admin sidebar - Autohide feature */
            #toplevel_page_security-tools {
                display: none !important;
            }
        </style>
        <?php
    }
}
