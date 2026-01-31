<?php
/**
 * Security Tools - Input Sanitization
 *
 * Handles sanitization and validation of all plugin settings input.
 * Each method validates input and tracks changes for admin notices.
 *
 * @package    Security_Tools
 * @subpackage Admin
 * @version    2.5
 * @author     Carlos Rodríguez
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Class Security_Tools_Admin_Sanitization
 *
 * Provides sanitization callbacks for all plugin settings.
 *
 * @since 1.2
 */
class Security_Tools_Admin_Sanitization {

    /**
     * ==========================================================================
     * STRING SANITIZATION
     * ==========================================================================
     */

    /**
     * Sanitize custom legend text
     *
     * @since  1.2
     * @param  string $input Raw input
     * @return string Sanitized input
     */
    public function sanitize_custom_legend( $input ) {
        $sanitized = sanitize_text_field( $input );
        $existing  = get_option( Security_Tools_Utils::OPTION_CUSTOM_LEGEND, '' );

        if ( $sanitized !== $existing ) {
            update_option( Security_Tools_Utils::OPTION_LEGEND_LAST_CHANGE, true );
        }

        return $sanitized;
    }

    /**
     * Sanitize login logo attachment ID
     *
     * Validates that the ID is a valid image attachment (including SVG).
     * Returns 0 if invalid or empty (which clears the logo).
     *
     * @since  2.3
     * @param  mixed $input Raw input (attachment ID)
     * @return int Sanitized attachment ID or 0
     */
    public function sanitize_login_logo_id( $input ) {
        $existing  = get_option( Security_Tools_Utils::OPTION_LOGIN_LOGO_ID, 0 );
        $sanitized = absint( $input );

        // If empty or zero, clear the logo
        if ( empty( $sanitized ) ) {
            if ( $existing !== 0 ) {
                update_option( Security_Tools_Utils::OPTION_LOGIN_LOGO_ID_LAST_CHANGE, true );
            }
            return 0;
        }

        // Verify the attachment exists
        $attachment = get_post( $sanitized );
        if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
            add_settings_error(
                Security_Tools_Utils::OPTION_LOGIN_LOGO_ID,
                'invalid_attachment',
                __( 'The selected logo image is not valid. Please select a valid image from the Media Library.', 'security-tools' ),
                'error'
            );
            return intval( $existing );
        }

        // Check if it's an image (including SVG)
        if ( ! $this->is_valid_logo_image( $sanitized ) ) {
            add_settings_error(
                Security_Tools_Utils::OPTION_LOGIN_LOGO_ID,
                'not_image',
                __( 'The selected file is not an image. Please select a valid image file (JPG, PNG, GIF, WebP, or SVG).', 'security-tools' ),
                'error'
            );
            return intval( $existing );
        }

        // Track change if different
        if ( $sanitized !== intval( $existing ) ) {
            update_option( Security_Tools_Utils::OPTION_LOGIN_LOGO_ID_LAST_CHANGE, true );
        }

        return $sanitized;
    }

    /**
     * Check if attachment is a valid logo image
     *
     * WordPress's wp_attachment_is_image() doesn't recognize SVG files.
     * This method checks for both standard images and SVG files.
     *
     * @since  2.3
     * @param  int $attachment_id The attachment ID to check
     * @return bool True if valid image (including SVG), false otherwise
     */
    private function is_valid_logo_image( $attachment_id ) {
        // First check if WordPress recognizes it as an image
        if ( wp_attachment_is_image( $attachment_id ) ) {
            return true;
        }

        // Check if it's an SVG (not recognized by wp_attachment_is_image)
        $mime_type = get_post_mime_type( $attachment_id );
        if ( 'image/svg+xml' === $mime_type || 'image/svg' === $mime_type ) {
            return true;
        }

        // Fallback: check file extension for SVG
        $file_path = get_attached_file( $attachment_id );
        if ( $file_path ) {
            $extension = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );
            if ( 'svg' === $extension ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Sanitize login logo URL
     *
     * Validates and sanitizes the custom URL for the login logo link.
     * Allows empty string to revert to default WordPress.org link.
     *
     * @since  2.3
     * @param  mixed $input Raw input (URL string)
     * @return string Sanitized URL or empty string
     */
    public function sanitize_login_logo_url( $input ) {
        $existing  = get_option( Security_Tools_Utils::OPTION_LOGIN_LOGO_URL, '' );
        $sanitized = '';

        // If not empty, validate and sanitize the URL
        if ( ! empty( $input ) ) {
            $sanitized = esc_url_raw( trim( $input ) );

            // If the URL became empty after sanitization, it was invalid
            if ( empty( $sanitized ) && ! empty( $input ) ) {
                add_settings_error(
                    Security_Tools_Utils::OPTION_LOGIN_LOGO_URL,
                    'invalid_url',
                    __( 'The login logo URL is not valid. Please enter a valid URL starting with http:// or https://.', 'security-tools' ),
                    'error'
                );
                return $existing;
            }
        }

        // Track change if different
        if ( $sanitized !== $existing ) {
            update_option( Security_Tools_Utils::OPTION_LOGIN_LOGO_URL_LAST_CHANGE, true );
        }

        return $sanitized;
    }

    /**
     * ==========================================================================
     * BOOLEAN SANITIZATION
     * ==========================================================================
     * All boolean options follow the same pattern:
     * 1. Compare with existing value
     * 2. Track change if different
     * 3. Return boolean
     */

    /**
     * Sanitize disable updates toggle
     *
     * @since  1.2
     * @param  mixed $input Raw input
     * @return bool Sanitized boolean
     */
    public function sanitize_disable_updates( $input ) {
        return $this->sanitize_boolean_option(
            $input,
            Security_Tools_Utils::OPTION_DISABLE_UPDATES,
            Security_Tools_Utils::OPTION_DISABLE_UPDATES_LAST_CHANGE
        );
    }

    /**
     * Sanitize disable emails toggle
     *
     * @since  1.2
     * @param  mixed $input Raw input
     * @return bool Sanitized boolean
     */
    public function sanitize_disable_emails( $input ) {
        return $this->sanitize_boolean_option(
            $input,
            Security_Tools_Utils::OPTION_DISABLE_EMAILS,
            Security_Tools_Utils::OPTION_DISABLE_EMAILS_LAST_CHANGE
        );
    }

    /**
     * Sanitize disable email check toggle
     *
     * @since  1.2
     * @param  mixed $input Raw input
     * @return bool Sanitized boolean
     */
    public function sanitize_disable_email_check( $input ) {
        return $this->sanitize_boolean_option(
            $input,
            Security_Tools_Utils::OPTION_DISABLE_EMAIL_CHECK,
            Security_Tools_Utils::OPTION_DISABLE_EMAIL_CHECK_LAST_CHANGE
        );
    }

    /**
     * Sanitize hide notices toggle
     *
     * @since  1.2
     * @param  mixed $input Raw input
     * @return bool Sanitized boolean
     */
    public function sanitize_hide_notices( $input ) {
        return $this->sanitize_boolean_option(
            $input,
            Security_Tools_Utils::OPTION_HIDE_NOTICES,
            Security_Tools_Utils::OPTION_HIDE_NOTICES_LAST_CHANGE
        );
    }

    /**
     * Sanitize disable comments toggle
     *
     * @since  1.2
     * @param  mixed $input Raw input
     * @return bool Sanitized boolean
     */
    public function sanitize_disable_comments( $input ) {
        return $this->sanitize_boolean_option(
            $input,
            Security_Tools_Utils::OPTION_DISABLE_COMMENTS,
            Security_Tools_Utils::OPTION_DISABLE_COMMENTS_LAST_CHANGE
        );
    }

    /**
     * Sanitize disable plugin controls toggle
     *
     * @since  1.2
     * @param  mixed $input Raw input
     * @return bool Sanitized boolean
     */
    public function sanitize_disable_plugin_controls( $input ) {
        return $this->sanitize_boolean_option(
            $input,
            Security_Tools_Utils::OPTION_DISABLE_PLUGIN_CONTROLS,
            Security_Tools_Utils::OPTION_DISABLE_PLUGIN_CONTROLS_LAST_CHANGE
        );
    }

    /**
     * Sanitize disable theme controls toggle
     *
     * @since  1.2
     * @param  mixed $input Raw input
     * @return bool Sanitized boolean
     */
    public function sanitize_disable_theme_controls( $input ) {
        return $this->sanitize_boolean_option(
            $input,
            Security_Tools_Utils::OPTION_DISABLE_THEME_CONTROLS,
            Security_Tools_Utils::OPTION_DISABLE_THEME_CONTROLS_LAST_CHANGE
        );
    }

    /**
     * Sanitize disable frontend admin bar toggle
     *
     * @since  1.2
     * @param  mixed $input Raw input
     * @return bool Sanitized boolean
     */
    public function sanitize_disable_frontend_admin_bar( $input ) {
        return $this->sanitize_boolean_option(
            $input,
            Security_Tools_Utils::OPTION_DISABLE_FRONTEND_ADMIN_BAR,
            Security_Tools_Utils::OPTION_DISABLE_FRONTEND_ADMIN_BAR_LAST_CHANGE
        );
    }

    /**
     * Sanitize autohide menu toggle
     *
     * @since  2.1
     * @param  mixed $input Raw input
     * @return bool Sanitized boolean
     */
    public function sanitize_autohide_menu( $input ) {
        return $this->sanitize_boolean_option(
            $input,
            Security_Tools_Utils::OPTION_AUTOHIDE_MENU,
            Security_Tools_Utils::OPTION_AUTOHIDE_MENU_LAST_CHANGE
        );
    }

    /**
     * Sanitize hide login enabled toggle
     *
     * @since  2.2
     * @param  mixed $input Raw input
     * @return bool Sanitized boolean
     */
    public function sanitize_hide_login_enabled( $input ) {
        return $this->sanitize_boolean_option(
            $input,
            Security_Tools_Utils::OPTION_HIDE_LOGIN_ENABLED,
            Security_Tools_Utils::OPTION_HIDE_LOGIN_ENABLED_LAST_CHANGE
        );
    }

    /**
     * Sanitize hide login custom slug
     *
     * Validates the slug to ensure it:
     * - Is URL-safe (sanitize_title)
     * - Does not conflict with WordPress reserved slugs
     * - Does not conflict with existing pages/posts
     *
     * @since  2.2
     * @param  mixed $input Raw input
     * @return string Sanitized slug
     */
    public function sanitize_hide_login_slug( $input ) {
        $existing  = get_option( Security_Tools_Utils::OPTION_HIDE_LOGIN_SLUG, '' );
        $sanitized = sanitize_title( $input );

        // List of reserved WordPress slugs that should not be used
        $reserved_slugs = array(
            'admin',
            'login',
            'wp-admin',
            'wp-login',
            'wp-login.php',
            'dashboard',
            'wp-content',
            'wp-includes',
            'wp-json',
            'xmlrpc.php',
            'feed',
            'rss',
            'rss2',
            'atom',
            'sitemap',
            'robots.txt',
            'favicon.ico',
        );

        // Check if slug is reserved
        if ( in_array( $sanitized, $reserved_slugs, true ) ) {
            // Add admin notice about reserved slug
            add_settings_error(
                Security_Tools_Utils::OPTION_HIDE_LOGIN_SLUG,
                'reserved_slug',
                sprintf(
                    /* translators: %s: The reserved slug that was attempted */
                    __( 'The login slug "%s" is reserved and cannot be used. Please choose a different slug.', 'security-tools' ),
                    esc_html( $sanitized )
                ),
                'error'
            );
            return $existing; // Return existing value
        }

        // Check if slug conflicts with an existing page or post
        $existing_page = get_page_by_path( $sanitized, OBJECT, array( 'page', 'post' ) );
        if ( $existing_page ) {
            add_settings_error(
                Security_Tools_Utils::OPTION_HIDE_LOGIN_SLUG,
                'slug_exists',
                sprintf(
                    /* translators: %s: The slug that conflicts with existing content */
                    __( 'The login slug "%s" conflicts with an existing page or post. Please choose a different slug.', 'security-tools' ),
                    esc_html( $sanitized )
                ),
                'error'
            );
            return $existing; // Return existing value
        }

        // Track change if different
        if ( $sanitized !== $existing ) {
            update_option( Security_Tools_Utils::OPTION_HIDE_LOGIN_SLUG_LAST_CHANGE, true );
        }

        return $sanitized;
    }

    /**
     * ==========================================================================
     * ARRAY SANITIZATION
     * ==========================================================================
     */

    /**
     * Sanitize hidden administrators list
     *
     * Validates that each ID is a valid administrator user.
     *
     * @since  1.2
     * @param  mixed $input Raw input
     * @return array Sanitized array of user IDs
     */
    public function sanitize_hidden_admins( $input ) {
        $existing  = get_option( Security_Tools_Utils::OPTION_HIDDEN_ADMINS, array() );
        $sanitized = array();

        if ( is_array( $input ) ) {
            foreach ( $input as $user_id ) {
                $user = get_userdata( intval( $user_id ) );
                if ( $user && in_array( 'administrator', $user->roles, true ) ) {
                    $sanitized[] = intval( $user_id );
                }
            }
        }

        if ( $sanitized !== $existing ) {
            update_option( Security_Tools_Utils::OPTION_ADMINS_LAST_CHANGE, true );
        }

        return $sanitized;
    }

    /**
     * Sanitize hidden plugins list
     *
     * Validates that each plugin path exists.
     *
     * @since  1.2
     * @param  mixed $input Raw input
     * @return array Sanitized array of plugin paths
     */
    public function sanitize_hidden_plugins( $input ) {
        $existing    = get_option( Security_Tools_Utils::OPTION_HIDDEN_PLUGINS, array() );
        $sanitized   = array();
        $all_plugins = get_plugins();

        if ( is_array( $input ) ) {
            foreach ( $input as $plugin_path ) {
                if ( array_key_exists( $plugin_path, $all_plugins ) ) {
                    $sanitized[] = sanitize_text_field( $plugin_path );
                }
            }
        }

        if ( $sanitized !== $existing ) {
            update_option( Security_Tools_Utils::OPTION_PLUGINS_LAST_CHANGE, true );
        }

        return $sanitized;
    }

    /**
     * Sanitize hidden themes list
     *
     * Validates that each theme slug exists.
     *
     * @since  1.2
     * @param  mixed $input Raw input
     * @return array Sanitized array of theme slugs
     */
    public function sanitize_hidden_themes( $input ) {
        $existing   = get_option( Security_Tools_Utils::OPTION_HIDDEN_THEMES, array() );
        $sanitized  = array();
        $all_themes = wp_get_themes();

        if ( is_array( $input ) ) {
            foreach ( $input as $theme_slug ) {
                if ( array_key_exists( $theme_slug, $all_themes ) ) {
                    $sanitized[] = sanitize_text_field( $theme_slug );
                }
            }
        }

        if ( $sanitized !== $existing ) {
            update_option( Security_Tools_Utils::OPTION_THEMES_LAST_CHANGE, true );
        }

        return $sanitized;
    }

    /**
     * Sanitize hidden widgets list
     *
     * Preserves hidden widget IDs that weren't rendered in the form.
     * Some plugins (e.g., Google Site Kit) register widgets late or under
     * specific conditions that aren't met when we enumerate available widgets
     * from the settings page. Without preservation, these widgets would be
     * silently unhidden when saving unrelated settings.
     *
     * Uses a hidden field (security_tools_rendered_widgets) from the form to
     * know exactly which widgets were displayed. This allows us to distinguish
     * between "user unchecked this widget" and "widget wasn't shown in form".
     *
     * Logic:
     * 1. Widgets in POST data → include (user checked them)
     * 2. Previously hidden widgets NOT in rendered list → preserve (wasn't in form)
     * 3. Widgets in rendered list but NOT in POST → exclude (user unchecked them)
     *
     * @since  1.2
     * @since  1.4 Added preservation of undetectable widget selections (bug fix)
     * @since  1.4 Now uses rendered widgets hidden field for accurate detection
     * @param  mixed $input Raw input
     * @return array Sanitized array of widget IDs
     */
    public function sanitize_hidden_widgets( $input ) {
        $existing  = get_option( Security_Tools_Utils::OPTION_HIDDEN_WIDGETS, array() );
        $existing  = is_array( $existing ) ? $existing : array();
        $sanitized = array();

        // Get the list of widgets that were actually rendered in the form
        // This comes from a hidden field added by render_widgets_table()
        $rendered_widget_ids = $this->get_rendered_widget_ids();

        // Step 1: Add widgets from POST data (user's current selections)
        if ( is_array( $input ) ) {
            foreach ( $input as $widget_id ) {
                $sanitized[] = sanitize_text_field( $widget_id );
            }
        }

        // Step 2: Preserve previously hidden widgets that weren't rendered in the form
        // These are widgets that somehow weren't shown (edge case protection)
        foreach ( $existing as $widget_id ) {
            // Skip if already in sanitized (was in POST data)
            if ( in_array( $widget_id, $sanitized, true ) ) {
                continue;
            }
            // Skip if widget was rendered in form (user explicitly unchecked it)
            if ( in_array( $widget_id, $rendered_widget_ids, true ) ) {
                continue;
            }
            // Preserve: widget was hidden but wasn't rendered in the form
            $sanitized[] = sanitize_text_field( $widget_id );
        }

        if ( $sanitized !== $existing ) {
            update_option( Security_Tools_Utils::OPTION_WIDGETS_LAST_CHANGE, true );
        }

        return $sanitized;
    }

    /**
     * Get IDs of widgets that were rendered in the settings form
     *
     * Reads from the hidden field 'security_tools_rendered_widgets' that is
     * populated by render_widgets_table() in class-admin-page.php.
     * This tells us exactly which widgets the user could see and interact with.
     *
     * @since  1.4
     * @return array Array of widget IDs that were rendered in the form
     */
    private function get_rendered_widget_ids() {
        // Check for the hidden field in POST data
        if ( ! isset( $_POST['security_tools_rendered_widgets'] ) ) {
            // Fallback: if hidden field is missing, return empty array
            // This means all existing hidden widgets will be preserved
            return array();
        }

        $rendered_string = sanitize_text_field( wp_unslash( $_POST['security_tools_rendered_widgets'] ) );

        if ( empty( $rendered_string ) ) {
            return array();
        }

        // Split comma-separated list into array
        return array_map( 'trim', explode( ',', $rendered_string ) );
    }

    /**
     * Sanitize hidden admin bar items list
     *
     * @since  1.2
     * @param  mixed $input Raw input
     * @return array Sanitized array of admin bar item IDs
     */
    public function sanitize_hidden_admin_bar( $input ) {
        $existing  = get_option( Security_Tools_Utils::OPTION_HIDDEN_ADMIN_BAR, array() );
        $sanitized = array();

        if ( is_array( $input ) ) {
            foreach ( $input as $item_id ) {
                $sanitized[] = sanitize_text_field( $item_id );
            }
        }

        if ( $sanitized !== $existing ) {
            update_option( Security_Tools_Utils::OPTION_ADMIN_BAR_LAST_CHANGE, true );
        }

        return $sanitized;
    }

    /**
     * Sanitize hidden admin bar CSS IDs list
     *
     * Sanitizes CSS element IDs for CSS-based admin bar item hiding.
     * Strips leading '#' if present and validates each ID contains only
     * valid CSS identifier characters.
     *
     * Uses a marker field to detect when the form was submitted but all
     * tokens were removed (empty array should be saved, not ignored).
     *
     * @since  2.4
     * @param  mixed $input Raw input (array of CSS IDs)
     * @return array Sanitized array of CSS element IDs
     */
    public function sanitize_hidden_admin_bar_css( $input ) {
        $existing  = get_option( Security_Tools_Utils::OPTION_HIDDEN_ADMIN_BAR_CSS, array() );
        $existing  = is_array( $existing ) ? $existing : array();
        $sanitized = array();

        // Check if the form section was rendered (marker field present)
        // This allows us to distinguish between "not submitted" and "submitted empty"
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by settings API
        $form_was_rendered = isset( $_POST['security_tools_admin_bar_css_rendered'] );

        // If the form wasn't rendered, preserve existing values
        if ( ! $form_was_rendered ) {
            return $existing;
        }

        // Form was rendered - process the input (which may be empty/null if all tokens removed)
        if ( is_array( $input ) ) {
            foreach ( $input as $css_id ) {
                // Sanitize the ID
                $clean_id = $this->sanitize_css_id( $css_id );
                
                // Only add non-empty, unique IDs
                if ( ! empty( $clean_id ) && ! in_array( $clean_id, $sanitized, true ) ) {
                    $sanitized[] = $clean_id;
                }
            }
        }

        if ( $sanitized !== $existing ) {
            update_option( Security_Tools_Utils::OPTION_ADMIN_BAR_CSS_LAST_CHANGE, true );
        }

        return $sanitized;
    }

    /**
     * Sanitize a single CSS ID
     *
     * Cleans and validates a CSS element ID by:
     * - Trimming whitespace
     * - Removing leading '#' character if present
     * - Ensuring only valid CSS identifier characters remain
     *
     * Valid CSS identifiers can contain: a-z, A-Z, 0-9, hyphens, underscores
     * (and must not start with a digit or hyphen followed by digit)
     *
     * @since  2.4
     * @param  string $css_id Raw CSS ID input
     * @return string Sanitized CSS ID or empty string if invalid
     */
    private function sanitize_css_id( $css_id ) {
        // Ensure we have a string
        if ( ! is_string( $css_id ) ) {
            return '';
        }

        // Trim whitespace
        $css_id = trim( $css_id );

        // Strip leading '#' if present (user may have copied from browser inspector)
        $css_id = ltrim( $css_id, '#' );

        // Return empty if nothing left
        if ( empty( $css_id ) ) {
            return '';
        }

        // Only allow valid CSS identifier characters: a-z, A-Z, 0-9, hyphen, underscore
        // This prevents XSS via malicious CSS selectors
        if ( ! preg_match( '/^[a-zA-Z_][a-zA-Z0-9_-]*$/', $css_id ) ) {
            // If the ID doesn't match the strict pattern, try to sanitize it
            $css_id = preg_replace( '/[^a-zA-Z0-9_-]/', '', $css_id );
            
            // Ensure it starts with a letter or underscore after sanitization
            if ( empty( $css_id ) || preg_match( '/^[0-9-]/', $css_id ) ) {
                return '';
            }
        }

        return $css_id;
    }

    /**
     * Sanitize hidden metaboxes list
     *
     * Preserves hidden metabox IDs that weren't rendered in the form.
     * Some metaboxes may not be detected on the settings page but were
     * previously discovered and hidden. Without preservation, these metaboxes
     * would be silently unhidden when saving settings.
     *
     * Uses a hidden field (security_tools_rendered_metaboxes) from the form to
     * know exactly which metaboxes were displayed. This allows us to distinguish
     * between "user unchecked this metabox" and "metabox wasn't shown in form".
     *
     * Logic:
     * 1. Metaboxes in POST data → include (user checked them)
     * 2. Previously hidden metaboxes NOT in rendered list → preserve (wasn't in form)
     * 3. Metaboxes in rendered list but NOT in POST → exclude (user unchecked them)
     *
     * @since  1.2
     * @since  2.5 Added preservation of undetectable metabox selections
     * @param  mixed $input Raw input
     * @return array Sanitized array of metabox IDs
     */
    public function sanitize_hidden_metaboxes( $input ) {
        $existing  = get_option( Security_Tools_Utils::OPTION_HIDDEN_METABOXES, array() );
        $existing  = is_array( $existing ) ? $existing : array();
        $sanitized = array();

        // Get the list of metaboxes that were actually rendered in the form
        // This comes from a hidden field added by render_metaboxes_table()
        $rendered_metabox_ids = $this->get_rendered_metabox_ids();

        // Step 1: Add metaboxes from POST data (user's current selections)
        if ( is_array( $input ) ) {
            foreach ( $input as $metabox_id ) {
                $sanitized[] = sanitize_text_field( $metabox_id );
            }
        }

        // Step 2: Preserve previously hidden metaboxes that weren't rendered in the form
        // These are metaboxes that somehow weren't shown (edge case protection)
        foreach ( $existing as $metabox_id ) {
            // Skip if already in sanitized (was in POST data)
            if ( in_array( $metabox_id, $sanitized, true ) ) {
                continue;
            }
            // Skip if metabox was rendered in form (user explicitly unchecked it)
            if ( in_array( $metabox_id, $rendered_metabox_ids, true ) ) {
                continue;
            }
            // Preserve: metabox was hidden but wasn't rendered in the form
            $sanitized[] = sanitize_text_field( $metabox_id );
        }

        if ( $sanitized !== $existing ) {
            update_option( Security_Tools_Utils::OPTION_METABOXES_LAST_CHANGE, true );
        }

        return $sanitized;
    }

    /**
     * Get IDs of metaboxes that were rendered in the settings form
     *
     * Reads from the hidden field 'security_tools_rendered_metaboxes' that is
     * populated by render_metaboxes_table() in class-admin-page.php.
     * This tells us exactly which metaboxes the user could see and interact with.
     *
     * @since  2.5
     * @return array Array of metabox IDs that were rendered in the form
     */
    private function get_rendered_metabox_ids() {
        // Check for the hidden field in POST data
        // phpcs:ignore WordPress.Security.NonceVerification.Missing -- Nonce verified by settings API
        if ( ! isset( $_POST['security_tools_rendered_metaboxes'] ) ) {
            // Fallback: if hidden field is missing, return empty array
            // This means all existing hidden metaboxes will be preserved
            return array();
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Missing
        $rendered_string = sanitize_text_field( wp_unslash( $_POST['security_tools_rendered_metaboxes'] ) );

        if ( empty( $rendered_string ) ) {
            return array();
        }

        // Split comma-separated list into array
        return array_map( 'trim', explode( ',', $rendered_string ) );
    }

    /**
     * ==========================================================================
     * HELPER METHODS
     * ==========================================================================
     */

    /**
     * Generic boolean option sanitizer
     *
     * @since  1.2
     * @param  mixed  $input           Raw input value
     * @param  string $option_name     Option name for comparison
     * @param  string $tracking_option Change tracking option name
     * @return bool Sanitized boolean value
     */
    private function sanitize_boolean_option( $input, $option_name, $tracking_option ) {
        $existing    = get_option( $option_name, false );
        $new_setting = (bool) $input;

        if ( $existing !== $new_setting ) {
            update_option( $tracking_option, true );
        }

        return $new_setting;
    }
}
