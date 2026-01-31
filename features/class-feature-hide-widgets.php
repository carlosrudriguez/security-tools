<?php
/**
 * Security Tools - Hide Widgets Feature
 *
 * Hides selected dashboard widgets.
 *
 * @package    Security_Tools
 * @subpackage Features
 * @version    2.5
 * @author     Carlos Rodríguez
 *
 * CHANGELOG v1.3:
 * - Added defensive checks for $wp_meta_boxes global (Issue #5)
 * - Both remove_widgets_by_id() and get_available_widgets() now verify
 *   the global exists and is an array before accessing it
 * - Consolidated inline CSS using wp_add_inline_style() (Issue #2 partial)
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Class Security_Tools_Feature_Hide_Widgets
 *
 * Implements dashboard widget hiding functionality.
 *
 * @since 1.2
 */
class Security_Tools_Feature_Hide_Widgets {

    /**
     * Constructor - Register hooks
     *
     * @since 1.2
     */
    public function __construct() {
        add_action( 'wp_dashboard_setup', array( $this, 'remove_widgets' ), 999 );
        add_action( 'admin_enqueue_scripts', array( $this, 'remove_late_widgets' ) );
    }

    /**
     * Remove hidden widgets during dashboard setup
     *
     * @since 1.2
     */
    public function remove_widgets() {
        $hidden = Security_Tools_Utils::get_array_option( Security_Tools_Utils::OPTION_HIDDEN_WIDGETS );

        if ( empty( $hidden ) ) {
            return;
        }

        $this->remove_widgets_by_id( $hidden );
    }

    /**
     * Remove widgets that are added late (after dashboard setup)
     *
     * Updated in v1.3 to use wp_add_inline_style() for CSP compliance.
     *
     * @since 1.2
     * @since 1.3 Changed from echo to wp_add_inline_style()
     */
    public function remove_late_widgets() {
        $screen = get_current_screen();
        
        // Only run on dashboard
        if ( ! $screen || $screen->id !== 'dashboard' ) {
            return;
        }

        $hidden = Security_Tools_Utils::get_array_option( Security_Tools_Utils::OPTION_HIDDEN_WIDGETS );

        if ( empty( $hidden ) ) {
            return;
        }

        $this->remove_widgets_by_id( $hidden );

        // CSS backup for any remaining widgets - now using wp_add_inline_style()
        $css = '';
        foreach ( $hidden as $widget_id ) {
            $css .= '#' . esc_attr( $widget_id ) . ', ';
            $css .= '#' . esc_attr( $widget_id ) . '_wrapper { display: none !important; }';
        }

        wp_register_style( 'security-tools-hide-widgets', false );
        wp_enqueue_style( 'security-tools-hide-widgets' );
        wp_add_inline_style( 'security-tools-hide-widgets', $css );
    }

    /**
     * Remove widgets by their IDs
     *
     * v1.3: Added defensive check for $wp_meta_boxes global to prevent
     * PHP errors if the array structure isn't as expected.
     *
     * @since 1.2
     * @since 1.3 Added defensive global variable checks
     * @param array $widget_ids Widget IDs to remove
     */
    private function remove_widgets_by_id( $widget_ids ) {
        global $wp_meta_boxes;

        foreach ( $widget_ids as $widget_id ) {
            if ( $widget_id === 'welcome_panel' ) {
                remove_action( 'welcome_panel', 'wp_welcome_panel' );
                continue;
            }

            // Remove from all contexts
            $contexts = array( 'normal', 'side', 'column3', 'column4' );
            foreach ( $contexts as $context ) {
                remove_meta_box( $widget_id, 'dashboard', $context );
            }

            // Also try to remove from wp_meta_boxes directly
            // v1.3: Added defensive check for global variable
            if ( ! isset( $wp_meta_boxes ) || ! is_array( $wp_meta_boxes ) ) {
                continue;
            }

            if ( ! isset( $wp_meta_boxes['dashboard'] ) || ! is_array( $wp_meta_boxes['dashboard'] ) ) {
                continue;
            }

            foreach ( $wp_meta_boxes['dashboard'] as $context => $priorities ) {
                if ( ! is_array( $priorities ) ) {
                    continue;
                }
                foreach ( $priorities as $priority => $boxes ) {
                    if ( is_array( $boxes ) && isset( $boxes[ $widget_id ] ) ) {
                        unset( $wp_meta_boxes['dashboard'][ $context ][ $priority ][ $widget_id ] );
                    }
                }
            }
        }
    }

    /**
     * Get available dashboard widgets for settings page
     *
     * v1.3: Added defensive check for $wp_meta_boxes global to prevent
     * PHP errors if the array structure isn't as expected.
     *
     * @since 1.2
     * @since 1.3 Added defensive global variable checks
     * @return array
     */
    public function get_available_widgets() {
        global $wp_meta_boxes;

        // Force dashboard setup to load widgets
        $this->force_dashboard_setup();

        $widgets = array();

        // Default WordPress widgets
        $defaults = array(
            'dashboard_right_now'   => array( 'title' => 'At a Glance', 'context' => 'normal' ),
            'dashboard_activity'    => array( 'title' => 'Activity', 'context' => 'normal' ),
            'dashboard_quick_press' => array( 'title' => 'Quick Draft', 'context' => 'side' ),
            'dashboard_primary'     => array( 'title' => 'WordPress Events and News', 'context' => 'side' ),
            'dashboard_site_health' => array( 'title' => 'Site Health Status', 'context' => 'normal' ),
            'welcome_panel'         => array( 'title' => 'Welcome Panel', 'context' => 'welcome' ),
        );

        foreach ( $defaults as $id => $data ) {
            $widgets[ $id ] = $data;
        }

        // Add widgets from wp_meta_boxes
        // v1.3: Added defensive checks for global variable
        if ( ! isset( $wp_meta_boxes ) || ! is_array( $wp_meta_boxes ) ) {
            return $widgets;
        }

        if ( ! isset( $wp_meta_boxes['dashboard'] ) || ! is_array( $wp_meta_boxes['dashboard'] ) ) {
            return $widgets;
        }

        foreach ( $wp_meta_boxes['dashboard'] as $context => $priorities ) {
            if ( ! is_array( $priorities ) ) {
                continue;
            }
            foreach ( $priorities as $priority => $boxes ) {
                if ( ! is_array( $boxes ) ) {
                    continue;
                }
                foreach ( $boxes as $id => $data ) {
                    if ( ! isset( $widgets[ $id ] ) && is_array( $data ) && ! empty( $data['title'] ) ) {
                        $widgets[ $id ] = array(
                            'title'   => strip_tags( $data['title'] ),
                            'context' => $context,
                        );
                    }
                }
            }
        }

        return $widgets;
    }

    /**
     * Force dashboard setup to load all widgets
     *
     * @since 1.2
     */
    private function force_dashboard_setup() {
        global $wp_meta_boxes;

        $current_screen = get_current_screen();
        set_current_screen( 'dashboard' );

        if ( ! function_exists( 'wp_dashboard_setup' ) ) {
            require_once ABSPATH . 'wp-admin/includes/dashboard.php';
        }

        $wp_meta_boxes['dashboard'] = array();
        wp_dashboard_setup();
        do_action( 'wp_dashboard_setup' );

        if ( $current_screen ) {
            set_current_screen( $current_screen );
        }
    }
}
