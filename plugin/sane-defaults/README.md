# Better by Default

Sane defaults for every new WordPress site — the installable plugin.

Every policy is individually toggleable under **Settings → Better by Default**, and the whole
thing is built around one idea worth carrying home:

> A "default" is just an opinionated `add_filter()` sitting behind a toggle.

## Install

1. Copy the `sane-defaults` folder into `wp-content/plugins/`
   (or upload the zip via **Plugins → Add New → Upload Plugin**).
2. Activate. On activation the documented defaults are seeded automatically.
3. Visit **Settings → Better by Default** to flip switches.

WP-CLI:

```bash
wp plugin install ./sane-defaults.zip --activate
```

For production you can also drop the main PHP file into `wp-content/mu-plugins/` so the
policy survives theme changes and can't be deactivated — though you lose the settings screen
convenience when loaded that way.

## How it's built

The whole map lives in one array: `wpyeg_defaults_schema()`. Read that first. Each entry
defines a key, its default, its type (`toggle` / `select` / `number`), and its group. The
bootstrap function then wires each *enabled* policy to its WordPress hook. The `wpyeg_`
option prefix is kept deliberately as the WPYEG org convention.

Defaults on out of the box: restrict REST user discovery, lock down XML-RPC by category
(incoming pingbacks off, remote publishing off, system.multicall refused), require strong
passwords (15+ chars, breach-screened, no forced composition), send security headers, disable
comments/pingbacks/self-pingbacks, disable author archives, redirect attachment pages, disable
emojis, install core maintenance/security releases automatically while holding major releases
for testing. Translation files retain WordPress's existing automatic-update behavior.

Off by default (opt-in, because they change behavior): require-auth-for-all-REST, prohibit
Application Passwords (left available by default — the safer, revocable REST credential),
block the XML-RPC endpoint entirely, title-only admin search, remove/unlink/replace the login
logo (the WordPress logo is kept by default), hide the front-end admin bar, disable Remember Me,
throttle Heartbeat, and remove the version fingerprint — that last one because it
is obscurity, not hardening: it trims scanner noise but does not make an out-of-date site safer.

Plugin and theme code updates keep using WordPress's per-item auto-update choices. The plugin
does not infer safety from version numbers. An explicit `WP_AUTO_UPDATE_CORE`,
`AUTOMATIC_UPDATER_DISABLED`, or `DISALLOW_FILE_MODS` policy remains operator-owned and is
reported on the settings screen rather than silently overridden.

XML-RPC is an aging API, not a backdoor. Pingbacks are the strongest reason for the locked-down
default; refusing `system.multicall` is modest defense-in-depth against batching, not protection
from the obsolete pre-WordPress-4.4 “thousands of password guesses” attack. Keep the endpoint and
Remote Publishing available when Jetpack needs them, and test connected features after changing
method controls. Application Passwords inherit the owning user's capabilities, so integrations
should use a least-privileged account.

## Three things this plugin can't do for you

These live in `wp-config.php`, above the plugin layer:

```php
define( 'DISALLOW_FILE_EDIT', true );  // no in-dashboard code editor
define( 'AUTOSAVE_INTERVAL', 120 );    // gentler autosave
define( 'WP_POST_REVISIONS', 10 );     // cap revision bloat
```

## License

GPL-3.0-or-later. Fork it, teach with it, ship it.
