=== Better by Default ===
Contributors: wpyeg
Tags: security, hardening, defaults, performance, cleanup
Requires at least: 6.4
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

A reviewed starting policy for new WordPress sites, with individually toggleable security, content, UX, login, branding, and performance controls.

== Description ==

Better by Default is a teaching plugin that bundles reviewable policy choices: restrict anonymous core REST user routes, disable XML-RPC methods, enforce a password policy, close unused discussion surfaces, disable public author archives, remove emoji compatibility support, and more. Compatibility-sensitive behavior remains opt-in.

Every policy is individually toggleable under **Settings → Better by Default**, and the whole plugin is built around one idea:

> A "default" is just an opinionated add_filter() sitting behind a toggle.

The plugin is data-driven: one `wpyeg_defaults_schema()` array is the single source of truth. It drives both the settings screen and the bootstrap that wires each enabled policy to its WordPress hook. Adding a new default is one array entry plus one `if`-block — no new settings-page code.

Built as the teaching project for the WPYEG — Edmonton WordPress Meetup.

= Defaults ON out of the box =

* Restrict REST API user discovery
* Disable all registered XML-RPC methods and strip discovery hints
* Require passwords of at least 15 characters and reject a filterable blocklist
* Disable comments, pingbacks & self-pingbacks
* Return 404 for public author archives and suppress numeric-author canonical redirects
* Disable emoji compatibility support

= Opt-in (OFF by default) =

* Require authentication for ALL REST requests
* Disable Application Passwords as an explicit site policy
* Remove the generator tag (output hygiene, not hardening)
* Redirect attachment pages retained by upgraded sites
* Title-only admin search
* Hide the front-end admin bar
* Disable "Remember Me"
* Change session length or login branding
* Throttle the Heartbeat API

== Installation ==

1. Upload the `better-by-default` folder to `/wp-content/plugins/`, or install the zip via Plugins → Add New → Upload Plugin.
2. Activate. Documented defaults are seeded automatically on activation.
3. Visit **Settings → Better by Default** to flip switches.

WP-CLI:

`wp plugin install ./better-by-default.zip --activate`

== Frequently Asked Questions ==

= Can a policy break an integration or publishing workflow? =

Yes. Site policy is contextual. Requiring authentication for all REST requests, disabling Application Passwords or XML-RPC methods, closing discussion, changing author archives, and throttling Heartbeat can affect legitimate workflows. Test the settings your site enables.

= Can I use it as an mu-plugin? =

Yes. Copy the main PHP file directly into `wp-content/mu-plugins/`; WordPress does not recursively discover plugin subdirectories there. The settings screen remains available, but the activation hook does not run. Schema fallbacks still apply until you save settings.

== Changelog ==

= 1.0.0 =
* Initial release.
