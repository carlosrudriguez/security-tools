<?php
/**
 * Plugin Name: Security Tools
 * Description: Advanced security management tools for WordPress.
 * Version: 2.5
 * Author: Carlos Rodríguez
 * Author URI: https://carlosrodriguez.mx/
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 *
 * Security Tools is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 3 of the License, or any later version. This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 * 
 * =============================================================================
 * SECURITY TOOLS - MAIN BOOTSTRAP FILE
 * =============================================================================
 *
 * This is the main entry point for the Security Tools plugin.
 * It defines constants, loads required files, and initializes the plugin.
 *
 * STRUCTURE:
 * ----------
 * /security-tools.php      - This file (bootstrap)
 * /includes/               - Core classes (Loader, Admin, Utils)
 * /features/               - Individual feature classes
 * /admin/                  - Admin interface classes
 * /assets/css/             - Stylesheets
 * /assets/js/              - JavaScript files
 *
 * @package    Security_Tools
 * @version    2.5
 * @author     Carlos Rodríguez
 * =============================================================================
 */

// Prevent direct access to this file
defined( 'ABSPATH' ) || exit;

/**
 * =============================================================================
 * PLUGIN CONSTANTS
 * =============================================================================
 * Define constants used throughout the plugin for paths, URLs, and version.
 */

// Plugin version - update this when releasing new versions
define( 'SECURITY_TOOLS_VERSION', '2.5' );

// Plugin directory path (with trailing slash)
define( 'SECURITY_TOOLS_PATH', plugin_dir_path( __FILE__ ) );

// Plugin directory URL (with trailing slash)
define( 'SECURITY_TOOLS_URL', plugin_dir_url( __FILE__ ) );

// Plugin basename (for hooks that need it)
define( 'SECURITY_TOOLS_BASENAME', plugin_basename( __FILE__ ) );

/**
 * =============================================================================
 * LOAD CORE FILES
 * =============================================================================
 * Load the essential classes that the plugin needs to function.
 * These are loaded unconditionally as they contain core functionality.
 */

// Utility functions used by multiple classes
require_once SECURITY_TOOLS_PATH . 'includes/class-utils.php';

// Main loader class - registers hooks and loads features
require_once SECURITY_TOOLS_PATH . 'includes/class-loader.php';

/**
 * =============================================================================
 * LOAD ADMIN FILES (Conditional)
 * =============================================================================
 * Admin-specific files are only loaded in the WordPress admin area.
 * This improves frontend performance by not loading unnecessary code.
 */

if ( is_admin() ) {
    // Admin initialization and menu registration
    require_once SECURITY_TOOLS_PATH . 'includes/class-admin.php';
    
    // Settings registration (options API)
    require_once SECURITY_TOOLS_PATH . 'admin/class-admin-settings.php';
    
    // Input sanitization callbacks
    require_once SECURITY_TOOLS_PATH . 'admin/class-admin-sanitization.php';
    
    // Admin notices (save confirmations, etc.)
    require_once SECURITY_TOOLS_PATH . 'admin/class-admin-notices.php';
    
    // Settings page renderer (HTML output)
    require_once SECURITY_TOOLS_PATH . 'admin/class-admin-page.php';
}

/**
 * =============================================================================
 * LOAD FEATURE FILES
 * =============================================================================
 * Each feature is contained in its own file for maintainability.
 * Features are loaded via the Loader class which handles conditional loading.
 */

// Self-hiding functionality (hides plugin from other admins)
require_once SECURITY_TOOLS_PATH . 'features/class-feature-self-hiding.php';

// Custom branding (login message, footer text)
require_once SECURITY_TOOLS_PATH . 'features/class-feature-branding.php';

// Admin notices control (hide dashboard notices)
require_once SECURITY_TOOLS_PATH . 'features/class-feature-notices.php';

// Disable WordPress updates
require_once SECURITY_TOOLS_PATH . 'features/class-feature-updates.php';

// Disable WordPress emails
require_once SECURITY_TOOLS_PATH . 'features/class-feature-emails.php';

// Disable comments system
require_once SECURITY_TOOLS_PATH . 'features/class-feature-comments.php';

// Plugin management controls
require_once SECURITY_TOOLS_PATH . 'features/class-feature-plugin-controls.php';

// Theme management controls
require_once SECURITY_TOOLS_PATH . 'features/class-feature-theme-controls.php';

// Admin bar controls (frontend visibility + item hiding)
require_once SECURITY_TOOLS_PATH . 'features/class-feature-admin-bar.php';

// Hide administrators from user list
require_once SECURITY_TOOLS_PATH . 'features/class-feature-hide-admins.php';

// Hide plugins from plugins list
require_once SECURITY_TOOLS_PATH . 'features/class-feature-hide-plugins.php';

// Hide themes from themes list
require_once SECURITY_TOOLS_PATH . 'features/class-feature-hide-themes.php';

// Hide dashboard widgets
require_once SECURITY_TOOLS_PATH . 'features/class-feature-hide-widgets.php';

// Hide metaboxes from post/page editors
require_once SECURITY_TOOLS_PATH . 'features/class-feature-hide-metaboxes.php';

// Wordfence 2FA integration
require_once SECURITY_TOOLS_PATH . 'features/class-feature-wordfence.php';

// Hide Login Page (custom login URL)
require_once SECURITY_TOOLS_PATH . 'features/class-feature-hide-login.php';

/**
 * =============================================================================
 * INITIALIZE PLUGIN
 * =============================================================================
 * Create instances of the main classes to start the plugin.
 * The Loader class handles registering all hooks and initializing features.
 */

/**
 * Initialize the plugin
 *
 * This function creates the main plugin instance and is hooked to 'plugins_loaded'
 * to ensure WordPress is fully loaded before we initialize.
 *
 * @since 1.2
 * @return void
 */
function security_tools_init() {
    // Initialize the main loader (registers hooks, initializes features)
    Security_Tools_Loader::get_instance();
    
    // Initialize admin functionality if in admin area
    if ( is_admin() ) {
        Security_Tools_Admin::get_instance();
    }
}

// Hook initialization to plugins_loaded for proper timing
add_action( 'plugins_loaded', 'security_tools_init' );
