# Better by Default

### WPYEG · Edmonton WordPress Meetup

*Explicit, testable starting policies — one toggle at a time.*

`hands-on workshop · better-by-default plugin · GPL-3.0-or-later`

Welcome to a workshop about making assumptions visible. These settings are a starting policy,
not a universal hardening recipe.

---

## “Default” does not mean “safe everywhere”

- **Compatibility first** — publishing, APIs, mobile apps, and integrations differ
- **Name the boundary** — say what a control does *and does not* do
- **Prefer core APIs** — preserve WordPress query and dependency semantics
- **Test the real path** — UI, REST, redirects, cookies, and server responses

Core optimizes for broad compatibility. Our job is to turn project assumptions into reviewed,
reversible policy.

---

## The one idea to take home

# A “default” is an opinionated hook behind a toggle.

```php
if ( wpyeg_defaults_enabled( 'disable_xmlrpc' ) ) {
	add_filter( 'xmlrpc_methods', '__return_empty_array' );
}
```

The toggle is only the delivery mechanism. Accuracy comes from choosing the right hook and
describing its scope honestly.

---

## Two words, gently: actions and filters

- **Action** — “At this moment, also do this.”
- **Filter** — “Before this value is used, transform it.”

```php
add_action( 'init', 'register_policy' );
add_filter( 'post_search_columns', 'limit_search_columns' );
```

Never edit WordPress core. Hook into documented extension points.

---

## A useful policy rubric

1. **Threat or user problem** — what concrete outcome are we changing?
2. **Compatibility cost** — what legitimate workflow can fail?
3. **Control layer** — plugin, WordPress config, web server, CDN, or identity provider?
4. **Verification** — what observable behavior proves it works?
5. **Rollback** — can a site owner reverse it safely?

Use this rubric on every proposed “hardening” snippet.

---

# Section 1 — Security and access surfaces

Reduce callable surface where it is unused. Do not confuse hiding metadata with authorization.

---

## Restrict anonymous REST user routes

`wpyeg_restrict_rest_user_discovery` · default **yes**

```php
foreach ( array_keys( $endpoints ) as $route ) {
	if ( preg_match( '#^/wp/v2/users(?:/|$)#', $route ) ) {
		unset( $endpoints[ $route ] );
	}
}
```

Core exposes public author records and `user_nicename` slugs, not private `user_login` values.
Removed routes normally return REST 404. Other public author data can remain.

---

## Disable XML-RPC methods — not “the file”

`wpyeg_disable_xmlrpc` · default **yes**

```php
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', '__return_empty_array', PHP_INT_MAX );
remove_action( 'wp_head', 'rsd_link' );
```

`xmlrpc_enabled` only controls authenticated methods. Removing the method registry closes the
callable surface. `xmlrpc.php` may still return a fault; block the endpoint at the server/edge if
that is the requirement.

---

## Application Passwords are credentials, not a flaw

`wpyeg_disable_application_passwords` · default **no**

```php
// Optional organizational prohibition.
add_filter(
	'wp_is_application_passwords_available',
	'__return_false'
);
```

Application Passwords are hashed, per-application, and revocable. Leave them available unless
site policy forbids them; inventory and revoke unused credentials.

---

## Enforce a modern password policy

`wpyeg_require_strong_passwords` · default **yes**

```php
$length = function_exists( 'mb_strlen' )
	? mb_strlen( $password )
	: strlen( $password );

if ( 15 > $length ) {
	return new WP_Error( 'password_too_short', 'Use 15+ characters.' );
}
```

Reject common/context-specific values. Do not require arbitrary character-class composition.
Cover wp-admin, reset, and REST paths; test custom registration, CLI, and SSO separately.

---

## Metadata and headers: choose the right layer

`wpyeg_remove_version` · default **no**

- Removing the generator tag is output hygiene, not meaningful hardening.
- `wp_headers` does not cover static files, every REST/error/redirect, or cached edge responses.
- Configure security headers at the web server/CDN and verify every response class.
- Build Content Security Policy from an inventory and report-only rollout.

Patch promptly. Do not mistake obscurity for access control.

---

## Require authentication for every REST request

`wpyeg_disable_rest` · default **no**

```php
if ( ! is_user_logged_in() ) {
	return new WP_Error(
		'rest_not_logged_in',
		'Authentication required.',
		array( 'status' => 401 )
	);
}
```

This can break public blocks, forms, oEmbed, search, and integrations. Use only after an endpoint
inventory and compatibility test.

---

# Section 2 — Content and public surfaces

Close unused discussion and low-value archive surfaces without creating misleading redirects.

---

## Disable comments, trackbacks, and pingbacks

`wpyeg_disable_comments` · default **yes**

```php
add_filter( 'comments_open', '__return_false', 20 );
add_filter( 'pings_open', '__return_false', 20 );
add_filter( 'comments_array', '__return_empty_array', 20 );
```

Appropriate for sites that do not publish discussion. Runtime closing, post-type support, admin
UI, and new-content defaults are separate concerns; cover each intentionally.

---

## Author archives and legacy attachment pages

`disable_author_archives` / `redirect_attachment_pages` · **yes / no**

- Disabled author archives return a real 404 and suppress `?author=1` canonical redirects.
- Homepage redirects create soft-404 and misleading canonical behavior.
- Multi-author publications should curate author pages instead of hiding them.
- WordPress 6.4+ disables attachment pages on new sites; redirects mainly serve upgraded sites.

When enabled, legacy attachment pages go to their parent post or media file.

---

## Disable emoji compatibility support

`wpyeg_disable_emojis` · default **yes**

```php
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
add_filter( 'emoji_svg_url', '__return_false' );
```

Also remove admin, feed, email, and TinyMCE support. Native emoji remain. This is compatibility
cleanup, not a security control.

---

# Section 3 — Admin UX and sessions

Preserve core query semantics and enforce session choices on the server.

---

## Title-only search; capability-based toolbar policy

`title_only_admin_search` / `frontend_admin_bar_behavior` · **no / unchanged**

```php
add_filter(
	'post_search_columns',
	static function () {
		return array( 'post_title' );
	}
);
```

`post_search_columns` preserves core term parsing, exclusions, password checks, and SQL
composition. `manage_options` is a capability, not a role name. Toolbar visibility is UX, not
authorization.

---

## Remember Me and cookie duration

`disable_remember_me` / session lengths · defaults **unchanged**

```php
add_action(
	'login_init',
	static function () {
		unset( $_POST['rememberme'], $_REQUEST['rememberme'] );
	}
);
```

Hiding the checkbox with JavaScript alone is bypassable. Remove the submitted value server-side.
Expiration filters affect new cookies only; revoke existing sessions separately.

---

# Section 4 — Branding, performance, and deployment

Keep preferences distinct from security, and avoid blanket asset mutations.

---

## Login branding is optional

`login_logo_behavior` / `login_logo_link_home` · defaults **unchanged**

```php
add_filter( 'login_headerurl', 'home_url' );
add_filter(
	'login_headertext',
	static function () {
		return get_bloginfo( 'name' );
	}
);
```

The WordPress logo is not a security leak. Brand or remove it when the project calls for it.

---

## Heartbeat and script strategy

`wpyeg_throttle_heartbeat` · default **no**

```php
$settings['interval'] = 60;

wp_enqueue_script(
	'example-feature',
	$src,
	array(),
	'1.0.0',
	array( 'strategy' => 'defer' )
);
```

Do not deregister Heartbeat: autosave, post locking, and plugins use it. Declare `defer`/`async`
per script through WordPress 6.3+ strategy APIs; never rewrite every script tag.

---

## Deployment-level defaults belong in config

```php
define( 'DISALLOW_FILE_EDIT', true );
define( 'DISALLOW_FILE_MODS', true ); // Managed deployments only.
define( 'AUTOSAVE_INTERVAL', 120 );
define( 'WP_POST_REVISIONS', 10 );
```

Plugins *can* define constants early, but `wp-config.php` or environment config makes ownership
clear. `DISALLOW_FILE_MODS` blocks dashboard installs and updates; do not use it without an
external deployment/update workflow.

---

## How the plugin is built

1. **schema()** — labels, types, defaults, groups
2. **settings page** — renders the schema
3. **bootstrap()** — registers hooks for enabled policies
4. **tests** — assert defaults, sanitization, hook coverage, and real WordPress behavior

```php
$stored = get_option( WPYEG_DEFAULTS_OPTION, array() );
```

One source of truth reduces drift between UI and runtime behavior.

---

## Hands-on: install and verify

1. Activate `better-by-default.zip`.
2. Open **Settings → Better by Default**.
3. Logged out, request `/wp-json/wp/v2/users` — expect REST 404.
4. Request `/?author=1` — expect HTTP 404 while author archives are disabled.
5. Call XML-RPC `system.listMethods` — expect no registered methods.
6. Test profile, reset, and REST password changes.

```bash
wp plugin install ./better-by-default.zip --activate
```

---

## Your turn: add one scoped default

*Goal: hide the dashboard Welcome panel.*

```php
'hide_welcome_panel' => array(
	'default' => 'yes',
	'type'    => 'toggle',
	'group'   => 'ux',
	'label'   => __( 'Hide dashboard Welcome panel', 'better-by-default' ),
),

if ( wpyeg_defaults_enabled( 'hide_welcome_panel' ) ) {
	remove_action( 'welcome_panel', 'wp_welcome_panel' );
}
```

State the compatibility boundary and write one regression assertion.

---

## What ships on

| Policy | Hook / mechanism | Boundary |
| --- | --- | --- |
| Restrict core REST user routes | `rest_endpoints` | Other author data remains |
| Remove XML-RPC methods | `xmlrpc_methods` | Endpoint may fault |
| Enforce password policy | UI/reset/REST hooks | Custom flows need coverage |
| Close comments and pings | discussion filters/support | Wrong for discussion sites |
| 404 author archives | canonical + query state | Enable for curated authors |
| Remove emoji compatibility | action/filter removal | Test legacy needs |

Everything else is opt-in or unchanged by default.

---

# Thanks, WPYEG

*A default is an explicit, testable assumption behind a hook.*

Authoritative reading: WordPress Developer Resources for XML-RPC, REST users, Application
Passwords, canonical redirects, `wp_headers`, auth cookies, and script strategies; WordPress
6.4 attachment-page dev note; NIST SP 800-63B password guidance.

`better-by-default.zip · wordpress-default-settings.md · GPL-3.0-or-later`
