# Security Tools v2.5

A Must-Use WordPress Plugin for Security Management and Administrative Control.

## Overview

Security Tools is a comprehensive **Must-Use (MU)** WordPress plugin designed to provide advanced security management, administrative control, and interface customization capabilities. The plugin operates invisibly, hidden from other administrators while maintaining full functionality.

### Key Characteristics

- **Must-Use Plugin:** Installed in `wp-content/mu-plugins/`, automatically activated.
- **Self-Hiding:** Invisible to other administrators.
- **Modular Architecture:** Clean, maintainable file structure.
- **Non-Destructive:** All features are toggleable; nothing is permanently changed.

## Installation

### As Must-Use Plugin (Recommended)

1. Copy the entire `security-tools` folder to `wp-content/mu-plugins/`.
2. Create a new file named `security-tools-loader.php` inside `wp-content/mu-plugins/` with the following content:

```php
<?php
/**
 * Plugin Name: Security Tools Loader
 * Description: Loads the Security Tools MU plugin.
 */

if ( file_exists( __DIR__ . '/security-tools/security-tools.php' ) ) {
    require_once __DIR__ . '/security-tools/security-tools.php';
}
```

3. The plugin activates automatically.
4. Access via **Security Tools** in the WordPress admin sidebar.

### File Structure

```
wp-content/
└── mu-plugins/
    ├── security-tools-loader.php    # Loader file
    └── security-tools/
        ├── security-tools.php       # Main bootstrap
        ├── README.md
        ├── includes/
        ├── features/
        ├── admin/
        └── assets/
```

## Features Overview

### General

| Feature | Description |
| :--- | :--- |
| Autohide Menu | Hide the Security Tools menu from the sidebar (access via URL or tabs). |
| Reset All Settings | Clear all plugin settings and return to defaults. |

### Branding

| Feature | Description |
| :--- | :--- |
| Custom Legend | Text shown on login page and dashboard footer. |
| Custom Login Logo | Replace WordPress logo on login page. |
| Logo Link URL | Custom URL for the login logo link. |

### System Controls

| Feature | Description |
| :--- | :--- |
| Disable Updates | Stops all WordPress update checks and notifications. |
| Hide Admin Notices | Hides all dashboard notifications. |
| Disable Emails | Prevents WordPress from sending any emails. |
| Disable Email Verification | Removes admin email verification prompts. |
| Disable Plugin/Theme Controls | Prevents installation, activation, and editing. |
| Disable Comments | Completely removes comment functionality. |
| Disable Frontend Admin Bar | Hides admin bar on frontend for all users. |
| Hide Login Page | Change the login URL to a custom slug. |

### Hiding Features

Specific tables to hide items while keeping them functional.

| Subpage | Description |
| :--- | :--- |
| Admins | Hide administrator accounts from Users list (they retain full access). |
| Plugins/Themes | Hide items from Plugins/Themes pages. |
| Widgets | Hide dashboard widgets. |
| Admin Bar | Hide items from the admin bar. (v2.4: Hide by CSS ID) |
| Metaboxes | (v2.5: Improved) Hide metaboxes from post/page editing screens. Dynamic metabox discovery. |

## Menu Structure

```
Security Tools (top-level, dashicons-shield)
├── General         → Autohide Menu + Reset
├── Branding        → Legend + Login Logo
├── System Controls → Toggles + Hide Login Page
├── Admins          → Hidden Administrators
├── Plugins         → Hidden Plugins
├── Themes          → Hidden Themes
├── Widgets         → Hidden Widgets
├── Admin Bar       → Hidden Menu Items + Hide by CSS ID
└── Metaboxes       → Hidden Metaboxes
```

## Technical Architecture

All classes use the prefix `Security_Tools_` to avoid conflicts.

### Settings Groups

| Subpage | Settings Group |
| :--- | :--- |
| General | `security_tools_general_group` |
| Branding | `security_tools_branding_group` |
| System Controls | `security_tools_system_group` |
| Admins | `security_tools_admins_group` |
| Plugins/Themes | `security_tools_plugins_group` / `_themes_group` |

## File Descriptions

### Core Files (`/includes/`)

| File | Purpose |
| :--- | :--- |
| `class-loader.php` | Main loader - initializes all features. |
| `class-admin.php` | Admin initialization, menu registration, assets. |
| `class-utils.php` | Constants, option names, and helper functions. |

### Feature Files (`/features/`)

Each feature is self-contained with its own hooks.

| File | Feature |
| :--- | :--- |
| `class-feature-self-hiding.php` | Hides plugin from other admins. |
| `class-feature-branding.php` | Custom login/footer text + Login Logo. |
| `class-feature-updates.php` | Disable WordPress updates. |
| `class-feature-hide-login.php` | Hide Login Page. |
| ... | (See file structure for full list) |

## Database Options Reference

All options are defined as constants in `Security_Tools_Utils`.

```php
// Access options using constants
get_option( Security_Tools_Utils::OPTION_DISABLE_UPDATES );
get_option( Security_Tools_Utils::OPTION_HIDDEN_PLUGINS );
get_option( Security_Tools_Utils::OPTION_AUTOHIDE_MENU );
get_option( Security_Tools_Utils::OPTION_HIDE_LOGIN_ENABLED );
get_option( Security_Tools_Utils::OPTION_LOGIN_LOGO_ID );
get_option( Security_Tools_Utils::OPTION_LOGIN_LOGO_URL );
get_option( Security_Tools_Utils::OPTION_HIDDEN_ADMIN_BAR_CSS );
get_option( Security_Tools_Utils::OPTION_DISCOVERED_METABOXES ); // Added in v2.5
```

## Version History

Please refer to `changelog.md` included in the plugin package for a detailed history of changes.

## Operational Risks & Warnings

**Warning:** This plugin provides powerful tools that modify the core behavior of WordPress. Inappropriate use can lead to administrative lockout or security vulnerabilities.

### 1. Total Administrative Lockout

Combining **Hide Login Page** with **Disable Emails** creates a high risk of lockout. If you forget your custom login URL and cannot receive password reset emails, you will be unable to access your dashboard via standard means.

- **Recovery:** You must access your server via FTP/SFTP and rename or delete the `wp-content/mu-plugins/security-tools` directory to disable the plugin and restore default WordPress behavior.

### 2. Security Vulnerabilities

The **Disable Updates** feature stops all core, theme, and plugin updates. While useful for stability:

- Your site will **not receive security patches**.
- Leaving this enabled indefinitely significantly increases the risk of your site being hacked.
- Only use this if you have an alternative patch management strategy.

### 3. Hidden Site Components

Features that hide **Administrators**, **Plugins**, or **Themes** do not remove them; they continue to execute code. A hidden malicious plugin or compromised administrator account poses a severe threat that is harder to detect when hidden from the UI.

## Legal Notice

### Disclaimer of Warranty

This software is provided "as is" without warranty of any kind, either express or implied, including, but not limited to, the implied warranties of merchantability and fitness for a particular purpose. The entire risk as to the quality and performance of the program is with you. Should the program prove defective, you assume the cost of all necessary servicing, repair or correction.

### Limitation of Liability

In no event unless required by applicable law or agreed to in writing will any copyright holder, or any other party who may modify and/or redistribute the program as permitted above, be liable to you for damages, including any general, special, incidental or consequential damages arising out of the use or inability to use the program (including but not limited to loss of data or data being rendered inaccurate or losses sustained by you or third parties or a failure of the program to operate with any other programs), even if such holder or other party has been advised of the possibility of such damages.

---

Developed by [Carlos Rodríguez](https://carlosrodriguez.mx/) and distributed under the [GNU GPLv3](https://www.gnu.org/licenses/gpl-3.0.html) license.
