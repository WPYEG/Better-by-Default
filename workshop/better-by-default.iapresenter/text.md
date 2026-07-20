# Better by Default

### WPYEG · Edmonton WordPress Meetup

*Secure defaults for every WordPress site.*

`a hands-on workshop · build the "sane-defaults" plugin`

Welcome to WPYEG. In this workshop we're building and reviewing a small plugin that defines and activates a dozen sensible defaults for WordPress sites in 2026. Whether you write PHP daily or just manage WordPress sites, you'll leave knowing why each default matters and how to enable (or disable) it. 

[This running text is the speaker script — in iA Presenter it stays in your notes, not on the slide.]

---

## WordPress is open by default; hosts vary in what they close.

	- **Usernames leak** — REST + author archives list every login name to anonymous visitors
	- **Legacy XML-RPC wide open** — pingback and `system.multicall` amplifiers answer by default
	- **Dead weight loads** — emoji scripts, version tags, and RSD links on every page
	- **Spam surface invites** — comments, pingbacks, and trackbacks open by default

None of these are bugs. They are defaults chosen for maximum compatibility on a 20+ year-old web application. You probably don't need them and can tighten up your own WordPress sites. This is also a good way to learn some important fundamentals about how WordPress works and how to keep it secure, fast, and pretty. 

---

## A "default" is just an opinionated filter behind a toggle.

```php
if ( wpyeg_defaults_enabled( 'restrict_rest_user_discovery' ) ) {
    add_filter( 'rest_endpoints', $hide_users_endpoint );
}   // that's the whole pattern, repeated ~20 times
```

In our demo plugin, a default is an `add_filter` behind an `if ( option )`. We have twenty of them.

---

## Hooks & filters

	- **Action** — "when you reach this moment, also DO this." 
	- **Filter** — "before you use this value, let me CHANGE it first." 

```php
add_filter( 'xmlrpc_enabled', '__return_false' );
```

WordPress is built to be interrupted at labeled moments (hooks) so you never edit core code. `__return_false` is a tiny built-in helper that just hands back false — perfect for switching a feature off.

---

## Six categories of defaults

	1. **Security** — shrink the attack surface
	2. **Content** — close spam channels & info leaks
	3. **Admin UX** — a calmer, faster dashboard
	4. **Login** — sessions & credentials
	5. **Branding** — own the login screen
	6. **Performance** — trim the page weight

We'll spend most of our time on security and content, then move quickly through UX, login, branding, and performance, and end up with a plugin that covers them all.

---

# Section 1 — Security & Attack Surface

Every item in this section removes something an attacker can poke — usually in one line. The theme is simple: disable what you don't use. You can't exploit an endpoint that isn't there.

---

## Restrict REST API user discovery

	`wpyeg_restrict_rest_user_discovery` · default **yes**

```php
add_filter( 'rest_endpoints', function ( $ep ) {
    if ( ! is_user_logged_in() ) {
        unset( $ep['/wp/v2/users'] );
        unset( $ep['/wp/v2/users/(?P<id>[\d]+)'] );
    }
    return $ep;
} );
```

The `/wp/v2/users` endpoint hands out every author's login name to anyone — half of a brute-force guess, for free. Author enumeration is step one of many attack scripts. By closing it for logged-out requests only, the editor and legit integrations will keep working. It's arguably an example of security-by-obscurity, but it also prevents a lot of junk traffic and bots that are up to no good.

---

## Lock REST to logged-in users (opt-in)

	`wpyeg_disable_rest` · default **no**

```php
add_filter( 'rest_authentication_errors',
  function ( $result ) {
    if ( ! empty( $result ) ) return $result;
    if ( ! is_user_logged_in() ) {
      return new WP_Error(
        'rest_not_logged_in', 'Auth required.',
        array( 'status' => 401 ) );
    }
    return $result;
} );
```

The sledgehammer version of the slide before. Requiring auth for ALL REST calls stops anonymous scraping cold. It does *not* break the block editor, though — you're logged in there, and the editor authenticates with your cookie plus a REST nonce, so it sails through this filter. What it breaks is **anonymous** REST: front-end blocks that fetch data for logged-out visitors, embeds, search, and outside integrations. That's why it ships off. Not every default should default to on; some are opt-in because they trade functionality for safety. Usually it's a better tradeoff to restrict a few REST routes — like the users endpoint we just closed — than to lock ALL of them.

---

## Lock XML-RPC down by category

	`wpyeg_xmlrpc_allow_pingbacks` / `_remote_publishing` / `_multicall` · default **no** each · (+ `_block_xmlrpc_endpoint`)

```php
// each category off → remove its methods
add_filter( 'xmlrpc_methods', function ( $m ) {
  if ( ! allow( 'pingbacks' ) )
    unset( $m['pingback.ping'] );
  if ( ! allow( 'remote_publishing' ) )
    // drop wp.* metaWeblog.* mt.* blogger.*
  return $m;
} );

// multicall can't be filtered off (IXR re-adds it)
// → swap in a server that refuses it
add_filter( 'wp_xmlrpc_server_class', $refuse_multicall );
```

XML-RPC isn't one door — it's an old switchboard, and every method is a phone line. Rather than rip the box off the wall, we unplug lines by category. Four switches, all off by default:

1. **Pingbacks** — drop `pingback.ping`, a spam and reflection-DDoS relay.
2. **Remote publishing** — drop the credential-authenticated blogging methods (`wp.*`, `metaWeblog.*`, `mt.*`, `blogger.*`), the classic brute-force target. This also flips `xmlrpc_enabled` off and removes the RSD discovery link.
3. **`system.multicall`** — the amplifier that batches thousands of login guesses into one request. You can't just unset it: `IXR_Server::setCallbacks()` re-adds it after the filter runs, so we swap in a replacement server that refuses it.
4. **Block the endpoint** — the blunt hammer: `xmlrpc.php` returns 403 for everything.

The first three are surgical — they leave third-party methods like Jetpack's `jetpack.*` connected, so the endpoint stays usable. The fourth nukes the lot, which breaks Jetpack; reach for it only if nothing on the site speaks XML-RPC.

[Aside — what's "IXR"? The Incutio XML-RPC library. Simon Willison released it in September 2002, one of his first open-source projects, while blogging from the University of Bath; both WordPress *and* Drupal adopted it, and it then sat largely untouched for 15+ years — long enough to pick up a CVE. Willison went on to co-create Django (2003–05 at the Lawrence Journal-World), build Lanyrd (sold to Eventbrite in 2013) and Datasette (2017), and is now one of the most-read writers on LLMs.]

---

## Keep Application Passwords available

	`wpyeg_disable_application_passwords` · default **no** (available)

```php
// available by default — prohibit only if opted in
if ( wpyeg_defaults_enabled(
       'disable_application_passwords' ) ) {
  add_filter(
    'wp_is_application_passwords_available',
    '__return_false'
  );
}
```

This is an existing default we *don't* lock down. An Application Password is like a spare key cut for one app: each app gets its own hashed key, so you can revoke one without touching the others or changing the account password. That makes it the safer REST credential and the only one core accepts for REST Basic Auth. So they are good — they just don't have a toggle in WordPress core settings. You might need to prohibit application passwords on a site that forbids non-interactive credentials, but switching them off doesn't stop people connecting things, it just pushes them to worse habits, like sharing an account.

---

## Require strong passwords

	`wpyeg_require_strong_passwords` · default **yes**

```php
// hooked on user_profile_update_errors
if ( strlen( $pw ) < 15 ) {
    $errors->add( 'short', 'Use 15+ characters.' );
}

// screen against known breaches (HIBP) —
// length + screening, not composition rules
if ( wpyeg_password_is_pwned( $pw ) ) {
    $errors->add( 'pwned', 'Seen in a breach.' );
}
```

The rules changed recently, and most people didn't notice: NIST 800-63B and OWASP now say **length plus breach screening beats composition rules**. Forcing upper/lower/number/symbol just herds everyone to `Password1!` — predictable, not strong. So we require 15+ characters and screen every new password against Have I Been Pwned — by **k-anonymity**, meaning only the first five characters of the SHA-1 hash ever leave the site; HIBP returns every hash sharing that prefix and the match happens locally, so the password itself is never transmitted. If HIBP is unreachable the check **fails open** rather than locking someone out of a password change. All enforced server-side on save and reset: the JS meter is UX; the server rule is the wall.

---

## Remove fingerprints, add headers

	`wpyeg_remove_version` **no** · `wpyeg_security_headers` **yes** · `wpyeg_frame_options` **SAMEORIGIN**

```php
remove_action( 'wp_head', 'wp_generator' );

add_filter( 'wp_headers', function ( $h ) {
  // only fill in what nothing else set
  if ( ! isset( $h['X-Content-Type-Options'] ) )
    $h['X-Content-Type-Options'] = 'nosniff';
  if ( ! isset( $h['Referrer-Policy'] ) )
    $h['Referrer-Policy'] =
      'strict-origin-when-cross-origin';
  return $h;
} );

// framing is its own setting — see the notes
```

One default and one deliberate non-default — and the difference is the lesson. Hiding the version is **obscurity, not hardening**: it does not make an out-of-date site any safer, and it does not even hide much, since the version still leaks from asset query strings and feeds. What it genuinely buys is quieter logs. That is worth opting into, not worth shipping on and calling security — so it defaults to off. The headers are the opposite: real, low-risk defaults most sites can adopt without breaking anything:

- **`X-Content-Type-Options: nosniff`** — the browser must trust the declared `Content-Type` instead of guessing; kills "a `.txt` the browser decides to run as JavaScript" tricks.
- **`Referrer-Policy: strict-origin-when-cross-origin`** — sends the full URL within your own site, only the bare domain to other sites, and nothing on an HTTPS→HTTP downgrade; keeps tokens and private paths from leaking in the `Referer`.

**`X-Frame-Options` is deliberately a separate setting** (`wpyeg_frame_options`, default `SAMEORIGIN`), and that split is the point worth teaching. It is the only one of the three that can break a working site: blocking cross-origin framing also blocks *legitimate* embedding — a client intranet, a partner site, a preview tool — and it fails as a silent blank frame, which is a miserable thing to debug. Bundled with the other two, a site that needs to be embeddable would have to give up `nosniff` as well. Two headers with no real downside and one with a genuine trade-off do not belong behind one checkbox.

Note the `isset()` guards too: a managed host or CDN often sets these already, and we should not fight that layer. Be honest about the limit, though — PHP only sees headers set in PHP, so one added by nginx or a CDN is invisible here. Check the response, not just the code. A full Content-Security-Policy is a bigger conversation for another time!

---

# Section 2 — Content & Public Surfaces

These reduce channels for spam and clean up the thin, duplicate URLs that bots and search engines get lost in.

---

## Disable comments, trackbacks & pingbacks

	`wpyeg_disable_comments` · default **yes**

```php
add_filter( 'comments_open', '__return_false', 20 );
add_filter( 'pings_open',    '__return_false', 20 );
add_filter( 'comments_array',
            '__return_empty_array', 20 );
// + remove_post_type_support() on init
// + remove_menu_page( 'edit-comments.php' )
// + drop the admin-bar comments node
```

For many sites, comments are a spam magnet with little upside. Here we close them everywhere, hide existing threads, and drop the admin menu. If you want comments, leave this tuned off — but consider closing pingbacks and trackbacks, which are almost pure spam.

---

## Redirect author & attachment pages

	`wpyeg_disable_author_archives` / `wpyeg_redirect_attachment_pages` · **yes / yes**

```php
add_action( 'template_redirect', function () {
  if ( is_author() ) {
    wp_safe_redirect( home_url('/'), 301 );
    exit;
  }
  if ( is_attachment() ) {
    // 301 to parent post, or home
  }
} );
```

Like the REST user routes, author archives expose the authors' usernames in the URL, and attachment pages are near-empty media wrappers. Both dilute SEO and are targets for trouble. `template_redirect` fires before a template loads — the perfect place to bounce the unwanted requests. Same hook, two conditions.

---

## Disable the emoji script

	`wpyeg_disable_emojis` · default **yes**

```php
add_action( 'init', function () {
  remove_action( 'wp_head',
    'print_emoji_detection_script', 7 );
  remove_action( 'wp_print_styles',
    'print_emoji_styles' );
  // ...admin + feed + mail variants too
  add_filter( 'emoji_svg_url', '__return_false' );
} );
```

WordPress core injects an emoji-detection script and inline CSS on every page load, plus a DNS-prefetch hint. Modern browsers render emoji natively, so this is pure dead weight. Small win, but it's on literally every page — a good example of a "why is this even on?" default that's not included in core settings.

---

# Section 3 — Admin UX & Login Sessions

Now the quality-of-life defaults. These are more about your daily user experience and session safety than raw hardening.

---

## Faster search, quieter admin bar

	`wpyeg_title_only_admin_search` / `wpyeg_frontend_admin_bar_behavior` · **no / ''**

```php
// title-only admin search — narrow the COLUMNS
add_filter( 'post_search_columns',
  function ( $cols, $s, $q ) {
    if ( is_admin() && $q->is_main_query() )
        return array( 'post_title' );  // titles only
    return $cols;                       // front-end untouched
  }, 10, 3 );

// hide bar for non-admins
add_filter( 'show_admin_bar', fn( $s ) =>
  current_user_can('manage_options') ? $s : false );
```

Search the admin post list on a big site and WordPress reads every word of every post — like finding a book by reading the whole library. Title-only search checks just the spines, and it's far faster. The craft is in the *how*: `post_search_columns` (WP 6.2+) narrows the columns instead of rewriting the whole SQL clause, so core's term parsing and the logged-out password guard stay intact. Scope the filter; don't bulldoze the query.

---

## Right-size the login session

	`wpyeg_remember_me_days` / `wpyeg_session_regular_hours` · default **5 / 0**

```php
add_filter( 'auth_cookie_expiration',
  function ( $exp, $uid, $remember ) {
    if ( $remember ) {
      return 5 * DAY_IN_SECONDS;
    }
    return 12 * HOUR_IN_SECONDS;
  }, 10, 3 );
```

If you click the "Remember Me" checkbox when you log in, you stay logged in for 14 days. Cap that extended session length, optionally shorten the regular session, or hide the "Remember Me" checkbox entirely. (Good idea for shared machines.) One filter covers all three. WordPress ships handy time constants like `DAY_IN_SECONDS`, so you never need to do the math.

---

# Section 4 — Branding & Performance

The last pair brands the login screen. Then we end with two performance levers to shave some weight off every page.

---

## Own the login screen

	`wpyeg_login_logo_behavior` · default **keep_default** (keep / remove / unlink / replace)

```php
// remove, unlink, or replace — a deliberate choice
add_action( 'login_head', $logo_css ); // hide or swap image

// any change points the link home (no separate toggle)
add_filter( 'login_headerurl', 'home_url' );
add_filter( 'login_headertext', fn() =>
            get_bloginfo( 'name' ) );
```

The login page is a WordPress site's staff entrance — the one door you and your clients actually log in through — and by default the welcome mat links to someone else's house! That little WordPress "W" on `wp-login.php` points out to wordpress.org. Changing a site's login screen out of the box is intrusive, though, so the default is to **leave it alone**. Removing, unlinking, or replacing the logo is an opt-in — and whichever you choose, the link always points home. Swap in a background-image to drop in the site's own logo. 

---

## Throttle Heartbeat — and a default we deleted

	`wpyeg_throttle_heartbeat` · default **no** (opt-in)

```php
add_filter( 'heartbeat_settings', fn( $s ) => {
  $s['interval'] = 60; return $s;
} );

// Deferring scripts is NOT a setting here. Since WP 6.3:
wp_enqueue_script( 'front', $src, array(), '1.0',
  array( 'strategy' => 'defer' ) );
```

The Heartbeat API polls `admin-ajax` every 15–60s. Throttle it to ease up on weak shared hosting. The more interesting half of this slide is the toggle that *used* to be here. We shipped a "defer front-end scripts" default that hooked `script_loader_tag` and string-replaced ` src=` with ` defer src=` on every handle. It had to skip jQuery core, and it still broke anything expecting a particular execution order — because a blanket filter cannot know which scripts are safe to defer. WordPress 6.3 added a per-script loading strategy, so core now answers this precisely, at the point of enqueue, where the person who wrote the script decides. Keeping our version would have meant teaching a workaround for a problem the platform already solved. Deleting a default is a legitimate result.

---

## Three things a plugin can't toggle

```php
define( 'DISALLOW_FILE_EDIT', true );  // no in-dashboard code editor
define( 'AUTOSAVE_INTERVAL', 120 );    // gentler autosave (seconds)
define( 'WP_POST_REVISIONS', 10 );     // cap revision-table bloat
```

	- **Kills the theme/plugin editor** — a stolen admin login can't rewrite your PHP
	- **Writes to the DB less often** — fewer autosave revisions during long edits
	- **Keeps revisions in check** — ten per post instead of unbounded growth

Some defaults live in `wp-config.php`, above the plugin layer, because they must load before plugins do. They can't be options — so document them as manual steps in your onboarding checklist and put them in your standard wp-config template.

---

## How the plugin is built

	1. **schema()** — one array: every setting, its default, type & group. The single source of truth.
	2. **settings page** — loops the schema to render toggles under Settings → Better by Default.
	3. **bootstrap()** — for each *enabled* key, wires its `add_filter` / `add_action` to the right hook.

```php
$stored = get_option( 'wpyeg_better_by_default' );   // read once
foreach ( wpyeg_defaults_schema() as $key => $field ) { /* render + wire */ }
```

[The design lesson is a data-driven plugin. Adding a new default equals one array entry plus one `if`-block in bootstrap — no new settings-page code. That's the pattern to steal for your own projects.]

---

## Hands-on: install & flip switches

	1. **Upload the plugin** — Plugins → Add New → Upload Plugin → choose `sane-defaults.zip` → Activate
	2. **Open the settings** — Settings → Better by Default; every toggle grouped by category
	3. **Verify a default** — visit `/wp-json/wp/v2/users` logged out → 401 or empty, not a list of usernames
	4. **Toggle & re-check** — flip a switch off, reload, watch the behavior change

```bash
# prefer the terminal?
wp plugin install ./sane-defaults.zip --activate
```

[Do this live if there's a sandbox. The `/wp-json/wp/v2/users` check is the crowd-pleaser — the before/after is instantly visible. For the terminal crowd, the WP-CLI one-liner installs and activates from the zip in one shot; swap the local path for a URL if the zip is hosted.]

---

## Your turn: add one new default

*Goal: disable the WordPress dashboard "Welcome" panel. Two small edits — no new settings-page code.*

```php
// 1) add a schema entry in wpyeg_defaults_schema()
'hide_welcome_panel' => array(
    'default' => 'yes', 'type' => 'toggle', 'group' => 'ux',
    'label' => 'Hide dashboard welcome panel',
),

// 2) wire it inside wpyeg_defaults_bootstrap()
if ( wpyeg_defaults_enabled( 'hide_welcome_panel' ) ) {
    remove_action( 'welcome_panel', 'wp_welcome_panel' );
}
```

[A great confidence-builder: it proves the data-driven pattern. Touch two spots and a real feature toggles. If time is short, walk it through verbally instead of live.]

---

## The cheat sheet — defaults that ship ON

| Default | Core hook | Category |
| --- | --- | --- |
| Restrict REST user discovery | `rest_endpoints` | Security |
| Lock down XML-RPC by category | `xmlrpc_methods` / `wp_xmlrpc_server_class` | Security |
| Require strong passwords (15+ / breach-screened) | `user_profile_update_errors` | Security |
| Send baseline security headers | `wp_headers` | Security |
| Disable comments & pingbacks | `comments_open` / `pings_open` | Content |
| Redirect author + attachment pages | `template_redirect` | Content / SEO |
| Disable emoji script | `init` (remove_action) | Performance |

[This is your screenshot slide — everything on-by-default in one view, mapped to the core hook. Three deliberate *non*-defaults worth calling out: Application Passwords stay **available** (the safer REST credential), the login logo is **left alone** unless you opt in, and removing the version fingerprint is **off**, because it is obscurity rather than hardening. All three are choices, not oversights — and the last one is the honest test of the whole talk: if a default doesn't actually make the site safer, don't ship it as security.]

---

# Thanks, WPYEG!

*Set your defaults wisely.*

`Files: sane-defaults.zip · wordpress-default-settings.md`

**Questions?** License GPL-3.0-or-later

[Hand out the zip and the reference doc. Invite everyone to add their own favorite default to the schema and share it back with the group.]
