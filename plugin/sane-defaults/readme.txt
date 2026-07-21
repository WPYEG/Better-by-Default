=== Better by Default ===
Contributors: wpyeg
Tags: security, updates, defaults, performance, cleanup
Requires at least: 6.4
Tested up to: 7.0.2
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Sane defaults for every new WordPress site. A menu of security, update, UX, SEO, and performance defaults — each one individually toggleable.

== Description ==

Better by Default bundles a menu of sensible defaults that most sites want on every build: restrict REST user discovery, lock down XML-RPC by category (incoming pingbacks, remote publishing, and system.multicall off — full-endpoint block available), require strong passwords, close comment spam, redirect thin author and attachment pages, drop the emoji script, right-size login sessions, own the login screen, and more. Application Passwords are deliberately left available — they are the safer, revocable REST credential, so prohibiting them is opt-in rather than a default.

Every policy is individually toggleable under **Settings → Better by Default**, and the whole plugin is built around one idea:

> A "default" is just an opinionated add_filter() sitting behind a toggle.

The plugin is data-driven: one `wpyeg_defaults_schema()` array is the single source of truth. It drives both the settings screen and the bootstrap that wires each enabled policy to its WordPress hook. Adding a new default is one array entry plus one `if`-block — no new settings-page code.

Built as the teaching project for the WPYEG — Edmonton WordPress Meetup.

= Defaults ON out of the box =

* Restrict REST API user discovery
* Lock down XML-RPC by category — incoming pingbacks off (header stripped), remote publishing off (RSD link dropped), system.multicall refused
* Require strong passwords (server-side: 15+ characters, breach-screened, no forced composition)
* Send baseline security headers
* Disable comments, pingbacks & self-pingbacks
* Redirect public author archives and attachment pages
* Disable the emoji script
* Automatically install WordPress core maintenance/security releases, while holding major releases for testing
* Automatically update WordPress, plugin, and theme translation files

= Opt-in (OFF by default) =

* Require authentication for ALL REST requests
* Remove the WordPress version fingerprint (obscurity, not hardening — it trims scanner noise but does not make an out-of-date site safer)
* Prohibit Application Passwords (left available by default; use them with a least-privileged account because they inherit that user's access)
* Block the XML-RPC endpoint entirely (403 for every request — not for Jetpack sites)
* Title-only admin search
* Remove, unlink, or replace the login logo (the WordPress logo and its wp.org link are kept by default; any change points the link home)
* Hide the front-end admin bar
* Disable "Remember Me"
* Throttle the Heartbeat API

Plugin and theme code updates continue to use WordPress's individual per-item choices. Better by Default does not guess release risk from plugin version numbers. Explicit update constants in wp-config.php remain operator-owned and are reported rather than silently overridden.

== Installation ==

1. Upload the `sane-defaults` folder to `/wp-content/plugins/`, or install the zip via Plugins → Add New → Upload Plugin.
2. Activate. Documented defaults are seeded automatically on activation.
3. Visit **Settings → Better by Default** to flip switches.

WP-CLI:

`wp plugin install ./sane-defaults.zip --activate`

== Frequently Asked Questions ==

= Will this break the block editor? =

No — requiring authentication for all REST requests still permits the logged-in editor's cookie-and-nonce requests. That opt-in policy can break anonymous front-end blocks, embeds, search, and outside integrations, so it ships OFF.

= Is XML-RPC a critical vulnerability? =

No. It is a legitimate but aging API and an additional attack/resource surface. Incoming pingbacks are the clearest live risk. WordPress 4.4 ended the old system.multicall technique that tested thousands of password guesses in one request; refusing multicall today is modest defense-in-depth against general batching. Keep the endpoint reachable and test method changes when Jetpack or another integration uses it.

= Can I use it as an mu-plugin? =

Yes. Drop the main PHP file into `wp-content/mu-plugins/` so the policy survives theme switches and can't be deactivated. You lose the settings screen convenience when loaded that way.

== Changelog ==

= 1.0.0 =
* Initial release.
