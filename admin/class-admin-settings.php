<?php
/**
 * Security Tools - Admin Settings Registration
 *
 * Handles WordPress Settings API registration for all plugin options.
 * In version 2.0, settings are organized into separate groups for each
 * subpage, allowing independent saving of each settings section.
 *
 * @package    Security_Tools
 * @subpackage Admin
 * @version    2.5
 * @author     Carlos Rodríguez
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Class Security_Tools_Admin_Settings
 *
 * Registers all plugin settings with the WordPress Settings API.
 * Each subpage has its own settings group for independent saving.
 *
 * @since 1.2
 * @since 2.0 Reorganized into separate settings groups per subpage
 */
class Security_Tools_Admin_Settings {

    /**
     * Sanitization handler instance
     *
     * @var Security_Tools_Admin_Sanitization
     */
    private $sanitization;

    /**
     * Constructor - Register hooks
     *
     * @since 1.2
     */
    public function __construct() {
        $this->sanitization = new Security_Tools_Admin_Sanitization();
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    /**
     * Register all plugin settings with WordPress
     *
     * Each subpage has its own settings group, allowing forms to save
     * only the settings relevant to that page.
     *
     * @since 1.2
     * @since 2.0 Split into separate groups per subpage
     * @since 2.1 Added General settings group for autohide feature
     * @return void
     */
    public function register_settings() {
        $this->register_general_settings();
        $this->register_branding_settings();
        $this->register_system_controls_settings();
        $this->register_admins_settings();
        $this->register_plugins_settings();
        $this->register_themes_settings();
        $this->register_widgets_settings();
        $this->register_admin_bar_settings();
        $this->register_metaboxes_settings();
    }

    /**
     * ==========================================================================
     * GENERAL SETTINGS GROUP
     * ==========================================================================
     * Settings for the General subpage (Autohide Menu).
     * @since 2.1
     */

    /**
     * Register General settings
     *
     * @since 2.1
     * @return void
     */
    private function register_general_settings() {
        $group = Security_Tools_Utils::SETTINGS_GROUP_GENERAL;

        // Autohide Menu (boolean)
        register_setting(
            $group,
            Security_Tools_Utils::OPTION_AUTOHIDE_MENU,
            array(
                'type'              => 'boolean',
                'sanitize_callback' => array( $this->sanitization, 'sanitize_autohide_menu' ),
                'default'           => false,
            )
        );
    }

    /**
     * ==========================================================================
     * BRANDING SETTINGS GROUP
     * ==========================================================================
     * Settings for the Branding subpage (Custom Legend).
     */

    /**
     * Register Branding settings
     *
     * Registers settings for custom legend text, login logo, and logo URL.
     *
     * @since 2.0
     * @since 2.3 Added login logo and logo URL settings
     * @return void
     */
    private function register_branding_settings() {
        $group = Security_Tools_Utils::SETTINGS_GROUP_BRANDING;

        // Custom Legend (string)
        register_setting(
            $group,
            Security_Tools_Utils::OPTION_CUSTOM_LEGEND,
            array(
                'type'              => 'string',
                'sanitize_callback' => array( $this->sanitization, 'sanitize_custom_legend' ),
                'default'           => '',
            )
        );

        // Custom Login Logo - Attachment ID (integer)
        register_setting(
            $group,
            Security_Tools_Utils::OPTION_LOGIN_LOGO_ID,
            array(
                'type'              => 'integer',
                'sanitize_callback' => array( $this->sanitization, 'sanitize_login_logo_id' ),
                'default'           => 0,
            )
        );

        // Custom Login Logo URL (string)
        register_setting(
            $group,
            Security_Tools_Utils::OPTION_LOGIN_LOGO_URL,
            array(
                'type'              => 'string',
                'sanitize_callback' => array( $this->sanitization, 'sanitize_login_logo_url' ),
                'default'           => '',
            )
        );
    }

    /**
     * ==========================================================================
     * SYSTEM CONTROLS SETTINGS GROUP
     * ==========================================================================
     * Settings for the System Controls subpage (all boolean toggles).
     */

    /**
     * Register System Controls settings
     *
     * @since 2.0
     * @return void
     */
    private function register_system_controls_settings() {
        $group = Security_Tools_Utils::SETTINGS_GROUP_SYSTEM_CONTROLS;

        // All boolean toggle options for system controls
        $boolean_options = array(
            Security_Tools_Utils::OPTION_DISABLE_UPDATES            => 'sanitize_disable_updates',
            Security_Tools_Utils::OPTION_DISABLE_EMAILS             => 'sanitize_disable_emails',
            Security_Tools_Utils::OPTION_DISABLE_EMAIL_CHECK        => 'sanitize_disable_email_check',
            Security_Tools_Utils::OPTION_HIDE_NOTICES               => 'sanitize_hide_notices',
            Security_Tools_Utils::OPTION_DISABLE_COMMENTS           => 'sanitize_disable_comments',
            Security_Tools_Utils::OPTION_DISABLE_PLUGIN_CONTROLS    => 'sanitize_disable_plugin_controls',
            Security_Tools_Utils::OPTION_DISABLE_THEME_CONTROLS     => 'sanitize_disable_theme_controls',
            Security_Tools_Utils::OPTION_DISABLE_FRONTEND_ADMIN_BAR => 'sanitize_disable_frontend_admin_bar',
            Security_Tools_Utils::OPTION_HIDE_LOGIN_ENABLED         => 'sanitize_hide_login_enabled',
        );

        foreach ( $boolean_options as $option_name => $sanitize_callback ) {
            register_setting(
                $group,
                $option_name,
                array(
                    'type'              => 'boolean',
                    'sanitize_callback' => array( $this->sanitization, $sanitize_callback ),
                    'default'           => false,
                )
            );
        }

        // Hide Login Page - Custom Slug (string)
        register_setting(
            $group,
            Security_Tools_Utils::OPTION_HIDE_LOGIN_SLUG,
            array(
                'type'              => 'string',
                'sanitize_callback' => array( $this->sanitization, 'sanitize_hide_login_slug' ),
                'default'           => '',
            )
        );
    }

    /**
     * ==========================================================================
     * ADMINS SETTINGS GROUP
     * ==========================================================================
     * Settings for the Admins subpage (Hidden Administrators).
     */

    /**
     * Register Admins settings
     *
     * @since 2.0
     * @return void
     */
    private function register_admins_settings() {
        $group = Security_Tools_Utils::SETTINGS_GROUP_ADMINS;

        register_setting(
            $group,
            Security_Tools_Utils::OPTION_HIDDEN_ADMINS,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this->sanitization, 'sanitize_hidden_admins' ),
                'default'           => array(),
            )
        );
    }

    /**
     * ==========================================================================
     * PLUGINS SETTINGS GROUP
     * ==========================================================================
     * Settings for the Plugins subpage (Hidden Plugins).
     */

    /**
     * Register Plugins settings
     *
     * @since 2.0
     * @return void
     */
    private function register_plugins_settings() {
        $group = Security_Tools_Utils::SETTINGS_GROUP_PLUGINS;

        register_setting(
            $group,
            Security_Tools_Utils::OPTION_HIDDEN_PLUGINS,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this->sanitization, 'sanitize_hidden_plugins' ),
                'default'           => array(),
            )
        );
    }

    /**
     * ==========================================================================
     * THEMES SETTINGS GROUP
     * ==========================================================================
     * Settings for the Themes subpage (Hidden Themes).
     */

    /**
     * Register Themes settings
     *
     * @since 2.0
     * @return void
     */
    private function register_themes_settings() {
        $group = Security_Tools_Utils::SETTINGS_GROUP_THEMES;

        register_setting(
            $group,
            Security_Tools_Utils::OPTION_HIDDEN_THEMES,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this->sanitization, 'sanitize_hidden_themes' ),
                'default'           => array(),
            )
        );
    }

    /**
     * ==========================================================================
     * WIDGETS SETTINGS GROUP
     * ==========================================================================
     * Settings for the Widgets subpage (Hidden Dashboard Widgets).
     */

    /**
     * Register Widgets settings
     *
     * @since 2.0
     * @return void
     */
    private function register_widgets_settings() {
        $group = Security_Tools_Utils::SETTINGS_GROUP_WIDGETS;

        register_setting(
            $group,
            Security_Tools_Utils::OPTION_HIDDEN_WIDGETS,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this->sanitization, 'sanitize_hidden_widgets' ),
                'default'           => array(),
            )
        );
    }

    /**
     * ==========================================================================
     * ADMIN BAR SETTINGS GROUP
     * ==========================================================================
     * Settings for the Admin Bar subpage (Hidden Admin Bar Items).
     */

    /**
     * Register Admin Bar settings
     *
     * @since 2.0
     * @since 2.4 Added CSS-based hiding option registration
     * @return void
     */
    private function register_admin_bar_settings() {
        $group = Security_Tools_Utils::SETTINGS_GROUP_ADMIN_BAR;

        // Standard admin bar item hiding (removes nodes from WP_Admin_Bar)
        register_setting(
            $group,
            Security_Tools_Utils::OPTION_HIDDEN_ADMIN_BAR,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this->sanitization, 'sanitize_hidden_admin_bar' ),
                'default'           => array(),
            )
        );

        // CSS-based admin bar item hiding (hides by element ID)
        register_setting(
            $group,
            Security_Tools_Utils::OPTION_HIDDEN_ADMIN_BAR_CSS,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this->sanitization, 'sanitize_hidden_admin_bar_css' ),
                'default'           => array(),
            )
        );
    }

    /**
     * ==========================================================================
     * METABOXES SETTINGS GROUP
     * ==========================================================================
     * Settings for the Metaboxes subpage (Hidden Metaboxes).
     * @since 2.5 Renamed from ELEMENTS to METABOXES
     */

    /**
     * Register Metaboxes settings
     *
     * @since 2.0
     * @since 2.5 Renamed from register_elements_settings()
     * @return void
     */
    private function register_metaboxes_settings() {
        $group = Security_Tools_Utils::SETTINGS_GROUP_METABOXES;

        register_setting(
            $group,
            Security_Tools_Utils::OPTION_HIDDEN_METABOXES,
            array(
                'type'              => 'array',
                'sanitize_callback' => array( $this->sanitization, 'sanitize_hidden_metaboxes' ),
                'default'           => array(),
            )
        );
    }

    /**
     * ==========================================================================
     * HELPER METHODS
     * ==========================================================================
     */

    /**
     * Get the sanitization handler
     *
     * @since  1.2
     * @return Security_Tools_Admin_Sanitization
     */
    public function get_sanitization() {
        return $this->sanitization;
    }

    /**
     * Get the settings group for a specific page slug
     *
     * Maps page slugs to their corresponding settings group constant.
     * Useful for forms that need to know which group to use.
     *
     * @since  2.0
     * @since  2.1 Added General settings group mapping
     * @param  string $page_slug The page slug
     * @return string|false The settings group name or false if not found
     */
    public static function get_settings_group_for_page( $page_slug ) {
        $map = array(
            Security_Tools_Utils::PAGE_GENERAL         => Security_Tools_Utils::SETTINGS_GROUP_GENERAL,
            Security_Tools_Utils::PAGE_BRANDING        => Security_Tools_Utils::SETTINGS_GROUP_BRANDING,
            Security_Tools_Utils::PAGE_SYSTEM_CONTROLS => Security_Tools_Utils::SETTINGS_GROUP_SYSTEM_CONTROLS,
            Security_Tools_Utils::PAGE_ADMINS          => Security_Tools_Utils::SETTINGS_GROUP_ADMINS,
            Security_Tools_Utils::PAGE_PLUGINS         => Security_Tools_Utils::SETTINGS_GROUP_PLUGINS,
            Security_Tools_Utils::PAGE_THEMES          => Security_Tools_Utils::SETTINGS_GROUP_THEMES,
            Security_Tools_Utils::PAGE_WIDGETS         => Security_Tools_Utils::SETTINGS_GROUP_WIDGETS,
            Security_Tools_Utils::PAGE_ADMIN_BAR       => Security_Tools_Utils::SETTINGS_GROUP_ADMIN_BAR,
            Security_Tools_Utils::PAGE_METABOXES       => Security_Tools_Utils::SETTINGS_GROUP_METABOXES,
        );

        return isset( $map[ $page_slug ] ) ? $map[ $page_slug ] : false;
    }
}
