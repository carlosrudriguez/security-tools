# Security Tools (MU Plugin)

> A powerful, self‑hiding WordPress MU plugin for security hardening, admin control, and interface cleanup.

**Security Tools** is built for administrators who want deeper control without editing core files or installing multiple plugins. It runs as a Must‑Use plugin (auto‑loaded by WordPress), stays hidden from other admins, and lets you toggle every feature on or off at any time.

---

## Why Security Tools?

- **Reduce risk:** Disable sensitive actions like updates, plugin installs, or theme editing when you don’t want them available.
- **Keep the dashboard clean:** Hide admin notices, widgets, admin bar items, and more.
- **Harden login access:** Replace the default login URL with a custom slug and block default login routes.
- **Stay invisible:** The plugin hides itself from other administrators to avoid discovery.

---

## Who It’s For

- Agencies managing client sites who want to lock down risky admin actions.
- Site owners who need a clean, focused admin experience.
- Teams that want security controls without custom code.

---

## Quick Start (MU Plugin)

1. Copy the `security-tools` folder to `wp-content/mu-plugins/`.
2. Create `wp-content/mu-plugins/security-tools-loader.php` with:

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

3. Open **Security Tools** in the WordPress admin sidebar.

---

## Feature Highlights

### System Controls
- **Disable Updates** (core, plugins, themes)
- **Disable Emails** (blocks all outgoing email)
- **Disable Email Verification**
- **Disable Comments** (site‑wide)
- **Disable Plugin/Theme Controls** (install, activate, edit, customize)
- **Disable Frontend Admin Bar**
- **Hide Admin Notices**

### Login Hardening
- **Hide Login Page** with a custom slug
- Default login routes return **404** for non‑logged users

### Admin UI Hiding
- **Admins:** hide selected administrator accounts from the Users list
- **Plugins / Themes:** hide items without disabling them
- **Widgets:** hide dashboard widgets
- **Admin Bar:** hide items by ID or **CSS ID**
- **Metaboxes:** hide post/page editor panels (Classic + Gutenberg)

### Branding
- **Custom Login Logo** (supports SVG)
- **Custom Legend** (login message + admin footer text)

---

## What Makes It Different

- **MU Plugin:** auto‑loaded and always on, no activation required.
- **Self‑hiding:** removes itself from the plugins list and MU tab.
- **Non‑destructive:** all features are reversible with toggles.
- **Designed for admins:** UI is clear, fast, and split into focused sections.

---

## Important Safety Notes

- **Lockout risk:** If you enable **Hide Login Page** and **Disable Emails**, you can lock yourself out. Always bookmark your custom login URL.
- **Security risk:** Disabling updates prevents security patches. Only do this if you manage updates another way.

---

## Documentation

- Full user guide: `user-guide.html`
- Changelog: `changelog.md`

---

## License

GPLv3
