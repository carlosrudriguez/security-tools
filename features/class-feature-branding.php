<?php
/**
 * Security Tools - Branding Feature
 *
 * Adds custom branding to WordPress login page and dashboard footer.
 * Features include:
 * - Custom legend text on login page and dashboard footer
 * - Custom login page logo (replaces WordPress logo)
 * - Custom login logo URL (replaces WordPress.org link)
 *
 * @package    Security_Tools
 * @subpackage Features
 * @version    2.5
 * @author     Carlos Rodríguez
 */

// Prevent direct access
defined( 'ABSPATH' ) || exit;

/**
 * Class Security_Tools_Feature_Branding
 *
 * Implements custom branding functionality for login page and dashboard.
 *
 * @since 1.2
 * @since 2.3 Added custom login logo and logo URL functionality
 */
class Security_Tools_Feature_Branding {

    /**
     * Constructor - Register hooks
     *
     * @since 1.2
     * @since 2.3 Added login logo hooks
     */
    public function __construct() {
        // Add login page message
        add_filter( 'login_message', array( $this, 'add_login_message' ) );

        // Modify dashboard footer
        add_filter( 'admin_footer_text', array( $this, 'modify_footer' ) );

        // Custom login logo functionality (added in 2.3)
        add_action( 'login_head', array( $this, 'output_custom_login_logo_css' ) );
        add_filter( 'login_headerurl', array( $this, 'modify_login_header_url' ) );
        add_filter( 'login_headertext', array( $this, 'modify_login_header_text' ) );
    }

    /**
     * ==========================================================================
     * CUSTOM LEGEND FUNCTIONALITY
     * ==========================================================================
     */

    /**
     * Add custom message to the login page
     *
     * Displays the custom legend text above the login form.
     *
     * @since 1.2
     * @param string $message Existing login message
     * @return string Modified login message
     */
    public function add_login_message( $message ) {
        $custom_legend = get_option( Security_Tools_Utils::OPTION_CUSTOM_LEGEND, '' );

        if ( ! empty( $custom_legend ) ) {
            return '<p class="message">' . esc_html( $custom_legend ) . '</p>' . $message;
        }

        return $message;
    }

    /**
     * Modify the dashboard footer text
     *
     * Replaces "Thank you for creating with WordPress" with custom legend.
     *
     * @since 1.2
     * @param string $text Original footer text
     * @return string Modified footer text
     */
    public function modify_footer( $text ) {
        $custom_legend = get_option( Security_Tools_Utils::OPTION_CUSTOM_LEGEND, '' );

        if ( ! empty( $custom_legend ) ) {
            return '<span id="footer-thankyou">' . esc_html( $custom_legend ) . '</span>';
        }

        return $text;
    }

    /**
     * ==========================================================================
     * CUSTOM LOGIN LOGO FUNCTIONALITY
     * ==========================================================================
     * @since 2.3
     */

    /**
     * Output custom CSS for the login page logo
     *
     * Injects CSS into the login page head to replace the WordPress logo
     * with the custom logo. The logo is constrained to max-width 300px
     * and centered for consistent display regardless of original image size.
     *
     * For SVG images or when dimensions cannot be determined, uses auto height
     * to preserve aspect ratio without distortion.
     *
     * @since 2.3
     * @return void
     */
    public function output_custom_login_logo_css() {
        $logo_id = get_option( Security_Tools_Utils::OPTION_LOGIN_LOGO_ID, 0 );

        // If no custom logo is set, don't output any CSS
        if ( empty( $logo_id ) ) {
            return;
        }

        // Ensure logo_id is an integer
        $logo_id = absint( $logo_id );

        // Get the logo URL from attachment ID
        $logo_url = $this->get_login_logo_url_from_id( $logo_id );

        // If the attachment doesn't exist or isn't an image, bail
        if ( empty( $logo_url ) ) {
            return;
        }

        // Check if this is an SVG image
        $mime_type = get_post_mime_type( $logo_id );
        $is_svg    = ( 'image/svg+xml' === $mime_type || 'image/svg' === $mime_type );

        // Fallback: check file extension for SVG
        if ( ! $is_svg ) {
            $file_path = get_attached_file( $logo_id );
            if ( $file_path ) {
                $extension = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );
                $is_svg    = ( 'svg' === $extension );
            }
        }

        // Get the image dimensions for proper aspect ratio
        // First try wp_get_attachment_metadata for processed images
        $image_meta = wp_get_attachment_metadata( $logo_id );
        $width      = isset( $image_meta['width'] ) ? intval( $image_meta['width'] ) : 0;
        $height     = isset( $image_meta['height'] ) ? intval( $image_meta['height'] ) : 0;

        // If metadata doesn't have dimensions, try wp_get_attachment_image_src
        if ( $width <= 0 || $height <= 0 ) {
            $image_src = wp_get_attachment_image_src( $logo_id, 'full' );
            if ( $image_src && ! empty( $image_src[1] ) && ! empty( $image_src[2] ) ) {
                $width  = intval( $image_src[1] );
                $height = intval( $image_src[2] );
            }
        }

        // Determine if we have valid dimensions
        // SVG files often return 1x1 or very small dimensions from WordPress functions,
        // so we need a minimum threshold (10px) to consider dimensions as valid
        $min_valid_dimension   = 10;
        $has_valid_dimensions  = ( $width >= $min_valid_dimension && $height >= $min_valid_dimension );

        // For SVG files, we should always use auto height since they scale infinitely
        // Even if WordPress returns some dimensions, SVG should use flexible sizing
        if ( $is_svg ) {
            $has_valid_dimensions = false;
        }

        // Calculate display dimensions
        $max_width     = 300;
        $display_width = $max_width; // Default to max width

        if ( $has_valid_dimensions ) {
            // Use actual dimensions, constrained to max width
            $display_width  = min( $width, $max_width );
            $aspect_ratio   = $height / $width;
            $display_height = round( $display_width * $aspect_ratio );
            $height_css     = intval( $display_height ) . 'px';
        } else {
            // SVG or unknown dimensions: use auto height to preserve aspect ratio
            // This prevents distortion of vector graphics
            $height_css = 'auto';
        }

        ?>
        <style type="text/css">
            /**
             * Security Tools - Custom Login Logo Styles
             * @since 2.3
             */
            #login h1 a,
            .login h1 a {
                background-image: url('<?php echo esc_url( $logo_url ); ?>');
                background-size: contain;
                background-repeat: no-repeat;
                background-position: center center;
                width: <?php echo intval( $display_width ); ?>px;
                height: <?php echo esc_attr( $height_css ); ?>;
                <?php if ( ! $has_valid_dimensions ) : ?>
                /* SVG/vector or unknown dimensions - set minimum height for visibility */
                min-height: 80px;
                <?php endif; ?>
                max-width: 100%;
                margin: 0 auto 25px;
            }
        </style>
        <script type="text/javascript">
            /**
             * Security Tools - Open login logo link in new tab
             * @since 2.3
             */
            document.addEventListener('DOMContentLoaded', function() {
                var logoLink = document.querySelector('#login h1 a, .login h1 a');
                if (logoLink) {
                    logoLink.setAttribute('target', '_blank');
                    logoLink.setAttribute('rel', 'noopener noreferrer');
                }
            });
        </script>
        <?php
    }

    /**
     * Modify the login header URL (logo link)
     *
     * Changes the URL that the login page logo links to.
     * By default, WordPress links to wordpress.org.
     *
     * @since 2.3
     * @param string $url Original header URL (default: wordpress.org)
     * @return string Modified header URL
     */
    public function modify_login_header_url( $url ) {
        $logo_id = get_option( Security_Tools_Utils::OPTION_LOGIN_LOGO_ID, 0 );

        // Only modify if a custom logo is set
        if ( empty( $logo_id ) ) {
            return $url;
        }

        $custom_url = get_option( Security_Tools_Utils::OPTION_LOGIN_LOGO_URL, '' );

        // If custom URL is set, use it; otherwise default to home URL
        if ( ! empty( $custom_url ) ) {
            return esc_url( $custom_url );
        }

        // If no custom URL but we have a logo, link to site home
        return home_url( '/' );
    }

    /**
     * Modify the login header text (logo title attribute)
     *
     * Changes the title attribute (hover text) of the login logo link.
     * By default, WordPress shows "Powered by WordPress".
     *
     * @since 2.3
     * @param string $text Original header text
     * @return string Modified header text
     */
    public function modify_login_header_text( $text ) {
        $logo_id = get_option( Security_Tools_Utils::OPTION_LOGIN_LOGO_ID, 0 );

        // Only modify if a custom logo is set
        if ( empty( $logo_id ) ) {
            return $text;
        }

        // Return the site name as the title attribute
        return get_bloginfo( 'name' );
    }

    /**
     * ==========================================================================
     * HELPER METHODS
     * ==========================================================================
     */

    /**
     * Get the logo image URL from attachment ID
     *
     * Returns the full-size image URL for the given attachment ID.
     * Returns empty string if attachment doesn't exist or isn't an image.
     * Supports both standard images and SVG files.
     *
     * @since  2.3
     * @param  int $attachment_id The attachment ID
     * @return string The image URL or empty string
     */
    private function get_login_logo_url_from_id( $attachment_id ) {
        if ( empty( $attachment_id ) ) {
            return '';
        }

        // Verify the attachment exists
        $attachment = get_post( $attachment_id );
        if ( ! $attachment || 'attachment' !== $attachment->post_type ) {
            return '';
        }

        // Check if it's a valid image (including SVG)
        $is_standard_image = wp_attachment_is_image( $attachment_id );
        $mime_type         = get_post_mime_type( $attachment_id );
        $is_svg            = ( 'image/svg+xml' === $mime_type || 'image/svg' === $mime_type );

        // Fallback: check file extension for SVG
        if ( ! $is_svg ) {
            $file_path = get_attached_file( $attachment_id );
            if ( $file_path ) {
                $extension = strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) );
                $is_svg    = ( 'svg' === $extension );
            }
        }

        if ( ! $is_standard_image && ! $is_svg ) {
            return '';
        }

        // For SVG files, get URL directly from attachment
        if ( $is_svg ) {
            return wp_get_attachment_url( $attachment_id );
        }

        // For standard images, get the full-size image URL
        $image_src = wp_get_attachment_image_src( $attachment_id, 'full' );

        if ( ! $image_src || empty( $image_src[0] ) ) {
            return '';
        }

        return $image_src[0];
    }
}
