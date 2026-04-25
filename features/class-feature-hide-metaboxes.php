<?php
/**
 * Security Tools - Hide Metaboxes Feature
 *
 * Hides selected metaboxes from post/page editing screens.
 * Includes dynamic metabox discovery for detecting third-party metaboxes
 * from plugins and themes.
 *
 * @package    Security_Tools
 * @subpackage Features
 * @author     Carlos Rodríguez
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Class Security_Tools_Feature_Hide_Metaboxes
 *
 * Implements metabox hiding functionality with dynamic discovery.
 *
 * Discovery works in two ways:
 * 1. Automatic: When editing any post type, metaboxes are captured via JavaScript
 *    and sent to the server via AJAX to update the discovered metaboxes list.
 * 2. Manual: A "Scan for Metaboxes" button on the settings page triggers a scan
 *    across all post types using hidden iframes.
 *
 * @since 1.2
 * @since 2.5 Added dynamic metabox discovery system
 */
class Security_Tools_Feature_Hide_Metaboxes {

    /**
     * Constructor - Register hooks
     *
     * @since 1.2
     * @since 2.5 Added hooks for metabox discovery and AJAX handlers
     */
    public function __construct() {
        // Metabox hiding functionality
        add_action( 'wp_loaded', array( $this, 'maybe_hide_metaboxes' ), 999 );

        // Automatic metabox discovery on post edit screens
        add_action( 'admin_enqueue_scripts', array( $this, 'maybe_enqueue_discovery_script' ) );

        // AJAX handlers for metabox discovery
        add_action( 'wp_ajax_security_tools_discover_metaboxes', array( $this, 'ajax_discover_metaboxes' ) );
        add_action( 'wp_ajax_security_tools_manual_scan', array( $this, 'ajax_manual_scan' ) );
        add_action( 'wp_ajax_security_tools_get_scan_url', array( $this, 'ajax_get_scan_url' ) );
    }

    /**
     * ==========================================================================
     * METABOX HIDING FUNCTIONALITY
     * ==========================================================================
     */

    /**
     * Setup metabox hiding if enabled
     *
     * @since 1.2
     * @since 2.5 Fixed hook timing for Classic Editor - use add_meta_boxes instead of admin_init
     * @since 2.5 Skip hiding during manual scan to allow discovery of all metaboxes
     */
    public function maybe_hide_metaboxes() {
        // Skip hiding during manual scan mode
        // This allows the discovery script to capture ALL metaboxes including hidden ones
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['security_tools_scan'] ) && '1' === sanitize_key( wp_unslash( $_GET['security_tools_scan'] ) ) ) {
            return;
        }

        $hidden = Security_Tools_Utils::get_array_option( Security_Tools_Utils::OPTION_HIDDEN_METABOXES );

        if ( empty( $hidden ) ) {
            return;
        }

        // Remove metaboxes AFTER they've been registered
        // Priority 999 ensures we run after plugins/themes have added their metaboxes
        add_action( 'add_meta_boxes', array( $this, 'remove_metaboxes' ), 999 );
        
        // Also try on do_meta_boxes for extra coverage (some plugins add late)
        add_action( 'do_meta_boxes', array( $this, 'remove_metaboxes' ), 999 );
        
        // CSS fallback for stubborn metaboxes
        add_action( 'admin_head', array( $this, 'hide_metaboxes_css' ), 999 );
        
        // JavaScript fallback for Classic Editor (catches late-loading metaboxes)
        add_action( 'admin_enqueue_scripts', array( $this, 'hide_metaboxes_js' ) );
        
        // Gutenberg panel hiding
        add_action( 'enqueue_block_editor_assets', array( $this, 'hide_gutenberg_panels' ) );
    }

    /**
     * Remove hidden metaboxes using WordPress API
     *
     * @since 1.2
     */
    public function remove_metaboxes() {
        $hidden     = Security_Tools_Utils::get_array_option( Security_Tools_Utils::OPTION_HIDDEN_METABOXES );
        $post_types = get_post_types( array( 'show_ui' => true ) );
        $contexts   = array( 'normal', 'advanced', 'side' );

        foreach ( $hidden as $metabox_id ) {
            foreach ( $post_types as $post_type ) {
                foreach ( $contexts as $context ) {
                    remove_meta_box( $metabox_id, $post_type, $context );
                }
            }
        }
    }

    /**
     * Hide metaboxes with CSS (fallback for stubborn metaboxes)
     *
     * @since 1.2
     */
    public function hide_metaboxes_css() {
        $screen = get_current_screen();

        if ( ! $screen || $screen->base !== 'post' ) {
            return;
        }

        $hidden = Security_Tools_Utils::get_array_option( Security_Tools_Utils::OPTION_HIDDEN_METABOXES );

        if ( empty( $hidden ) ) {
            return;
        }

        $css = '';
        foreach ( $hidden as $metabox_id ) {
            $safe_id    = Security_Tools_Utils::esc_css_identifier( $metabox_id );
            $safe_label = Security_Tools_Utils::esc_css_string( $metabox_id . '-hide' );

            if ( '' === $safe_id ) {
                continue;
            }

            $css .= '#' . $safe_id . ',';
            $css .= '#' . $safe_id . 'div,';
            $css .= '.postbox#' . $safe_id . ',';
            $css .= '#' . $safe_id . '-hide,';
            $css .= 'label[for="' . $safe_label . '"]';
            $css .= '{ display: none !important; }';
        }

        wp_add_inline_style( 'wp-admin', $css );
    }

    /**
     * Hide metaboxes with JavaScript (fallback for Classic Editor)
     *
     * Some plugins add metaboxes very late or in non-standard ways that
     * remove_meta_box() and CSS cannot catch. This JavaScript fallback
     * runs in the footer and catches any remaining visible metaboxes.
     *
     * @since 2.5
     */
    public function hide_metaboxes_js() {
        $screen = get_current_screen();

        // Only on post edit screens
        if ( ! $screen || 'post' !== $screen->base ) {
            return;
        }

        // Skip if this is the block editor (Gutenberg has its own handler)
        if ( method_exists( $screen, 'is_block_editor' ) && $screen->is_block_editor() ) {
            return;
        }

        $hidden = Security_Tools_Utils::get_array_option( Security_Tools_Utils::OPTION_HIDDEN_METABOXES );

        if ( empty( $hidden ) ) {
            return;
        }

        wp_enqueue_script(
            'security-tools-hide-metaboxes-classic',
            SECURITY_TOOLS_URL . 'assets/js/hide-metaboxes-classic.js',
            array(),
            SECURITY_TOOLS_VERSION,
            true
        );

        wp_localize_script(
            'security-tools-hide-metaboxes-classic',
            'securityToolsHiddenMetaboxes',
            array(
                'ids' => array_values( $hidden ),
            )
        );
    }

    /**
     * Hide Gutenberg editor panels
     *
     * Uses JavaScript to remove panels in the block editor that correspond
     * to hidden classic metaboxes.
     *
     * @since 1.2
     * @since 2.5 Expanded panel mapping for more comprehensive coverage
     */
    public function hide_gutenberg_panels() {
        $hidden = Security_Tools_Utils::get_array_option( Security_Tools_Utils::OPTION_HIDDEN_METABOXES );

        if ( empty( $hidden ) ) {
            return;
        }

        // Map classic metabox IDs to Gutenberg panel names
        // Core WordPress panels
        $panel_map = array(
            'submitdiv'            => 'post-status',
            'categorydiv'          => 'taxonomy-panel-category',
            'tagsdiv-post_tag'     => 'taxonomy-panel-post_tag',
            'postimagediv'         => 'featured-image',
            'postexcerpt'          => 'post-excerpt',
            'commentstatusdiv'     => 'discussion-panel',
            'pageparentdiv'        => 'page-attributes',
            'authordiv'            => 'post-author',
            'slugdiv'              => 'post-link',
            'postcustom'           => 'custom-fields',
            'revisionsdiv'         => 'post-revisions',
        );

        $panels_to_hide   = array();
        $metaboxes_to_css = array();

        foreach ( $hidden as $metabox_id ) {
            if ( isset( $panel_map[ $metabox_id ] ) ) {
                $panels_to_hide[] = $panel_map[ $metabox_id ];
            }
            // Also track for CSS hiding of metabox containers in Gutenberg
            $metaboxes_to_css[] = $metabox_id;
        }

        wp_enqueue_script(
            'security-tools-hide-metaboxes-gutenberg',
            SECURITY_TOOLS_URL . 'assets/js/hide-metaboxes-gutenberg.js',
            array( 'wp-data', 'wp-dom-ready', 'wp-edit-post' ),
            SECURITY_TOOLS_VERSION,
            true
        );

        wp_localize_script(
            'security-tools-hide-metaboxes-gutenberg',
            'securityToolsGutenbergMetaboxes',
            array(
                'panels'    => $panels_to_hide,
                'metaboxes' => $metaboxes_to_css,
            )
        );
    }

    /**
     * ==========================================================================
     * METABOX DISCOVERY - AUTOMATIC
     * ==========================================================================
     */

    /**
     * Enqueue discovery script on post edit screens
     *
     * Adds JavaScript that captures all metaboxes on the page and sends them
     * to the server via AJAX for storage.
     *
     * @since 2.5
     * @param string $hook_suffix The current admin page hook suffix
     */
    public function maybe_enqueue_discovery_script( $hook_suffix ) {
        // Only on post edit screens
        if ( 'post.php' !== $hook_suffix && 'post-new.php' !== $hook_suffix ) {
            return;
        }

        // Only for users who can manage options
        if ( ! Security_Tools_Utils::current_user_can_manage() ) {
            return;
        }

        // Get current post type
        $post_type = $this->get_current_post_type();
        if ( ! $post_type ) {
            return;
        }

        $is_scan = isset( $_GET['security_tools_scan'] ) && '1' === sanitize_key( wp_unslash( $_GET['security_tools_scan'] ) );

        wp_enqueue_script(
            'security-tools-discover-metaboxes',
            SECURITY_TOOLS_URL . 'assets/js/discover-metaboxes.js',
            array(),
            SECURITY_TOOLS_VERSION,
            true
        );

        wp_localize_script(
            'security-tools-discover-metaboxes',
            'securityToolsMetaboxDiscovery',
            array(
                'ajaxurl'  => admin_url( 'admin-ajax.php' ),
                'nonce'    => wp_create_nonce( 'security_tools_discover_metaboxes' ),
                'postType' => $post_type,
                'scanMode' => $is_scan ? '1' : '0',
            )
        );
    }

    /**
     * Get the current post type being edited
     *
     * @since 2.5
     * @return string|false Post type slug or false if not determinable
     */
    private function get_current_post_type() {
        global $typenow, $post;

        if ( $typenow ) {
            return $typenow;
        }

        if ( $post && isset( $post->post_type ) ) {
            return $post->post_type;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['post_type'] ) ) {
            return sanitize_key( $_GET['post_type'] );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['post'] ) ) {
            $post_id   = absint( $_GET['post'] );
            $post_obj  = get_post( $post_id );
            if ( $post_obj ) {
                return $post_obj->post_type;
            }
        }

        return 'post'; // Default fallback
    }

    /**
     * AJAX handler for automatic metabox discovery
     *
     * Receives discovered metaboxes from post edit screens and merges them
     * into the stored discovered metaboxes option.
     *
     * @since 2.5
     */
    public function ajax_discover_metaboxes() {
        // Verify nonce
        if ( ! check_ajax_referer( 'security_tools_discover_metaboxes', 'nonce', false ) ) {
            wp_send_json_error( 'Invalid nonce' );
        }

        // Verify capability
        if ( ! Security_Tools_Utils::current_user_can_manage() ) {
            wp_send_json_error( 'Insufficient permissions' );
        }

        // Get POST data
        $post_type      = isset( $_POST['post_type'] ) ? sanitize_key( wp_unslash( $_POST['post_type'] ) ) : '';
        $is_scan        = isset( $_POST['scan_mode'] ) && '1' === sanitize_key( wp_unslash( $_POST['scan_mode'] ) );
        $option_name    = $is_scan ? Security_Tools_Utils::OPTION_DISCOVERED_METABOXES_SCAN_BUFFER : Security_Tools_Utils::OPTION_DISCOVERED_METABOXES;
        $metaboxes_json = isset( $_POST['metaboxes'] ) ? wp_unslash( $_POST['metaboxes'] ) : '';

        if ( empty( $post_type ) || empty( $metaboxes_json ) ) {
            wp_send_json_error( 'Missing data' );
        }

        // Decode metaboxes
        $metaboxes = json_decode( $metaboxes_json, true );
        if ( ! is_array( $metaboxes ) ) {
            wp_send_json_error( 'Invalid metaboxes data' );
        }

        // Get existing discovered metaboxes
        $discovered = get_option( $option_name, array() );
        if ( ! is_array( $discovered ) ) {
            $discovered = array();
        }

        // Merge new metaboxes
        foreach ( $metaboxes as $metabox ) {
            if ( empty( $metabox['id'] ) || empty( $metabox['title'] ) ) {
                continue;
            }

            $id      = sanitize_text_field( $metabox['id'] );
            $title   = sanitize_text_field( $metabox['title'] );
            $context = isset( $metabox['context'] ) ? sanitize_key( $metabox['context'] ) : 'normal';

            // Initialize or update metabox entry
            if ( ! isset( $discovered[ $id ] ) ) {
                $discovered[ $id ] = array(
                    'title'      => $title,
                    'context'    => $context,
                    'post_types' => array(),
                );
            }

            // Add post type if not already present
            if ( ! in_array( $post_type, $discovered[ $id ]['post_types'], true ) ) {
                $discovered[ $id ]['post_types'][] = $post_type;
            }

            // Update title if it was previously empty or generic
            if ( empty( $discovered[ $id ]['title'] ) || $discovered[ $id ]['title'] === $id ) {
                $discovered[ $id ]['title'] = $title;
            }
        }

        // Save updated discovered metaboxes
        update_option( $option_name, $discovered, false );

        wp_send_json_success( array( 'count' => count( $discovered ) ) );
    }

    /**
     * ==========================================================================
     * METABOX DISCOVERY - MANUAL SCAN
     * ==========================================================================
     */

    /**
     * AJAX handler to get scan URL for a post type
     *
     * Returns the URL to an edit screen for the specified post type.
     * Used by the manual scan feature to load edit screens in iframes.
     *
     * @since 2.5
     */
    public function ajax_get_scan_url() {
        // Verify nonce
        if ( ! check_ajax_referer( 'security_tools_manual_scan', 'nonce', false ) ) {
            wp_send_json_error( 'Invalid nonce' );
        }

        // Verify capability
        if ( ! Security_Tools_Utils::current_user_can_manage() ) {
            wp_send_json_error( 'Insufficient permissions' );
        }

        $post_type = isset( $_POST['post_type'] ) ? sanitize_key( wp_unslash( $_POST['post_type'] ) ) : '';

        if ( empty( $post_type ) ) {
            wp_send_json_error( 'Missing post type' );
        }

        // Get an existing post of this type, or create URL for new post
        $posts = get_posts( array(
            'post_type'      => $post_type,
            'posts_per_page' => 1,
            'post_status'    => array( 'publish', 'draft', 'pending', 'private' ),
            'orderby'        => 'ID',
            'order'          => 'ASC',
        ) );

        if ( ! empty( $posts ) ) {
            // Edit existing post
            $url = admin_url( 'post.php?post=' . $posts[0]->ID . '&action=edit&security_tools_scan=1' );
        } else {
            // New post screen
            $url = admin_url( 'post-new.php?post_type=' . $post_type . '&security_tools_scan=1' );
        }

        wp_send_json_success( array( 'url' => $url ) );
    }

    /**
     * AJAX handler for completing manual scan
     *
     * Called after all post type edit screens have been loaded.
     * Cleans up metaboxes that were not detected during the scan.
     *
     * @since 2.5
     */
    public function ajax_manual_scan() {
        // Verify nonce
        if ( ! check_ajax_referer( 'security_tools_manual_scan', 'nonce', false ) ) {
            wp_send_json_error( 'Invalid nonce' );
        }

        // Verify capability
        if ( ! Security_Tools_Utils::current_user_can_manage() ) {
            wp_send_json_error( 'Insufficient permissions' );
        }

        $action_type = isset( $_POST['scan_action'] ) ? sanitize_key( wp_unslash( $_POST['scan_action'] ) ) : '';

        if ( 'start' === $action_type ) {
            // Use a temporary buffer so an interrupted scan does not erase existing discoveries.
            update_option( Security_Tools_Utils::OPTION_DISCOVERED_METABOXES_SCAN_BUFFER, array(), false );

            // Get all scannable post types
            $post_types = $this->get_scannable_post_types();

            wp_send_json_success( array(
                'post_types' => $post_types,
                'total'      => count( $post_types ),
            ) );

        } elseif ( 'complete' === $action_type ) {
            $discovered = get_option( Security_Tools_Utils::OPTION_DISCOVERED_METABOXES_SCAN_BUFFER, array() );
            if ( ! is_array( $discovered ) ) {
                $discovered = array();
            }

            update_option( Security_Tools_Utils::OPTION_DISCOVERED_METABOXES, $discovered, false );
            delete_option( Security_Tools_Utils::OPTION_DISCOVERED_METABOXES_SCAN_BUFFER );

            wp_send_json_success( array(
                'count'   => count( $discovered ),
                'message' => 'Scan complete',
            ) );
        }

        wp_send_json_error( 'Invalid action' );
    }

    /**
     * Get all post types that should be scanned for metaboxes
     *
     * @since 2.5
     * @return array Array of post type slugs
     */
    public function get_scannable_post_types() {
        $post_types = get_post_types( array( 'show_ui' => true ), 'names' );

        // Remove attachment as it has a different edit screen
        unset( $post_types['attachment'] );

        return array_values( $post_types );
    }

    /**
     * ==========================================================================
     * GET AVAILABLE METABOXES FOR SETTINGS PAGE
     * ==========================================================================
     */

    /**
     * Get available metaboxes for settings page
     *
     * Merges static core metaboxes with dynamically discovered metaboxes
     * and previously hidden but undetected metaboxes.
     *
     * @since 1.2
     * @since 2.5 Now merges static list with discovered metaboxes
     * @return array
     */
    public function get_available_metaboxes() {
        // Static list of common WordPress core metaboxes
        $metaboxes = $this->get_core_metaboxes();

        // Merge with discovered metaboxes
        $discovered = get_option( Security_Tools_Utils::OPTION_DISCOVERED_METABOXES, array() );
        if ( is_array( $discovered ) ) {
            foreach ( $discovered as $id => $data ) {
                // Skip if already in static list (core metaboxes take precedence for titles)
                if ( isset( $metaboxes[ $id ] ) ) {
                    // But merge post types
                    if ( ! empty( $data['post_types'] ) ) {
                        $existing_types = $metaboxes[ $id ]['post_type'];
                        $new_types      = implode( ', ', array_map( array( $this, 'get_post_type_label' ), $data['post_types'] ) );
                        if ( $existing_types !== $new_types && $existing_types !== 'All' ) {
                            $metaboxes[ $id ]['post_type'] = $new_types;
                        }
                    }
                    continue;
                }

                // Format post type display
                $post_type_display = 'Unknown';
                if ( ! empty( $data['post_types'] ) ) {
                    $labels = array_map( array( $this, 'get_post_type_label' ), $data['post_types'] );
                    $post_type_display = implode( ', ', $labels );
                }

                $metaboxes[ $id ] = array(
                    'title'     => ! empty( $data['title'] ) ? $data['title'] : $id,
                    'context'   => ! empty( $data['context'] ) ? $data['context'] : 'normal',
                    'post_type' => $post_type_display,
                );
            }
        }

        // Merge in any hidden metaboxes that weren't detected
        // This allows users to unhide metaboxes that may have disappeared
        $hidden = Security_Tools_Utils::get_array_option( Security_Tools_Utils::OPTION_HIDDEN_METABOXES );
        foreach ( $hidden as $metabox_id ) {
            if ( ! isset( $metaboxes[ $metabox_id ] ) ) {
                $metaboxes[ $metabox_id ] = array(
                    'title'     => $metabox_id,
                    'context'   => 'unknown',
                    'post_type' => 'Unknown',
                );
            }
        }

        // Sort by title
        uasort( $metaboxes, function( $a, $b ) {
            return strcasecmp( $a['title'], $b['title'] );
        } );

        return $metaboxes;
    }

    /**
     * Get static list of core WordPress metaboxes
     *
     * @since 2.5
     * @return array
     */
    private function get_core_metaboxes() {
        return array(
            'submitdiv'        => array( 'title' => 'Publish', 'context' => 'side', 'post_type' => 'All' ),
            'slugdiv'          => array( 'title' => 'Slug', 'context' => 'normal', 'post_type' => 'All' ),
            'authordiv'        => array( 'title' => 'Author', 'context' => 'normal', 'post_type' => 'All' ),
            'postexcerpt'      => array( 'title' => 'Excerpt', 'context' => 'normal', 'post_type' => 'Post' ),
            'trackbacksdiv'    => array( 'title' => 'Send Trackbacks', 'context' => 'normal', 'post_type' => 'Post' ),
            'postcustom'       => array( 'title' => 'Custom Fields', 'context' => 'normal', 'post_type' => 'All' ),
            'commentstatusdiv' => array( 'title' => 'Discussion', 'context' => 'normal', 'post_type' => 'All' ),
            'commentsdiv'      => array( 'title' => 'Comments', 'context' => 'normal', 'post_type' => 'All' ),
            'revisionsdiv'     => array( 'title' => 'Revisions', 'context' => 'normal', 'post_type' => 'All' ),
            'postimagediv'     => array( 'title' => 'Featured Image', 'context' => 'side', 'post_type' => 'All' ),
            'pageparentdiv'    => array( 'title' => 'Page Attributes', 'context' => 'side', 'post_type' => 'Page' ),
            'formatdiv'        => array( 'title' => 'Format', 'context' => 'side', 'post_type' => 'Post' ),
            'categorydiv'      => array( 'title' => 'Categories', 'context' => 'side', 'post_type' => 'Post' ),
            'tagsdiv-post_tag' => array( 'title' => 'Tags', 'context' => 'side', 'post_type' => 'Post' ),
        );
    }

    /**
     * Get display label for a post type
     *
     * @since 2.5
     * @param string $post_type Post type slug
     * @return string Display label
     */
    private function get_post_type_label( $post_type ) {
        $post_type_obj = get_post_type_object( $post_type );
        if ( $post_type_obj ) {
            return $post_type_obj->labels->singular_name;
        }
        return ucfirst( $post_type );
    }

    /**
     * Get count of discovered metaboxes
     *
     * @since 2.5
     * @return int Number of discovered metaboxes
     */
    public function get_discovered_count() {
        $discovered = get_option( Security_Tools_Utils::OPTION_DISCOVERED_METABOXES, array() );
        return is_array( $discovered ) ? count( $discovered ) : 0;
    }
}
