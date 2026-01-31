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
 * @version    2.5
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
        if ( isset( $_GET['security_tools_scan'] ) && '1' === $_GET['security_tools_scan'] ) {
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
        add_action( 'admin_footer', array( $this, 'hide_metaboxes_js' ), 999 );
        
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
            $safe_id = esc_attr( $metabox_id );
            $css .= '#' . $safe_id . ',';
            $css .= '#' . $safe_id . 'div,';
            $css .= '.postbox#' . $safe_id . ',';
            $css .= '#' . $safe_id . '-hide,';
            $css .= 'label[for="' . $safe_id . '-hide"]';
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

        $hidden_json = wp_json_encode( $hidden );
        ?>
        <script type="text/javascript">
        (function() {
            'use strict';
            
            var hiddenMetaboxes = <?php echo $hidden_json; ?>;
            
            function hideMetaboxes() {
                hiddenMetaboxes.forEach(function(id) {
                    // Hide the metabox itself
                    var metabox = document.getElementById(id);
                    if (metabox) {
                        metabox.style.display = 'none';
                    }
                    
                    // Also try with 'div' suffix (some plugins use this)
                    var metaboxDiv = document.getElementById(id + 'div');
                    if (metaboxDiv) {
                        metaboxDiv.style.display = 'none';
                    }
                    
                    // Hide the Screen Options checkbox for this metabox
                    var checkbox = document.getElementById(id + '-hide');
                    if (checkbox) {
                        checkbox.style.display = 'none';
                        // Also hide the label
                        var label = document.querySelector('label[for="' + id + '-hide"]');
                        if (label) {
                            label.style.display = 'none';
                        }
                    }
                });
            }
            
            // Run immediately
            hideMetaboxes();
            
            // Run again after short delays to catch late-loading metaboxes
            setTimeout(hideMetaboxes, 500);
            setTimeout(hideMetaboxes, 1500);
            setTimeout(hideMetaboxes, 3000);
            
            // Also run when DOM changes (for dynamically added metaboxes)
            if (typeof MutationObserver !== 'undefined') {
                var observer = new MutationObserver(function(mutations) {
                    hideMetaboxes();
                });
                
                // Observe the post body for changes
                var postBody = document.getElementById('post-body');
                if (postBody) {
                    observer.observe(postBody, { childList: true, subtree: true });
                }
                
                // Stop observing after 10 seconds to avoid performance issues
                setTimeout(function() {
                    observer.disconnect();
                }, 10000);
            }
        })();
        </script>
        <?php
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

        // Build JavaScript to hide panels
        $panels_json    = wp_json_encode( $panels_to_hide );
        $metaboxes_json = wp_json_encode( $metaboxes_to_css );

        $script = "
            (function() {
                var panels = {$panels_json};
                var metaboxes = {$metaboxes_json};
                
                function hidePanels() {
                    if (wp && wp.data && wp.data.dispatch) {
                        var dispatch = wp.data.dispatch('core/edit-post');
                        if (dispatch && dispatch.removeEditorPanel) {
                            panels.forEach(function(p) { 
                                try { dispatch.removeEditorPanel(p); } catch(e) {}
                            });
                        }
                    }
                }
                
                function hideMetaboxContainers() {
                    metaboxes.forEach(function(id) {
                        var el = document.getElementById(id);
                        if (el) el.style.display = 'none';
                        // Also try with 'div' suffix (some metaboxes use this)
                        var elDiv = document.getElementById(id + 'div');
                        if (elDiv) elDiv.style.display = 'none';
                    });
                }
                
                if (wp && wp.domReady) { 
                    wp.domReady(function() {
                        hidePanels();
                        hideMetaboxContainers();
                    });
                }
                
                // Retry after delays for late-loading panels
                setTimeout(function() { hidePanels(); hideMetaboxContainers(); }, 500);
                setTimeout(function() { hidePanels(); hideMetaboxContainers(); }, 1500);
            })();
        ";

        wp_add_inline_script( 'wp-edit-post', $script );
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
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        // Get current post type
        $post_type = $this->get_current_post_type();
        if ( ! $post_type ) {
            return;
        }

        // Inline script for discovery (no separate file needed)
        $script = $this->get_discovery_script( $post_type );

        // Add after common admin scripts are loaded
        wp_add_inline_script( 'common', $script );
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
     * Generate the discovery JavaScript
     *
     * @since 2.5
     * @param string $post_type Current post type
     * @return string JavaScript code
     */
    private function get_discovery_script( $post_type ) {
        $nonce = wp_create_nonce( 'security_tools_discover_metaboxes' );

        return "
        (function() {
            'use strict';
            
            function discoverMetaboxes() {
                var metaboxes = [];
                var postType = " . wp_json_encode( $post_type ) . ";
                
                // Find all postbox containers (Classic Editor metaboxes)
                var postboxes = document.querySelectorAll('.postbox');
                postboxes.forEach(function(box) {
                    var id = box.id;
                    if (!id) return;
                    
                    // Get title from h2 or button inside hndle
                    var title = '';
                    var handleEl = box.querySelector('.hndle');
                    if (handleEl) {
                        // Try to get text content, excluding button text
                        var titleSpan = handleEl.querySelector('span');
                        if (titleSpan) {
                            title = titleSpan.textContent.trim();
                        } else {
                            title = handleEl.textContent.trim();
                        }
                    }
                    
                    // Determine context from parent container
                    var context = 'normal';
                    var parent = box.parentElement;
                    if (parent) {
                        if (parent.id === 'side-sortables' || parent.id.indexOf('side') !== -1) {
                            context = 'side';
                        } else if (parent.id === 'advanced-sortables' || parent.id.indexOf('advanced') !== -1) {
                            context = 'advanced';
                        }
                    }
                    
                    if (id && title) {
                        metaboxes.push({
                            id: id,
                            title: title,
                            context: context,
                            post_type: postType
                        });
                    }
                });
                
                // Also check for Gutenberg plugin panels/sidebars if available
                if (wp && wp.data && wp.data.select) {
                    try {
                        var editPost = wp.data.select('core/edit-post');
                        if (editPost && editPost.getMetaBoxesPerLocation) {
                            ['normal', 'side', 'advanced'].forEach(function(location) {
                                var boxes = editPost.getMetaBoxesPerLocation(location);
                                if (boxes && boxes.length) {
                                    boxes.forEach(function(box) {
                                        // Check if we already have this metabox
                                        var exists = metaboxes.some(function(m) { return m.id === box.id; });
                                        if (!exists && box.id && box.title) {
                                            metaboxes.push({
                                                id: box.id,
                                                title: box.title,
                                                context: location,
                                                post_type: postType
                                            });
                                        }
                                    });
                                }
                            });
                        }
                    } catch(e) {
                        // Gutenberg API not available or error
                    }
                }
                
                // Send to server if we found any metaboxes
                if (metaboxes.length > 0) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', ajaxurl, true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.send(
                        'action=security_tools_discover_metaboxes' +
                        '&nonce=" . esc_js( $nonce ) . "' +
                        '&post_type=' + encodeURIComponent(postType) +
                        '&metaboxes=' + encodeURIComponent(JSON.stringify(metaboxes))
                    );
                }
            }
            
            // Run discovery after page load and after a delay (for late-loading metaboxes)
            if (document.readyState === 'complete') {
                setTimeout(discoverMetaboxes, 1000);
            } else {
                window.addEventListener('load', function() {
                    setTimeout(discoverMetaboxes, 1000);
                });
            }
            
            // Also run after Gutenberg is ready if available
            if (typeof wp !== 'undefined' && wp.domReady) {
                wp.domReady(function() {
                    setTimeout(discoverMetaboxes, 2000);
                });
            }
        })();
        ";
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
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
        }

        // Get POST data
        $post_type = isset( $_POST['post_type'] ) ? sanitize_key( $_POST['post_type'] ) : '';
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
        $discovered = get_option( Security_Tools_Utils::OPTION_DISCOVERED_METABOXES, array() );
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
        update_option( Security_Tools_Utils::OPTION_DISCOVERED_METABOXES, $discovered );

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
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
        }

        $post_type = isset( $_POST['post_type'] ) ? sanitize_key( $_POST['post_type'] ) : '';

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
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( 'Insufficient permissions' );
        }

        $action_type = isset( $_POST['scan_action'] ) ? sanitize_key( $_POST['scan_action'] ) : '';

        if ( 'start' === $action_type ) {
            // Clear discovered metaboxes to start fresh
            // This ensures removed plugins/themes have their metaboxes cleaned up
            update_option( Security_Tools_Utils::OPTION_DISCOVERED_METABOXES, array() );

            // Get all scannable post types
            $post_types = $this->get_scannable_post_types();

            wp_send_json_success( array(
                'post_types' => $post_types,
                'total'      => count( $post_types ),
            ) );

        } elseif ( 'complete' === $action_type ) {
            // Scan complete - get final count
            $discovered = get_option( Security_Tools_Utils::OPTION_DISCOVERED_METABOXES, array() );

            wp_send_json_success( array(
                'count'   => is_array( $discovered ) ? count( $discovered ) : 0,
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
