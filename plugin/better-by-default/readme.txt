=== Better by Default ===
Contributors: wpyeg
Tags: security, hardening, defaults, performance, cleanup
Requires at least: 6.4
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Sane defaults for every new WordPress site. A menu of security, UX, SEO, and performance defaults — each one individually toggleable.

== Description ==

Better by Default bundles a menu of sensible defaults that most sites want on every build: restrict REST user discovery, disable XML-RPC and Application Passwords, require strong passwords, close comment spam, redirect thin author and attachment pages, drop the emoji script, right-size login sessions, own the login screen, and more.

Every policy is individually toggleable under **Settings → Better by Default**, and the whole plugin is built around one idea:

> A "default" is just an opinionated add_filter() sitting behind a toggle.

The plugin is data-driven: one `wpyeg_defaults_schema()` array is the single source of truth. It drives both the settings screen and the bootstrap that wires each enabled policy to its WordPress hook. Adding a new default is one array entry plus one `if`-block — no new settings-page code.

Built as the teaching project for the WPYEG — Edmonton WordPress Meetup.

= Defaults ON out of the box =

* Restrict REST API user discovery
* Disable XML-RPC (and strip the pingback header)
* Disable Application Passwords
* Require strong passwords (server-side)
* Remove the version fingerprint + send baseline security headers
* Disable comments, pingbacks & self-pingbacks
* Redirect public author archives and attachment pages
* Disable the emoji script
* Remove/replace the login logo and point its link home

= Opt-in (OFF by default) =

* Require authentication for ALL REST requests
* Title-only admin search
* Hide the front-end admin bar
* Disable "Remember Me"
* Throttle the Heartbeat API
* Defer front-end scripts

== Installation ==

1. Upload the `better-by-default` folder to `/wp-content/plugins/`, or install the zip via Plugins → Add New → Upload Plugin.
2. Activate. Documented defaults are seeded automatically on activation.
3. Visit **Settings → Better by Default** to flip switches.

WP-CLI:

`wp plugin install ./better-by-default.zip --activate`

== Frequently Asked Questions ==

= Will this break the block editor? =

No — the ON-by-default set is safe for nearly any site. The one policy that can break the editor (require-auth-for-all-REST) ships OFF for exactly that reason.

= Can I use it as an mu-plugin? =

Yes. Drop the main PHP file into `wp-content/mu-plugins/` so the policy survives theme switches and can't be deactivated. You lose the settings screen convenience when loaded that way.

== Changelog ==

= 1.0.0 =
* Initial release.
