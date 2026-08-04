=== Better by Default ===
Contributors: wpyeg
Tags: security, updates, defaults, performance, cleanup
Requires at least: 6.4
Tested up to: 7.0.2
Requires PHP: 7.4
Stable tag: 1.2.3
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Sane defaults for every new WordPress site. A menu of security, update, UX, SEO, and performance defaults — each one individually toggleable.

== Description ==

Better by Default bundles a menu of sensible defaults that most sites want on every build: restrict REST user discovery, lock down XML-RPC by category (incoming pingbacks, remote publishing, and system.multicall off — full-endpoint block available), require strong passwords, close comment spam, redirect thin author and attachment pages, drop the emoji script, right-size login sessions, own the login screen, and more. Application Passwords are deliberately left available — they are the safer, revocable REST credential, so prohibiting them is opt-in rather than a default.

Every policy is individually toggleable under **Settings → Better by Default**, and the whole plugin is built around one idea:

> A "default" is just an opinionated add_filter() sitting behind a toggle.

The plugin is data-driven: one `wpyeg_defaults_schema()` array is the single source of truth. It drives both the settings screen and the bootstrap that wires each enabled policy to its WordPress hook. Adding a new default is one array entry plus one `if`-block — no new settings-page code.

Built as the teaching project for the WPYEG — Edmonton WordPress Meetup.

= Every setting and its default =

This table is generated from the same `wpyeg_defaults_schema()` array the plugin runs on, and the test suite fails if a row here disagrees with it. `yes`/`no` are toggles; the rest are select or number fields.

| Setting | Key | Default |
| --- | --- | --- |
| Restrict REST API user discovery | `restrict_rest_user_discovery` | `yes` |
| Require auth for all REST requests | `disable_rest` | `no` |
| Accept incoming pingbacks | `xmlrpc_allow_pingbacks` | `no` |
| Allow remote publishing (blogging apps) | `xmlrpc_allow_remote_publishing` | `no` |
| Allow system.multicall | `xmlrpc_allow_multicall` | `no` |
| Block the endpoint entirely (returns 403) | `block_xmlrpc_endpoint` | `no` |
| Prohibit Application Passwords | `disable_application_passwords` | `no` |
| Require strong passwords | `require_strong_passwords` | `yes` |
| Limit unfiltered HTML to administrators | `limit_unfiltered_html_to_admins` | `yes` |
| Remove WordPress version fingerprint | `remove_version` | `no` |
| Send baseline security headers | `security_headers` | `yes` |
| X-Frame-Options (clickjacking) | `frame_options` | `SAMEORIGIN` |
| Disable AI connectors | `disable_ai_connectors` | `yes` |
| Automatic WordPress Core Updates | `core_update_policy` | `minor` |
| Disable comments, trackbacks and pingbacks | `disable_comments` | `yes` |
| Default new posts to pings closed | `disable_pingbacks` | `yes` |
| Disable self-pingbacks | `disable_self_pingbacks` | `yes` |
| Disable public author archives | `disable_author_archives` | `yes` |
| Redirect attachment pages | `redirect_attachment_pages` | `yes` |
| Disable emoji script | `disable_emojis` | `yes` |
| Hide post-password protection | `disable_post_passwords` | `no` |
| Force the classic editor | `force_classic_editor` | `no` |
| Title-only admin search | `title_only_admin_search` | `no` |
| Front-End Admin Bar | `frontend_admin_bar_behavior` | `''` |
| Lowercase upload filenames | `lowercase_upload_filenames` | `yes` |
| Show generated image sizes | `media_sizes_panel` | `yes` |
| Disable "Remember Me" | `disable_remember_me` | `no` |
| Regular session length (days) | `session_regular_days` | `2` |
| Remember Me length (days) | `remember_me_days` | `14` |
| Login Logo | `login_logo_behavior` | `keep_default` |
| Warn when the site's From address looks undeliverable | `mail_deliverability_notice` | `yes` |
| Throttle the Heartbeat API | `throttle_heartbeat` | `no` |

Sessions are in days, and the remembered length can never be shorter than the regular one — a 2-day regular login and 14 days when remembered, matching WordPress's own values. WordPress's existing automatic translation-file updates are left unchanged.

= The three defaults that are deliberately not locked down =

**Application Passwords stay available.** They are the safer, revocable REST and XML-RPC credential. Prohibiting them does not remove an integration's need for credentials; it pushes people to a shared login or a third-party auth plugin, which are harder to isolate and revoke and bypass 2FA the same way. Use them on a least-privileged account, because they inherit that user's access.

**The login screen is left untouched.** Changing what someone sees at wp-login.php out of the box is intrusive, so removing, unlinking, or replacing the logo is an administrator's choice. Any of those changes points the header link at your home page instead of wordpress.org.

**Removing the version fingerprint is off**, and not because it is risky. It is obscurity rather than hardening: it trims automated scanner noise from your logs, but it does not make an out-of-date site any safer, and the version still leaks from asset query strings and feeds. Worth opting into; not worth presenting as a security default.

Plugin and theme code updates continue to use WordPress's individual per-item choices. Better by Default does not guess release risk from plugin version numbers. Explicit update constants in wp-config.php remain operator-owned and are reported rather than silently overridden.

== Installation ==

1. Upload the `sane-defaults` folder to `/wp-content/plugins/`, or install the zip via Plugins → Add New → Upload Plugin.
2. Activate. The documented defaults apply immediately. They live in the plugin's schema rather than the database, so nothing is written to `wp_options` until you save the settings screen — and a setting you have never saved follows the current default, including after an upgrade that improves one.
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

= 1.2.3 =
* "Require authentication for all REST requests" no longer closes oEmbed with everything else. Closing REST used to take `/oembed/1.0` with it, and the damage landed where the site owner never looks: every post of theirs that another site had embedded degraded to a bare link, silently, on somebody else's page. The route is allowlisted now, filterable through `wpyeg_public_rest_routes`. Matching is on a path boundary, so `/oembed/1.0-internal` is not admitted by sharing a prefix, and the route is read from parsed query vars rather than `REQUEST_URI`, which carries whatever the client sent.
* The carve-out is only safe because of what sits behind it. oEmbed returns `author_name` and an `author_url` carrying the account nicename — opening that route would hand an anonymous caller exactly the usernames the REST user-discovery default exists to refuse. So closing REST now registers the author strip itself, independently of the author-archive setting, rather than relying on another default happening to be on.
* Workshop: the deck's eighth category is finally in it. The agenda promised "Email — say so when the site cannot send mail" and the talk delivered seven categories; the mail-deliverability notice now has its own slide. Four other claims in the deck that the code contradicted are corrected — a speaker note that had remembered sessions at five days and a "zero means unchanged" sentinel removed in 1.1.0, `block_xmlrpc_endpoint` listed against the wrong hook, `wpyeg_frame_options` for a key that is `frame_options`, and an understated release count.
* Internal: the settings-heading case rule is now shared verbatim with the sibling plugins, and every dimension it can be wrong about is pinned — a word's position in a title, a segment's position in a hyphenated compound, and each entry in both word lists. Sixteen of nineteen list entries could previously be deleted with the suite staying green.

= 1.2.2 =
* The session-length filter registers at priority 50 instead of 10. `auth_cookie_expiration` is a replacing filter, so the *last* callback to run is the one that counts — registering at the default priority guaranteed losing to any other plugin that did not choose one. 1.2.0 made this plugin stop contesting the filter when it had nothing to say; this is the other half, so that when it does have something to say it is not overruled by load order. Both sibling plugins already register at 50.
* Corrected the plugin header's own explanation of its patterns. It cited `auth_cookie_expiration` as the example of "register always, decide inside", which stopped being true in 1.2.0 when that registration became conditional. The correction is worth more than the error: needing a runtime argument (`$remember`) does not oblige you to register — what a login's length should *be* and whether this plugin should answer at all are different questions, and the second is answerable at bootstrap.
* The reference doc's session snippet showed priority 10 to match the old code, and now shows 50 with the reasoning.
* A test asserts the priority, so it cannot drift back silently.

= 1.2.1 =
* Every setting now sits in a labelled section, so no row has an empty left column. A toggle used to draw full-width with its label beside the checkbox and nothing in the label column, which left twenty bare rows interleaved with the seven that had one — ragged rather than deliberate. Twenty-seven rows become nineteen, each under a heading that names the category ("Capabilities", "Response Headers") while the text beside each checkbox stays the specific claim. "Remember Me" joins the two session-length fields, because those three settings are one policy.
* Section headings are Title Case, matching the group headings directly above them and the sibling plugins' screens. Sentence case in the left column under a Title Case heading is a mixed convention nobody decides on; a test now fails on it.
* No behaviour change: this is layout and wording only. Every control keeps its key, its default, its bound label and its `aria-describedby`.

= 1.2.0 =
* Session lengths no longer contest the `auth_cookie_expiration` filter on a site that has not changed them. That filter is a *replacing* one — a callback returns its own number and discards the value it was handed — so two plugins registering one do not compose: WordPress keeps whichever ran last, the loser does nothing, and both settings screens go on displaying a number the site is not using. Both defaults here are WordPress's own values, so on an untouched site this plugin was entering that fight only to assert the answer core already gives. It now registers the filter when a length differs from its default, or when "Remember Me" is disabled, and the callback is a named function instead of an anonymous one so `$wp_filter` can identify it. See `docs/when-two-plugins-set-the-same-default.md`.
* Removed the activation hook. It seeded the option with every schema default, which changed nothing — settings already fall back to the schema when the stored array has no entry — while freezing a site at its activation-time defaults, so a default improved in a later release never reached it. One rule now: a setting you have saved is yours and is never touched; a setting you have never saved follows the current default.
* The `wpyeg_disable_ai_connectors` seam fires on `init` (priority 20) instead of during `plugins_loaded`. It used to fire before any plugin that registers hooks from its own `plugins_loaded` callback had run, and before the AI providers it exists to unregister had registered at all — a seam nothing could reach.
* Documentation corrections throughout, and guards so they stay corrected. The reference doc taught the "set only if unset" header rule that 1.1.1 replaced, a `login_footer` script for the Remember Me default that this plugin deliberately does not use, and a password snippet with the two bugs the plugin's own comments explain fixing. Both READMEs listed "Lowercase upload filenames" and "Show generated image sizes" as opt-in; both have defaulted to on since 1.1.3. The prose lists are gone in favour of one table per artifact, checked per schema key.
* The settings screen is three small functions instead of one long template. It renders byte-identical markup — the row shapes no longer thread a `$section_open` variable through the loop and branch three ways on which closing tag to emit. A setting whose group has no title used to save and take effect while never appearing on screen; that now fails a test.
* Internal: the schema is built once per request rather than on each of the ~50 reads; three strings that print after `init` are translated; `wpyeg_password_is_pwned()` becomes `wpyeg_defaults_password_is_pwned()` (the filter of that name is unchanged); phpcs warnings now fail the build in CI and locally, which run the same command; the settings-screen tests render the screen instead of grepping the template for punctuation.

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

= 1.2.3 =
Only affects sites running "Require authentication for all REST requests". That setting no longer blocks `/oembed/1.0`, so anonymous oEmbed requests succeed where they previously returned 401 — which is what stops other sites' embeds of your posts degrading to bare links. It is a deliberate loosening: if you want the endpoint closed to everyone, filter `wpyeg_public_rest_routes` to an empty array. oEmbed responses have their author name and author URL stripped whenever REST is closed, so the route cannot be used to recover the usernames that setting exists to hide.

= 1.2.2 =
Only affects sites that changed a session length (or turned "Remember Me" off) AND run another plugin that also sets session length. This plugin now registers that filter at priority 50 instead of 10, so where it previously lost to the other plugin it may now win, and logins can get longer or shorter accordingly. That is the intended fix — a length you set deliberately should not be decided by which plugin loaded last — but check the result if two plugins on the site both manage sessions. Nothing changes on a site at the shipped 2/14 defaults, where this plugin still does not register at all.

= 1.2.0 =
If your session lengths are still 2 and 14 days and "Remember Me" is enabled — the shipped defaults — this plugin no longer filters `auth_cookie_expiration` at all. Your sessions do not change, but another plugin that sets session length will now take effect where it was previously overruled at random. Change either length, or disable "Remember Me", and this plugin filters as before. The activation hook is also gone: existing sites keep every value already saved, and a setting you have never saved now follows the current default instead of the default that was current when you activated.

= 1.1.3 =
Two settings change their default to on: "Lowercase upload filenames" (new uploads only) and "Show generated image sizes" (a read-only panel). If you have saved the settings screen since 1.1.0 your stored choices are untouched. A site that has never saved it — including one upgraded from 1.0.x, which predates both settings — picks up the new defaults.

= 1.1.2 =
Password rules are now scoped by role, and Subscribers are exempt by default. An account whose roles are all in `wpyeg_weak_roles` (default `array( 'subscriber' )`) no longer faces the 15-character minimum, the blocklist, or the personal-context checks. Breach screening still applies to everyone. If you want the full policy for every role, filter `wpyeg_weak_roles` to an empty array.

= 1.1.1 =
Security headers are now compared instead of yielded to. If something upstream — a host, a CDN, another plugin — already sends a weaker `X-Frame-Options` than the one configured here, the configured value now wins, where before the upstream one did. A meaningless `X-Content-Type-Options` is corrected to `nosniff`. Check your response headers after upgrading if another layer sets them. Breach screening can now be switched off with `WPYEG_DISABLE_HIBP`.

= 1.1.0 =
Adds a default that removes `unfiltered_html` from everyone below administrator. Editors and Authors who could save raw script into a post no longer can. Turn "Limit unfiltered HTML to administrators" off under Settings → Better by Default if your workflow needs it. Session lengths are now in days; the old hours field is gone.
