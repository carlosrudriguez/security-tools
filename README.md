# Security Tools (MU Plugin)

> A powerful, self‑hiding WordPress MU plugin for security hardening, admin control, and interface cleanup.

**Security Tools** is built for administrators who want deeper control without editing core files or installing multiple plugins. It runs as a Must‑Use plugin (auto‑loaded by WordPress), stays hidden from other admins, and lets you toggle every feature on or off at any time.

---

## ✨ Why Security Tools?

- **Reduce risk:** Disable sensitive actions like updates, plugin installs, or theme editing when you don’t want them available.
- **Keep the dashboard clean:** Hide admin notices, widgets, admin bar items, and more.
- **Harden login access:** Replace the default login URL with a custom slug and block default login routes.
- **Stay invisible:** The plugin hides itself from other administrators to avoid discovery.

---

## 🎯 Who It’s For

- Agencies managing client sites who want to lock down risky admin actions.
- Site owners who need a clean, focused admin experience.
- Teams that want security controls without custom code.

---

## 🚀 Quick Start (MU Plugin)

1. Download and extract the [v2.6.4 installation ZIP](https://github.com/carlosrudriguez/security-tools/releases/download/v2.6.4/security-tools-2.6.4.zip).
2. Using your host's file manager or SFTP, copy both `security-tools-loader.php` and the **unversioned** `security-tools` folder into `wp-content/mu-plugins/`. They must sit beside each other:

   ```text
   wp-content/mu-plugins/
   ├── security-tools-loader.php
   └── security-tools/
       └── security-tools.php
   ```

3. Open **Security Tools** in the WordPress admin sidebar. MU plugins load automatically; the standard plugin uploader does not install this package.

---

## 🧰 Feature Highlights

### System Controls
- **Disable Updates** (core, plugins, themes)
- **Disable Emails** (blocks all outgoing email)
- **Disable Email Verification**
- **Disable Comments** (site‑wide)
- **Disable Plugin/Theme Controls** (install, activate, edit, customize)
- **Disable Frontend Admin Bar**
- **Hide Admin Notices**

### Administrator Access
- The first administrator to open Security Tools when no access list exists receives access.
- In **General**, authorized administrators can enable or disable access for each current administrator. Newly created administrators remain disabled until enabled manually.
- At least one administrator must retain access. A disabled administrator cannot save Security Tools settings from an old open form.

### Login Hardening
- **Hide Login Page** with a custom slug
- Default login routes return **404** for non‑logged users

### Admin UI Hiding
- **Admins:** hide selected administrator accounts from the Users list; this is separate from Security Tools access
- **Plugins / Themes:** hide items without disabling them
- **Widgets:** hide dashboard widgets
- **Admin Bar:** hide items by ID or **CSS ID**
- **Metaboxes:** hide post/page editor panels (Classic + Gutenberg)
- The six Hide tables use individual **Hide** toggles and an **All** toggle for the visible list. Existing selections remain in place when upgrading.

### Branding
- **Custom Login Logo** (supports SVG)
- **Custom Legend** (login message + admin footer text)

---

## 🧠 What Makes It Different

- **MU Plugin:** auto‑loaded and always on, no activation required.
- **Self‑hiding:** removes itself from the plugins list and MU tab.
- **Non‑destructive:** all features are reversible with toggles.
- **Designed for admins:** UI is clear, fast, and split into focused sections.

---

## Compatibility

Version 2.6.4 uses WordPress's current `core/editor` panel API for block editor metabox hiding. The plugin was checked locally with WordPress 7.1.2; browser verification of editor screens remains site-specific.

---

## ⚠️ Important Safety Notes

- **Lockout risk:** If you enable **Hide Login Page** and **Disable Emails**, you can lock yourself out. Always bookmark your custom login URL.
- **Security risk:** Disabling updates prevents security patches. Only do this if you manage updates another way.

---

## 📚 Documentation

- Full user guide: `user-guide.html`
- Changelog: `changelog.md`

---

## ⚖️ License

GPLv3

---

## 📜 Legal Notice

### Disclaimer of Warranty

This software is provided \"as is\" without warranty of any kind, either express or implied, including, but not limited
to, the implied warranties of merchantability and fitness for a particular purpose. The entire risk as to the quality
and performance of the program is with you. Should the program prove defective, you assume the cost of all necessary
servicing, repair or correction.

### Limitation of Liability

In no event unless required by applicable law or agreed to in writing will any copyright holder, or any other party who
may modify and/or redistribute the program as permitted above, be liable to you for damages, including any general,
special, incidental or consequential damages arising out of the use or inability to use the program (including but not
limited to loss of data or data being rendered inaccurate or losses sustained by you or third parties or a failure of
the program to operate with any other programs), even if such holder or other party has been advised of the possibility
of such damages.
