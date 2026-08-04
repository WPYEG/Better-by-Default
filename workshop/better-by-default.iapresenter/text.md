# Better by Default

### WPYEG · Edmonton WordPress Meetup

*Secure defaults for every WordPress site.*

`a hands-on workshop · build the "sane-defaults" plugin`

Welcome to WPYEG. In this workshop we're building and reviewing a small plugin that defines and activates 32 sensible but little-known and seldom used defaults for WordPress sites in 2026. Whether you write PHP daily or just manage WordPress sites, you'll leave knowing why each default matters and how to enable (or disable) it. This workshop and plugin distils years of experience and new learning from a recent project that I've summed up in this workshop.

[This running text is the speaker script — in iA Presenter it stays in your notes, not on the slide.]

---

## WordPress is open by default; hosts vary in what they close.

	- **Usernames leak** — REST and author archives expose public author slugs that often resemble login names.
	- **XML-RPC exposed** — WordPress has been a refuge for this venerable interop protocol, which Jetpack still uses. If you don't use Jetpack or pingbacks, XML-RPC just adds to your attack surface.
	- **Dead weight loads** — Emoji scripts, version tags, and RSD links on every page. Do you need them?
	- **Spam surface invites** — Comments, pingbacks, and trackbacks are open by default: legacy cruft or heart and soul of the open web? They are forgotten treasures to some, but if you don't use them, turn them off. If you don't know what they are, find out!

**None of these are bugs or quite the security risks popular human and AI opinion allege.** They are defaults chosen for maximum compatibility on a 20+ year-old web application. You probably don't need them and can tighten up your own WordPress sites unless you're into the IndieWeb and radical open source anarchism, which I highly recommend. Probing the oldest parts of WordPress is a good way to learn some history and important fundamentals about how WordPress works — and how to keep it secure, fast, and pretty. 

---

## A "default" is just an opinionated filter behind a toggle.

```php
if ( wpyeg_defaults_enabled( 'restrict_rest_user_discovery' ) ) {
    add_filter( 'rest_endpoints', $hide_users_endpoint );
}   // that's the whole pattern, repeated across the plugin
```

In our demo plugin, a default is an `add_filter` behind an `if ( option )`. We have 32 settings built around that pattern.

---

## Hooks

	- **Actions** — "when you reach this moment, also DO this." 
	- **Filters** — "before you use this value, let me CHANGE it first." 

```php
add_filter( 'xmlrpc_enabled', '__return_false' );
```

WordPress is built to be interrupted at labelled moments (hooks) so you never edit core code. `__return_false` is a tiny built-in helper that just hands back false — perfect for switching a feature off.

---

## What wins when settings overlap?

	1. **`wp-config.php` constants** — Load first. When core treats one as authoritative, plugin settings cannot override it.
	2. **Must-use plugins** — Load before normal plugins, so their callbacks register first.
	3. **Normal plugins** — Load in `active_plugins` order — PMP before BBD on this demo site.
	4. **Hook priority** — Lower runs earlier; higher runs later. Ties keep registration order.

`effective behaviour = hard constants + every callback, in execution order`

This is a debugging model, not a universal “last plugin wins” rule. Constants cannot be redefined; filters pass a value through every callback; actions may accumulate effects. Load order establishes registration order, while hook priority establishes execution order. At equal priority, the callback registered later runs later — which is why the demo site's PMP-before-BBD plugin order can matter.

[Sources: WordPress Advanced Administration Handbook, [Must Use Plugins — Features](https://developer.wordpress.org/advanced-administration/plugins/mu-plugins/); WordPress Plugin Handbook, [Actions — Priority](https://developer.wordpress.org/plugins/hooks/actions/#priority); WordPress Code Reference, [`wp_get_active_and_valid_plugins()` — Source](https://developer.wordpress.org/reference/functions/wp_get_active_and_valid_plugins/); WordPress Advanced Administration Handbook, [Editing `wp-config.php`](https://developer.wordpress.org/advanced-administration/wordpress/wp-config/).]

---

## Eight categories of defaults

	1. **Security** — Make the attack surface smaller.
	2. **Updates** — Apply a deliberate core and translation update policy.
	3. **Content** — Close spam channels and potential info leaks.
	4. **Admin UX** — A calmer, faster, prettier dashboard.
	5. **Login** — Sessions and credentials.
	6. **Branding** — Own your login screen: make it secure and attractive.
	7. **Performance** — Trim the fat.
	8. **Email** — Say so when the site cannot send mail.

We'll spend most of our time on security and content, then move quickly through updates, UX, login, branding, and performance, and we'll end up with a plugin that covers them all.

---

# Section 1 — Security & Attack Surface

Every item in this section removes something an attacker can poke — usually in one line. The theme is simple: disable what you don't use. You can't exploit an endpoint that isn't there.

---

## Restrict REST API user discovery

	`restrict_rest_user_discovery` · default **yes**

```php
add_filter( 'rest_endpoints', function ( $ep ) {
    if ( ! is_user_logged_in() ) {
        unset( $ep['/wp/v2/users'] );
        unset( $ep['/wp/v2/users/(?P<id>[\d]+)'] );
    }
    return $ep;
} );
```

The `/wp/v2/users` endpoint exposes every public author's name, ID, profile link, and slug to anyone. Because an author slug often resembles a login name, that gives attack scripts a useful credential hint for free. By closing the user-list and numeric user routes for logged-out requests only, the editor and legitimate integrations keep working while anonymous enumeration attempts receive an ordinary 404. It's partly security by obscurity—not a substitute for strong passwords, MFA, or rate limiting—but it also rejects junk requests from bots that are up to no good. Why spend even a few extra electrons helping them? Author archives take the separate path we'll see later: a 301 to the homepage. If probes persist, a properly configured host can count those request patterns and ban the source IP with Fail2Ban or a similar tool such as CrowdSec, SSHGuard, or Defensia.

---

## Lock REST to logged-in users (opt-in)

	`disable_rest` · default **no**

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

This is the sledgehammer version of the slide before. Requiring auth for ALL REST calls stops anonymous scraping cold. It does *not* break the block editor, though — you're logged in there, and the editor authenticates with your cookie plus a REST nonce, so it sails through this filter. What it breaks is **anonymous** REST: front-end blocks that fetch data for logged-out visitors, embeds, search, and outside integrations. That's why it ships `off`. Not every default should default to `on`; some are opt-in because they trade functionality for safety. Usually it's a better tradeoff to restrict a few REST routes — like the users endpoint we just closed — than to lock ALL of them.

---

## Lock XML-RPC down by category

	`xmlrpc_allow_pingbacks` / `xmlrpc_allow_remote_publishing` / `xmlrpc_allow_multicall` · default **no** each · `block_xmlrpc_endpoint` **no**

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

XML-RPC is a legitimate but aging API. (Mad love to Dave Winer!) It's not a backdoor or an emergency. It is an old switchboard where every method is a phone line. Rather than rip out a connection that Jetpack or a publishing client may need, we unplug unused lines by category. Four switches, all off by default:

1. **Pingbacks** — drop `pingback.ping`, the clearest live nuisance and reflection-DDoS surface. A valid call performs database work, waits a second, and fetches the claimed source URL. Keep it if you're a crusty punk who loves the IndieWeb and everything before Facebook turned everything to shit, ca. 2005.
2. **Remote publishing** — drop the credential-authenticated blogging methods (`wp.*`, `metaWeblog.*`, `mt.*`, `blogger.*`), another password-guessing entrance when legacy clients are not needed. This also flips `xmlrpc_enabled` off and removes the RSD discovery link.
3. **`system.multicall`** — refuse a general batching wrapper with little established modern use. WordPress 4.4 prevented it from being used as a password-guessing multiplier, so the old “thousands of guesses” story is obsolete. (To this day, people say XML-RPC is some kind of open, free credential verification oracle — NOT TRUE.) Multicall can still batch other work, including pingbacks, but it does not enable pingback abuse.
4. **Block the endpoint** — the blunt hammer: `xmlrpc.php` returns 403 for everything. Prefer doing this at the CDN, WAF, or web server so the request never consumes PHP.

The first three are surgical and leave third-party registrations such as Jetpack's `jetpack.*` in place. That is not a compatibility guarantee: keep the endpoint reachable, leave Remote Publishing enabled until testing proves it unnecessary, and test the Jetpack connection and features after method changes. Block the endpoint only when nothing on the site speaks XML-RPC.

[Aside — what's "IXR"? The Incutio XML-RPC library. Simon Willison released it in September 2002, one of his first open-source projects, while blogging from the University of Bath; both WordPress *and* Drupal adopted it, and it then sat largely untouched for 15+ years — long enough to pick up a CVE. Willison went on to co-create Django (2003–05 at the Lawrence Journal-World), build Lanyrd (sold to Eventbrite in 2013) and Datasette (2017), and is now one of the most-read writers on LLMs.]

---

## Keep Application Passwords available

	`disable_application_passwords` · default **no** (available)

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

## Screen breaches without sending the password

	`require_strong_passwords` · default **yes**

```php
// hooked on user_profile_update_errors
if ( strlen( $pw ) < 15 ) {
    $errors->add( 'short', 'Use 15+ characters.' );
}

$hash   = strtoupper( sha1( $pw ) );
$prefix = substr( $hash, 0, 5 );
$suffix = substr( $hash, 5 );

// HIBP receives $prefix and returns matching suffixes.
// BBD compares $suffix locally; a match is rejected.
// Invalid or 128 KiB responses fail open.
```

[NIST SP 800-63B-4 § 3.1.1.2, Password Verifiers](https://pages.nist.gov/800-63-4/sp800-63b/authenticators/#passwordver) calls for at least 15 characters for single-factor passwords, no composition rules, and a blocklist of commonly used, expected, or compromised passwords. BBD first applies its length rule, a small local blocklist, and checks for the username or email name. It then screens the candidate against the [Have I Been Pwned Pwned Passwords range API](https://haveibeenpwned.com/API/v3#SearchingPwnedPasswordsByRange).

The privacy trick is **k-anonymity**. BBD computes the candidate's SHA-1 hash locally, sends HIBP only the first five hexadecimal characters, and receives roughly 800–1,000 suffixes that share that prefix. BBD compares the remaining 35 characters locally. The password and its full hash never leave WordPress. SHA-1 is only HIBP's lookup format here; WordPress still owns password storage and uses its normal password hashing.

BBD also sends `Add-Padding: true`, so response size does not disclose how many real matches exist; synthetic rows have a count of zero and are ignored. WordPress caps the response at 128 KiB with `limit_response_size`. Because a response reaching that cap may be truncated, capped, empty, malformed, failed, and non-200 responses are treated as unavailable and **fail open**. Only structurally valid prefix responses are cached for 12 hours. The local length, blocklist, and personal-context checks still apply. The same server-side validator covers profile changes, password resets, and REST user-password requests.

**And you can switch it off** — `WPYEG_DISABLE_HIBP` in `wp-config.php`, or the `wpyeg_disable_hibp` filter for a per-password decision. This is the only thing the whole plugin does that leaves your server, and everything on this slide is an argument that it is safe to do: hashed locally, five characters sent, padded response, nothing recoverable. All true, and none of it is an answer to "may I decline." Someone under a data-protection regime, on an air-gapped network, or just unwilling to make an outbound call on every password change does not owe anyone a justification. Which is the whole talk, pointed back at us: a default you cannot turn off is not a default, it is a requirement wearing a default's clothes. Every other setting here has a toggle. This one is the test of whether we meant it.

---

## Remove fingerprints, add headers

	`remove_version` **no** · `security_headers` **yes** · `frame_options` **SAMEORIGIN**

```php
remove_action( 'wp_head', 'wp_generator' );

add_filter( 'wp_headers', function ( $h ) {
  // compare, do not yield — see the notes
  $key = find_key( $h, 'X-Frame-Options' );
  if ( stronger( $want, $h[ $key ] ) )
    $h[ $key ] = $want;

  // one effective value, so correct it
  $h[ ctk( $h ) ] = 'nosniff';
  return $h;
} );
```

One default and one deliberate non-default — and the difference is the lesson. Hiding the version is **obscurity, not hardening**: it does not make an out-of-date site any safer, and it does not even hide much, since the version still leaks from asset query strings and feeds. What it genuinely buys is quieter logs. That is worth opting into, not worth shipping on and calling security — so it defaults to off. The headers are the opposite: real, low-risk defaults most sites can adopt without breaking anything:

- **`X-Content-Type-Options: nosniff`** — the browser must trust the declared `Content-Type` instead of guessing; kills "a `.txt` the browser decides to run as JavaScript" tricks.
- **`Referrer-Policy: strict-origin-when-cross-origin`** — sends the full URL within your own site, only the bare domain to other sites, and nothing on an HTTPS→HTTP downgrade; keeps tokens and private paths from leaking in the `Referer`.

**`X-Frame-Options` is deliberately a separate setting** (`wpyeg_frame_options`, default `SAMEORIGIN`), and that split is the point worth teaching. It is the only one of the three that can break a working site: blocking cross-origin framing also blocks *legitimate* embedding — a client intranet, a partner site, a preview tool — and it fails as a silent blank frame, which is a miserable thing to debug. Bundled with the other two, a site that needs to be embeddable would have to give up `nosniff` as well. Two headers with no real downside and one with a genuine trade-off do not belong behind one checkbox.

Note *how* they are applied, too, because this changed in 1.1.1. The original rule was "set only if unset", which sounds polite and is wrong: whatever arrived first won, so a host's permissive `X-Frame-Options` silently beat a deliberately configured `DENY`. The values are compared now, and the configured one replaces what is there only when it is strictly stronger — an unrecognised value, a deprecated `ALLOW-FROM` say, is left alone rather than guessed at. `X-Content-Type-Options` has exactly one effective value, so an existing header saying anything else is not a policy to respect, it is a header doing nothing; it gets corrected in place. And names are matched case-insensitively, because HTTP says header names are and PHP array keys say they are not — that mismatch used to make another plugin's `x-content-type-options` invisible here and add a second, conflicting line. `Referrer-Policy` still defers to whatever is already set: its tokens have no single strictness axis, so there is nothing to compare.

Be honest about the limit, though — PHP only sees headers set in PHP, so one added by nginx or a CDN is invisible here. Check the response, not just the code. A full Content-Security-Policy is a bigger conversation for another time!

---

## The filter that calls itself

	`limit_unfiltered_html_to_admins` · default **yes**

```php
add_filter( 'user_has_cap', function (
    $allcaps, $caps, $args, $user ) {

  if ( empty( $allcaps['unfiltered_html'] ) ) {
    return $allcaps;
  }

  $roles = isset( $user->roles )
    ? (array) $user->roles : array();

  // Read what you were handed. Never ask.
  if ( in_array( 'administrator', $roles, true )
    || ! empty( $allcaps['manage_options'] ) ) {
    return $allcaps;
  }

  $allcaps['unfiltered_html'] = false;
  return $allcaps;
}, PHP_INT_MAX - 1, 4 );
```

Editors hold `unfiltered_html` on a single-site install. That is enough to save a raw `<script>` into a post — not a vulnerability, a *capability*, and one most sites never consciously granted. This takes it back to administrators, plus Super Admins on multisite.

**The lesson here is the trap, not the policy.** `user_has_cap` fires on every capability check there is. So a filter hooked to it that *asks* a capability question calls itself, and calls itself again, until the stack blows. `current_user_can( 'manage_options' )` inside this callback is infinite recursion. So is `is_super_admin()` on single site, which is the one that catches people — it calls `has_cap( 'delete_users' )`, straight back in here. On multisite it reads the network list instead and is safe, which is exactly why the real code guards it with `is_multisite()`.

The fix is not cleverness, it is discipline: **decide from what you were handed.** `$user->roles` is already on the object. `$allcaps['manage_options']` is already resolved — it was computed before your filter ran. Read those. Never ask.

One more detail worth stealing: the priority is `PHP_INT_MAX - 1`, so this has close to the final say over other `user_has_cap` filters — a plugin that grants the capability back later in the chain would otherwise quietly win. Not `PHP_INT_MAX` itself, which leaves a slot for something that genuinely must run last.

Put this beside the comment-feed 404 and you have the pattern's two failure modes. There, a filter that looked complete and was not. Here, a filter that can destroy itself by asking an innocent question. Both of them pass every test you would think to write.

---

# Section 2 — Content & Public Surfaces

These reduce channels for spam and clean up the thin, duplicate URLs that bots and search engines get lost in.

---

## Disable comments, trackbacks & pingbacks

	`disable_comments` / `disable_pingbacks` / `disable_self_pingbacks` · default **yes** each

```php
add_filter( 'comments_open', '__return_false', 20 );
add_filter( 'pings_open',    '__return_false', 20 );
add_filter( 'comments_array',
            '__return_empty_array', 20 );
add_filter( 'get_comments_number', '__return_zero', 20 );
add_filter( 'comments_pre_query',
            $empty_comment_queries, 10, 2 );
add_filter( 'render_block',
            $suppress_comment_blocks, 10, 2 );
// + remove_post_type_support() on init
// + remove_menu_page( 'edit-comments.php' )
// + drop the admin-bar comments node
```

For many sites, comments are a spam magnet with little upside. Here we close them everywhere, hide existing threads, and drop the admin menu. If you want comments, leave this tuned off — but consider closing pingbacks and trackbacks, which are almost pure spam.

Closing comments is four jobs, not one: the template, the data, the editor, and the page. `comments_open` and `comments_array` answer the theme's comment template. `comments_pre_query` answers everything else — `/wp/v2/comments` most of all, which otherwise serves every comment the site has ever had. `allowed_block_types_all` takes the comment blocks out of the inserter, but the inserter only decides what an editor can add *next*, and a block theme has already put those blocks in its post template. That markup needs `render_block` to return an empty string, or every post prints a "Comments" heading over an empty wrapper and the site reads as broken rather than as one that deliberately has no comments. `get_comments_number` is the same gap one layer down: `wp_count_comments()` answers zero once the query filter is in place, but the theme's heading reads the post's cached `comment_count` and cheerfully prints "1 Comment" above a thread that renders nothing.

Returning an empty string rather than unregistering the block types is what keeps this reversible. The blocks stay registered, the theme's markup stays as its author wrote it, and turning the setting off brings the whole thing back with nothing to undo.

---

## A clean 404 needs `redirect_canonical` gone

	`disable_comments`, continued · the part only a real request catches

```php
add_action( 'template_redirect',
            $block_comment_feeds, 9 );

function block_comment_feeds() {
  if ( ! is_comment_feed() ) { return; }

  $wp_query->set_404();
  remove_action( 'template_redirect',
                 'redirect_canonical' );

  status_header( 404 );
  nocache_headers();
}
```

Dropping the `<link rel="alternate">` stops the feed being advertised; it does not stop it being served. `/comments/feed/` and `<post>/feed/` keep answering 200 to anyone who types the URL, and a crawler that saw one once keeps asking. With comment queries already emptied, they answer 200 with nothing — a live, crawlable endpoint whose only purpose is to say nothing. That is the worst of both.

`set_404()` re-runs `init_query_flags()`, which clears `is_feed()` along with everything else, so the template loader stops routing to `do_feed()` and renders the theme's 404 instead. That is why `is_comment_feed()` has to be tested first: a moment later there is nothing left to test.

And `redirect_canonical` has to go with it — this is the part worth remembering. It does not bail on a 404. It calls `redirect_guess_404_permalink()`, and against the query we have just emptied it answers `/post-name/feed/` with a 301 to `/post-name/feed/feed/`. Leaving it hooked turns a clean 404 into a redirect to a URL that has never existed, which is worse than the bug we set out to fix.

**Every filter-level test still passes. Only a real request catches it.** That is the honest limit of the pattern this whole talk is built on: a filter behind a toggle is a claim about one hook, and what a visitor actually gets is the sum of all of them. The recursion trap in `limit_unfiltered_html_to_admins` is the same lesson from the other direction — a `user_has_cap` filter that asks a capability question calls itself, so it has to decide from `$user->roles` and the already-resolved `$allcaps` and never ask. Test the request, not just the hook.

---

## Redirect author & attachment pages

	`disable_author_archives` / `redirect_attachment_pages` · **yes / yes**

```php
add_action( 'template_redirect', function () {
  if ( is_author() ) {
    wp_safe_redirect( home_url('/'), 301 );
    exit;
  }
  if ( is_attachment() ) {
    // parent post, else the FILE — never home
  }
} );
```

Like the REST user routes, author archives expose the authors' usernames in the URL, and attachment pages are near-empty media wrappers. Both dilute SEO and are targets for trouble. `template_redirect` fires before a template loads — the perfect place to bounce the unwanted requests. Same hook, two conditions.

Two details on the attachment half, because the obvious version of this is subtly wrong. Unattached media has no parent — and that is most of the Media Library — so the naive `else home_url()` points every one of those at your homepage, which search engines read as a soft 404. Fall back to the *file* instead, which is what core does. And skip the redirect entirely when the theme ships `attachment.php` or `image.php`: that theme built those pages deliberately (the photography case), and quietly bouncing past it deletes someone's feature.

Worth knowing core moved here too: WordPress 6.4 added `wp_attachment_pages_enabled`, off for new installs. So this default is not adding the redirect so much as choosing a better destination than the bare file.

---

## Disable the emoji script

	`disable_emojis` · default **yes**

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

	`title_only_admin_search` / `frontend_admin_bar_behavior` · **no / ''**

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

	`disable_remember_me` / `session_regular_days` / `remember_me_days` · default **no / 2 / 14**

```php
function session_length( $exp, $uid, $remember ) {
  return $remember
    ? 14 * DAY_IN_SECONDS
    : 2 * DAY_IN_SECONDS;
}

// Only when we differ from core's own 2 / 14.
if ( policy_is_custom() )
  add_filter( 'auth_cookie_expiration',
    'session_length', 10, 3 );
```

A normal login lasts 2 days; ticking "Remember Me" extends it to 14. Both lengths are in days, and the remembered one can never be shorter than the regular one. Shorten either, or hide the "Remember Me" checkbox entirely so every login uses the regular length. (Good idea for shared machines.) WordPress ships handy time constants like `DAY_IN_SECONDS`, so you never need to do the math.

Now look at the two lines that are *not* about session length, because they are the most portable thing in this deck. This callback ignores the `$exp` it was handed and returns its own number. A filter that **adds to** its input composes — several plugins can each contribute a header to `wp_headers` and all of them survive. A filter that **replaces** its input does not: WordPress runs all of them and keeps whichever answered last, the others do nothing at all, and every losing plugin's settings screen goes on displaying a number the site is not using. No error, nothing logged, nothing on Site Health.

Nothing in the API tells you which kind you are writing. Both are `add_filter()`. The difference is entirely in what your callback does with the argument it was given, and it decides whether your plugin can coexist with another one or silently fights it.

You cannot make a number filter additive. What you can do is decline the fights you have no stake in — our defaults *are* WordPress's own 2 and 14, so on a site that has not changed them we would be registering only to assert the answer core already gives. Hence the `if`. And the callback has a name rather than being anonymous, because `$wp_filter` can only report a callback that has one; an anonymous one shows up as `closure` in exactly the diagnostic you would run to work out who won.

When you write a filter callback, ask: *if a second plugin did exactly this, would both still work?* If the answer is no, you are setting policy, and only one plugin on the site can win.

---

# Section 4 — Branding & Performance

The last pair brands the login screen. Then we end with two performance levers to shave some weight off every page.

---

## Own the login screen

	`login_logo_behavior` · default **keep_default** (keep / remove / unlink / replace)

```php
// remove, unlink, or replace — a deliberate choice
add_action( 'login_head', $logo_css ); // hide or swap image

// any change points the link home (no separate toggle)
add_filter( 'login_headerurl', 'home_url' );
add_filter( 'login_headertext', fn() =>
            get_bloginfo( 'name' ) );
```

The login page is a WordPress site's staff entrance, and the default WordPress "W" on `wp-login.php` links to wordpress.org. Removing, unlinking, or replacing it keeps the login screen organizationally consistent and prevents the logo from sending users to an unexpected external site. Changing a site's login screen out of the box is intrusive, though, so the default is to **leave it alone**. Any opt-in change points the link home. Swap in a background-image to use the site's own logo.

---

## Throttle Heartbeat — and a default we deleted

	`throttle_heartbeat` · default **no** (opt-in)

```php
add_filter( 'heartbeat_settings', function ( $s ) {
  $s['interval'] = 60;
  return $s;
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
	4. **Toggle & re-check** — flip a switch off, reload, watch the behaviour change

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

## Schema map — security surfaces and credentials

| Setting key | Default | Core hook |
| --- | --- | --- |
| `restrict_rest_user_discovery` | `yes` | `rest_endpoints` |
| `disable_rest` | `no` | `rest_authentication_errors` |
| `xmlrpc_allow_pingbacks` | `no` | `xmlrpc_methods` / headers |
| `xmlrpc_allow_remote_publishing` | `no` | `xmlrpc_methods` / discovery |
| `xmlrpc_allow_multicall` | `no` | `wp_xmlrpc_server_class` |
| `block_xmlrpc_endpoint` | `no` | `template_redirect` |
| `disable_application_passwords` | `no` | `wp_is_application_passwords_available` |
| `require_strong_passwords` | `yes` | server-side password validation |

[These are the exact unprefixed keys stored inside the single `wpyeg_better_by_default` option. An allow-setting at `no` can still mean a protective behaviour is active: the three XML-RPC categories are unavailable by default, while the all-or-nothing endpoint block remains opt-in. Application Passwords remain available; strong-password validation is active.]

---

## Schema map — security policy and updates

| Setting key or owner | Default | Core hook / authority |
| --- | --- | --- |
| `remove_version` | `no` | `wp_head` / `the_generator` |
| `security_headers` | `yes` | `wp_headers` |
| `frame_options` | `SAMEORIGIN` | `wp_headers` |
| `disable_ai_connectors` | `yes` | `wp_supports_ai` / Connectors screen |
| `core_update_policy` | `minor` | automatic core-update filters |
| Translation files | inherit | WordPress / host / fleet tooling |
| Plugin and theme code | per-item | WordPress per-item choices |
| `WP_AUTO_UPDATE_CORE` | operator-owned | `wp-config.php` wins |

[This slide completes the security and update inventory. AI connectors are disabled through the WordPress 7.0 core gate and the Connectors screen is closed. Baseline headers and `SAMEORIGIN` ship separately because framing can break legitimate embeds. Update ownership stays explicit: BBD governs core release classes unless a constant wins, while language files and plugin/theme code remain with WordPress, the host, or fleet tooling.]

---

## Schema map — content and everyday UX

| Setting key | Default | Core hook |
| --- | --- | --- |
| `disable_comments` | `yes` | comments, UI, and post-type support |
| `disable_pingbacks` | `yes` | default ping options |
| `disable_self_pingbacks` | `yes` | `pre_ping` |
| `disable_author_archives` | `yes` | `template_redirect` |
| `redirect_attachment_pages` | `yes` | `template_redirect` |
| `disable_emojis` | `yes` | `init` removes emoji assets |
| `limit_unfiltered_html_to_admins` | `yes` | `user_has_cap` drops the cap for non-admins |
| `disable_post_passwords` | `no` | CSS hides the editor's password option |
| `force_classic_editor` | `no` | four editor gates answered false |
| `lowercase_upload_filenames` | `yes` | `sanitize_file_name` at priority 20 |
| `media_sizes_panel` | `yes` | read-only meta box on attachments |
| `title_only_admin_search` | `no` | `post_search_columns` |
| `frontend_admin_bar_behavior` | `''` | `show_admin_bar` |

[The schema group is authoritative: emoji removal lives under Content, not Performance. The three comment and pingback settings are separate because a site may keep comments while closing new-post pings and suppressing self-pingbacks. Title-only search and front-end admin-bar changes remain opt-in.]

---

## Schema map — login, branding, and performance

| Setting key or storage | Default | Core hook / role |
| --- | --- | --- |
| `disable_remember_me` | `no` | login UI / cookie expiration |
| `session_regular_days` | `2` | `auth_cookie_expiration` |
| `remember_me_days` | `14` | `auth_cookie_expiration` |
| `login_logo_behavior` | `keep_default` | login header presentation |
| `mail_deliverability_notice` | `yes` | `admin_notices` when the From address looks undeliverable |
| `throttle_heartbeat` | `no` | Heartbeat settings / enqueue |
| `wpyeg_better_by_default` | array | the only `wp_options` row |
| `DISALLOW_FILE_EDIT` | manual | `wp-config.php` |
| revisions / autosave constants | manual | `wp-config.php` |

[The visible names on earlier slides are schema keys, not separate WordPress options. All values live in the `wpyeg_better_by_default` array. Remembered sessions are capped at five days by default; regular sessions inherit core because zero means unchanged. The login logo and Heartbeat remain opt-in, and the three configuration constants stay above the plugin layer.]

---

# Thanks, WPYEG!

*Set your defaults wisely.*

`Files: sane-defaults.zip · wordpress-default-settings.md`

**Questions?** License GPL-3.0-or-later

[Hand out the zip and the reference doc. Invite everyone to add their own favourite default to the schema and share it back with the group.]
