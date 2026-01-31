# Security Tools - Changelog

All notable changes to this project will be documented in this file.

---

## [2.5] - 2024

### Improved Feature: Hidden Post/Page Elements (Metabox Discovery)

Version 2.5 fixes and enhances the Hidden Post/Page Elements feature to properly detect ALL metaboxes, including those registered by third-party plugins and themes. Previously, only core WordPress metaboxes were reliably detected.

### Added

- **Dynamic Metabox Discovery System**
  - Automatic background discovery when editing any post type
  - Manual "Scan for Metaboxes" button for comprehensive detection
  - Discovered metaboxes are stored persistently in the database
  - Metaboxes from deactivated plugins/themes are automatically cleaned up during scans

- **New "Scan for Metaboxes" Feature**
  - Located in the Elements settings page above the metaboxes table
  - Loads edit screens for all post types via hidden iframes
  - Progress bar with animated stripe effect shows scan progress
  - Displays count of discovered metaboxes upon completion
  - Automatically reloads page to show updated metabox list

- **Automatic Discovery on Post Edit Screens**
  - JavaScript captures all metaboxes when editing posts/pages/CPTs
  - Sends discovered metaboxes to server via AJAX
  - Works with both Classic Editor and Gutenberg
  - Captures metabox ID, title, context, and post type

- **New Constants in `Security_Tools_Utils`**
  - `OPTION_DISCOVERED_METABOXES` - Array storing discovered metabox data

- **New AJAX Handlers**
  - `security_tools_discover_metaboxes` - Receives auto-discovered metaboxes
  - `security_tools_manual_scan` - Manages manual scan start/complete actions
  - `security_tools_get_scan_url` - Returns edit screen URL for a post type

- **New CSS Styles**
  - `.security-tools-scan-controls` - Scan button container
  - `.security-tools-scan-status` - Status text styling
  - `.security-tools-scan-progress` - Progress container
  - `.security-tools-progress-bar` - Progress bar track
  - `.security-tools-progress-fill` - Animated progress fill with stripe effect
  - `.security-tools-progress-text` - Progress counter text

- **New JavaScript Functions**
  - `initializeMetaboxScanner()` - Initializes scan functionality
  - Discovery script injection on post edit screens
  - Gutenberg metabox detection via `wp.data` API

- **Improved Sanitization**
  - Added rendered metaboxes tracking (similar to widgets fix in 1.4)
  - Preserves hidden metaboxes not shown in form
  - `get_rendered_metabox_ids()` helper method

### Changed

- **`get_available_metaboxes()` Method**
  - Now merges: core metaboxes + discovered metaboxes + previously hidden
  - Displays "(Title not available)" for metaboxes without titles
  - Shows comma-separated post type list for multi-post-type metaboxes
  - Sorted alphabetically by title

- **Metabox Hiding Scope**
  - Now applies to ALL post types with `show_ui => true`
  - Updated from `public => true` for broader coverage

- **Gutenberg Panel Hiding**
  - Expanded panel mapping for more core panels
  - Added retry logic for late-loading panels
  - Improved CSS hiding for metabox containers in Gutenberg

- **Elements Settings Page**
  - Added "Metabox Discovery" section with scan controls
  - Shows current count of discovered metaboxes
  - Displays helpful message when no metaboxes are detected
  - Post Type column widened for multiple post types

### Technical Details

#### Discovery Data Structure

```php
array(
    'metabox_id' => array(
        'title'      => 'Metabox Title',
        'context'    => 'normal|side|advanced',
        'post_types' => array('post', 'page', 'custom_type'),
    ),
)
```

#### Scan Process

1. Click "Scan for Metaboxes" button
2. AJAX call clears existing discovered metaboxes (fresh start)
3. For each post type with `show_ui => true`:
   - Get edit URL (existing post or new post screen)
   - Load in hidden iframe
   - Wait for discovery script to capture metaboxes
   - Discovery script sends AJAX with found metaboxes
4. After all post types scanned, page reloads
5. Metaboxes from deactivated plugins won't be detected, effectively cleaning them up

#### Files Modified

1. `includes/class-utils.php`
   - Added `OPTION_DISCOVERED_METABOXES` constant
   - Updated `get_all_option_names()` array

2. `features/class-feature-hide-metaboxes.php`
   - Complete rewrite with discovery system
   - Added automatic discovery script injection
   - Added AJAX handlers for discovery and manual scan
   - Updated `get_available_metaboxes()` to merge all sources
   - Improved Gutenberg panel hiding

3. `admin/class-admin-page.php`
   - Added `render_metabox_scan_section()` method
   - Updated `render_elements()` to include scan section
   - Updated `render_metaboxes_table()` with rendered tracking field
   - Added empty state message for metaboxes table

4. `admin/class-admin-sanitization.php`
   - Updated `sanitize_hidden_metaboxes()` with preservation logic
   - Added `get_rendered_metabox_ids()` helper method

5. `assets/js/admin-scripts.js`
   - Added `initializeMetaboxScanner()` function
   - Full scan workflow with progress tracking
   - Hidden iframe management for edit screen loading

6. `assets/css/admin-styles.css`
   - Added metabox scanner styles
   - Progress bar with animated stripe effect

### Backwards Compatibility

- All existing hidden metaboxes settings are preserved
- Core metaboxes still shown even without discovery
- No database migration required
- Automatic discovery is passive and doesn't affect normal editing

### Bug Fixes

- **Fixed Classic Editor metabox hiding**: Changed hook from `admin_init` to `add_meta_boxes` and `do_meta_boxes` to ensure `remove_meta_box()` is called after metaboxes are registered. Previously, metaboxes were hidden in Gutenberg but not in Classic Editor due to incorrect hook timing.
- **Added JavaScript fallback for Classic Editor**: New `hide_metaboxes_js()` method runs in admin footer and uses `MutationObserver` to catch late-loading or dynamically added metaboxes that CSS and PHP hooks miss.
- **Fixed scan not detecting hidden metaboxes**: When running a manual scan with already-hidden metaboxes, the scan would fail to discover them because they were being removed before the discovery script could capture them. Added detection for `?security_tools_scan=1` parameter to bypass all metabox hiding during scans.

### Changed

- **Renamed "Elements" to "Metaboxes"**: The submenu, tab navigation, page headers, and all related constants have been renamed from "Elements" to "Metaboxes" for clarity. The page slug changed from `security-tools-elements` to `security-tools-metaboxes`.

### Reversibility

- Discovered metaboxes cleared when plugin is reset
- Manual scan replaces discovered list (cleans up removed plugins)
- Hidden metaboxes become visible immediately when unchecked

---

## [2.4] - 2024

### New Feature: Hide Admin Bar Items by CSS ID

Version 2.4 introduces a new sub-feature to the "Hidden Admin Bar Items" feature that allows administrators to hide admin bar items using their CSS element ID. This is useful for items that cannot be hidden through conventional WP_Admin_Bar methods.

### Added

- **CSS-Based Admin Bar Item Hiding**
  - New section "Hide by CSS ID" on the Admin Bar settings page
  - Tokenfield/chip-style input for managing multiple CSS IDs
  - Add CSS IDs with an input field and "Add ID" button
  - Remove IDs by clicking the × button on each token
  - Press Enter in the input field to quickly add IDs
  - Duplicate prevention with visual feedback
  - Items are hidden using CSS `display: none !important`
  - Works on both admin dashboard and frontend (when admin bar is visible)

- **New Constants in `Security_Tools_Utils`**
  - `OPTION_HIDDEN_ADMIN_BAR_CSS` - Array storing CSS IDs to hide
  - `OPTION_ADMIN_BAR_CSS_LAST_CHANGE` - Change tracking for admin notices

- **New Sanitization Method**
  - `sanitize_hidden_admin_bar_css()` - Validates and sanitizes CSS IDs
  - `sanitize_css_id()` - Helper to clean individual CSS IDs
  - Strips leading `#` character (allows users to paste IDs from browser inspector)
  - Validates CSS identifier format (letters, numbers, hyphens, underscores)
  - Prevents XSS via malicious CSS selectors

- **New CSS Styles**
  - `.security-tools-tokenfield` - Container for tokenfield component
  - `.security-tools-token-container` - Holds displayed tokens
  - `.security-tools-token` - Individual token/chip styling (Material Design inspired)
  - `.security-tools-tokenfield-input-row` - Input and button layout
  - Token remove button with hover effects

- **New JavaScript Functions**
  - `initializeAdminBarCssTokenfield()` - Initializes tokenfield functionality
  - Token add/remove handlers
  - Duplicate detection with visual feedback
  - Client-side CSS ID sanitization

### Technical Details

#### How It Works

The feature injects CSS rules to hide admin bar elements by their ID:

```css
#wpadminbar li#wp-admin-bar-example { display: none !important; }
```

This approach is useful when:
- Plugins add admin bar items late in the WordPress lifecycle
- Items are added via JavaScript after page load
- The standard WP_Admin_Bar removal methods don't work

#### Input Validation

| Input | Result |
|-------|--------|
| `wp-admin-bar-example` | ✓ Accepted |
| `#wp-admin-bar-example` | ✓ Accepted (# stripped) |
| `my_custom_item` | ✓ Accepted |
| `invalid id with spaces` | Sanitized to `invalididwithspaces` |
| `<script>alert(1)</script>` | Rejected (invalid characters) |

#### New Database Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `security_tools_hidden_admin_bar_css` | array | `[]` | CSS IDs to hide |
| `security_tools_admin_bar_css_last_changes` | boolean | - | Change tracking |

### Files Modified

1. `includes/class-utils.php`
   - Added `OPTION_HIDDEN_ADMIN_BAR_CSS` constant
   - Added `OPTION_ADMIN_BAR_CSS_LAST_CHANGE` constant
   - Updated `get_all_option_names()` array
   - Updated `get_all_change_tracking_options()` array

2. `admin/class-admin-settings.php`
   - Added registration for `OPTION_HIDDEN_ADMIN_BAR_CSS` (array)
   - Updated `register_admin_bar_settings()` method

3. `admin/class-admin-sanitization.php`
   - Added `sanitize_hidden_admin_bar_css()` method
   - Added `sanitize_css_id()` helper method

4. `admin/class-admin-page.php`
   - Updated `render_admin_bar()` to include CSS section
   - Added `render_admin_bar_css_section()` method

5. `features/class-feature-admin-bar.php`
   - Added `setup_css_hiding()` method
   - Added `add_css_id_hide_styles()` method
   - Updated constructor to register CSS hiding hooks

6. `assets/css/admin-styles.css`
   - Added tokenfield/chip component styles
   - Material Design-inspired token appearance

7. `assets/js/admin-scripts.js`
   - Added `initializeAdminBarCssTokenfield()` function
   - Token management (add, remove, duplicate detection)
   - Keyboard support (Enter key)

### Backwards Compatibility

- All existing settings are preserved
- No database migration required
- Feature is independent of the main admin bar hiding functionality
- Existing hidden admin bar items continue to work as before

### Reversibility

Admin bar items hidden via CSS ID become visible again when:
- The ID token is removed from settings
- Settings are reset via "Reset All Settings"
- The plugin is deactivated

### Bug Fixes

- **Fixed: Token deletion not persisting** - The PHP-generated hidden inputs for existing tokens were missing the `data-token-value` attribute that the JavaScript uses to find and remove them. When clicking the remove button on existing tokens, the hidden input was not being removed from the DOM, so the form would still submit the "deleted" values. Added the missing `data-token-value` attribute to PHP-rendered hidden inputs.

---

## [2.3.1] - 2024

### Bug Fix: Hide Login Page - Canonical Redirect Exposure

Version 2.3.1 fixes a security issue where the custom login page could be discovered through WordPress canonical URL redirects.

### Fixed

- **Login Page Exposure via `/login` and `/admin` URLs** (Security Fix)
  - **Issue**: When the Hide Login Page feature was enabled, visiting `/login` or `/admin` would allow non-authenticated users to access the login page instead of showing a 404 error. This partially defeated the purpose of hiding the login page.
  - **Root causes** (two separate mechanisms):
    1. **`/login` path**: WordPress has a built-in rewrite rule (since version 3.0) that directly serves `wp-login.php` when visiting `/login`. This bypasses the normal WordPress request flow, so our `init` hook never fires.
    2. **`/admin` path**: WordPress redirects `/admin` to `/wp-admin`, which then redirects non-logged users to `wp-login.php`. Our `filter_wp_redirect()` method was converting this redirect to the custom login URL instead of blocking it.
  - **Fixes applied**:
    1. Added `login_init` hook to block access at `wp-login.php` level - this catches `/login` access because it fires inside `wp-login.php` itself
    2. Updated `filter_wp_redirect()` to trigger 404 for non-authenticated users instead of redirecting to custom login URL
    3. Added multiple detection methods in `is_login_page_request()` including `$pagenow` and `PHP_SELF` checks
  - **New behavior**: Both `/login` and `/admin` now return 404 for non-authenticated users. Authenticated users retain normal redirect behavior for logout and other flows.

### Technical Details

#### URLs Now Properly Blocked (for non-authenticated users)

| URL | Previous Behavior | New Behavior |
|-----|-------------------|---------------|
| `/wp-login.php` | 404 ✓ | 404 ✓ |
| `/wp-admin` | 404 ✓ | 404 ✓ |
| `/login` | Redirect to custom login ✗ | 404 ✓ |
| `/admin` | Redirect to custom login ✗ | 404 ✓ |

#### Methods Modified

**NEW: `block_login_init_access()`** in `class-feature-hide-login.php`:
- Hooks into `login_init` action which fires at the beginning of `wp-login.php`
- This is the critical fix for `/login` - it catches access regardless of how `wp-login.php` was loaded
- Allows access only if user is logged in OR accessed via custom login slug
- Uses `DOING_SECURITY_TOOLS_LOGIN` constant to identify legitimate custom slug access

**`is_login_page_request()`** in `class-feature-hide-login.php`:
- Added `$pagenow` global variable check
- Added `PHP_SELF` server variable check
- Added detection for `/login` path (WordPress rewrite rule)
- Now uses 5 different detection methods for comprehensive coverage

**`filter_wp_redirect()`** in `class-feature-hide-login.php`:
- Added authentication check before processing redirects to `wp-login.php`
- Non-authenticated redirect attempts now trigger `trigger_404()` method
- Authenticated users retain the redirect-to-custom-URL behavior for logout and other flows

### Files Modified

1. `features/class-feature-hide-login.php`
   - Added new `block_login_init_access()` method hooked to `login_init` action
   - Added `login_init` hook registration in constructor
   - Updated `is_login_page_request()` method with multiple detection methods
   - Updated `filter_wp_redirect()` method with authentication-aware redirect handling
   - Added detailed PHPDoc comments explaining all security fixes
   - Added `@since 2.3.1` version tags to modified methods

### Security Considerations

This fix ensures that all common WordPress login URL patterns are properly blocked:
- Direct access: `/wp-login.php`
- Admin redirect: `/wp-admin`
- Canonical redirects: `/login`, `/admin`

The custom login URL remains the only way for non-authenticated users to access the login page.

### Backwards Compatibility

- No database changes required
- No configuration changes required
- Existing custom login slugs continue to work
- Logged-in user flows (logout, password change) unaffected

---

## [2.3] - 2024

### New Feature: Custom Login Logo

Version 2.3 introduces a new branding feature that allows administrators to replace the WordPress logo on the login page with a custom logo.

### Added

- **Custom Login Logo Feature**
  - New section in Branding settings page for login logo customization
  - Select any image from the WordPress Media Library as login logo
  - Logo automatically sized to max 300px width with preserved aspect ratio
  - Logo is centered on the login page
  - Custom URL option for the logo link (defaults to site homepage)
  - Logo title attribute shows site name instead of "Powered by WordPress"

- **New Constants in `Security_Tools_Utils`**
  - `OPTION_LOGIN_LOGO_ID` - Integer storing the attachment ID for the logo
  - `OPTION_LOGIN_LOGO_URL` - String storing the custom logo link URL
  - `OPTION_LOGIN_LOGO_ID_LAST_CHANGE` - Change tracking for notices
  - `OPTION_LOGIN_LOGO_URL_LAST_CHANGE` - Change tracking for notices

- **New Admin UI Components**
  - "Custom Login Logo" section in Branding page
  - Media Library uploader button with image preview
  - Remove logo button to clear selection
  - Logo Link URL text input field
  - Recommended image size guidance (320×120 pixels)

- **New Sanitization Methods**
  - `sanitize_login_logo_id()` - Validates attachment ID and ensures it's a valid image
  - `sanitize_login_logo_url()` - Validates and sanitizes the custom URL

- **New CSS Styles**
  - `.security-tools-media-upload` - Container for media uploader
  - `.security-tools-logo-preview` - Preview image container with border
  - `.security-tools-media-buttons` - Button container with flex layout

- **New JavaScript Functions**
  - `initializeLoginLogoUploader()` - Initializes WordPress Media Library integration
  - `updateLogoPreview()` - Updates the preview image when selection changes

### Changed

- **Branding Page Section Header**
  - Changed from "Custom Legend" to "Custom branding" for the page section header
  - Description now encompasses all branding features

- **Branding Feature Class**
  - `Security_Tools_Feature_Branding` now handles login logo display
  - Added `login_head` hook for custom CSS injection
  - Added `login_headerurl` filter for custom logo URL
  - Added `login_headertext` filter for custom logo title

- **Admin Asset Loading**
  - Media Library (`wp_enqueue_media()`) now loaded on Branding page
  - Localized strings added for Media Library modal

### Technical Details

#### WordPress Filters Used

| Filter | Purpose |
|--------|----------|
| `login_head` | Injects CSS for custom logo background image |
| `login_headerurl` | Changes the logo link URL |
| `login_headertext` | Changes the logo title attribute |

#### Logo Display CSS

The custom logo CSS is injected into the login page head and:
- Uses `background-image` to replace the WordPress logo
- Sets `background-size: contain` for proper scaling
- Calculates height based on original image aspect ratio
- Maximum width constrained to 300px
- Logo is centered using `margin: 0 auto`

#### New Database Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `security_tools_login_logo_id` | integer | 0 | Attachment ID of the logo image |
| `security_tools_login_logo_url` | string | '' | Custom URL for logo link |
| `security_tools_login_logo_id_last_change` | boolean | - | Change tracking |
| `security_tools_login_logo_url_last_change` | boolean | - | Change tracking |

### Files Modified

1. `includes/class-utils.php`
   - Added 4 new constants for login logo options
   - Updated `get_all_option_names()` to include new options
   - Updated `get_all_change_tracking_options()` to include new tracking options

2. `includes/class-admin.php`
   - Updated `enqueue_assets()` to load Media Library on Branding page
   - Added localized strings for Media Library modal

3. `admin/class-admin-settings.php`
   - Added registration for `OPTION_LOGIN_LOGO_ID` (integer)
   - Added registration for `OPTION_LOGIN_LOGO_URL` (string)

4. `admin/class-admin-sanitization.php`
   - Added `sanitize_login_logo_id()` method with image validation
   - Added `sanitize_login_logo_url()` method with URL validation

5. `admin/class-admin-page.php`
   - Updated `render_branding()` to include new section header
   - Updated `render_legend_section()` to remove duplicate header
   - Added `render_login_logo_section()` method with media uploader

6. `features/class-feature-branding.php`
   - Added `output_custom_login_logo_css()` method
   - Added `modify_login_header_url()` method
   - Added `modify_login_header_text()` method
   - Added `get_login_logo_url_from_id()` helper method

7. `assets/css/admin-styles.css`
   - Added media uploader styles
   - Added logo preview container styles
   - Added media buttons layout styles

8. `assets/js/admin-scripts.js`
   - Added `initializeLoginLogoUploader()` function
   - Added `updateLogoPreview()` function
   - Added WordPress Media Library integration

9. `README.md`
   - Updated version to 2.3
   - Documented Custom Login Logo feature
   - Added configuration instructions
   - Updated feature tables

10. `CHANGELOG.md`
    - Added version 2.3 release notes

### Bug Fixes

- **Division by zero error**: Fixed fatal error when displaying custom login logo if image metadata was missing or incomplete. Now includes multiple fallback methods to retrieve image dimensions.
- **SVG image support**: Fixed SVG images not being recognized as valid images. WordPress's `wp_attachment_is_image()` doesn't recognize SVG files, so added custom validation that checks:
  - MIME type for `image/svg+xml` or `image/svg`
  - File extension as fallback (`.svg`)
  - Updated JavaScript Media Library filter to explicitly include SVG files
  - SVG logos now properly save, display in preview, and render on the login page.
- **SVG dimensions**: Fixed SVG logos rendering at 1x1 pixel size. WordPress's `wp_get_attachment_image_src()` returns 1x1 for SVG files, so SVG images now always use flexible sizing (`width: 300px; height: auto; min-height: 80px`) regardless of any reported dimensions.
- **SVG preview**: Fixed SVG images not displaying in the settings page preview. SVG images require a defined width (not just max-width) to render properly.
- **Logo link target**: Login logo link now opens in a new tab (`target="_blank"`) with proper security attributes (`rel="noopener noreferrer"`).
- **SVG aspect ratio**: When image dimensions cannot be determined (common with SVG files), the logo now uses `height: auto` with a minimum height of 80px instead of a fixed height, preventing distortion of vector graphics.
- **Logo preview persistence**: Ensured logo ID is properly cast to integer when retrieving from database to prevent display issues.

### Backwards Compatibility

- All existing settings are preserved
- No database migration required
- Feature is disabled by default (no logo selected)
- Existing branding behavior unchanged

---

## [2.2] - 2024

### New Feature: Hide Login Page

Version 2.2 introduces a new security feature that allows administrators to hide the default WordPress login page and use a custom login URL instead.

### Added

- **Hide Login Page Feature**
  - New feature in System Controls to change the WordPress login URL
  - Set a custom slug (e.g., `my-secret-login`) for your login page
  - Standard `wp-login.php` and `/wp-admin` (for non-logged users) return 404 errors
  - All login functionality preserved (login, logout, password reset, registration)
  - Fully reversible - disable the feature and standard login returns immediately

- **New Feature File**
  - `class-feature-hide-login.php` - Implements the custom login URL functionality
  - Intercepts requests via `init` hook at priority 1
  - Filters all login-related URLs (`login_url`, `logout_url`, `lostpassword_url`, `register_url`)
  - Filters `site_url` and `network_site_url` for login paths
  - Filters `wp_redirect` to catch any remaining login redirects

- **New Constants in `Security_Tools_Utils`**
  - `OPTION_HIDE_LOGIN_ENABLED` - Boolean to enable/disable the feature
  - `OPTION_HIDE_LOGIN_SLUG` - String containing the custom login slug
  - `OPTION_HIDE_LOGIN_ENABLED_LAST_CHANGE` - Change tracking for notices
  - `OPTION_HIDE_LOGIN_SLUG_LAST_CHANGE` - Change tracking for notices

- **New Admin UI Components**
  - "Login Security" section in System Controls page
  - Toggle switch to enable/disable the feature
  - Text input for custom login slug
  - Dynamic URL preview showing current custom login URL
  - Warning messages when feature is misconfigured

- **New Sanitization Methods**
  - `sanitize_hide_login_enabled()` - Boolean sanitizer for the toggle
  - `sanitize_hide_login_slug()` - Validates slug against reserved words and existing content

### Technical Details

#### Request Handling

The feature intercepts requests at the earliest possible point (`init` with priority 1) and:

1. **Custom Slug Requests**: Loads `wp-login.php` to render the login form
2. **wp-login.php Requests**: Returns 404 for non-authenticated users
3. **wp-admin Requests**: Returns 404 for non-authenticated users (except allowed endpoints)

#### Allowed Endpoints (Not Blocked)

- `admin-ajax.php` - Frontend AJAX requests
- `admin-post.php` - Frontend form submissions
- REST API endpoints
- WP-CLI requests
- Cron requests
- All requests from logged-in users

#### Reserved Slugs

The following slugs are blocked to prevent conflicts:

- `admin`, `login`, `wp-admin`, `wp-login`, `wp-login.php`
- `dashboard`, `wp-content`, `wp-includes`, `wp-json`
- `xmlrpc.php`, `feed`, `rss`, `rss2`, `atom`
- `sitemap`, `robots.txt`, `favicon.ico`

#### URL Filtering

The feature filters these WordPress functions:

| Filter | Purpose |
|--------|----------|
| `login_url` | Main login URL |
| `logout_url` | Logout URL with nonce |
| `lostpassword_url` | Password reset URL |
| `register_url` | Registration URL |
| `site_url` | Catches `site_url('wp-login.php')` calls |
| `network_site_url` | Multisite compatibility |
| `wp_redirect` | Catches redirects to wp-login.php |

### New Database Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `security_tools_hide_login_enabled` | boolean | false | Feature enabled state |
| `security_tools_hide_login_slug` | string | '' | Custom login slug |
| `security_tools_hide_login_enabled_last_change` | boolean | - | Change tracking |
| `security_tools_hide_login_slug_last_change` | boolean | - | Change tracking |

### Files Modified

1. `includes/class-utils.php`
   - Added 4 new constants for Hide Login options
   - Updated `get_all_option_names()` to include new options
   - Updated `get_all_change_tracking_options()` to include new tracking options

2. `includes/class-loader.php`
   - Added initialization of `Security_Tools_Feature_Hide_Login` class

3. `security-tools.php`
   - Added require_once for `class-feature-hide-login.php`

4. `admin/class-admin-settings.php`
   - Added registration for `OPTION_HIDE_LOGIN_ENABLED` (boolean)
   - Added registration for `OPTION_HIDE_LOGIN_SLUG` (string)

5. `admin/class-admin-sanitization.php`
   - Added `sanitize_hide_login_enabled()` method
   - Added `sanitize_hide_login_slug()` method with validation

6. `admin/class-admin-page.php`
   - Added `render_hide_login_section()` method
   - Updated `render_system_controls_content()` to include Login Security section

### Files Added

1. `features/class-feature-hide-login.php`
   - Complete implementation of the Hide Login Page feature
   - ~450 lines with full documentation

### Backwards Compatibility

- All existing settings are preserved
- No database migration required
- Feature is disabled by default
- Existing login behavior unchanged until feature is enabled

### Security Considerations

- Only affects non-authenticated users
- Logged-in users can access wp-admin normally
- AJAX, REST API, and cron endpoints remain functional
- Password reset and registration flows work through custom URL
- 404 responses use theme's 404 template for consistency

### Recovery Options

If the custom login URL is forgotten:

1. **Via FTP**: Set `security_tools_hide_login_enabled` to `0` in database
2. **Via phpMyAdmin**: Update option in `wp_options` table
3. **Via WP-CLI**: `wp option update security_tools_hide_login_enabled 0`

---

## [2.1] - 2024

### Usability Improvements Release

Version 2.1 focuses on improving usability with better navigation, contextual feedback, and a new autohide feature.

### Added

- **Specific Save Notices**
  - Save confirmation notices now specify which section was saved
  - Example: "Hidden Plugins settings saved successfully." instead of generic "Settings saved successfully."
  - Implemented via new `get_page_title()` helper method in `Security_Tools_Utils`

- **Menu Position First**
  - Security Tools menu now appears as the first item in the admin sidebar
  - Changed menu position from 76 (after Tools) to 1.1 (first position)

- **Autohide Menu Feature**
  - New toggle card in General settings page
  - When enabled, hides the Security Tools menu from the admin sidebar
  - Settings remain accessible via direct URL (`/wp-admin/admin.php?page=security-tools`)
  - Settings also accessible via the new internal tab navigation
  - Separate from existing Self-Hiding feature (which hides from other admins)
  - Shows warning with bookmark URL when enabled

- **Internal Tab Navigation**
  - Horizontal navigation bar on all settings pages
  - Positioned below the header and above the section header
  - Allows navigation between subpages without sidebar menu
  - Essential when Autohide Menu is enabled
  - Responsive design - switches to vertical on mobile

- **New Constants in `Security_Tools_Utils`**
  - `OPTION_AUTOHIDE_MENU` - Stores autohide setting
  - `OPTION_AUTOHIDE_MENU_LAST_CHANGE` - Change tracking
  - `SETTINGS_GROUP_GENERAL` - Settings group for General page

- **New Helper Methods in `Security_Tools_Utils`**
  - `get_page_title()` - Returns display title for a page slug
  - `get_navigation_items()` - Returns array of all nav items for tab navigation

### Changed

- **Menu Position**
  - Changed from position 76 (after Tools) to position 1.1 (first item)
  - Using string '1.1' to ensure proper ordering before Dashboard

- **General Settings Page**
  - Now includes a form with the Autohide Menu toggle
  - Added Save Settings button
  - Reset section moved below the new settings form

- **Admin Notices**
  - `display_settings_updated_notice()` now builds contextual messages
  - Uses `get_page_title()` to determine section name
  - Includes translator comment for i18n compatibility

### Technical Details

#### New Settings Group

| Page | Settings Group |
|------|---------------|
| General | `security_tools_general_group` |

#### New Database Options

| Option | Type | Purpose |
|--------|------|----------|
| `security_tools_autohide_menu` | boolean | Stores autohide enabled state |
| `security_tools_autohide_menu_last_change` | boolean | Change tracking for notices |

#### CSS Classes Added

| Class | Purpose |
|-------|----------|
| `.security-tools-tab-nav` | Tab navigation container |
| `.security-tools-tab-list` | Navigation list |
| `.security-tools-tab-item` | Individual tab item |
| `.security-tools-tab-link` | Tab link styling |
| `.security-tools-tab-link.active` | Active tab indicator |
| `.security-tools-single-card` | Single-card grid layout |

### Files Modified

1. `includes/class-utils.php`
   - Added `OPTION_AUTOHIDE_MENU` constant
   - Added `OPTION_AUTOHIDE_MENU_LAST_CHANGE` constant
   - Added `SETTINGS_GROUP_GENERAL` constant
   - Updated `get_all_option_names()` to include autohide option
   - Updated `get_all_change_tracking_options()` to include autohide tracking
   - Updated `get_settings_group_for_page()` to include General mapping
   - Added `get_page_title()` method
   - Added `get_navigation_items()` method

2. `includes/class-admin.php`
   - Changed menu position from 76 to '1.1'
   - Added `maybe_hide_admin_menu()` method
   - Registered new admin_menu hook for autohide at priority 9999

3. `admin/class-admin-settings.php`
   - Added `register_general_settings()` method
   - Updated `register_settings()` to call general settings registration
   - Updated `get_settings_group_for_page()` to include General mapping

4. `admin/class-admin-sanitization.php`
   - Added `sanitize_autohide_menu()` method

5. `admin/class-admin-notices.php`
   - Updated `display_settings_updated_notice()` for contextual messages
   - Now uses page title to build specific notice text

6. `admin/class-admin-page.php`
   - Added `render_tab_navigation()` method
   - Added `render_autohide_section()` method
   - Updated `render_general()` to include form and autohide toggle
   - Added tab navigation call to all 9 render methods

7. `assets/css/admin-styles.css`
   - Added tab navigation styles
   - Added responsive styles for mobile
   - Added `.security-tools-single-card` modifier class

8. `README.md`
   - Updated version to 2.1
   - Documented all new features
   - Updated menu structure diagram
   - Added General page feature table

9. `CHANGELOG.md`
   - Added version 2.1 release notes

### Fixed

- **General Settings Page Layout**
  - Improved Autohide Menu section to use full-width card style (matching Reset All Settings)
  - Added proper spacing (margin-bottom) below Save Settings button
  - Removed unused `.security-tools-single-card` CSS class
  - General page now has consistent visual hierarchy

- **Autohide Menu Access Bug**
  - Fixed issue where enabling Autohide Menu would prevent access to settings pages
  - Changed implementation from `remove_menu_page()` to CSS-based hiding
  - Using `remove_menu_page()` also removed capability checks, making pages inaccessible
  - New CSS approach hides menu visually while keeping pages fully accessible
  - Added `output_autohide_css()` method to inject hiding styles in admin head

- **Save Notice Persistence Bug**
  - Fixed issue where save notices would persist when navigating between sections
  - Fixed issue where notices would reappear when returning to a previously saved page
  - The `display_settings_updated_notice()` method now cleans up tracking options after displaying
  - Added `cleanup_tracking_options_for_page()` helper method for consistent cleanup
  - The fallback `display_save_notice()` method now only checks tracking options relevant to the current page
  - Added `get_tracking_options_for_page()` method to map pages to their tracking options
  - Notices now only appear once on the page where settings were actually saved
  - Fallback notices now also show contextual messages instead of generic text

### Backwards Compatibility

- All existing settings are preserved
- No database migration required
- Autohide is disabled by default (existing behavior unchanged)
- Tab navigation is visible on all pages but doesn't affect existing functionality

### Upgrade Notes

1. Replace the entire plugin folder contents
2. All settings are automatically preserved
3. The plugin menu will now appear first in the sidebar
4. Clear any caching plugins after upgrade

---

## [2.0] - 2024

### Major Release: Settings Interface Reorganization

Version 2.0 introduces a complete reorganization of the plugin's settings interface, moving from a single long page to a top-level menu with 9 separate subpages.

### Added

- **Top-Level Admin Menu**
  - New dedicated "Security Tools" menu in WordPress admin sidebar
  - Positioned after the core "Tools" menu (position 76)
  - Uses `dashicons-shield` icon for easy identification

- **9 Separate Subpages**
  - General: Reset All Settings functionality
  - Branding: Custom Legend text configuration
  - System Controls: All 8 toggle switches
  - Admins: Hidden Administrators table
  - Plugins: Hidden Plugins table
  - Themes: Hidden Themes table
  - Widgets: Hidden Dashboard Widgets table
  - Admin Bar: Hidden Admin Bar Items table
  - Elements: Hidden Post/Page Elements table

- **Independent Settings Groups**
  - Each subpage has its own WordPress Settings API group
  - Settings save independently per page
  - Prevents accidental changes to unrelated settings

- **New Constants in `Security_Tools_Utils`**
  - 8 new settings group constants (`SETTINGS_GROUP_BRANDING`, etc.)
  - 9 new page slug constants (`PAGE_GENERAL`, `PAGE_BRANDING`, etc.)
  - `MENU_SLUG` constant for top-level menu

- **New Helper Methods**
  - `get_all_page_slugs()` - Returns array of all subpage slugs
  - `get_current_page_slug()` - Returns current page slug or false
  - `get_settings_group_for_page()` - Maps page slugs to settings groups

### Changed

- **Menu Location**
  - Moved from Tools submenu to dedicated top-level menu
  - Plugin now more prominent and easier to find

- **Settings Page Structure**
  - Single page split into 9 separate subpages
  - Each subpage has its own form and Save button
  - Reset button moved to dedicated General page

- **`is_settings_page()` Method**
  - Now checks all 9 subpages instead of single page
  - Uses screen ID patterns: `toplevel_page_*` and `security-tools_page_*`

- **Reset Functionality**
  - Now redirects to General page after reset
  - Shows reset confirmation notice on General page

- **Admin Notices**
  - Now detect WordPress Settings API `settings-updated` parameter
  - Work correctly across all 9 subpages

### Fixed

- **Empty Admin Bar Items Table**
  - Admin bar refresh was triggering on all Security Tools pages instead of just the Admin Bar subpage
  - Added `is_admin_bar_page()` method to check for specific subpage
  - `refresh_for_settings()` now only initializes admin bar on the Admin Bar subpage
  - Admin bar items now properly detected and displayed in the table

- **Save/Reset Notices Not Showing When Admin Notices Disabled**
  - The "Hide Admin Notices" feature was using outdated screen ID check (`tools_page_security-tools`)
  - Updated `hide_notices_css()` to use `is_settings_page()` method which correctly detects all 9 subpages
  - Security Tools notices (save confirmations, reset alerts) now display properly even when admin notices are disabled

### Technical Details

#### New Page Slugs

| Page | Slug |
|------|------|
| General | `security-tools` |
| Branding | `security-tools-branding` |
| System Controls | `security-tools-system` |
| Admins | `security-tools-admins` |
| Plugins | `security-tools-plugins` |
| Themes | `security-tools-themes` |
| Widgets | `security-tools-widgets` |
| Admin Bar | `security-tools-admin-bar` |
| Elements | `security-tools-elements` |

#### New Settings Groups

| Subpage | Settings Group |
|---------|---------------|
| Branding | `security_tools_branding_group` |
| System Controls | `security_tools_system_group` |
| Admins | `security_tools_admins_group` |
| Plugins | `security_tools_plugins_group` |
| Themes | `security_tools_themes_group` |
| Widgets | `security_tools_widgets_group` |
| Admin Bar | `security_tools_admin_bar_group` |
| Elements | `security_tools_elements_group` |

### Files Modified

1. `includes/class-utils.php`
   - Added 8 settings group constants
   - Added 9 page slug constants
   - Added `MENU_SLUG` constant
   - Added `get_all_page_slugs()` method
   - Updated `is_settings_page()` for multiple pages
   - Added `get_current_page_slug()` method
   - Added `get_settings_group_for_page()` method

2. `includes/class-admin.php`
   - Changed from `add_submenu_page()` to `add_menu_page()`
   - Added `get_subpages()` method with all 9 subpage configurations
   - Updated `enqueue_assets()` for multiple page hooks
   - Updated `handle_reset()` with redirect to General page

3. `admin/class-admin-page.php`
   - Added 9 public render methods (`render_general()`, `render_branding()`, etc.)
   - Refactored internal methods to avoid naming conflicts
   - Added `render_save_button()` method
   - Added `render_reset_section()` method
   - Marked legacy `render()` method as deprecated

4. `admin/class-admin-settings.php`
   - Split `register_settings()` into 8 separate methods
   - Each settings group registered independently
   - Added `get_settings_group_for_page()` static method

5. `admin/class-admin-notices.php`
   - Added `display_settings_updated_notice()` method
   - Updated to detect Settings API success parameter
   - Works across all subpages

6. `features/class-feature-notices.php`
   - Fixed: Updated `hide_notices_css()` to use `is_settings_page()` instead of hardcoded screen ID
   - Security Tools notices now display properly when admin notices are disabled

7. `features/class-feature-admin-bar.php`
   - Fixed: Added `is_admin_bar_page()` method to detect Admin Bar subpage specifically
   - Fixed: `refresh_for_settings()` now only triggers on Admin Bar subpage
   - Admin bar items now properly detected and displayed

8. `README.md`
   - Complete rewrite for version 2.0
   - New menu structure documentation
   - Updated technical architecture section
   - Added upgrade notes

9. `CHANGELOG.md`
   - Added version 2.0 release notes

### Backwards Compatibility

- All existing settings are preserved
- Legacy `SETTINGS_GROUP` constant kept for reference
- Legacy `render()` method redirects to `render_general()`
- No database migration required

### Upgrade Notes

1. Replace the entire plugin folder contents
2. All settings are automatically preserved
3. The plugin will now appear as a top-level menu
4. Clear any caching plugins after upgrade

---

## [1.4] - 2024

### Bug Fixes

- **Fixed: Hidden widgets becoming unhidden after saving unrelated settings** (Issue #6)
  - Some plugins (e.g., Google Site Kit) register dashboard widgets late or under specific conditions
  - These widgets weren't detectable when enumerating available widgets from the settings page
  - Previously, saving any setting would silently remove these "undetectable" widgets from the hidden list
  - Three-part fix implemented:
    1. Settings page now displays undetectable hidden widgets so users can see and manage them
    2. A hidden form field tracks which widgets were actually rendered in the form
    3. Sanitization uses the rendered list to distinguish "user unchecked" from "widget not shown"
  - Undetectable widgets display their raw widget ID as the title (see README.md for details)
  - Users can now properly hide and unhide all widgets, including late-registering ones

### Technical Details

- Modified `render_widgets_table()` in `class-admin-page.php`
  - Now merges undetectable hidden widgets into the display table
  - Added new helper method `merge_undetected_hidden_widgets()`
  - Displays raw widget ID for undetectable widgets (original title not available)
  - Outputs hidden field `security_tools_rendered_widgets` with all rendered widget IDs
- Modified `sanitize_hidden_widgets()` in `class-admin-sanitization.php`
  - Now reads from `security_tools_rendered_widgets` hidden field
  - Replaced `get_available_widget_ids()` with `get_rendered_widget_ids()`
  - Logic: Widgets in POST → include; Previously hidden but not rendered → preserve; Rendered but not in POST → exclude (user unchecked)

### Files Modified

1. `admin/class-admin-sanitization.php` - Bug fix (use rendered widgets list for accurate detection)
2. `admin/class-admin-page.php` - Bug fix (display undetectable widgets + track rendered widgets)

---

## [1.3] - 2024

### Security & Performance Audit Fixes

This release addresses findings from a third-party security and performance audit.

### Fixed

- **Performance: Removed `debug_backtrace()` call** (Issue #1 - High Severity)
  - Replaced expensive `debug_backtrace()` in `class-feature-admin-bar.php` with a simple boolean flag property
  - The `is_collecting_data()` method now uses `$this->is_collecting` flag instead of stack introspection
  - Eliminates unnecessary CPU/memory overhead on admin page loads when hidden admin bar items are configured

- **Stability: Complete transient object structure** (Issue #3 - Medium Severity)
  - Updated `class-feature-updates.php` to return complete transient objects with all expected properties
  - `disable_theme_plugin_check()` now includes: `last_checked`, `checked`, `response`, `translations`, `no_update`
  - `disable_core_check()` now includes: `last_checked`, `version_checked`, `updates`, `translations`
  - Prevents PHP warnings/notices from WordPress core and other plugins that expect these properties

- **Stability: Defensive global variable checks** (Issue #5 - Low Severity)
  - Added strict type checking in `class-feature-hide-widgets.php` before accessing `$wp_meta_boxes` global
  - Both `remove_widgets_by_id()` and `get_available_widgets()` now verify the global exists and is an array
  - Prevents potential PHP errors if another plugin modifies the expected array structure

### Improved

- **Code Quality: Consolidated inline CSS** (Issue #2 - Medium Severity, Partial)
  - Merged `hide_add_button()` CSS into `hide_management_ui()` in `class-feature-plugin-controls.php`
  - Both methods now use `wp_add_inline_style()` for consistent, CSP-friendly styling
  - Similar consolidation applied to `class-feature-theme-controls.php`
  - Standardized approach to inline CSS injection across multiple feature files

### Technical Notes

- All changes are backwards compatible
- No database schema changes
- All existing settings are preserved during upgrade
- Functionality remains identical to version 1.2

### Files Modified

1. `features/class-feature-admin-bar.php` - Performance fix (debug_backtrace removal)
2. `features/class-feature-updates.php` - Stability fix (complete transient structure)
3. `features/class-feature-hide-widgets.php` - Stability fix (defensive checks)
4. `features/class-feature-plugin-controls.php` - Code quality (inline CSS consolidation)
5. `features/class-feature-theme-controls.php` - Code quality (inline CSS consolidation)

---

## [1.2] - 2024

### Added
- Modular file structure with separate files for each feature
- External CSS file (`/assets/css/admin-styles.css`)
- External JavaScript file (`/assets/js/admin-scripts.js`)
- Centralized utility class (`Security_Tools_Utils`) with all option constants
- Separate admin classes for settings, sanitization, notices, and page rendering
- `CHANGELOG.md` file for version history
- Constants for plugin version, path, URL, and basename

### Changed
- **Complete architectural restructure** from single 4,000+ line file to modular codebase
- All features now have their own dedicated class files in `/features/`
- Admin functionality split into dedicated classes in `/admin/`
- Core functionality organized in `/includes/`
- CSS variables renamed with `st-` prefix to avoid conflicts
- Initialization now uses `plugins_loaded` hook for proper timing
- All classes use singleton pattern where appropriate

### Technical Improvements
- Each file under 400 lines for AI-friendly collaboration
- Conditional loading of admin-only files
- Centralized option names prevent typos
- Better separation of concerns
- Easier to maintain and extend
- Follows WordPress plugin handbook standards

### No Functional Changes
- All features work identically to version 1.1
- All settings are preserved during upgrade
- Database schema unchanged

---

## [1.1] - 2024

### Added
- Comprehensive file header with Table of Contents
- Section dividers for all major code areas
- PHPDoc comments for all functions
- BEGIN/END markers for code blocks
- Changelog section in file header

### Changed
- Code reorganized for better readability
- Improved code documentation

### No Functional Changes
- All features work identically to version 1.0

---

## [1.0] - 2024

### Initial Release

#### System Controls
- Disable WordPress updates (core, plugins, themes)
- Disable all WordPress email sending
- Disable admin email verification prompts
- Hide all admin notices
- Disable comments system completely
- Disable plugin management controls
- Disable theme management controls
- Disable frontend admin bar

#### Hiding Features
- Hide administrators from Users list
- Hide plugins from Plugins page
- Hide themes from Themes page
- Hide dashboard widgets
- Hide admin bar items
- Hide metaboxes from post/page editors

#### Branding
- Custom legend text for login page
- Custom footer text for dashboard

#### Integrations
- Wordfence 2FA sections hiding

#### Core
- Self-hiding from plugins list
- MU plugins tab hiding
- Complete admin settings interface
- Reset all settings functionality

---

## Upgrade Notes

### Upgrading from 1.x to 2.0

1. **Backup your site** before upgrading
2. Replace the entire plugin folder contents
3. All settings are automatically preserved
4. The plugin menu will move from Tools submenu to top-level
5. Clear any caching plugins after upgrade
6. No configuration changes required

### Upgrading from 1.2 to 1.3

1. **Backup your site** before upgrading
2. Replace the entire plugin folder contents
3. All settings are automatically preserved
4. Clear any caching plugins after upgrade
5. No configuration changes required

### Upgrading from 1.1 to 1.2

1. **Backup your site** before upgrading
2. Replace the entire plugin folder contents
3. If using as MU plugin, ensure loader file points to new structure
4. All settings are automatically preserved
5. Clear any caching plugins after upgrade

### File Structure (1.2+)

```
mu-plugins/
├── security-tools-loader.php
└── security-tools/
    ├── security-tools.php
    ├── includes/
    ├── features/
    ├── admin/
    └── assets/
```
