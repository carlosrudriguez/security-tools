<?php
/**
 * Security Tools - Admin Page Renderer
 *
 * Renders the settings pages HTML for the Security Tools admin interface.
 * In version 2.0, the single settings page has been split into 9 subpages,
 * each with its own form and Save button.
 *
 * @package    Security_Tools
 * @subpackage Admin
 * @version    2.5
 * @author     Carlos Rodríguez
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Class Security_Tools_Admin_Page
 *
 * Handles rendering of the Security Tools settings pages.
 * Each subpage has its own public render method.
 *
 * @since 1.2
 * @since 2.0 Split into multiple subpages with independent forms
 */
class Security_Tools_Admin_Page {

    /**
     * ==========================================================================
     * SUBPAGE RENDER METHODS
     * ==========================================================================
     * Each subpage has its own render method called from the menu registration.
     * These methods wrap content in the standard page container and header.
     */

    /**
     * Render the General settings subpage
     *
     * Contains the Autohide Menu toggle and Reset All Settings button.
     * The autohide feature allows hiding the plugin menu while maintaining access via URL.
     *
     * @since 2.0
     * @since 2.1 Added Autohide Menu toggle card
     * @return void
     */
    public function render_general() {
        ?>
        <div class="wrap security-tools-page">
            <h1 style="display: none;">Security Tools - General</h1>
            <?php $this->render_header(); ?>
            <?php $this->render_tab_navigation(); ?>
            
            <div class="security-tools-section-header">
                <h2><?php esc_html_e( 'General Settings', 'security-tools' ); ?></h2>
            </div>
            <div class="security-tools-table-description">
                <p class="control-info">
                    <?php esc_html_e( 'General plugin settings and maintenance options.', 'security-tools' ); ?>
                </p>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields( Security_Tools_Utils::SETTINGS_GROUP_GENERAL ); ?>
                <?php $this->render_autohide_section(); ?>
                <?php $this->render_save_button(); ?>
            </form>

            <?php $this->render_reset_section(); ?>
        </div>
        <?php
    }

    /**
     * Render the Branding settings subpage
     *
     * Contains the Custom Legend and Custom Login Logo sections.
     *
     * @since 2.0
     * @since 2.1 Added tab navigation
     * @since 2.3 Added Custom Login Logo section
     * @return void
     */
    public function render_branding() {
        $custom_legend  = get_option( Security_Tools_Utils::OPTION_CUSTOM_LEGEND, '' );
        $login_logo_id  = absint( get_option( Security_Tools_Utils::OPTION_LOGIN_LOGO_ID, 0 ) );
        $login_logo_url = get_option( Security_Tools_Utils::OPTION_LOGIN_LOGO_URL, '' );
        ?>
        <div class="wrap security-tools-page">
            <h1 style="display: none;">Security Tools - Branding</h1>
            <?php $this->render_header(); ?>
            <?php $this->render_tab_navigation(); ?>
            
            <div class="security-tools-section-header">
                <h2><?php esc_html_e( 'Custom branding', 'security-tools' ); ?></h2>
            </div>
            <div class="security-tools-table-description">
                <p class="control-info">
                    <?php esc_html_e( 'Customize branding elements displayed on the login page and dashboard.', 'security-tools' ); ?>
                </p>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields( Security_Tools_Utils::SETTINGS_GROUP_BRANDING ); ?>
                <?php $this->render_legend_section( $custom_legend ); ?>
                <?php $this->render_login_logo_section( $login_logo_id, $login_logo_url ); ?>
                <?php $this->render_save_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render the System Controls subpage
     *
     * Contains all toggle cards for system controls.
     *
     * @since 2.0
     * @since 2.1 Added tab navigation
     * @return void
     */
    public function render_system_controls() {
        ?>
        <div class="wrap security-tools-page">
            <h1 style="display: none;">Security Tools - System Controls</h1>
            <?php $this->render_header(); ?>
            <?php $this->render_tab_navigation(); ?>
            
            <form method="post" action="options.php">
                <?php settings_fields( Security_Tools_Utils::SETTINGS_GROUP_SYSTEM_CONTROLS ); ?>
                <?php $this->render_system_controls_content(); ?>
                <?php $this->render_save_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render the Hidden Administrators subpage
     *
     * Contains the Hidden Administrators table.
     *
     * @since 2.0
     * @since 2.1 Added tab navigation
     * @return void
     */
    public function render_admins() {
        $hidden_admins = Security_Tools_Utils::get_array_option( Security_Tools_Utils::OPTION_HIDDEN_ADMINS );
        ?>
        <div class="wrap security-tools-page">
            <h1 style="display: none;">Security Tools - Hidden Administrators</h1>
            <?php $this->render_header(); ?>
            <?php $this->render_tab_navigation(); ?>
            
            <form method="post" action="options.php">
                <?php settings_fields( Security_Tools_Utils::SETTINGS_GROUP_ADMINS ); ?>
                <?php $this->render_admins_table( $hidden_admins ); ?>
                <?php $this->render_save_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render the Hidden Plugins subpage
     *
     * Contains the Hidden Plugins table.
     *
     * @since 2.0
     * @since 2.1 Added tab navigation
     * @return void
     */
    public function render_plugins() {
        $hidden_plugins = Security_Tools_Utils::get_array_option( Security_Tools_Utils::OPTION_HIDDEN_PLUGINS );
        ?>
        <div class="wrap security-tools-page">
            <h1 style="display: none;">Security Tools - Hidden Plugins</h1>
            <?php $this->render_header(); ?>
            <?php $this->render_tab_navigation(); ?>
            
            <form method="post" action="options.php">
                <?php settings_fields( Security_Tools_Utils::SETTINGS_GROUP_PLUGINS ); ?>
                <?php $this->render_plugins_table( $hidden_plugins ); ?>
                <?php $this->render_save_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render the Hidden Themes subpage
     *
     * Contains the Hidden Themes table.
     *
     * @since 2.0
     * @since 2.1 Added tab navigation
     * @return void
     */
    public function render_themes() {
        $hidden_themes = Security_Tools_Utils::get_array_option( Security_Tools_Utils::OPTION_HIDDEN_THEMES );
        ?>
        <div class="wrap security-tools-page">
            <h1 style="display: none;">Security Tools - Hidden Themes</h1>
            <?php $this->render_header(); ?>
            <?php $this->render_tab_navigation(); ?>
            
            <form method="post" action="options.php">
                <?php settings_fields( Security_Tools_Utils::SETTINGS_GROUP_THEMES ); ?>
                <?php $this->render_themes_table( $hidden_themes ); ?>
                <?php $this->render_save_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render the Hidden Widgets subpage
     *
     * Contains the Hidden Dashboard Widgets table.
     *
     * @since 2.0
     * @since 2.1 Added tab navigation
     * @return void
     */
    public function render_widgets() {
        $hidden_widgets = Security_Tools_Utils::get_array_option( Security_Tools_Utils::OPTION_HIDDEN_WIDGETS );
        ?>
        <div class="wrap security-tools-page">
            <h1 style="display: none;">Security Tools - Hidden Widgets</h1>
            <?php $this->render_header(); ?>
            <?php $this->render_tab_navigation(); ?>
            
            <form method="post" action="options.php">
                <?php settings_fields( Security_Tools_Utils::SETTINGS_GROUP_WIDGETS ); ?>
                <?php $this->render_widgets_table( $hidden_widgets ); ?>
                <?php $this->render_save_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render the Hidden Admin Bar Items subpage
     *
     * Contains the Hidden Admin Bar Items table and CSS-based hiding section.
     *
     * @since 2.0
     * @since 2.1 Added tab navigation
     * @since 2.4 Added CSS-based hiding section
     * @return void
     */
    public function render_admin_bar() {
        $hidden_admin_bar     = Security_Tools_Utils::get_array_option( Security_Tools_Utils::OPTION_HIDDEN_ADMIN_BAR );
        $hidden_admin_bar_css = Security_Tools_Utils::get_array_option( Security_Tools_Utils::OPTION_HIDDEN_ADMIN_BAR_CSS );
        ?>
        <div class="wrap security-tools-page">
            <h1 style="display: none;">Security Tools - Hidden Admin Bar Items</h1>
            <?php $this->render_header(); ?>
            <?php $this->render_tab_navigation(); ?>
            
            <form method="post" action="options.php">
                <?php settings_fields( Security_Tools_Utils::SETTINGS_GROUP_ADMIN_BAR ); ?>
                <?php $this->render_admin_bar_table( $hidden_admin_bar ); ?>
                <?php $this->render_admin_bar_css_section( $hidden_admin_bar_css ); ?>
                <?php $this->render_save_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render the Hidden Metaboxes subpage
     *
     * Contains the Hidden Metaboxes table and metabox discovery tools.
     *
     * @since 2.0
     * @since 2.1 Added tab navigation
     * @since 2.5 Added metabox discovery scan functionality
     * @since 2.5 Renamed from render_elements() to render_metaboxes()
     * @return void
     */
    public function render_metaboxes() {
        $hidden_metaboxes = Security_Tools_Utils::get_array_option( Security_Tools_Utils::OPTION_HIDDEN_METABOXES );
        ?>
        <div class="wrap security-tools-page">
            <h1 style="display: none;">Security Tools - Hidden Metaboxes</h1>
            <?php $this->render_header(); ?>
            <?php $this->render_tab_navigation(); ?>
            
            <?php $this->render_metabox_scan_section(); ?>
            
            <form method="post" action="options.php">
                <?php settings_fields( Security_Tools_Utils::SETTINGS_GROUP_METABOXES ); ?>
                <?php $this->render_metaboxes_table( $hidden_metaboxes ); ?>
                <?php $this->render_save_button(); ?>
            </form>
        </div>
        <?php
    }

    /**
     * ==========================================================================
     * LEGACY RENDER METHOD
     * ==========================================================================
     * Kept for backwards compatibility. Not used in version 2.0.
     */

    /**
     * Render the settings page (legacy - single page version)
     *
     * @since 1.2
     * @deprecated 2.0 Use individual render methods instead
     * @return void
     */
    public function render() {
        // Redirect to General page for backwards compatibility
        $this->render_general();
    }

    /**
     * ==========================================================================
     * SHARED RENDER COMPONENTS
     * ==========================================================================
     * Reusable components used across multiple subpages.
     */

    /**
     * Render page header
     *
     * @since 1.2
     * @return void
     */
    private function render_header() {
        ?>
        <div class="security-tools-header">
            <h1>
                Security Tools
                <span class="security-tools-version">v<?php echo esc_html( SECURITY_TOOLS_VERSION ); ?></span>
            </h1>
            <p class="security-tools-author">by Carlos Rodríguez</p>
        </div>
        <?php
    }

    /**
     * Render internal tab navigation
     *
     * Displays a horizontal navigation bar for moving between plugin subpages.
     * This is especially useful when the Autohide Menu feature is enabled,
     * as the main sidebar menu won't be visible.
     *
     * @since 2.1
     * @return void
     */
    private function render_tab_navigation() {
        $nav_items    = Security_Tools_Utils::get_navigation_items();
        $current_page = Security_Tools_Utils::get_current_page_slug();
        ?>
        <nav class="security-tools-tab-nav">
            <ul class="security-tools-tab-list">
                <?php foreach ( $nav_items as $item ) : ?>
                    <li class="security-tools-tab-item">
                        <a href="<?php echo esc_url( $item['url'] ); ?>"
                           class="security-tools-tab-link <?php echo ( $current_page === $item['slug'] ) ? 'active' : ''; ?>">
                            <?php echo esc_html( $item['title'] ); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <?php
    }

    /**
     * Render Autohide Menu section
     *
     * Displays a card for the Autohide Menu feature in the General settings.
     * When enabled, the Security Tools menu is hidden from the admin sidebar
     * but remains accessible via direct URL.
     *
     * @since 2.1
     * @return void
     */
    private function render_autohide_section() {
        $autohide_enabled = Security_Tools_Utils::get_bool_option( Security_Tools_Utils::OPTION_AUTOHIDE_MENU );
        $settings_url     = admin_url( 'admin.php?page=' . Security_Tools_Utils::MENU_SLUG );
        ?>
        <div class="security-tools-legend-section">
            <div class="security-tools-legend-header">
                <h2><?php esc_html_e( 'Autohide Menu', 'security-tools' ); ?></h2>
            </div>
            <div class="security-tools-legend-content">
                <label class="control-switch">
                    <input type="checkbox" 
                           name="<?php echo esc_attr( Security_Tools_Utils::OPTION_AUTOHIDE_MENU ); ?>" 
                           value="1" 
                           <?php checked( $autohide_enabled ); ?> />
                    <span class="control-slider"></span>
                    <span class="control-label"><?php esc_html_e( 'Hide Security Tools menu', 'security-tools' ); ?></span>
                </label>
                <p class="control-info">
                    <?php esc_html_e( 'When enabled, the Security Tools menu will be hidden from the admin sidebar. You can still access the settings directly via URL or the tab navigation above.', 'security-tools' ); ?>
                </p>
                <?php if ( $autohide_enabled ) : ?>
                    <p class="control-warning">
                        <strong>
                            <?php
                            printf(
                                /* translators: %s: URL to access Security Tools settings */
                                esc_html__( 'Menu is currently hidden. Bookmark this URL: %s', 'security-tools' ),
                                '<code>' . esc_html( $settings_url ) . '</code>'
                            );
                            ?>
                        </strong>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render Save Settings button
     *
     * @since 2.0
     * @return void
     */
    private function render_save_button() {
        ?>
        <div class="security-tools-buttons">
            <input type="submit" name="submit" class="security-tools-submit" value="<?php esc_attr_e( 'Save Settings', 'security-tools' ); ?>">
        </div>
        <?php
    }

    /**
     * Render Reset All Settings section
     *
     * @since 2.0
     * @return void
     */
    private function render_reset_section() {
        ?>
        <div class="security-tools-legend-section">
            <div class="security-tools-legend-header">
                <h2><?php esc_html_e( 'Reset All Settings', 'security-tools' ); ?></h2>
            </div>
            <div class="security-tools-legend-content">
                <p class="control-info">
                    <?php esc_html_e( 'This will reset all Security Tools settings to their default values. This action cannot be undone.', 'security-tools' ); ?>
                </p>
                <p class="control-warning">
                    <strong><?php esc_html_e( 'Warning: All hidden administrators, plugins, themes, widgets, and other settings will be cleared.', 'security-tools' ); ?></strong>
                </p>
                <form method="post" action="" style="margin-top: 15px;">
                    <input type="hidden" name="security_tools_reset" value="1">
                    <?php wp_nonce_field( 'security_tools_reset_action', 'security_tools_reset_nonce' ); ?>
                    <input type="submit" class="security-tools-reset" value="<?php esc_attr_e( 'Reset All Settings', 'security-tools' ); ?>">
                </form>
            </div>
        </div>
        <?php
    }

    /**
     * ==========================================================================
     * BRANDING SECTION
     * ==========================================================================
     */

    /**
     * Render custom legend section
     *
     * Displays the text input for custom legend that appears on login page
     * and dashboard footer.
     *
     * @since 1.2
     * @since 2.3 Removed section header (now rendered in render_branding)
     * @param string $custom_legend Current legend value
     * @return void
     */
    private function render_legend_section( $custom_legend ) {
        ?>
        <div class="security-tools-legend-section">
            <div class="security-tools-legend-header">
                <h2><?php esc_html_e( 'Custom Legend', 'security-tools' ); ?></h2>
            </div>
            <div class="security-tools-legend-content">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="custom_legend"><?php esc_html_e( 'Custom Legend Text', 'security-tools' ); ?></label>
                        </th>
                        <td>
                            <input type="text" id="custom_legend"
                                name="<?php echo esc_attr( Security_Tools_Utils::OPTION_CUSTOM_LEGEND ); ?>"
                                value="<?php echo esc_attr( $custom_legend ); ?>"
                                class="regular-text"
                                placeholder="<?php esc_attr_e( 'Enter custom legend text', 'security-tools' ); ?>" />
                            <p class="control-info">
                                <?php esc_html_e( 'Add a custom message to appear on the login page and dashboard footer. Leave blank to disable.', 'security-tools' ); ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Render custom login logo section
     *
     * Displays the media uploader for custom login logo and the URL input
     * for the logo link. Uses WordPress Media Library for image selection.
     *
     * @since 2.3
     * @param int    $logo_id  Current logo attachment ID
     * @param string $logo_url Current logo link URL
     * @return void
     */
    private function render_login_logo_section( $logo_id, $logo_url ) {
        // Ensure logo_id is an integer
        $logo_id = absint( $logo_id );
        
        // Get the logo image URL if we have a valid ID
        $logo_image_url = '';
        if ( $logo_id > 0 ) {
            // Check if it's a standard image or SVG
            $is_standard_image = wp_attachment_is_image( $logo_id );
            $mime_type         = get_post_mime_type( $logo_id );
            $is_svg            = ( 'image/svg+xml' === $mime_type || 'image/svg' === $mime_type );

            // Fallback: check file extension for SVG
            if ( ! $is_svg ) {
                $file_path = get_attached_file( $logo_id );
                if ( $file_path ) {
                    $extension = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );
                    $is_svg    = ( 'svg' === $extension );
                }
            }

            if ( $is_standard_image ) {
                // For standard images, get medium size for preview
                $image_src = wp_get_attachment_image_src( $logo_id, 'medium' );
                if ( $image_src && ! empty( $image_src[0] ) ) {
                    $logo_image_url = $image_src[0];
                }
            } elseif ( $is_svg ) {
                // For SVG, get the direct URL
                $logo_image_url = wp_get_attachment_url( $logo_id );
            }
        }
        ?>
        <div class="security-tools-legend-section">
            <div class="security-tools-legend-header">
                <h2><?php esc_html_e( 'Custom Login Logo', 'security-tools' ); ?></h2>
            </div>
            <div class="security-tools-legend-content">
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="login_logo_id"><?php esc_html_e( 'Login Page Logo', 'security-tools' ); ?></label>
                        </th>
                        <td>
                            <div class="security-tools-media-upload">
                                <!-- Hidden field to store the attachment ID -->
                                <input type="hidden" 
                                       id="login_logo_id" 
                                       name="<?php echo esc_attr( Security_Tools_Utils::OPTION_LOGIN_LOGO_ID ); ?>" 
                                       value="<?php echo esc_attr( $logo_id ); ?>" />
                                
                                <!-- Logo preview container -->
                                <div id="login-logo-preview" class="security-tools-logo-preview" <?php echo empty( $logo_image_url ) ? 'style="display:none;"' : ''; ?>>
                                    <?php if ( ! empty( $logo_image_url ) ) : ?>
                                        <img src="<?php echo esc_url( $logo_image_url ); ?>" alt="<?php esc_attr_e( 'Login logo preview', 'security-tools' ); ?>" style="max-width: 300px; height: auto;" />
                                    <?php endif; ?>
                                </div>
                                
                                <!-- Upload and remove buttons -->
                                <div class="security-tools-media-buttons">
                                    <button type="button" id="upload-login-logo" class="button button-secondary">
                                        <?php echo empty( $logo_id ) ? esc_html__( 'Select Logo', 'security-tools' ) : esc_html__( 'Change Logo', 'security-tools' ); ?>
                                    </button>
                                    <button type="button" id="remove-login-logo" class="button button-link-delete" <?php echo empty( $logo_id ) ? 'style="display:none;"' : ''; ?>>
                                        <?php esc_html_e( 'Remove Logo', 'security-tools' ); ?>
                                    </button>
                                </div>
                            </div>
                            <p class="control-info">
                                <?php esc_html_e( 'Select an image from the Media Library to replace the WordPress logo on the login page.', 'security-tools' ); ?>
                            </p>
                            <p class="control-info">
                                <?php esc_html_e( 'Recommended size: maximum of 300×120 pixels.', 'security-tools' ); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="login_logo_url"><?php esc_html_e( 'Logo Link URL', 'security-tools' ); ?></label>
                        </th>
                        <td>
                            <input type="url" 
                                   id="login_logo_url"
                                   name="<?php echo esc_attr( Security_Tools_Utils::OPTION_LOGIN_LOGO_URL ); ?>"
                                   value="<?php echo esc_attr( $logo_url ); ?>"
                                   class="regular-text"
                                   placeholder="<?php echo esc_attr( home_url( '/' ) ); ?>" />
                            <p class="control-info">
                                <?php esc_html_e( 'Enter a custom URL for the logo link. Leave blank to link to your site homepage.', 'security-tools' ); ?>
                            </p>
                            <p class="control-info">
                                <?php 
                                printf(
                                    /* translators: %s: WordPress.org URL */
                                    esc_html__( 'By default, WordPress links the login logo to %s.', 'security-tools' ),
                                    '<code>wordpress.org</code>'
                                ); 
                                ?>
                            </p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * ==========================================================================
     * SYSTEM CONTROLS SECTION
     * ==========================================================================
     */

    /**
     * Render system controls grid content
     *
     * @since 1.2
     * @since 2.0 Renamed from render_system_controls() to avoid conflict with public method
     * @return void
     */
    private function render_system_controls_content() {
        ?>
        <div class="security-tools-section-header">
            <h2><?php esc_html_e( 'System Controls', 'security-tools' ); ?></h2>
        </div>
        <div class="security-tools-table-description">
            <p class="control-info">
                <?php esc_html_e( 'Configuration options that adjust WordPress core behaviour and administrative functions.', 'security-tools' ); ?>
            </p>
        </div>
        <div class="security-tools-controls-grid">
            <?php
            $this->render_toggle_card( 'WordPress Updates', 'Control update functionality',
                Security_Tools_Utils::OPTION_DISABLE_UPDATES, 'Disable all WordPress updates',
                'Stops WordPress from checking and installing updates.',
                'Warning: Disabling updates may leave your site vulnerable.' );

            $this->render_toggle_card( 'Admin Notices', 'Manage admin notifications',
                Security_Tools_Utils::OPTION_HIDE_NOTICES, 'Hide all admin notices',
                'Removes update prompts and system alerts from the dashboard.',
                'Warning: You may miss important alerts.' );

            $this->render_toggle_card( 'Email Sending', 'Control email functionality',
                Security_Tools_Utils::OPTION_DISABLE_EMAILS, 'Disable all email sending',
                'Prevents WordPress from sending any emails.',
                'Warning: This disables password resets and notifications.' );

            $this->render_toggle_card( 'Email Verification', 'Control email verification prompts',
                Security_Tools_Utils::OPTION_DISABLE_EMAIL_CHECK, 'Disable email verification notices',
                'Prevents WordPress from asking admins to verify their email.' );

            $this->render_toggle_card( 'Plugin Management', 'Control plugin management',
                Security_Tools_Utils::OPTION_DISABLE_PLUGIN_CONTROLS, 'Disable plugin controls',
                'Prevents plugin installation, activation, and editing.' );

            $this->render_toggle_card( 'Theme Management', 'Control theme management',
                Security_Tools_Utils::OPTION_DISABLE_THEME_CONTROLS, 'Disable theme controls',
                'Prevents theme changes, customization, and editing.' );

            $this->render_toggle_card( 'Comments System', 'Control comment functionality',
                Security_Tools_Utils::OPTION_DISABLE_COMMENTS, 'Disable all comments',
                'Completely disables comments, pingbacks, and trackbacks.' );

            $this->render_toggle_card( 'Frontend Admin Bar', 'Control frontend admin bar',
                Security_Tools_Utils::OPTION_DISABLE_FRONTEND_ADMIN_BAR, 'Disable frontend admin bar',
                'Hides the admin bar on the frontend for all users.' );
            ?>
        </div>

        <?php
        // Render the Hide Login Page section (separate from the grid)
        $this->render_hide_login_section();
    }

    /**
     * Render Hide Login Page section
     *
     * Displays a card for the Hide Login Page feature in System Controls.
     * This feature allows administrators to set a custom login URL slug,
     * hiding the default wp-login.php from unauthorized access.
     *
     * @since 2.2
     * @return void
     */
    private function render_hide_login_section() {
        $hide_login_enabled = Security_Tools_Utils::get_bool_option( Security_Tools_Utils::OPTION_HIDE_LOGIN_ENABLED );
        $hide_login_slug    = get_option( Security_Tools_Utils::OPTION_HIDE_LOGIN_SLUG, '' );
        $custom_login_url   = ! empty( $hide_login_slug ) ? home_url( sanitize_title( $hide_login_slug ) ) : '';
        ?>
        <div class="security-tools-section-header" style="margin-top: 30px;">
            <h2><?php esc_html_e( 'Login Security', 'security-tools' ); ?></h2>
        </div>
        <div class="security-tools-table-description">
            <p class="control-info">
                <?php esc_html_e( 'Protect your login page by changing its URL. Unauthorized access to the default login URLs will show a 404 error.', 'security-tools' ); ?>
            </p>
        </div>

        <div class="security-tools-legend-section">
            <div class="security-tools-legend-header">
                <h2><?php esc_html_e( 'Hide Login Page', 'security-tools' ); ?></h2>
            </div>
            <div class="security-tools-legend-content">
                <label class="control-switch">
                    <input type="checkbox" 
                           name="<?php echo esc_attr( Security_Tools_Utils::OPTION_HIDE_LOGIN_ENABLED ); ?>" 
                           value="1" 
                           id="hide-login-enabled"
                           <?php checked( $hide_login_enabled ); ?> />
                    <span class="control-slider"></span>
                    <span class="control-label"><?php esc_html_e( 'Enable custom login URL', 'security-tools' ); ?></span>
                </label>
                <p class="control-info">
                    <?php esc_html_e( 'When enabled, the default wp-login.php and wp-admin (for non-logged users) will return a 404 error. Only the custom URL below will work for logging in.', 'security-tools' ); ?>
                </p>

                <table class="form-table" style="margin-top: 15px;">
                    <tr>
                        <th scope="row">
                            <label for="hide-login-slug"><?php esc_html_e( 'Custom Login Slug', 'security-tools' ); ?></label>
                        </th>
                        <td>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <code><?php echo esc_html( home_url( '/' ) ); ?></code>
                                <input type="text" 
                                       id="hide-login-slug"
                                       name="<?php echo esc_attr( Security_Tools_Utils::OPTION_HIDE_LOGIN_SLUG ); ?>"
                                       value="<?php echo esc_attr( $hide_login_slug ); ?>"
                                       class="regular-text"
                                       placeholder="<?php esc_attr_e( 'my-secret-login', 'security-tools' ); ?>"
                                       style="max-width: 200px;" />
                            </div>
                            <p class="control-info">
                                <?php esc_html_e( 'Enter a custom slug for your login page. Use only lowercase letters, numbers, and hyphens.', 'security-tools' ); ?>
                            </p>
                            <p class="control-info">
                                <?php esc_html_e( 'Example: If you enter "my-login", your new login URL will be:', 'security-tools' ); ?>
                                <code><?php echo esc_html( home_url( 'my-login' ) ); ?></code>
                            </p>
                        </td>
                    </tr>
                </table>

                <?php if ( $hide_login_enabled && ! empty( $hide_login_slug ) ) : ?>
                    <p class="control-warning" style="margin-top: 15px;">
                        <strong>
                            <?php
                            printf(
                                /* translators: %s: The custom login URL */
                                esc_html__( 'Your current login URL is: %s', 'security-tools' ),
                                '<code>' . esc_html( $custom_login_url ) . '</code>'
                            );
                            ?>
                        </strong>
                    </p>
                    <p class="control-warning">
                        <strong><?php esc_html_e( 'Important: Bookmark this URL! If you forget it, you will need to disable this feature via FTP or database.', 'security-tools' ); ?></strong>
                    </p>
                <?php elseif ( $hide_login_enabled && empty( $hide_login_slug ) ) : ?>
                    <p class="control-warning" style="margin-top: 15px;">
                        <strong><?php esc_html_e( 'Warning: Feature is enabled but no custom slug is set. Please enter a custom slug above.', 'security-tools' ); ?></strong>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * Render a toggle control card
     *
     * @since 1.2
     * @param string $title   Card title
     * @param string $desc    Card description
     * @param string $option  Option name constant
     * @param string $label   Toggle label
     * @param string $info    Info text
     * @param string $warning Warning text (optional)
     * @return void
     */
    private function render_toggle_card( $title, $desc, $option, $label, $info, $warning = '' ) {
        $enabled = Security_Tools_Utils::get_bool_option( $option );
        ?>
        <div class="security-tools-control-card">
            <div class="control-card-header">
                <h3><?php echo esc_html( $title ); ?></h3>
                <p class="control-description"><?php echo esc_html( $desc ); ?></p>
            </div>
            <div class="control-card-content">
                <label class="control-switch">
                    <input type="checkbox" name="<?php echo esc_attr( $option ); ?>" value="1" <?php checked( $enabled ); ?> />
                    <span class="control-slider"></span>
                    <span class="control-label"><?php echo esc_html( $label ); ?></span>
                </label>
                <p class="control-info"><?php echo esc_html( $info ); ?></p>
                <?php if ( $warning ) : ?>
                    <p class="control-warning"><strong><?php echo esc_html( $warning ); ?></strong></p>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    /**
     * ==========================================================================
     * TABLE SECTIONS
     * ==========================================================================
     * Methods for rendering data tables (admins, plugins, themes, etc.)
     */

    /**
     * Render hidden administrators table
     *
     * @since 1.2
     * @param array $hidden Array of hidden admin IDs
     * @return void
     */
    private function render_admins_table( $hidden ) {
        $admins = get_users( array( 'role' => 'administrator' ) );
        $this->render_section_header( 'Hidden Administrators',
            'Administrator accounts hidden from the Users screen. Hidden admins retain full access.' );
        ?>
        <div class="security-tools-enhanced-table" data-table-type="administrators">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <?php $this->render_checkbox_header(); ?>
                        <th class="sortable-header" data-column="username">Username <span class="sort-indicator">⇅</span></th>
                        <th class="sortable-header" data-column="email">Email <span class="sort-indicator">⇅</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $admins as $admin ) : ?>
                        <tr>
                            <?php $this->render_checkbox_cell( Security_Tools_Utils::OPTION_HIDDEN_ADMINS, $admin->ID, in_array( $admin->ID, $hidden, true ) ); ?>
                            <td><strong><?php echo esc_html( $admin->user_login ); ?></strong></td>
                            <td><?php echo esc_html( $admin->user_email ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render hidden plugins table
     *
     * @since 1.2
     * @param array $hidden Array of hidden plugin paths
     * @return void
     */
    private function render_plugins_table( $hidden ) {
        $plugins = get_plugins();
        $this->render_section_header( 'Hidden Plugins',
            'Plugins hidden from the Plugins page. Hidden plugins remain functional.' );
        ?>
        <div class="security-tools-enhanced-table" data-table-type="plugins">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <?php $this->render_checkbox_header(); ?>
                        <th class="sortable-header" data-column="plugin-name">Plugin Name <span class="sort-indicator">⇅</span></th>
                        <th class="sortable-header" data-column="status" style="width:100px">Status <span class="sort-indicator">⇅</span></th>
                        <th style="width:80px">Version</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $plugins as $path => $data ) :
                        // Skip Security Tools itself
                        if ( strpos( $path, 'security-tools' ) !== false ) continue;
                        $active = is_plugin_active( $path );
                    ?>
                        <tr>
                            <?php $this->render_checkbox_cell( Security_Tools_Utils::OPTION_HIDDEN_PLUGINS, $path, in_array( $path, $hidden, true ) ); ?>
                            <td><strong><?php echo esc_html( $data['Name'] ); ?></strong></td>
                            <td><span class="status-badge <?php echo $active ? 'status-active' : 'status-inactive'; ?>"><?php echo $active ? 'Active' : 'Inactive'; ?></span></td>
                            <td><?php echo esc_html( $data['Version'] ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render hidden themes table
     *
     * @since 1.2
     * @param array $hidden Array of hidden theme slugs
     * @return void
     */
    private function render_themes_table( $hidden ) {
        $themes = wp_get_themes();
        $active = get_stylesheet();
        $this->render_section_header( 'Hidden Themes', 'Themes hidden from the Themes page. Hidden themes remain functional.' );
        ?>
        <div class="security-tools-enhanced-table" data-table-type="themes">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <?php $this->render_checkbox_header(); ?>
                        <th class="sortable-header" data-column="theme-name">Theme Name <span class="sort-indicator">⇅</span></th>
                        <th class="sortable-header" data-column="status" style="width:100px">Status <span class="sort-indicator">⇅</span></th>
                        <th style="width:80px">Version</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $themes as $slug => $theme ) :
                        $is_active = ( $slug === $active );
                    ?>
                        <tr>
                            <?php $this->render_checkbox_cell( Security_Tools_Utils::OPTION_HIDDEN_THEMES, $slug, in_array( $slug, $hidden, true ) ); ?>
                            <td><strong><?php echo esc_html( $theme->get( 'Name' ) ); ?></strong></td>
                            <td><span class="status-badge <?php echo $is_active ? 'status-active' : 'status-inactive'; ?>"><?php echo $is_active ? 'Active' : 'Inactive'; ?></span></td>
                            <td><?php echo esc_html( $theme->get( 'Version' ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render hidden widgets table
     *
     * Merges detected widgets with previously hidden widgets that couldn't be
     * detected. This ensures users can always unhide widgets even if they
     * register late or under specific conditions (e.g., Google Site Kit).
     *
     * Also outputs a hidden field with all rendered widget IDs so the
     * sanitization logic can distinguish between "user unchecked" and
     * "widget not shown in form".
     *
     * @since 1.2
     * @since 1.4 Added merging of undetectable hidden widgets for display
     * @since 1.4 Added hidden field for rendered widget IDs
     * @param array $hidden Array of hidden widget IDs
     * @return void
     */
    private function render_widgets_table( $hidden ) {
        $loader  = Security_Tools_Loader::get_instance();
        $feature = $loader->get_feature( 'hide_widgets' );
        $widgets = $feature ? $feature->get_available_widgets() : array();

        // Merge in any hidden widgets that weren't detected
        // This allows users to unhide widgets that register late (e.g., Google Site Kit)
        $widgets = $this->merge_undetected_hidden_widgets( $widgets, $hidden );

        // Get all widget IDs that will be rendered in the form
        $rendered_widget_ids = array_keys( $widgets );

        $this->render_section_header( 'Hidden Dashboard Widgets', 'Widgets hidden from the WordPress dashboard.' );
        ?>
        <!-- Hidden field to track which widgets were rendered in the form -->
        <input type="hidden" 
               name="security_tools_rendered_widgets" 
               value="<?php echo esc_attr( implode( ',', $rendered_widget_ids ) ); ?>" />
        <div class="security-tools-enhanced-table" data-table-type="widgets">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <?php $this->render_checkbox_header(); ?>
                        <th class="sortable-header" data-column="widget-name">Widget Name <span class="sort-indicator">⇅</span></th>
                        <th class="sortable-header" data-column="context" style="width:120px">Context <span class="sort-indicator">⇅</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $widgets as $id => $data ) : ?>
                        <tr>
                            <?php $this->render_checkbox_cell( Security_Tools_Utils::OPTION_HIDDEN_WIDGETS, $id, in_array( $id, $hidden, true ) ); ?>
                            <td><strong><?php echo esc_html( $data['title'] ); ?></strong></td>
                            <td><?php echo esc_html( ucfirst( $data['context'] ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render hidden admin bar items table
     *
     * @since 1.2
     * @param array $hidden Array of hidden admin bar item IDs
     * @return void
     */
    private function render_admin_bar_table( $hidden ) {
        $loader  = Security_Tools_Loader::get_instance();
        $feature = $loader->get_feature( 'admin_bar' );
        $items   = $feature ? $feature->get_available_items() : array();
        $this->render_section_header( 'Hidden Admin Bar Items', 'Items hidden from the backend admin bar.' );
        ?>
        <div class="security-tools-enhanced-table" data-table-type="admin-bar">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <?php $this->render_checkbox_header(); ?>
                        <th class="sortable-header" data-column="item-name">Item Name <span class="sort-indicator">⇅</span></th>
                        <th style="width:220px">Item ID</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $items as $id => $data ) : ?>
                        <tr>
                            <?php $this->render_checkbox_cell( Security_Tools_Utils::OPTION_HIDDEN_ADMIN_BAR, $id, in_array( $id, $hidden, true ) ); ?>
                            <td><strong><?php echo esc_html( $data['title'] ); ?></strong></td>
                            <td><code><?php echo esc_html( $id ); ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * Render CSS-based admin bar hiding section
     *
     * Displays a tokenfield/chip input for adding CSS element IDs to hide
     * admin bar items that cannot be hidden through conventional methods.
     * Uses CSS `display: none !important` to hide elements by their ID.
     *
     * @since 2.4
     * @param array $hidden_css Array of CSS element IDs to hide
     * @return void
     */
    private function render_admin_bar_css_section( $hidden_css ) {
        ?>
        <div class="security-tools-section-header" style="margin-top: 30px;">
            <h2><?php esc_html_e( 'Hide by CSS ID', 'security-tools' ); ?></h2>
        </div>
        <div class="security-tools-table-description">
            <p class="control-info">
                <?php esc_html_e( 'Some admin bar items cannot be hidden using the table above. Use this section to hide items by their CSS element ID.', 'security-tools' ); ?>
            </p>
        </div>

        <div class="security-tools-legend-section">
            <div class="security-tools-legend-header">
                <h2><?php esc_html_e( 'CSS Element IDs', 'security-tools' ); ?></h2>
            </div>
            <div class="security-tools-legend-content">
                <p class="control-info" style="margin-top: 0;">
                    <?php esc_html_e( 'Enter the CSS ID of admin bar elements you want to hide. You can find these IDs by inspecting the admin bar in your browser\'s developer tools.', 'security-tools' ); ?>
                </p>
                <p class="control-info">
                    <?php 
                    printf(
                        /* translators: %s: Example HTML element */
                        esc_html__( 'Example: For an element like %s, enter: wp-admin-bar-gform-forms', 'security-tools' ),
                        '<code>&lt;li id="wp-admin-bar-gform-forms"&gt;</code>'
                    ); 
                    ?>
                </p>

                <!-- Tokenfield Input Container -->
                <div class="security-tools-tokenfield" id="admin-bar-css-tokenfield">
                    <!-- Marker field to indicate this section was rendered (enables clearing all tokens) -->
                    <input type="hidden" name="security_tools_admin_bar_css_rendered" value="1" />

                    <!-- Token Container - displays added tokens/chips -->
                    <div class="security-tools-token-container" id="admin-bar-css-tokens">
                        <?php foreach ( $hidden_css as $css_id ) : ?>
                            <span class="security-tools-token" data-value="<?php echo esc_attr( $css_id ); ?>">
                                <span class="token-text"><?php echo esc_html( $css_id ); ?></span>
                                <button type="button" class="token-remove" aria-label="<?php esc_attr_e( 'Remove', 'security-tools' ); ?>">&times;</button>
                            </span>
                        <?php endforeach; ?>
                    </div>

                    <!-- Input Row -->
                    <div class="security-tools-tokenfield-input-row">
                        <input type="text" 
                               id="admin-bar-css-input" 
                               class="regular-text" 
                               placeholder="<?php esc_attr_e( 'e.g., wp-admin-bar-example', 'security-tools' ); ?>" />
                        <button type="button" id="admin-bar-css-add" class="button button-secondary">
                            <?php esc_html_e( 'Add ID', 'security-tools' ); ?>
                        </button>
                    </div>

                    <!-- Hidden inputs container - holds the actual form values -->
                    <div id="admin-bar-css-hidden-inputs">
                        <?php foreach ( $hidden_css as $css_id ) : ?>
                            <input type="hidden" 
                                   name="<?php echo esc_attr( Security_Tools_Utils::OPTION_HIDDEN_ADMIN_BAR_CSS ); ?>[]" 
                                   value="<?php echo esc_attr( $css_id ); ?>"
                                   data-token-value="<?php echo esc_attr( $css_id ); ?>" />
                        <?php endforeach; ?>
                    </div>
                </div>

                <p class="control-info">
                    <?php esc_html_e( 'Items will be hidden using CSS. Remove a token to make the item visible again.', 'security-tools' ); ?>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * Render metabox scan section
     *
     * Displays the metabox discovery tools including scan button and status.
     * The scan feature loads post edit screens to capture all registered metaboxes
     * from plugins and themes.
     *
     * @since 2.5
     * @return void
     */
    private function render_metabox_scan_section() {
        $loader           = Security_Tools_Loader::get_instance();
        $feature          = $loader->get_feature( 'hide_metaboxes' );
        $discovered_count = $feature ? $feature->get_discovered_count() : 0;
        $post_types       = $feature ? $feature->get_scannable_post_types() : array();
        $scan_nonce       = wp_create_nonce( 'security_tools_manual_scan' );
        ?>
        <div class="security-tools-section-header">
            <h2><?php esc_html_e( 'Metabox Discovery', 'security-tools' ); ?></h2>
        </div>
        <div class="security-tools-table-description">
            <p class="control-info">
                <?php esc_html_e( 'Third-party metaboxes from plugins and themes are discovered automatically when you edit posts. Use the scan button to detect all metaboxes at once.', 'security-tools' ); ?>
            </p>
        </div>

        <div class="security-tools-legend-section">
            <div class="security-tools-legend-header">
                <h2><?php esc_html_e( 'Scan for Metaboxes', 'security-tools' ); ?></h2>
            </div>
            <div class="security-tools-legend-content">
                <p class="control-info" style="margin-top: 0;">
                    <?php 
                    printf(
                        /* translators: %d: Number of discovered metaboxes */
                        esc_html__( 'Currently discovered: %d metaboxes from plugins and themes.', 'security-tools' ),
                        intval( $discovered_count )
                    ); 
                    ?>
                </p>
                <p class="control-info">
                    <?php esc_html_e( 'Click the button below to scan all post types and discover available metaboxes. This will also clean up metaboxes from deactivated plugins or themes.', 'security-tools' ); ?>
                </p>

                <div class="security-tools-scan-controls">
                    <button type="button" id="security-tools-scan-metaboxes" class="button button-secondary">
                        <span class="dashicons dashicons-search" style="margin-top: 3px;"></span>
                        <?php esc_html_e( 'Scan for Metaboxes', 'security-tools' ); ?>
                    </button>
                    <span id="security-tools-scan-status" class="security-tools-scan-status"></span>
                </div>

                <!-- Progress bar (hidden by default) -->
                <div id="security-tools-scan-progress" class="security-tools-scan-progress" style="display: none;">
                    <div class="security-tools-progress-bar">
                        <div class="security-tools-progress-fill" style="width: 0%;"></div>
                    </div>
                    <p class="security-tools-progress-text">
                        <?php esc_html_e( 'Scanning...', 'security-tools' ); ?>
                        <span id="security-tools-scan-current">0</span> / <span id="security-tools-scan-total">0</span>
                    </p>
                </div>

                <!-- Hidden iframe container for scanning -->
                <div id="security-tools-scan-frame-container" style="display: none;">
                    <iframe id="security-tools-scan-frame" name="security-tools-scan-frame" style="width: 1px; height: 1px; position: absolute; left: -9999px;"></iframe>
                </div>

                <!-- Pass data to JavaScript -->
                <script type="text/javascript">
                    var securityToolsScan = {
                        nonce: <?php echo wp_json_encode( $scan_nonce ); ?>,
                        ajaxurl: <?php echo wp_json_encode( admin_url( 'admin-ajax.php' ) ); ?>,
                        postTypes: <?php echo wp_json_encode( $post_types ); ?>,
                        strings: {
                            scanning: <?php echo wp_json_encode( __( 'Scanning...', 'security-tools' ) ); ?>,
                            complete: <?php echo wp_json_encode( __( 'Scan complete!', 'security-tools' ) ); ?>,
                            error: <?php echo wp_json_encode( __( 'Scan error. Please try again.', 'security-tools' ) ); ?>,
                            discovered: <?php echo wp_json_encode( __( 'Discovered %d metaboxes.', 'security-tools' ) ); ?>,
                            reloading: <?php echo wp_json_encode( __( 'Reloading page...', 'security-tools' ) ); ?>
                        }
                    };
                </script>
            </div>
        </div>
        <?php
    }

    /**
     * Render hidden metaboxes table
     *
     * Displays all available metaboxes (core + discovered + previously hidden)
     * with checkboxes to select which ones to hide.
     *
     * Also outputs a hidden field with all rendered metabox IDs so the
     * sanitization logic can distinguish between "user unchecked" and
     * "metabox not shown in form".
     *
     * @since 1.2
     * @since 2.5 Added rendered metaboxes tracking field, improved descriptions
     * @param array $hidden Array of hidden metabox IDs
     * @return void
     */
    private function render_metaboxes_table( $hidden ) {
        $loader    = Security_Tools_Loader::get_instance();
        $feature   = $loader->get_feature( 'hide_metaboxes' );
        $metaboxes = $feature ? $feature->get_available_metaboxes() : array();

        // Get all metabox IDs that will be rendered in the form
        $rendered_metabox_ids = array_keys( $metaboxes );

        $this->render_section_header( 'Hidden Metaboxes',
            'Meta boxes hidden from post and page editing screens. Metaboxes are hidden in both Classic Editor and Gutenberg.' );
        ?>
        <!-- Hidden field to track which metaboxes were rendered in the form -->
        <input type="hidden" 
               name="security_tools_rendered_metaboxes" 
               value="<?php echo esc_attr( implode( ',', $rendered_metabox_ids ) ); ?>" />
        <div class="security-tools-enhanced-table" data-table-type="metaboxes">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <?php $this->render_checkbox_header(); ?>
                        <th class="sortable-header" data-column="element-name">Element Name <span class="sort-indicator">⇅</span></th>
                        <th class="sortable-header" data-column="context" style="width:120px">Context <span class="sort-indicator">⇅</span></th>
                        <th class="sortable-header" data-column="post-type" style="width:150px">Post Type <span class="sort-indicator">⇅</span></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ( empty( $metaboxes ) ) : ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px;">
                                <?php esc_html_e( 'No metaboxes detected yet. Edit a post or page to automatically discover metaboxes, or use the "Scan for Metaboxes" button above.', 'security-tools' ); ?>
                            </td>
                        </tr>
                    <?php else : ?>
                        <?php foreach ( $metaboxes as $id => $data ) : ?>
                            <tr>
                                <?php $this->render_checkbox_cell( Security_Tools_Utils::OPTION_HIDDEN_METABOXES, $id, in_array( $id, $hidden, true ) ); ?>
                                <td>
                                    <strong><?php echo esc_html( $data['title'] ); ?></strong>
                                    <?php if ( $data['title'] === $id ) : ?>
                                        <br><small class="description"><?php esc_html_e( '(Title not available)', 'security-tools' ); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo esc_html( ucfirst( $data['context'] ) ); ?></td>
                                <td><?php echo esc_html( $data['post_type'] ); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    /**
     * ==========================================================================
     * TABLE HELPER METHODS
     * ==========================================================================
     */

    /**
     * Render section header
     *
     * @since 1.2
     * @param string $title       Section title
     * @param string $description Section description
     * @return void
     */
    private function render_section_header( $title, $description ) {
        ?>
        <div class="security-tools-section-header">
            <h2><?php echo esc_html( $title ); ?></h2>
        </div>
        <div class="security-tools-table-description">
            <p class="control-info"><?php echo esc_html( $description ); ?></p>
        </div>
        <?php
    }

    /**
     * Render checkbox header cell
     *
     * @since 1.2
     * @return void
     */
    private function render_checkbox_header() {
        ?>
        <th style="width:60px">
            <label class="security-tools-checkbox">
                <input type="checkbox" class="select-all-checkbox">
                <span class="security-tools-checkbox-custom"></span>
            </label>
        </th>
        <?php
    }

    /**
     * Render checkbox cell
     *
     * @since 1.2
     * @param string $option_name Option name for the checkbox
     * @param mixed  $value       Checkbox value
     * @param bool   $checked     Whether checkbox is checked
     * @return void
     */
    private function render_checkbox_cell( $option_name, $value, $checked ) {
        ?>
        <td>
            <label class="security-tools-checkbox">
                <input type="checkbox" name="<?php echo esc_attr( $option_name ); ?>[]"
                    value="<?php echo esc_attr( $value ); ?>" <?php checked( $checked ); ?> />
                <span class="security-tools-checkbox-custom"></span>
            </label>
        </td>
        <?php
    }

    /**
     * ==========================================================================
     * UTILITY METHODS
     * ==========================================================================
     */

    /**
     * Merge undetected hidden widgets into the widgets array
     *
     * Some plugins register dashboard widgets late or under specific conditions
     * that aren't met when we enumerate widgets from the settings page. This
     * method ensures that any previously hidden widgets that weren't detected
     * are still displayed in the table, allowing users to unhide them.
     *
     * Undetected widgets are displayed using their raw widget ID as the title
     * since the original title is not available.
     *
     * @since  1.4
     * @param  array $widgets Detected widgets from get_available_widgets()
     * @param  array $hidden  Currently hidden widget IDs from saved option
     * @return array Merged widgets array including undetected hidden widgets
     */
    private function merge_undetected_hidden_widgets( $widgets, $hidden ) {
        if ( empty( $hidden ) ) {
            return $widgets;
        }

        foreach ( $hidden as $widget_id ) {
            // Skip if widget was already detected
            if ( isset( $widgets[ $widget_id ] ) ) {
                continue;
            }

            // Add undetected hidden widget using raw widget ID as title
            // The original title is not available for late-registering widgets
            $widgets[ $widget_id ] = array(
                'title'   => $widget_id,
                'context' => 'unknown',
            );
        }

        return $widgets;
    }
}
