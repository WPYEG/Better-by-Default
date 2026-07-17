# Better by Default: reviewed WordPress policy reference

This is a teaching reference, not a universal hardening checklist. A good default is an
explicit assumption with a compatibility boundary, an owner, and a test. The companion plugin
puts each runtime policy behind a setting under **Settings → Better by Default**.

The complete, executable implementation is in
[`plugin/better-by-default/better-by-default.php`](../plugin/better-by-default/better-by-default.php).
The shorter snippets below demonstrate the relevant core hook without pretending to cover
every deployment.

## Security and access surfaces

### Restrict anonymous core REST user routes — default `yes`

Core's users controller exposes public author records, including `slug` (`user_nicename`), not
the private `user_login`. That data can still aid account discovery, but authors can also be
discoverable through posts, feeds, sitemaps, themes, and intentional author archives. Removing
routes is therefore a privacy/surface-reduction policy, not a claim that usernames become secret.
Logged-out requests to removed routes return the normal REST “no route” response (typically
404), not 401.

```php
add_filter(
	'rest_endpoints',
	static function ( $endpoints ) {
		if ( ! is_user_logged_in() ) {
			foreach ( array_keys( $endpoints ) as $route ) {
				if ( preg_match( '#^/wp/v2/users(?:/|$)#', $route ) ) {
					unset( $endpoints[ $route ] );
				}
			}
		}

		return $endpoints;
	}
);
```

### Require authentication for all REST requests — default `no`

This is compatibility-sensitive. Public blocks, oEmbed, forms, search, headless front ends, and
integrations may need anonymous REST access. Existing authentication errors must pass through.

Two details decide whether this filter means what its label says. Core registers
`rest_application_password_check_errors` at priority 90 and `rest_cookie_check_errors` at 100, so
anything deciding at the default 10 runs before authentication has resolved. And the value is not a
plain boolean: when a cookie arrives without an `X-WP-Nonce`, `rest_cookie_check_errors()` calls
`wp_set_current_user( 0 )` and returns **`true`**. The widely copied `! empty( $result )` guard reads
that `true` as "authenticated" and lets the request dispatch as user 0. Run last, and let only a
`WP_Error` short-circuit.

```php
add_filter(
	'rest_authentication_errors',
	static function ( $result ) {
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'REST API restricted to authenticated users.', 'better-by-default' ),
				array( 'status' => 401 )
			);
		}

		return $result;
	},
	PHP_INT_MAX
);
```

### Disable XML-RPC methods — default `yes`

The `xmlrpc_enabled` filter only controls methods requiring authentication. It does **not**
disable the endpoint or pingback methods. To make the setting truthful, remove the registered
method surface as well as discovery hints. `xmlrpc.php` can still respond with an XML-RPC fault;
blocking the file itself belongs at the web server or edge.

```php
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'xmlrpc_methods', '__return_empty_array', PHP_INT_MAX );
add_filter(
	'wp_headers',
	static function ( $headers ) {
		unset( $headers['X-Pingback'] );
		return $headers;
	}
);
remove_action( 'wp_head', 'rsd_link' );
```

### Application Passwords — available by default

Application Passwords are hashed, per-application, revocable credentials intended for API
authentication. Availability is not inherently a vulnerability. The plugin offers an opt-in
prohibition for sites whose policy forbids them:

```php
add_filter( 'wp_is_application_passwords_available', '__return_false' );
```

### Password policy — default `yes`

The plugin enforces at least 15 characters, rejects a small filterable blocklist, and rejects
the current user's login/nicename/email name. It deliberately does not require arbitrary mixes
of uppercase, lowercase, numbers, and symbols. Validation runs for wp-admin profile/user flows,
password reset, and the core REST users controller. Custom registration forms, WP-CLI, direct
`wp_update_user()` calls, and third-party identity providers need their own integration tests.

Enforcing a policy means hooking where core actually reads the password, which differs by screen:

- **Profile and user-new** (`user_profile_update_errors`): `edit_user()` trims `$_POST['pass1']`
  into a local and stores *that*, but fires the hook with `$_POST['pass1']` still untrimmed. A
  validator reading the raw value measures a different string than the one core saves — 15 spaces
  and an `a` passes a 15-character minimum and stores a one-character password. Trim first.
- **Password reset** (`validate_password_reset`): `wp-login.php` writes the trimmed value back into
  `$_POST['pass1']` before firing, so this path is already consistent.
- **REST** (`rest_pre_insert_user`): the documented seam, but neither `create_item()` nor
  `update_item()` checks its return for an error. Returning a `WP_Error` there fails closed — the
  password is never stored — yet an update answers `200 OK` with the user unchanged and a create
  answers a misleading `500`. Enforce on the `password` argument's `sanitize_callback` via
  `rest_endpoints` instead: `sanitize_params()` turns that error into `rest_invalid_param`, which
  dispatch returns as a `400` carrying the actual reason. Keep `rest_pre_insert_user` as a backstop.

For production, connect `wpyeg_password_blocklist` to a substantially larger compromised or
common-password source. NIST recommends blocklist comparison, password-manager/paste support,
Unicode support, and rate limiting rather than composition rules or periodic forced rotation.

### Generator tag — default `no`

Removing `wp_generator` is output hygiene only. WordPress and version clues remain fingerprintable
through assets, files, behavior, and other metadata. Prompt patching matters; hiding a tag does not.

### Security headers — configure at the server or edge

The plugin no longer claims to set a complete baseline through `wp_headers`. That filter covers
WordPress-generated front-end responses, not every server response, static file, cached response,
redirect, REST response, or error document. Configure and test headers at the web server/CDN.
Prefer CSP `frame-ancestors` over relying only on obsolete `X-Frame-Options`, and deploy CSP from
an inventory/report-only phase rather than copying a universal value.

## Content and public surfaces

### Comments, pings, and self-pings — defaults `yes`

Closing comments is appropriate only for sites that do not use discussion. The plugin closes
comments and pings at runtime, hides existing comments from templates, removes post-type support
after post types register, and removes related admin UI. Ping defaults affect newly created
content; they do not rewrite old database rows.

Self-ping detection compares parsed hosts rather than string prefixes, avoiding false matches
such as `example.com.evil.test`.

### Public author archives — default `yes` (disabled)

When archives are not part of the publishing model, the plugin returns an actual 404 and suppresses
numeric-author canonical redirects. Redirecting every author URL to the homepage creates soft-404
and misleading canonical behavior. Multi-author publications should normally enable and curate
author pages rather than hide them.

```php
add_filter(
	'redirect_canonical',
	static function ( $redirect_url ) {
		return ( is_author() || get_query_var( 'author' ) ) ? false : $redirect_url;
	}
);
```

### Legacy attachment pages — default `no`

WordPress 6.4+ disables attachment pages on new sites. Upgraded sites can retain them, so the
plugin offers an opt-in redirect to the parent post or, when unattached, the media file. Do not
describe this as a necessary new-site default.

### Emoji compatibility support — default `yes` (disabled)

This removes core's compatibility script/styles plus feed, email, DNS-prefetch, and TinyMCE emoji
support. Native emoji still render in modern platforms. Test older-browser/accessibility support
requirements before enabling the policy.

## Admin, login, and performance

### Title-only admin search — default `no`

Use `post_search_columns`, introduced in WordPress 6.2, rather than replacing the `posts_search`
SQL fragment. This preserves core token parsing, exclusions, password constraints, and query
composition.

```php
add_filter(
	'post_search_columns',
	static function ( $columns, $search, $query ) {
		if ( is_admin() && $query->is_main_query() && $query->is_search() ) {
			return array( 'post_title' );
		}

		return $columns;
	},
	10,
	3
);
```

### Front-end admin bar — default unchanged

`manage_options` is a capability, not a synonym for the Administrator role. Hiding the bar is a
presentation preference, not hardening; it does not remove access to wp-admin.

### Remember Me and session duration — defaults unchanged

WordPress defaults are approximately 14 days for remembered sessions and 48 hours otherwise.
The plugin can change new cookie expirations. Disabling Remember Me removes submitted values on
the server and hides the checkbox with CSS; a client-side-only removal would be bypassable.
Changing expiration does not revoke cookies already issued.

### Login branding — default unchanged

The WordPress logo is not a security leak. Removing it or pointing it home is an optional branding
decision and should not be mixed into a hardening baseline.

### Heartbeat — default unchanged

The opt-in setting raises the requested interval to 60 seconds but does not deregister Heartbeat.
Disabling it can break post locking, autosave, session warnings, and plugin workflows.

### Script loading strategy — per script, not blanket mutation

WordPress 6.3+ supports `defer` and `async` strategies while accounting for dependency trees.
Declare a strategy on scripts you own and test it; do not inject `defer` into every script tag.

```php
wp_enqueue_script(
	'example-feature',
	plugins_url( 'assets/feature.js', __FILE__ ),
	array(),
	'1.0.0',
	array(
		'in_footer' => true,
		'strategy'  => 'defer',
	)
);
```

## Deployment-level policy

These constants can technically be defined before plugins load, but they are clearer in a standard
`wp-config.php` or environment configuration because they describe deployment behavior:

```php
define( 'DISALLOW_FILE_EDIT', true );
define( 'DISALLOW_FILE_MODS', true ); // Managed deployments only.
define( 'AUTOSAVE_INTERVAL', 120 );
define( 'WP_POST_REVISIONS', 10 );
```

`DISALLOW_FILE_MODS` also blocks dashboard installation and updates. Use it only when an external
deployment/update process owns those changes. Revision and autosave values are editorial/capacity
tradeoffs, not security controls.

## Defaults summary

| Policy | Default | Important boundary |
| --- | --- | --- |
| Restrict anonymous core REST user routes | On | Other public author data can remain |
| Require auth for every REST request | Off | Often breaks public integrations/features |
| Disable XML-RPC methods | On | Endpoint file can still return a fault |
| Disable Application Passwords | Off | Optional organizational policy |
| Workshop password policy | On | Covered core UI/reset/REST paths only |
| Remove generator tag | Off | Hygiene, not hardening |
| Disable comments/pings/self-pings | On | Wrong for discussion sites |
| Disable public author archives | On | Wrong for curated multi-author sites |
| Redirect legacy attachment pages | Off | New WP 6.4+ sites already disable them |
| Disable emoji compatibility support | On | Test legacy support needs |
| Title-only admin search | Off | Changes editor expectations |
| Admin bar/session/login branding changes | Off | UX/policy choices |
| Heartbeat interval 60 seconds | Off | Test collaboration/plugin workflows |

## Authoritative references

- [WordPress `xmlrpc_enabled` hook](https://developer.wordpress.org/reference/hooks/xmlrpc_enabled/)
- [WordPress REST users controller](https://developer.wordpress.org/reference/classes/wp_rest_users_controller/)
- [WordPress `redirect_canonical()`](https://developer.wordpress.org/reference/functions/redirect_canonical/)
- [WordPress 6.4 attachment-page behavior](https://make.wordpress.org/core/2023/10/16/changes-to-attachment-pages/)
- [WordPress Application Passwords](https://developer.wordpress.org/advanced-administration/security/application-passwords/)
- [WordPress script loading strategies](https://make.wordpress.org/core/2023/07/14/registering-scripts-with-async-and-defer-attributes-in-wordpress-6-3/)
- [WordPress `wp_headers` hook](https://developer.wordpress.org/reference/hooks/wp_headers/)
- [WordPress authentication cookies](https://developer.wordpress.org/advanced-administration/security/logging-in/)
- [WordPress plugin internationalization](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/)
- [NIST SP 800-63B password guidance](https://pages.nist.gov/800-63-4/sp800-63b.html)

## Verification checklist

1. Run `composer lint` and `composer test`.
2. Activate on a fresh WordPress 6.4+ site and confirm defaults are seeded.
3. Test logged-out and authenticated REST requests.
4. Confirm `system.listMethods` exposes no methods when XML-RPC policy is enabled.
5. Exercise profile, reset, and REST password paths.
6. Test author and legacy attachment URLs, including `?author=1`.
7. Test comments, autosave, post locking, login cookies, and every integration affected by an
   enabled policy.
8. Verify response headers at the server/CDN across HTML, REST, redirects, errors, static assets,
   and cached responses.
