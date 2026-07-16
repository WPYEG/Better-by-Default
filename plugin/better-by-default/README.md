# Better by Default

A reviewed starting policy for new WordPress sites — the installable teaching plugin.

Every policy is individually toggleable under **Settings → Better by Default**, and the whole
thing is built around one idea worth carrying home:

> A "default" is just an opinionated `add_filter()` sitting behind a toggle.

## Install

1. Copy the `better-by-default` folder into `wp-content/plugins/`
   (or upload the zip via **Plugins → Add New → Upload Plugin**).
2. Activate. On activation the documented defaults are seeded automatically.
3. Visit **Settings → Better by Default** to flip switches.

WP-CLI:

```bash
wp plugin install ./better-by-default.zip --activate
```

For a simple must-use installation, copy the main PHP file directly into
`wp-content/mu-plugins/`. WordPress does not scan subdirectories there unless a loader requires
them. The settings screen still works, but the activation hook does not run; schema fallbacks
still supply defaults until the settings are saved.

## How it's built

The whole map lives in one array: `wpyeg_defaults_schema()`. Read that first. Each entry
defines a key, its default, its type (`toggle` / `select` / `number`), and its group. The
bootstrap function then wires each *enabled* policy to its WordPress hook. The `wpyeg_`
option prefix is kept deliberately as the WPYEG org convention.

Defaults on out of the box: restrict anonymous core REST user routes, disable all registered
XML-RPC methods and discovery hints, enforce a 15-character password floor plus a filterable
blocklist, disable comments/pingbacks/self-pingbacks and author archives, and remove emoji
compatibility support.

Off by default: require-auth-for-all-REST, disable Application Passwords, remove the generator
tag, redirect legacy attachment pages, title-only admin search, admin-bar changes, Remember Me
and session changes, login branding, and Heartbeat throttling.

## Deployment-level examples

These are normally clearer in `wp-config.php` because they describe deployment policy:

```php
define( 'DISALLOW_FILE_EDIT', true );  // no in-dashboard code editor
define( 'DISALLOW_FILE_MODS', true );  // managed deployments only
define( 'AUTOSAVE_INTERVAL', 120 );    // gentler autosave
define( 'WP_POST_REVISIONS', 10 );     // cap revision bloat
```

Do not enable `DISALLOW_FILE_MODS` on a site that depends on dashboard-managed updates.

## License

GPL-3.0-or-later. Fork it, teach with it, ship it.
