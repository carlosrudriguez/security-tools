<?php
/**
 * Security Tools - Main Loader
 *
 * Handles plugin initialization, hook registration, and feature loading.
 * This is the central coordinator that brings all plugin components together.
 *
 * @package    Security_Tools
 * @subpackage Includes
 * @version    2.5
 * @author     Carlos Rodríguez
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Class Security_Tools_Loader
 *
 * Main loader class that initializes all plugin features.
 * Uses singleton pattern to ensure only one instance exists.
 *
 * @since 1.2
 */
class Security_Tools_Loader {

    /**
     * Single instance of this class
     *
     * @var Security_Tools_Loader
     */
    private static $instance = null;

    /**
     * Array of feature instances
     *
     * @var array
     */
    private $features = array();

    /**
     * Get the singleton instance
     *
     * @since  1.2
     * @return Security_Tools_Loader
     */
    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor - Initialize all features
     *
     * Private to enforce singleton pattern.
     *
     * @since 1.2
     */
    private function __construct() {
        $this->init_features();
    }

    /**
     * Initialize all feature classes
     *
     * Creates instances of each feature class which register their own hooks.
     *
     * @since 1.2
     * @return void
     */
    private function init_features() {
        // Self-hiding (always active - core functionality)
        $this->features['self_hiding'] = new Security_Tools_Feature_Self_Hiding();

        // Branding (always active - checks options internally)
        $this->features['branding'] = new Security_Tools_Feature_Branding();

        // Admin notices control
        $this->features['notices'] = new Security_Tools_Feature_Notices();

        // Disable updates
        $this->features['updates'] = new Security_Tools_Feature_Updates();

        // Disable emails
        $this->features['emails'] = new Security_Tools_Feature_Emails();

        // Disable comments
        $this->features['comments'] = new Security_Tools_Feature_Comments();

        // Plugin controls
        $this->features['plugin_controls'] = new Security_Tools_Feature_Plugin_Controls();

        // Theme controls
        $this->features['theme_controls'] = new Security_Tools_Feature_Theme_Controls();

        // Admin bar controls
        $this->features['admin_bar'] = new Security_Tools_Feature_Admin_Bar();

        // Hide administrators
        $this->features['hide_admins'] = new Security_Tools_Feature_Hide_Admins();

        // Hide plugins
        $this->features['hide_plugins'] = new Security_Tools_Feature_Hide_Plugins();

        // Hide themes
        $this->features['hide_themes'] = new Security_Tools_Feature_Hide_Themes();

        // Hide dashboard widgets
        $this->features['hide_widgets'] = new Security_Tools_Feature_Hide_Widgets();

        // Hide metaboxes
        $this->features['hide_metaboxes'] = new Security_Tools_Feature_Hide_Metaboxes();

        // Wordfence integration
        $this->features['wordfence'] = new Security_Tools_Feature_Wordfence();

        // Hide Login Page (custom login URL)
        $this->features['hide_login'] = new Security_Tools_Feature_Hide_Login();
    }

    /**
     * Get a specific feature instance
     *
     * @since  1.2
     * @param  string $feature_key The feature key
     * @return object|null The feature instance or null if not found
     */
    public function get_feature( $feature_key ) {
        return isset( $this->features[ $feature_key ] ) ? $this->features[ $feature_key ] : null;
    }

    /**
     * Get all feature instances
     *
     * @since  1.2
     * @return array Array of feature instances
     */
    public function get_all_features() {
        return $this->features;
    }
}
