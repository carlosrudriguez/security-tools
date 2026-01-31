<?php
/**
 * Security Tools - Admin Bar Feature
 *
 * Controls admin bar visibility and hidden items.
 *
 * @package    Security_Tools
 * @subpackage Features
 * @version    2.5
 * @author     Carlos Rodríguez
 *
 * CHANGELOG v1.3:
 * - Removed debug_backtrace() call for performance improvement (Issue #1)
 * - Added $is_collecting boolean flag property to detect data collection state
 * - Simplified is_collecting_data() method to use flag instead of stack inspection
 *
 * CHANGELOG v2.0:
 * - Simplified admin bar initialization for settings page
 * - Removed refresh_for_settings() in favor of on-demand initialization
 * - get_available_items() now always ensures admin bar is properly initialized
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Class Security_Tools_Feature_Admin_Bar
 *
 * Implements admin bar control functionality.
 *
 * @since 1.2
 * @since 2.0 Simplified initialization approach for multi-page settings
 */
class Security_Tools_Feature_Admin_Bar {

    /**
     * Flag to indicate if we're currently collecting admin bar data
     *
     * Used to prevent removing items while collecting data for the settings page.
     * This replaces the expensive debug_backtrace() call used in v1.2.
     *
     * @since 1.3
     * @var bool
     */
    private $is_collecting = false;

    /**
     * Cached admin bar items
     *
     * Stores the collected admin bar items to avoid re-initialization
     * during the same request.
     *
     * @since 2.0
     * @var array|null
     */
    private $cached_items = null;

    /**
     * Constructor - Register hooks
     *
     * @since 1.2
     * @since 2.0 Removed refresh_for_settings hook - now handled on-demand
     * @since 2.4 Added CSS-based hiding setup
     */
    public function __construct() {
        // Frontend admin bar toggle
        add_action( 'init', array( $this, 'maybe_disable_frontend_bar' ) );

        // Hidden items (node removal method)
        add_action( 'wp_loaded', array( $this, 'setup_item_removal' ) );

        // Hidden items (CSS-based method)
        add_action( 'wp_loaded', array( $this, 'setup_css_hiding' ) );
    }

    /**
     * Disable frontend admin bar if enabled
     *
     * @since 1.2
     */
    public function maybe_disable_frontend_bar() {
        if ( ! Security_Tools_Utils::get_bool_option( Security_Tools_Utils::OPTION_DISABLE_FRONTEND_ADMIN_BAR ) ) {
            return;
        }

        add_filter( 'show_admin_bar', '__return_false' );

        // Hide profile option using wp_add_inline_style for CSP compliance
        add_action( 'admin_enqueue_scripts', array( $this, 'hide_profile_option' ) );
    }

    /**
     * Hide admin bar option in user profiles
     *
     * Updated in v1.3 to use wp_add_inline_style() for better CSP compliance.
     *
     * @since 1.2
     * @since 1.3 Changed from echo to wp_add_inline_style()
     */
    public function hide_profile_option() {
        $screen = get_current_screen();
        
        // Only run on profile and user-edit pages
        if ( ! $screen || ! in_array( $screen->id, array( 'profile', 'user-edit' ), true ) ) {
            return;
        }

        $css = '.show-admin-bar { display: none !important; }';

        wp_register_style( 'security-tools-hide-profile-bar', false );
        wp_enqueue_style( 'security-tools-hide-profile-bar' );
        wp_add_inline_style( 'security-tools-hide-profile-bar', $css );
    }

    /**
     * Setup admin bar item removal
     *
     * @since 1.2
     */
    public function setup_item_removal() {
        $hidden = Security_Tools_Utils::get_array_option( Security_Tools_Utils::OPTION_HIDDEN_ADMIN_BAR );

        if ( empty( $hidden ) ) {
            return;
        }

        add_action( 'wp_before_admin_bar_render', array( $this, 'remove_items' ), 999 );
        add_action( 'admin_bar_menu', array( $this, 'remove_items' ), 999 );
        add_action( 'admin_enqueue_scripts', array( $this, 'add_hide_css' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'add_hide_css' ) );
    }

    /**
     * Remove hidden admin bar items
     *
     * @since 1.2
     */
    public function remove_items() {
        // Don't remove during data collection for settings page
        // v1.3: Now uses simple boolean flag instead of debug_backtrace()
        if ( $this->is_collecting_data() ) {
            return;
        }

        global $wp_admin_bar;

        if ( ! $wp_admin_bar || ! method_exists( $wp_admin_bar, 'remove_node' ) ) {
            return;
        }

        $hidden = Security_Tools_Utils::get_array_option( Security_Tools_Utils::OPTION_HIDDEN_ADMIN_BAR );

        foreach ( $hidden as $item_id ) {
            if ( $wp_admin_bar->get_node( $item_id ) ) {
                $wp_admin_bar->remove_node( $item_id );
            }
        }
    }

    /**
     * Add CSS to hide admin bar items
     *
     * Updated in v1.3 to use wp_add_inline_style() for better CSP compliance.
     *
     * @since 1.2
     * @since 1.3 Changed from echo to wp_add_inline_style()
     */
    public function add_hide_css() {
        $hidden = Security_Tools_Utils::get_array_option( Security_Tools_Utils::OPTION_HIDDEN_ADMIN_BAR );

        if ( empty( $hidden ) ) {
            return;
        }

        $css = '';
        foreach ( $hidden as $item_id ) {
            $css .= '#wpadminbar #wp-admin-bar-' . esc_attr( $item_id ) . ',';
            $css .= '#wpadminbar #' . esc_attr( $item_id ) . ' { display: none !important; }';
        }

        wp_register_style( 'security-tools-hide-admin-bar-items', false );
        wp_enqueue_style( 'security-tools-hide-admin-bar-items' );
        wp_add_inline_style( 'security-tools-hide-admin-bar-items', $css );
    }

    /**
     * Setup CSS-based admin bar item hiding
     *
     * Checks if there are CSS IDs configured for hiding and registers
     * the appropriate hooks to inject the hiding CSS.
     *
     * @since 2.4
     */
    public function setup_css_hiding() {
        $hidden_css = Security_Tools_Utils::get_array_option( Security_Tools_Utils::OPTION_HIDDEN_ADMIN_BAR_CSS );

        if ( empty( $hidden_css ) ) {
            return;
        }

        // Add CSS hiding on both admin and frontend (same as main feature)
        add_action( 'admin_enqueue_scripts', array( $this, 'add_css_id_hide_styles' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'add_css_id_hide_styles' ) );
    }

    /**
     * Add CSS to hide admin bar items by their element ID
     *
     * Injects CSS rules to hide admin bar elements using their CSS ID.
     * This is useful for items that cannot be removed via WP_Admin_Bar methods.
     * Uses `display: none !important` to ensure the elements are hidden.
     *
     * @since 2.4
     */
    public function add_css_id_hide_styles() {
        $hidden_css = Security_Tools_Utils::get_array_option( Security_Tools_Utils::OPTION_HIDDEN_ADMIN_BAR_CSS );

        if ( empty( $hidden_css ) ) {
            return;
        }

        $css = '';
        foreach ( $hidden_css as $css_id ) {
            // Generate CSS rule for hiding by ID
            // Target the element directly within the admin bar
            $css .= '#wpadminbar li#' . esc_attr( $css_id ) . ' { display: none !important; } ';
        }

        // Use a separate handle to avoid conflicts with the main hiding CSS
        wp_register_style( 'security-tools-hide-admin-bar-css-ids', false );
        wp_enqueue_style( 'security-tools-hide-admin-bar-css-ids' );
        wp_add_inline_style( 'security-tools-hide-admin-bar-css-ids', $css );
    }

    /**
     * Check if currently collecting data for settings
     *
     * v1.3: Simplified to use boolean flag instead of expensive debug_backtrace().
     * The flag is set/unset by get_available_items() and initialize_admin_bar().
     *
     * @since 1.2
     * @since 1.3 Changed from debug_backtrace() to boolean flag check
     * @return bool
     */
    private function is_collecting_data() {
        return $this->is_collecting;
    }

    /**
     * Initialize admin bar for data collection
     *
     * Creates a fresh WP_Admin_Bar instance and populates it with
     * all standard WordPress admin bar items.
     *
     * @since 1.2
     * @since 1.3 Added $is_collecting flag management
     * @since 2.0 Made more robust - always creates fresh instance
     */
    private function initialize_admin_bar() {
        global $wp_admin_bar;

        // Set flag to prevent item removal during initialization
        $this->is_collecting = true;

        // Load the WP_Admin_Bar class if needed
        if ( ! class_exists( 'WP_Admin_Bar' ) ) {
            require_once ABSPATH . WPINC . '/class-wp-admin-bar.php';
        }

        // Always create a fresh instance for collecting items
        $wp_admin_bar = new WP_Admin_Bar();

        // Initialize the admin bar
        $wp_admin_bar->initialize();

        // Call WordPress functions to populate admin bar items
        // These are the core functions that add standard items
        
        // Initialize admin bar (sets up user, checks capabilities)
        _wp_admin_bar_init();
        
        // Fire the init action for plugins
        do_action( 'admin_bar_init' );
        
        // Add standard menus
        do_action( 'add_admin_bar_menus' );

        // Add core WordPress items
        if ( function_exists( 'wp_admin_bar_wp_menu' ) ) {
            wp_admin_bar_wp_menu( $wp_admin_bar );
        }
        if ( function_exists( 'wp_admin_bar_my_account_menu' ) ) {
            wp_admin_bar_my_account_menu( $wp_admin_bar );
        }
        if ( function_exists( 'wp_admin_bar_site_menu' ) ) {
            wp_admin_bar_site_menu( $wp_admin_bar );
        }
        if ( function_exists( 'wp_admin_bar_customize_menu' ) ) {
            wp_admin_bar_customize_menu( $wp_admin_bar );
        }
        if ( function_exists( 'wp_admin_bar_updates_menu' ) ) {
            wp_admin_bar_updates_menu( $wp_admin_bar );
        }
        if ( function_exists( 'wp_admin_bar_comments_menu' ) ) {
            wp_admin_bar_comments_menu( $wp_admin_bar );
        }
        if ( function_exists( 'wp_admin_bar_new_content_menu' ) ) {
            wp_admin_bar_new_content_menu( $wp_admin_bar );
        }
        if ( function_exists( 'wp_admin_bar_edit_menu' ) ) {
            wp_admin_bar_edit_menu( $wp_admin_bar );
        }

        // Let plugins add their items
        do_action( 'admin_bar_menu', $wp_admin_bar, 200 );

        // Reset flag after initialization
        $this->is_collecting = false;
    }

    /**
     * Get available admin bar items for settings page
     *
     * Returns all top-level admin bar items that can be hidden.
     * Uses caching to avoid re-initialization during the same request.
     *
     * @since 1.2
     * @since 1.3 Added $is_collecting flag management
     * @since 2.0 Added caching, improved initialization reliability
     * @return array
     */
    public function get_available_items() {
        // Return cached items if available
        if ( $this->cached_items !== null ) {
            return $this->cached_items;
        }

        global $wp_admin_bar;

        // Set flag to prevent item removal during data collection
        $this->is_collecting = true;

        // Always initialize fresh for settings page to ensure we get all items
        $this->initialize_admin_bar();

        $items = array();

        if ( $wp_admin_bar && method_exists( $wp_admin_bar, 'get_nodes' ) ) {
            $nodes = $wp_admin_bar->get_nodes();

            if ( $nodes && is_array( $nodes ) ) {
                foreach ( $nodes as $node ) {
                    // Only include root-level items (no parent)
                    if ( ! empty( $node->id ) && empty( $node->parent ) ) {
                        // Get clean title
                        $title = isset( $node->title ) ? wp_strip_all_tags( $node->title ) : '';
                        
                        // Skip items with empty or whitespace-only titles
                        $title = trim( $title );
                        if ( empty( $title ) || $title === '&nbsp;' ) {
                            continue;
                        }

                        $items[ $node->id ] = array(
                            'title' => $title,
                            'href'  => isset( $node->href ) ? $node->href : '',
                        );
                    }
                }
            }
        }

        // Sort by title alphabetically
        uasort( $items, function( $a, $b ) {
            return strcasecmp( $a['title'], $b['title'] );
        } );

        // Reset flag after data collection
        $this->is_collecting = false;

        // Cache the items
        $this->cached_items = $items;

        return $items;
    }
}
