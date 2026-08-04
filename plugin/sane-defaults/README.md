# Better by Default

Sane defaults for every new WordPress site — the installable plugin.

Every policy is individually toggleable under **Settings → Better by Default**, and the whole
thing is built around one idea worth carrying home:

> A "default" is just an opinionated `add_filter()` sitting behind a toggle.

## Install

1. Copy the `sane-defaults` folder into `wp-content/plugins/`
   (or upload the zip via **Plugins → Add New → Upload Plugin**).
2. Activate. The documented defaults apply immediately — they live in the schema, not the
   database, so nothing is written until you save the settings screen.
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

A new `group` needs a title in `wpyeg_defaults_groups()` and a new `section` one in
`wpyeg_defaults_section_labels()`; the tests fail if either is missing, because a setting whose
group has no title would save and take effect while never appearing on the screen.

Settings-screen convention: toggle rows place the descriptive schema label immediately after
the checkbox inside one clickable label; they never use a generic `Enabled` label. Select and
number fields retain descriptive row labels. Every control connects its help text with
`aria-describedby`. Labels and descriptions inherit WordPress's classic `form-table` styles;
do not add custom typography. Help text may contain attribute-free `<code>` only, sanitized
through `wp_kses()`, for machine-facing identifiers. It may also contain an `<a>` with only an
`href` when an external claim benefits from a direct authoritative reference. Name the specific
publication or directive and section when one exists; do not use vague attributions such as
"per NIST." Keep that pattern when adding settings.

Every setting and its shipped default is tabulated in [`readme.txt`](readme.txt), and again with
the reasoning and a code snippet for each in
[`docs/wordpress-default-settings.md`](../../docs/wordpress-default-settings.md). Both tables are
checked against `wpyeg_defaults_schema()` by `composer test`, which is why this file does not
restate them: an unchecked fourth copy is just somewhere else for the truth to go stale.

Sessions are in days — a 2-day regular login, 14 days when remembered, floored so ticking
"Remember Me" can never shorten a session. Translation files retain WordPress's existing
automatic-update behaviour.

Plugin and theme code updates keep using WordPress's per-item auto-update choices. The plugin
does not infer safety from version numbers. An explicit `WP_AUTO_UPDATE_CORE`,
`AUTOMATIC_UPDATER_DISABLED`, or `DISALLOW_FILE_MODS` policy remains operator-owned and is
reported on the settings screen rather than silently overridden.

XML-RPC is an aging API, not a backdoor. Pingbacks are the strongest reason for the locked-down
default; refusing `system.multicall` is modest defence-in-depth against batching, not a password
control — WordPress 4.4 prevented it from being used as a password-guessing multiplier. Keep the endpoint and
Remote Publishing available when Jetpack needs them, and test connected features after changing
method controls. Application Passwords inherit the owning user's capabilities, so integrations
should use a least-privileged account.

## Switching the breach lookup off

Breach screening is the one thing this plugin does that leaves your server, so it has a
switch. It is already k-anonymous — the password is hashed locally, only the first five SHA-1
characters are sent, and neither the password nor its full hash ever leaves the site — but
"it is safe" is not the same as "you have no choice."

```php
define( 'WPYEG_DISABLE_HIBP', true );          // wp-config.php, whole site
add_filter( 'wpyeg_disable_hibp', '__return_true' );  // or per password
```

With it off, no request is made and the check answers "not breached", exactly as it does when
the API is unreachable — it fails open either way. The length minimum, blocklist, and
personal-context rules are local and keep running.

## Three things this plugin can't do for you

These live in `wp-config.php`, above the plugin layer:

```php
define( 'DISALLOW_FILE_EDIT', true );  // no in-dashboard code editor
define( 'AUTOSAVE_INTERVAL', 120 );    // gentler autosave
define( 'WP_POST_REVISIONS', 10 );     // cap revision bloat
```

## License

GPL-3.0-or-later. Fork it, teach with it, ship it.
