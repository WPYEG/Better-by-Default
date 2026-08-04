=== Better by Default ===
Contributors: wpyeg
Tags: security, updates, defaults, performance, cleanup
Requires at least: 6.4
Tested up to: 7.0.2
Requires PHP: 7.4
Stable tag: 1.1.4
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
* Limit unfiltered HTML to administrators — Editors hold `unfiltered_html` on single-site installs, which is enough to save a raw script into a post
* Send baseline security headers
* Set X-Frame-Options to SAMEORIGIN
* Disable AI connectors
* Disable comments, pingbacks and self-pingbacks
* Redirect public author archives and attachment pages
* Disable the emoji script
* Lowercase upload filenames (new uploads only)
* Show generated image sizes — a read-only panel on the attachment edit screen
* Warn when the site's From address looks undeliverable
* Right-size login sessions in days: a 2-day regular login, 14 days when remembered
* Automatically install WordPress core maintenance/security releases, while holding major releases for testing
* Leave WordPress's existing automatic translation-file updates unchanged

= Opt-in (OFF by default) =

* Require authentication for ALL REST requests
* Remove the WordPress version fingerprint (obscurity, not hardening — it trims scanner noise but does not make an out-of-date site safer)
* Prohibit Application Passwords (left available by default; use them with a least-privileged account because they inherit that user's access)
* Block the XML-RPC endpoint entirely (403 for every request — not for Jetpack sites)
* Title-only admin search
* Remove, unlink, or replace the login logo (the WordPress logo and its wp.org link are kept by default; any change points the link home)
* Hide the front-end admin bar
* Hide post-password protection (hides the editor's option; no data changes, and a post that already has a password keeps its field)
* Force the classic editor for posts, pages, custom post types, and widgets
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

No. It is a legitimate but aging API and an additional attack/resource surface. Incoming pingbacks are the clearest live risk. WordPress 4.4 prevented system.multicall from being used as a password-guessing multiplier; refusing it today is modest defence-in-depth against general batching, not a password control. Keep the endpoint reachable and test method changes when Jetpack or another integration uses it.

= Does this send passwords anywhere? =

No. Breach screening asks Have I Been Pwned whether a password appears in a known breach corpus, and it does so by k-anonymity: the password is hashed locally with SHA-1, only the first five characters of that hash are sent, and the remaining 35 are compared against the returned suffixes on your server. Neither the password nor its full hash leaves the site, and the response is padded so its size does not reveal how many real matches came back. SHA-1 is only the lookup format the API uses; WordPress still owns password storage and its own hashing.

= Can I turn breach screening off? =

Yes, and you do not have to justify it. It is the only thing this plugin does that leaves your server, so it has a switch. Add `define( 'WPYEG_DISABLE_HIBP', true );` to `wp-config.php` for the whole site, or filter `wpyeg_disable_hibp` when the decision depends on the individual password. With it off, no request is made and the check answers "not breached" — the same thing it answers when the API is unreachable, since the check fails open either way. The length minimum, the blocklist, and the personal-context checks are all local and keep working.

= Can I use it as an mu-plugin? =

Yes. Drop the main PHP file into `wp-content/mu-plugins/` so the policy survives theme switches and can't be deactivated. You lose the settings screen convenience when loaded that way.

== Changelog ==

= 1.1.4 =
* The two REST settings stack under a "REST API" row, matching the XML-RPC and session groupings added in 1.1.3.
* Internal: the mail-deliverability warning splits its decision from its rendering, so every branch — including the local-environment exemption — is reachable from a test. No behaviour change.

= 1.1.3 =
* New, ON by default: a warning when the site's From address looks undeliverable. WordPress sends mail from `wordpress@yourdomain` unless something changes it, and on a domain that cannot send — a staging host, a `.local` address — password resets fail silently, because `wp_mail()` returns false and nothing surfaces it. This shows an admin notice, never blocks or alters mail, and stays quiet on local environments where an undeliverable address is correct.
* The settings screen groups related controls. The four XML-RPC settings now stack under one "XML-RPC" row instead of four, and the two session-length fields under "Session length". The XML-RPC labels drop their repeated prefix, because the row header carries it.
* "Lowercase upload filenames" and "Show generated image sizes" now default to on. Lowercasing costs nothing and removes a whole class of bug: a case-sensitive server and a case-insensitive one disagree about whether `Photo.JPG` and `photo.jpg` are the same file, and only new uploads are affected either way. The sizes panel is read-only — it lists files WordPress already generated and changes nothing — so there is no reason to make someone go looking for it. Both shipped off here with no recorded reason for the difference.

= 1.1.2 =
* Password rules can be scoped by role with the `wpyeg_weak_roles` filter, which defaults to `array( 'subscriber' )`. A 15-character minimum is right for an account that can publish or configure and disproportionate for one that can only read. Exemption requires *every* one of a user's roles to be exempt — a Subscriber who is also an Editor is an Editor — and an unknown or empty role set enforces. Breach screening deliberately runs *before* the role gate, so an exempt account is still screened: a password already in a breach corpus costs its owner nothing to avoid, and low-privilege accounts are the ones most likely to reuse one.
* All ten public `wpyeg_*` filters are documented in the reference doc, and a test now fails when one is added without an entry.
* The password field's help text states the rule and points at the readme instead of carrying the k-anonymity protocol inline; that detail lives in the reference doc now.
* The settings screen reports `DISALLOW_UNFILTERED_HTML`. When that constant is set in `wp-config.php`, WordPress strips unfiltered HTML from every role including administrators, so "Limit unfiltered HTML to administrators" can add nothing on top of it. The control now says so and is disabled, rather than presenting a switch that cannot change anything.
* Removed ampersands from the settings screen: the group headings and the comments label read "and" instead of "&".

= 1.1.1 =
* Security headers are compared rather than yielded to. "Set only if unset" sounded polite and was the wrong rule: whatever arrived first won, so a host's permissive `X-Frame-Options` silently beat a deliberately configured `DENY`. The configured value now replaces an existing one only when it is strictly stronger, and an unrecognised value — a deprecated `ALLOW-FROM`, say — is still left alone rather than guessed at.
* `X-Content-Type-Options` is corrected in place. It has exactly one effective value, so an existing header of `set-by-the-cdn` was not a policy to respect, it was a header doing nothing. It is now set to `nosniff` whatever it said before.
* Header names are matched case-insensitively. HTTP header names are case-insensitive and PHP array keys are not, so another plugin's `x-content-type-options` used to be invisible here and this plugin added a second, conflicting line. Corrections are now written back to the key that is already there.
* `Referrer-Policy` still defers to an existing value — its tokens have no single strictness axis, so there is nothing to compare — but it defers case-insensitively now.
* Breach screening can be switched off. The Have I Been Pwned lookup is the one thing this plugin does that leaves your server, and there was no way to decline it. It is already k-anonymous — five characters of a locally computed SHA-1, with a padded response — but "it is safe" is not the same as "you have no choice." Define `WPYEG_DISABLE_HIBP` in `wp-config.php` for a site-wide declaration, or filter `wpyeg_disable_hibp` for a per-password decision. With it off, no request is made and the check answers "not breached"; the length, blocklist, and personal-context rules still apply.

= 1.1.0 =
* New, ON by default: "Limit unfiltered HTML to administrators." Editors hold `unfiltered_html` on single-site installs, which is enough to save a raw script into a post. Administrators, and Super Admins on multisite, keep it. **This takes a capability away from existing Editors and Authors on upgrade** — turn the setting off if your workflow depends on it.
* New, OFF by default: hide post-password protection, force the classic editor, lowercase upload filenames, and a read-only "Generated Sizes" panel on the attachment edit screen.
* The comment teardown now reaches the rendered page. Comment blocks a block theme already placed in its post templates render as nothing instead of printing an empty "Comments" heading; comment counts report zero rather than reading the post's cached `comment_count`; and comment feeds answer 404 instead of serving an empty but crawlable 200.
* Login sessions are coherent. Both lengths are in days (the old hours field is gone), each has a one-day floor, and the remembered length can never be shorter than the regular one, so ticking "Remember Me" can no longer shorten a session.
* The settings screen names a `wp-config.php` constant that overrides a control, inline on the control it affects, rather than leaving the toggle looking effective.
* The Jetpack warning on the XML-RPC endpoint block now appears only on sites where Jetpack is actually active, and states when the rule stops applying.
* Corrected the `system.multicall` explanation: WordPress 4.4 prevented it from being used as a password-guessing multiplier, so refusing it today is modest defence-in-depth against batching, not a password control.

= 1.0.1 =
* Removed the "Automatically update translations" setting. WordPress handles translation-file updates on its own, and the plugin no longer filters `auto_update_translation`. Note that a site which had explicitly turned this setting off will go back to WordPress's default, which installs translation updates automatically. Turn it off with your own `auto_update_translation` filter if you need that.
* Breach screening no longer trusts a Have I Been Pwned range response that reaches the transport size cap, including one whose truncation happens to land on a row boundary.
* A cached range response that no longer validates is now discarded instead of being reused until its 12-hour cache entry expires.
* The release ZIP now packages plugin subdirectories rather than only top-level files.

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.1.3 =
Two settings change their default to on: "Lowercase upload filenames" (new uploads only) and "Show generated image sizes" (a read-only panel). If you have saved the settings screen since 1.1.0 your stored choices are untouched. A site that has never saved it — including one upgraded from 1.0.x, which predates both settings — picks up the new defaults.

= 1.1.2 =
Password rules are now scoped by role, and Subscribers are exempt by default. An account whose roles are all in `wpyeg_weak_roles` (default `array( 'subscriber' )`) no longer faces the 15-character minimum, the blocklist, or the personal-context checks. Breach screening still applies to everyone. If you want the full policy for every role, filter `wpyeg_weak_roles` to an empty array.

= 1.1.1 =
Security headers are now compared instead of yielded to. If something upstream — a host, a CDN, another plugin — already sends a weaker `X-Frame-Options` than the one configured here, the configured value now wins, where before the upstream one did. A meaningless `X-Content-Type-Options` is corrected to `nosniff`. Check your response headers after upgrading if another layer sets them. Breach screening can now be switched off with `WPYEG_DISABLE_HIBP`.

= 1.1.0 =
Adds a default that removes `unfiltered_html` from everyone below administrator. Editors and Authors who could save raw script into a post no longer can. Turn "Limit unfiltered HTML to administrators" off under Settings → Better by Default if your workflow needs it. Session lengths are now in days; the old hours field is gone.
