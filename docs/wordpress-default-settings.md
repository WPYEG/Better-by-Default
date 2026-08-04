# WordPress "Sane Defaults" Reference

A menu of default settings that can be applied to just about any WordPress install to
tighten security, trim attack surface, clean up UX, and shave weight off the front end.

Each item lists the companion plugin's unprefixed **schema key**, its **default value**, a short
**description**, and a **code snippet** illustrating the WordPress behaviour behind that setting.

> Built for the **WPYEG — Edmonton WordPress Meetup** hands-on workshop. The companion
> `sane-defaults` plugin wires every one of these behind a toggle.

**How to read this:** Better by Default stores one WordPress option,
`wpyeg_better_by_default`, as an array. The keys below are entries in that array, not separate
rows in `wp_options`. Runtime code reads them through `wpyeg_defaults_get( 'key' )` or
`wpyeg_defaults_enabled( 'key' )`. Snippets are shown mostly unwrapped so the underlying core
hook remains easy to see.

A few items are flagged **plugin-specific** — they have no stable WordPress core equivalent
and depend on your own plugin's logic.

---

> **Running this alongside another defaults plugin?** See
> [When two plugins set the same default](when-two-plugins-set-the-same-default.md).
> Two plugins setting the same value do not error — WordPress keeps whichever
> answered last, and the loser's settings screen goes on showing a number the
> site is not using.

---

## 1. Security & Attack-Surface Reduction

### Restrict REST API User Discovery
- **Setting key:** `restrict_rest_user_discovery`
- **Default:** `yes`
- **Why:** The `/wp/v2/users` endpoint leaks usernames (author slugs) to anonymous visitors,
  which hands attackers half of every brute-force credential. Closing it to logged-out users
  keeps the API working for authenticated tools while shutting the enumeration door.

```php
add_filter( 'rest_endpoints', function ( $endpoints ) {
    if ( ! is_user_logged_in() ) {
        unset( $endpoints['/wp/v2/users'] );
        unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
    }

    return $endpoints;
} );
```

### Disable REST API for Anonymous Requests
- **Setting key:** `disable_rest`
- **Default:** `no` *(leave off unless the site is a pure brochure site — anonymous front-end
  blocks, embeds, and outside integrations rely on unauthenticated REST; the logged-in block
  editor is unaffected, since it authenticates with a cookie plus a REST nonce)*
- **Why:** Fully disabling REST is a blunt instrument. The safer posture is to require
  authentication for all REST calls, which blocks anonymous scraping without breaking the
  editor for logged-in users.

```php
add_filter( 'rest_authentication_errors', function ( $result ) {
    // Only an existing error short-circuits. Treating any truthy $result as
    // "already authenticated" is the trap: core's rest_cookie_check_errors()
    // returns true after calling wp_set_current_user( 0 ) when a cookie carries
    // no X-WP-Nonce, so `if ( ! empty( $result ) ) return $result;` reads that
    // true as success and dispatches the request as user 0.
    if ( is_wp_error( $result ) ) {
        return $result;
    }

    if ( ! is_user_logged_in() ) {
        return new WP_Error(
            'rest_not_logged_in',
            __( 'REST API restricted to authenticated users.' ),
            array( 'status' => 401 )
        );
    }

    return $result;
}, PHP_INT_MAX );   // After core resolves app-password (90) and cookie (100) auth.

// Closing a door and taking down the sign are two separate jobs: with the filter
// above in place the page still advertises the endpoint, and core prints the
// discovery link three different ways.
remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
remove_action( 'template_redirect', 'rest_output_link_header', 11 );
remove_action( 'xmlrpc_rsd_apis', 'rest_output_rsd' );
```

### Harden XML-RPC (per-category, not all-or-nothing)
- **Setting key:** `xmlrpc_allow_pingbacks` / `xmlrpc_allow_remote_publishing` / `xmlrpc_allow_multicall` / `block_xmlrpc_endpoint`
- **Defaults:** `no` / `no` / `no` / `no`
- **Why:** XML-RPC is a legitimate but aging API. On a current, patched site it is not a
  backdoor or emergency-level vulnerability; it is additional attack and resource-consumption
  surface whose value is site-specific. Incoming pingbacks remain the clearest live risk,
  remote-publishing methods are another credential-authentication entrance, and
  `system.multicall` is a general batching wrapper whose security value is now modest.

  `add_filter( 'xmlrpc_enabled', '__return_false' )` is a common trap: despite its name, it only
  disables methods that require authentication. It does not block `xmlrpc.php`, pingbacks, or
  custom unauthenticated methods. The better model is to remove unused WordPress methods **by
  category**, keep the endpoint reachable when an integration needs it, and block or rate-limit
  unwanted traffic at the CDN/WAF/web-server edge.

Three independent categories, all off by default:

```php
add_filter( 'xmlrpc_methods', function ( $methods ) {
    // 1. Incoming pingbacks.
    if ( ! wpyeg_defaults_enabled( 'xmlrpc_allow_pingbacks' ) ) {
        unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );
    }
    // 2. Remote publishing (blogging apps) — the credential-authenticated methods.
    if ( ! wpyeg_defaults_enabled( 'xmlrpc_allow_remote_publishing' ) ) {
        foreach ( array_keys( $methods ) as $name ) {
            if ( preg_match( '/^(wp|metaWeblog|mt|blogger)\./', (string) $name ) ) {
                unset( $methods[ $name ] );
            }
        }
    }
    return $methods;
}, PHP_INT_MAX );

// Remote publishing also gates xmlrpc_enabled and the RSD discovery link.
add_filter( 'xmlrpc_enabled', function ( $enabled ) {
    return wpyeg_defaults_enabled( 'xmlrpc_allow_remote_publishing' ) ? $enabled : false;
} );

// Pingbacks off → drop the X-Pingback discovery header.
add_filter( 'wp_headers', function ( $headers ) {
    if ( ! wpyeg_defaults_enabled( 'xmlrpc_allow_pingbacks' ) ) {
        unset( $headers['X-Pingback'] );
    }
    return $headers;
} );
```

`system.multicall` **can't be removed with the `xmlrpc_methods` filter** — `IXR_Server::setCallbacks()`
re-adds it after the filter runs — so refuse it with a replacement server. This is modest
defence-in-depth against batching, not a password control: WordPress 4.4 prevented it from being
used as a password-guessing multiplier, because after the first failed authentication in one
XML-RPC request, later attempts fail without testing more credentials. Multicall can still batch other work, including pingback calls, but
pingbacks are also directly callable and do not depend on it. See
[WordPress Trac #34336](https://core.trac.wordpress.org/ticket/34336).

```php
add_filter( 'wp_xmlrpc_server_class', function ( $class ) {
    if ( wpyeg_defaults_enabled( 'block_xmlrpc_endpoint' ) ) {
        return 'Wpyeg_Blocked_XMLRPC_Server';     // serve_request() → 403 for everything
    }
    if ( ! wpyeg_defaults_enabled( 'xmlrpc_allow_multicall' ) ) {
        return 'Wpyeg_Multicall_Disabled_Server'; // extends wp_xmlrpc_server, overrides multiCall() → IXR_Error
    }
    return $class;
} );
```

> **Jetpack:** Jetpack currently requires a publicly accessible XML-RPC endpoint, so never apply
> the blanket 403 on a Jetpack site. Turning off incoming pingbacks is the low-risk change. Removing
> core publishing methods or refusing multicall leaves `jetpack.*` registrations untouched, but
> method registration alone is not a compatibility guarantee; test the Jetpack connection and the
> features the site uses. Keep Remote Publishing enabled until that testing proves it unnecessary.
> A plugin-level 403 still boots WordPress and occupies PHP; only an edge block prevents the request
> from reaching PHP. See [Jetpack's current requirements](https://jetpack.com/support/getting-started-with-jetpack/).
> **`demo.*`:** the inert `demo.sayHello`/`demo.addTwoNumbers` methods still confirm XML-RPC is
> live to a scanner, so the companion plugin always drops them — no toggle:
> `unset( $methods['demo.sayHello'], $methods['demo.addTwoNumbers'] )`.

### Application Passwords — leave available (don't reflexively disable)
- **Setting key:** `disable_application_passwords`
- **Default:** `no` *(available)*
- **Why:** The reflexive advice is "disable them," but that's usually the wrong call.
  Application Passwords are hashed, per-application, individually revocable credentials that
  carry the same access as the owning account — and core supports them for REST and XML-RPC.
  They normally bypass an interactive 2FA challenge, so create them on a least-privileged account.
  Prohibiting them doesn't remove an
  integration's need; it pushes people to a third-party auth plugin or a shared login —
  credentials that are harder to isolate and revoke and that bypass 2FA the same way. Keep them
  available; offer an opt-in to prohibit them for sites whose policy forbids non-interactive
  credentials.
  See the [WordPress Application Passwords documentation](https://developer.wordpress.org/advanced-administration/security/application-passwords/).

```php
// Off by default — the feature stays available. Only prohibit when explicitly opted in.
add_filter( 'wp_is_application_passwords_available', function ( $available ) {
    return wpyeg_defaults_enabled( 'disable_application_passwords' ) ? false : $available;
} );
```
> **Note:** they authenticate REST/XML-RPC without the login form, so a 2FA companion never
> challenges them. That's a real trade — but it's core behaviour, and the alternatives are worse.
> Use core's `wp_is_application_passwords_available_for_user` filter to withhold them per account
> (e.g. from human 2FA accounts) if that gap matters.

### Require Strong Passwords
- **Setting key:** `require_strong_passwords`
- **Default:** `yes`
- **Why:** Core ships a password meter but won't *enforce* strength. Enforce it server-side —
  but follow [NIST SP 800-63B-4 § 3.1.1.2, Password
  Verifiers](https://pages.nist.gov/800-63-4/sp800-63b/authenticators/#passwordver): favour
  **length and breached-password screening** over forced composition rules. The publication
  prohibits upper/lower/number/symbol composition requirements.

**What the breach check sends.** The password is hashed locally with SHA-1 and only the
**first five characters** of that hash go to the [HIBP Pwned Passwords range
API](https://haveibeenpwned.com/API/v3#SearchingPwnedPasswordsByRange). The remaining 35 are
compared against the returned suffixes on your own server, so neither the password nor its
full hash ever leaves the site. The response is padded, so its size does not reveal how many
real matches came back. SHA-1 is only the lookup format the API uses — WordPress still owns
password storage and its own hashing.

An outage, a truncated response, or malformed range data all **fail open**: the check answers
"not breached" and the password is allowed. A breach-data outage never blocks a password
change. Switch the lookup off entirely with the `WPYEG_DISABLE_HIBP` constant or the
`wpyeg_disable_hibp` filter; the length, blocklist and personal-context rules still apply.

This is the detail that used to live in the settings field. It is here, and in `readme.txt`
for the wordpress.org external-services disclosure, because a third copy in a narrow admin
column is not compliance — it is one more thing to keep in step.

```php
add_action( 'user_profile_update_errors', 'wpyeg_enforce_strong_password', 10, 3 );
add_action( 'validate_password_reset', function ( $errors, $user ) {
    wpyeg_enforce_strong_password( $errors, true, $user );
}, 10, 2 );

function wpyeg_enforce_strong_password( $errors, $update, $user ) {
    // Measure exactly what core will store. edit_user() trims the password and
    // saves the trimmed value, but fires this hook with $_POST untouched — so
    // validating the raw string lets "              a" pass a length rule while
    // core saves a one-character password.
    $password = isset( $_POST['pass1'] ) ? trim( (string) wp_unslash( $_POST['pass1'] ) ) : '';

    if ( '' === $password ) {
        return; // No password change requested (or whitespace-only).
    }

    // Breach screening first, before any exemption: a password already in a
    // breach corpus costs its owner nothing to avoid, whatever the account can do.
    if ( wpyeg_defaults_password_is_pwned( $password ) ) {
        $errors->add( 'pass_pwned', __( '<strong>Error:</strong> Choose a password that has not appeared in a known data breach.' ) );
        return;
    }

    // NIST SP 800-63B-4 § 3.1.1.2 favours length over composition. Count
    // characters, not bytes: strlen() reads eight emoji as 32 and waves through
    // a password far shorter than the rule intends.
    $length = function_exists( 'mb_strlen' ) ? mb_strlen( $password ) : strlen( $password );

    if ( $length < 15 ) {
        $errors->add( 'pass_too_short', __( '<strong>Error:</strong> Password must be at least 15 characters.' ) );
    }
}
```
> **Note:** the companion plugin ships a working `wpyeg_defaults_password_is_pwned()`. It queries the
> [Have I Been Pwned range API](https://haveibeenpwned.com/API/v3#SearchingPwnedPasswordsByRange)
> by k-anonymity: it hashes the candidate locally, sends only the first 5 SHA-1 characters, and
> compares the remaining 35 characters against the returned suffixes locally. Neither the password
> nor its full hash leaves the site. SHA-1 is only the API lookup format, not the password-storage
> hash. BBD requests `Add-Padding`, ignores padded count-0 rows, and caps the WordPress HTTP response
> with `limit_response_size` at 128 KiB. A response that reaches the cap may be truncated, so capped,
> empty, malformed, failed, and non-200 responses are treated as unavailable and **fail open**; only
> structurally valid prefix responses are cached for 12 hours. This prevents bad remote evidence from
> blocking password changes. A
> strength estimator (`wpyeg_zxcvbn_score()` via `bjeavons/zxcvbn-php`) is still yours
> to add if you want one. Server-side validation is the enforcement layer; pair it with the core
> JS meter for UX, but never trust the client alone.

#### Switching the breach lookup off

This is the one thing the plugin does that leaves your server, so it has a switch. Either of
these turns it off:

```php
// wp-config.php — an operator declaration for the whole site.
define( 'WPYEG_DISABLE_HIBP', true );
```

```php
// Or per password, when the decision depends on who or what is setting it.
add_filter( 'wpyeg_disable_hibp', function ( $disabled, $password ) {
    return $disabled;   // Return true to skip the lookup for this candidate.
}, 10, 2 );
```

With the lookup off, `wpyeg_defaults_password_is_pwned()` answers "not breached" without making a
request — the same answer it gives when the API is unreachable, because the check fails open
either way. **The rest of the policy still applies:** the length minimum, the blocklist, and
the personal-context rules are all local and keep running.

> **Why offer this at all, when the lookup is already private?** It is k-anonymous — five
> characters of a locally computed SHA-1, a padded response, and neither the password nor its
> full hash ever leaves the site. That is a good answer to "is this safe," and it is not an
> answer to "may I decline." Sites under a data-protection regime, on an air-gapped network, or
> simply run by someone who does not want an outbound call on every password change all have
> standing to say no. A default that cannot be switched off is not a default; it is a
> requirement wearing a default's clothes.

### Disable AI Connectors
- **Setting key:** `disable_ai_connectors`
- **Default:** `yes`
- **Why:** AI connectors can transmit unpublished content, media, prompts, and user data to
  third-party services. WordPress 7.0 added a core gate for exactly this, so the default
  posture is off-until-asked-for rather than on-by-inheritance.

WordPress 7.0 introduced the `wp_supports_ai` filter (default `true`), which decides whether
the current request may use AI. Returning `false` stops core's AI provider connectors from
registering:

```php
add_filter( 'wp_supports_ai', '__return_false' );

// Settings → Connectors configures those providers, so take the menu out too.
add_action( 'admin_menu', function () {
    remove_submenu_page( 'options-general.php', 'options-connectors.php' );
}, 11 );

// Removing a menu hides the link, it does not block the URL. Close the screen.
add_action( 'admin_init', function () {
    global $pagenow;
    if ( 'options-connectors.php' === $pagenow ) {
        wp_die( esc_html__( 'AI connectors are disabled on this site.' ), '', array( 'response' => 403 ) );
    }
} );
```

> **Note:** core also honours a `WP_AI_SUPPORT` constant, which a deployment can set to
> `false` in `wp-config.php` to hard-lock the disabled posture above the plugin layer. The
> workshop plugin additionally fires a `wpyeg_disable_ai_connectors` action as a seam for AI
> integrations core does not know about (a plugin's own provider, say).

---

## 2. Content, Comments and Public Surfaces

### Disable Comments, Trackbacks, and Pingbacks
- **Setting key:** `disable_comments`
- **Default:** `yes`
- **Why:** For most business/brochure sites, comments are pure spam surface. This closes
  comments everywhere, drops existing open threads from the UI, and removes the admin menu.

```php
// Close comments and pings on the front end for all post types.
add_filter( 'comments_open', '__return_false', 20, 2 );
add_filter( 'pings_open', '__return_false', 20, 2 );

// Hide existing comments.
add_filter( 'comments_array', '__return_empty_array', 20, 2 );

// Remove support so meta boxes disappear.
add_action( 'init', function () {
    foreach ( get_post_types() as $type ) {
        if ( post_type_supports( $type, 'comments' ) ) {
            remove_post_type_support( $type, 'comments' );
            remove_post_type_support( $type, 'trackbacks' );
        }
    }
} );

// Strip the admin menu + admin-bar node.
add_action( 'admin_menu', function () {
    remove_menu_page( 'edit-comments.php' );
} );
add_action( 'wp_before_admin_bar_render', function () {
    global $wp_admin_bar;
    $wp_admin_bar->remove_node( 'comments' );
} );

// New content defaults to closed.
add_filter( 'get_default_comment_status', function () {
    return 'closed';
} );

// Report zero. wp_count_comments() answers zero once the query filter below is
// in place, but get_comments_number() reads the post's cached comment_count and
// does not — so the theme prints "1 Comment" over a thread that renders nothing.
add_filter( 'get_comments_number', '__return_zero', 20 );

// Stop advertising the comment feeds, then stop serving them.
add_filter( 'feed_links_show_comments_feed', '__return_false' );
add_filter( 'feed_links_extra_show_post_comments_feed', '__return_false' );
add_action( 'template_redirect', 'wpyeg_defaults_block_comment_feeds', 9 );

// Answer comment queries as empty. Everything above covers the theme's comment
// template; without this, /wp/v2/comments still serves every comment the site
// has. Block Notes (WordPress 6.9, comment_type 'note') stay queryable.
add_filter( 'comments_pre_query', 'wpyeg_defaults_empty_comment_queries', 10, 2 );

// Take the comment blocks out of the inserter: with comments off they can only
// render nothing.
add_filter( 'allowed_block_types_all', 'wpyeg_defaults_remove_comment_blocks', PHP_INT_MAX );

// And stop the ones a block theme already placed in its post templates from
// rendering. The inserter filter decides what an editor may add next; it has no
// say over markup that is already in the template.
add_filter( 'render_block', 'wpyeg_defaults_suppress_comment_blocks', 10, 2 );
```

Returning an empty string from `render_block` rather than unregistering the block types is
what keeps the default reversible: the blocks stay registered, the theme's template markup
stays as its author wrote it, and turning the setting off brings the whole thing back with
nothing to undo.

The feed handler is where this stops being a list of filters:

```php
function wpyeg_defaults_block_comment_feeds() {
    if ( ! is_comment_feed() ) {
        return;   // set_404() clears is_feed(), so test before, never after.
    }

    global $wp_query;

    if ( $wp_query instanceof WP_Query ) {
        $wp_query->set_404();
    }

    remove_action( 'template_redirect', 'redirect_canonical' );

    status_header( 404 );
    nocache_headers();
}
```

> **`redirect_canonical` does not bail on a 404.** It calls
> `redirect_guess_404_permalink()`, and against the query this function has just emptied it
> answers `/post-name/feed/` with a 301 to `/post-name/feed/feed/` — a clean 404 turned into
> a redirect to a URL that has never existed, which is worse than the bug being fixed. Every
> filter-level test still passes; only a real request catches it. That is the honest limit
> of a filter behind a toggle: it is a claim about one hook, and what a visitor gets is the
> sum of all of them.

> **The `comment` type is not exempt, and that is the point.** It is tempting to let a
> query that explicitly asks for comments run — code asking deliberately should get what
> it asked for. But core's REST comments controller declares
> `'type' => array( 'default' => 'comment' )`, so *every* `GET /wp/v2/comments` arrives
> asking for exactly that type. Exempting it leaves the largest reader of comment data
> untouched while looking careful. Nothing is deleted either way: turn the default off
> and every comment is queryable again.

### Limit Unfiltered HTML to Administrators
- **Setting key:** `limit_unfiltered_html_to_admins`
- **Default:** `yes`
- **Why:** Editors hold `unfiltered_html` on single-site installs — enough to save a raw
  `<script>` into a post. This removes it from everyone except administrators, and Super
  Admins on multisite.

```php
add_filter( 'user_has_cap', function ( $allcaps, $caps, $args, $user ) {
    if ( empty( $allcaps['unfiltered_html'] ) ) {
        return $allcaps;
    }

    $roles = ( isset( $user->roles ) && is_array( $user->roles ) ) ? $user->roles : array();

    // Decide only from $user->roles and the already-resolved $allcaps. Any capability
    // check here re-enters this same filter and recurses until the stack blows.
    if ( in_array( 'administrator', $roles, true ) || ! empty( $allcaps['manage_options'] ) ) {
        return $allcaps;
    }

    // is_super_admin() is safe only on multisite, where it reads the network list. On
    // single site it calls has_cap( 'delete_users' ) — straight back into this filter.
    if ( is_multisite() && isset( $user->ID ) && is_super_admin( $user->ID ) ) {
        return $allcaps;
    }

    $allcaps['unfiltered_html'] = false;

    return $allcaps;
}, PHP_INT_MAX - 1, 4 );
```

> **The recursion trap is the lesson here.** A capability filter that asks a capability
> question calls itself. The fix is not cleverness, it is discipline: read what you were
> handed, never ask.

### Hide Post-Password Protection
- **Setting key:** `disable_post_passwords`
- **Default:** `no`
- **Why:** Post passwords are weak, and full-page caches bypass them. This hides the option
  rather than removing the feature: no data changes, and a post that already has a password
  keeps its field so it stays editable.

```php
add_action( 'admin_print_footer_scripts', function () {
    global $pagenow, $post;

    if ( empty( $pagenow ) || ( 'post.php' !== $pagenow && 'post-new.php' !== $pagenow ) ) {
        return;
    }

    if ( ! empty( $post->post_password ) ) {
        return;   // Already protected: leave the field so the post stays editable.
    }
    ?>
    <style>#visibility-radio-password, label[for="visibility-radio-password"] { display: none; }</style>
    <?php
} );
```

### Force the Classic Editor
- **Setting key:** `force_classic_editor`
- **Default:** `no`
- **Why:** Restores the pre-block editing experience for posts, pages, and custom post
  types, plus the classic Widgets screen. Front-end rendering of existing block content is
  unaffected — `do_blocks()` still runs.

```php
add_filter( 'use_block_editor_for_post', '__return_false' );
add_filter( 'use_block_editor_for_post_type', '__return_false' );  // Separate gate: CPTs are measured against this one.
add_filter( 'gutenberg_can_edit_post', '__return_false' );         // Standalone Gutenberg plugin.
add_filter( 'use_widgets_block_editor', '__return_false' );        // Classic Widgets screen.
```

> Four filters, because core asks the question four ways. Filtering only the per-post gate
> leaves custom post types registered with `show_in_rest` on the block editor, which is the
> version of this snippet you will find on most blogs.

### Lowercase Upload Filenames
- **Setting key:** `lowercase_upload_filenames`
- **Default:** `yes`
- **Why:** A case-sensitive server and a case-insensitive one disagree about whether
  `Photo.JPG` and `photo.jpg` are the same file. Lowercasing on upload removes the argument.
  Only new uploads are affected.

```php
add_filter( 'sanitize_file_name', function ( $filename ) {
    return function_exists( 'mb_strtolower' ) ? mb_strtolower( $filename, 'UTF-8' ) : strtolower( $filename );
}, 20 );   // After core sanitizes.
```

### Show Generated Image Sizes
- **Setting key:** `media_sizes_panel`
- **Default:** `yes`
- **Why:** A read-only panel on the attachment edit screen listing the resized files
  WordPress generated, with dimensions. Useful for confirming what exists without installing
  a media-management plugin.

```php
add_action( 'add_meta_boxes_attachment', function () {
    add_meta_box( 'wpyeg-media-sizes', 'Generated Sizes', 'wpyeg_defaults_render_media_sizes', null, 'side', 'low' );
} );
```

### Disable Pingbacks and Trackbacks (defaults for new posts)
- **Setting key:** `disable_pingbacks`
- **Default:** `yes`
- **Why:** Even with comments on, pingbacks/trackbacks are low-value and spammy. This forces
  the "closed" default for any newly created content.

```php
add_filter( 'pre_option_default_pingback_flag', '__return_zero' );
add_filter( 'pre_option_default_ping_status', function () {
    return 'closed';
} );
```

### Disable Self-Pingbacks
- **Setting key:** `disable_self_pingbacks`
- **Default:** `yes`
- **Why:** Internal links should not create pingback notifications on the same site. Filtering
  the pre-ping link list removes only local URLs and leaves external links available for any
  remaining pingback behaviour.

```php
add_action( 'pre_ping', function ( &$links ) {
    $home = home_url();
    foreach ( (array) $links as $key => $link ) {
        if ( 0 === strpos( $link, $home ) ) {
            unset( $links[ $key ] );
        }
    }
} );
```

### Disable Public Author Archives
- **Setting key:** `disable_author_archives`
- **Default:** `yes`
- **Why:** Author archive URLs (`/author/{slug}/`) are another username-enumeration path and
  usually thin, duplicate content. Redirect them home.

```php
add_action( 'template_redirect', function () {
    if ( is_author() ) {
        wp_safe_redirect( home_url( '/' ), 301 );
        exit;
    }
} );
```

### Redirect Attachment Pages
- **Setting key:** `redirect_attachment_pages`
- **Default:** `yes`
- **Why:** Standalone attachment pages (`?attachment_id=…`) are thin, index-bloating pages that
  expose media out of context. Core agrees: **WordPress 6.4** added a `wp_attachment_pages_enabled`
  option, set to `0` on new installs (core redirects to the file) and `1` on sites upgraded from
  earlier, which keeps rendering them. This default overrides the destination, preferring the
  **parent post** — landing on a real article beats landing on a bare JPEG.

Two details matter more than the toggle itself.

**Do not fall back to the homepage.** Unattached media has no parent, and that is common — anything
uploaded straight into the Media Library. Pointing all of those at `/` is a soft-404 pattern search
engines read badly. Fall back to the file, which is what core does.

**Respect a theme that built these pages.** A theme shipping `attachment.php` or `image.php` opted
into rendering them — the photography and portfolio case — and redirecting past it silently deletes
a feature someone wrote on purpose.

```php
/**
 * Decide the target separately from performing the redirect, so the decision is
 * testable without a request.
 */
function wpyeg_defaults_attachment_redirect_target( $attachment_id ) {
    $keep = (bool) locate_template( array( 'attachment.php', 'image.php' ) );
    if ( apply_filters( 'wpyeg_keep_attachment_page', $keep, $attachment_id ) ) {
        return '';
    }

    $parent = wp_get_post_parent_id( $attachment_id );

    // Parent post if there is one; otherwise the file — never the homepage.
    return $parent ? (string) get_permalink( $parent ) : (string) wp_get_attachment_url( $attachment_id );
}

add_action( 'template_redirect', function () {
    if ( ! is_attachment() ) {
        return;
    }

    $target = wpyeg_defaults_attachment_redirect_target( get_queried_object_id() );
    if ( '' === $target ) {
        return;
    }

    wp_safe_redirect( $target, 301 );
    exit;
} );
```

> **Offloaded media:** if the file lives on S3 or a CDN, `wp_safe_redirect()` will refuse the
> off-site host and bounce to `wp-admin`. Add that one host via `allowed_redirect_hosts` for the
> redirect rather than reaching for the unguarded `wp_redirect()`.

### Disable Emojis
- **Setting key:** `disable_emojis`
- **Default:** `yes`
- **Why:** Core injects an emoji detection script and inline CSS on every page. Modern
  browsers render emoji natively, so this is dead weight (an extra script + a DNS lookup).

```php
add_action( 'init', function () {
    remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
    remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
    remove_action( 'wp_print_styles', 'print_emoji_styles' );
    remove_action( 'admin_print_styles', 'print_emoji_styles' );
    remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
    remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
    remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

    // Stop the emoji DNS-prefetch hint too.
    add_filter( 'emoji_svg_url', '__return_false' );

    // The classic editor loads emoji support as a TinyMCE plugin, which none of
    // the removals above reach — they cover the front end, the admin head, feeds
    // and mail. Without this, the editor still loads wp-emoji-release.min.js on a
    // site that asked for no emojis.
    add_filter( 'tiny_mce_plugins', function ( $plugins ) {
        return is_array( $plugins ) ? array_values( array_diff( $plugins, array( 'wpemoji' ) ) ) : array();
    } );
} );
```

---

## 3. Admin & Front-End UX

### Title-Only Admin Search
- **Setting key:** `title_only_admin_search`
- **Default:** `no`
- **Why:** On big sites, the admin list-table search scans post content and can be painfully
  slow. Restricting it to titles is much faster — but it changes editor expectations, so it's
  off by default. **Narrow the search *columns*, don't replace the whole search clause:** the
  `post_search_columns` filter keeps core's term parsing, `-exclusions`, and the logged-out
  `post_password` guard intact, where a raw `posts_search` string throws all of that away.

```php
add_filter( 'post_search_columns', function ( $columns, $search, WP_Query $query ) {
    if ( is_admin() && $query->is_main_query() ) {
        return array( 'post_title' );
    }
    return $columns;
}, 10, 3 );
```
> **Note:** `post_search_columns` landed in WordPress 6.2. The older pattern — returning a
> hand-built `posts_search` SQL string — *replaces* core's entire search clause and silently
> drops term parsing, `-term` exclusions, and the `AND post_password = ''` guard core appends for
> logged-out users. Prefer the columns filter.

### Disable Front-End Admin Bar
- **Setting key:** `frontend_admin_bar_behavior`
- **Default:** `''` (unchanged) — or `hide_non_admins` as a common hardening default
- **Why:** The admin bar (toolbar) on the front end nudges layout, leaks that a user is logged
  in, and is rarely needed for subscribers/customers. Two common policies below.

```php
// Option A: hide the admin bar on the front end for everyone.
add_filter( 'show_admin_bar', '__return_false' );

// Option B: hide it only for users who can't manage options (keep it for admins).
add_filter( 'show_admin_bar', function ( $show ) {
    return current_user_can( 'manage_options' ) ? $show : false;
} );
```

---

## 4. Login & Session Policy

### Disable Remember Me
- **Setting key:** `disable_remember_me`
- **Default:** `no`
- **Why:** On shared or kiosk machines, a persistent "Remember Me" cookie is a risk. Removing
  the checkbox routes every login through the regular session length below. Off by default
  because it hurts convenience.

```php
// The policy: strip the submitted flag so a forged POST cannot opt back into a
// persistent session. login_init fires before wp-login.php reads $_POST.
add_action( 'login_init', function () {
    unset( $_POST['rememberme'], $_REQUEST['rememberme'] );
} );

// The UI: CSS at login_head, not a script at login_footer.
add_action( 'login_head', function () {
    echo '<style id="wpyeg-hide-remember-me">.login form .forgetmenot { display: none; }</style>';
} );
```

> **Why CSS and not JavaScript.** The version of this you will find on most blogs hides the
> checkbox with a script at `login_footer`. That leaves it visible and tickable with JavaScript
> off, and under a strict `script-src` Content Security Policy that blocks inline scripts — on
> a login screen, of all places. The `$_POST` strip is what actually enforces the policy either
> way; this is the half that has to survive a browser that will not run your JavaScript, so it
> should not be the half written in JavaScript.

### Change the Session Lengths
- **Setting keys:** `session_regular_days` / `remember_me_days`
- **Defaults:** `2` / `14` *(WordPress's real defaults, in days)*
- **Why:** Core signs a normal login in for 2 days and a remembered one for 14 — often too long.
  Both lengths are in days here, each with a 1-day floor, and sanitize clamps the remembered
  length up so it can never be shorter than the regular one (ticking "Remember Me" must never
  *shorten* a session).

```php
function wpyeg_auth_cookie_expiration( $expiration, $user_id, $remember ) {
    $regular = 2 * DAY_IN_SECONDS;   // session_regular_days

    return $remember ? 14 * DAY_IN_SECONDS : $regular;  // remember_me_days >= regular
}

// Register it only when these settings say something WordPress does not already
// do — see below for why that condition is the interesting part.
if ( wpyeg_defaults_session_policy_is_custom() ) {
    add_filter( 'auth_cookie_expiration', 'wpyeg_auth_cookie_expiration', 10, 3 );
}
```

> **Why the `if`, and why a named function.** This is a *replacing* filter: the callback returns
> its own number and discards `$expiration`. Two plugins registering one do not compose —
> WordPress keeps whichever ran last, the other silently does nothing, and both settings screens
> go on displaying their own value with no error anywhere. A number filter cannot be made
> additive, so the only thing left is to not enter a fight with nothing to win: both defaults
> here are WordPress's own values, so on a site that has never changed them, registering would
> assert core's answer over another plugin's deliberate one. And the callback is a named
> function rather than a closure because `$wp_filter` can only report a callback that has a
> name — an anonymous one shows up as `closure` in exactly the diagnostic you would run to work
> out who won. See
> [When two plugins set the same default](when-two-plugins-set-the-same-default.md).

### Login Logo & Link
- **Setting key:** `login_logo_behavior`
- **Default:** `keep_default` *(leave the login screen untouched)*
- **Why:** The default WordPress "W" on `wp-login.php` links to wordpress.org. Removing,
  unlinking, or replacing it keeps the login screen organizationally consistent and prevents the
  logo from sending users to an unexpected external site. Changing the login screen out of the
  box is intrusive, so the safe default is to **leave it alone** and let an administrator opt in.
  Behaviours: `keep_default` (unchanged), `remove_logo` (recommended — drop the logo and the
  wp.org link), `unlink_logo` (keep the logo, kill the link), `replace_logo` (swap in the site
  logo/icon, linked to the site home).

```php
$behavior = wpyeg_defaults_get( 'login_logo_behavior' );

if ( 'remove_logo' === $behavior ) {
    add_action( 'login_head', function () {
        echo '<style>#login h1 a, .login h1 a { display: none; }</style>';
    } );
}

// Whenever the logo is removed or replaced, point the header link at the site home
// instead of wordpress.org — a replacement logo always links home, so there is no
// separate toggle for it.
if ( in_array( $behavior, array( 'remove_logo', 'unlink_logo', 'replace_logo' ), true ) ) {
    add_filter( 'login_headerurl', 'home_url' );
    add_filter( 'login_headertext', function () {
        return get_bloginfo( 'name' );
    } );
}
```
> **Note:** an earlier version of this reference paired the behaviour with a separate
> `login_logo_link_home` setting. That was redundant — a replacement logo should always link
> home — so the toggle is gone and the behaviour option alone covers it.

---

## 5. Update Policy

### Automatically install core maintenance/security releases

- **Setting key:** `core_update_policy`
- **Default:** `minor`

The default enables in-branch maintenance and security releases (`x.y.z`) while leaving major
core releases (`x.y`) for a tested agency rollout. The settings screen can also allow every
stable release, make core updates manual, or leave the decision unchanged.

```php
add_filter( 'allow_minor_auto_core_updates', '__return_true' );
add_filter( 'allow_major_auto_core_updates', '__return_false' );
add_filter( 'allow_dev_auto_core_updates', '__return_false' );
```

Better by Default does not register those filters when `WP_AUTO_UPDATE_CORE` is defined in
`wp-config.php`; an explicit operator-level policy wins and the settings screen reports that
the control is locked. `AUTOMATIC_UPDATER_DISABLED` and `DISALLOW_FILE_MODS` can prevent the
updater from running at all, so the screen warns about those overrides too.

**A note on honest settings UI — this is the part worth studying.** A setting that keeps
rendering as a live control while an external constant silently overrides it is a trap: the
user changes the dropdown, nothing happens, and there is no feedback explaining why. The fix
is to *detect the override* (`defined( 'WP_AUTO_UPDATE_CORE' )`) and **disable the control
with an explanation** rather than let it pretend to work — the screen should always reflect
the state that will actually take effect, not the state the user thinks they set. The same
applies to `AUTOMATIC_UPDATER_DISABLED` and `DISALLOW_FILE_MODS`. Making a control tell the
truth about who is really in charge is a few lines of code and a large amount of user trust;
a plugin that skips it looks fine in a demo and quietly misleads on a real site.

Major releases should be tested on staging and deployed within 30 days, not frozen
indefinitely. Expedite the rollout when a security fix is unavailable on the installed branch.
Only the latest WordPress major release is officially supported; security backports to older
branches are a courtesy, not a guaranteed support policy.

References:

- [Configuring Automatic Background Updates](https://developer.wordpress.org/advanced-administration/upgrade/upgrading/)
- [`Core_Upgrader::should_update_to_version()`](https://developer.wordpress.org/reference/classes/core_upgrader/should_update_to_version/)
- [Supported WordPress versions](https://wordpress.org/documentation/article/supported-versions/)

### Leave translation updates unchanged

WordPress already updates translation files automatically by default. Better by Default does not
register `auto_update_translation`, so WordPress, the host, or fleet-management tooling retains
ownership of that policy. These are language-pack updates for WordPress core and installed plugins
and themes; they do not update plugin or theme code.

Plugin and theme **code** updates are intentionally left to WordPress's individual per-item
choices. The plugin ecosystem has no enforceable semantic-versioning or security-release
metadata, so a generic defaults plugin cannot safely infer that `2.4` is harmless while `3.0`
is risky. Agencies can maintain a reviewed allowlist in their fleet-management tooling.

---

## 6. Additional Recommended Defaults

Beyond your list, these are the defaults I'd reach for on nearly every build.

### Security

**Disable the theme/plugin file editor.** Removes the in-dashboard code editor so a
compromised admin account can't rewrite PHP on the fly. Set in `wp-config.php`:

```php
define( 'DISALLOW_FILE_EDIT', true );
```

**Remove the WordPress version fingerprint** *(setting key `remove_version`, default `no`)*.
Stops the generator tag broadcasting your exact core version.

Deliberately **not** on by default, because this is obscurity rather than hardening. It does
not make an out-of-date site any safer, and it is not even a complete cover: the version still
leaks through asset query strings (`?ver=`), feeds, and readme files. What it genuinely buys is
less automated scanner noise in your logs — worth opting into, not worth presenting as
security.

```php
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );
```

**Send baseline security headers** *(setting key `security_headers`, default `yes`)*. Two headers with
essentially no downside: `nosniff` stops the browser second-guessing a declared `Content-Type`,
and `Referrer-Policy` keeps full URLs from leaking to other sites.

Note what is *not* in this group. `X-Frame-Options` is a separate setting
(`frame_options`, default `SAMEORIGIN`) because it is the only one of the three that can
break a working site: blocking cross-origin framing also blocks *legitimate* embedding — a
client intranet, a partner site, a preview or proofing tool — and it usually fails as a silent
blank frame. Bundling it with `nosniff` would mean a site that needs to be embeddable has to
give up `nosniff` as well. Set it to *leave unchanged* when a host or CDN already sends it.

**Deferring to an existing header is the wrong default, and this is the part worth studying.**
The obvious rule is "set it only if nothing else has" — it sounds polite, and it means whatever
arrived first wins, so a host's permissive header silently beats a deliberately configured
strict one. Each of the three headers needs a different rule, and which rule applies depends
entirely on whether its values can be ranked:

| Header | Rule | Why |
| --- | --- | --- |
| `X-Content-Type-Options` | overwrite, always | `nosniff` is its only effective value, so an existing anything-else is not a policy to respect — it is a header doing nothing |
| `X-Frame-Options` | overwrite only when strictly stronger | `DENY` > `SAMEORIGIN` is a real ranking, so a host's `DENY` is never reduced, and a configured `DENY` still tightens a weaker value |
| `Referrer-Policy` | defer to any existing value | its tokens have no single strictness axis — `same-origin` and `strict-origin-when-cross-origin` are not comparable, so there is nothing to compare |

```php
/**
 * HTTP header names are case-insensitive; PHP array keys are not. Another
 * plugin's `x-frame-options` is the same header to every browser, but isset()
 * misses it — so a naive "already set?" check adds a second, conflicting line
 * instead of deferring. Find the key as it was actually written.
 */
function wpyeg_find_header_key( $headers, $name ) {
    foreach ( array_keys( (array) $headers ) as $key ) {
        if ( 0 === strcasecmp( (string) $key, $name ) ) {
            return (string) $key;
        }
    }

    return null;
}

// nosniff: correct in place, whatever was there before.
add_filter( 'wp_headers', function ( $headers ) {
    $key = wpyeg_find_header_key( $headers, 'X-Content-Type-Options' );
    $headers[ $key ? $key : 'X-Content-Type-Options' ] = 'nosniff';

    return $headers;
} );

// Referrer-Policy: no strictness axis, so an existing policy stands.
add_filter( 'wp_headers', function ( $headers ) {
    if ( null === wpyeg_find_header_key( $headers, 'Referrer-Policy' ) ) {
        $headers['Referrer-Policy'] = 'strict-origin-when-cross-origin';
    }

    return $headers;
} );
```

Framing is its own setting, and its own comparison. Only the two values browsers honour are
ranked; anything else — a deprecated `ALLOW-FROM`, a typo — returns `null` and the response is
left alone rather than silently tightened:

```php
function wpyeg_frame_option_strength( $value ) {
    switch ( strtoupper( trim( (string) $value ) ) ) {
        case 'DENY':       return 2;
        case 'SAMEORIGIN': return 1;
        default:           return null;   // Unrecognised: do not guess.
    }
}

add_filter( 'wp_headers', function ( $headers ) {
    $configured = wpyeg_defaults_get( 'frame_options' );   // '' = leave unchanged
    $key        = wpyeg_find_header_key( $headers, 'X-Frame-Options' );

    if ( null === $key ) {
        $headers['X-Frame-Options'] = $configured;

        return $headers;
    }

    $existing_strength   = wpyeg_frame_option_strength( $headers[ $key ] );
    $configured_strength = wpyeg_frame_option_strength( $configured );

    // Replace only when both are rankable and ours is strictly stronger.
    if ( null !== $existing_strength && null !== $configured_strength
        && $configured_strength > $existing_strength ) {
        $headers[ $key ] = $configured;   // Write back to the key already there.
    }

    return $headers;
} );
```

> **What none of this can see:** PHP only knows about headers set in PHP. One added by nginx,
> Apache, or a CDN is invisible to `wp_headers` entirely, so no amount of comparison here
> catches every duplicate — check the actual response, not just this code. Headers are
> ultimately an edge concern; this is the fallback for when you do not control the edge.

**Disable self-pingbacks.** Stops your own internal links from creating pingback noise.

```php
add_action( 'pre_ping', function ( &$links ) {
    $home = home_url();
    foreach ( $links as $key => $link ) {
        if ( 0 === strpos( $link, $home ) ) {
            unset( $links[ $key ] );
        }
    }
} );
```

### UX

**Sensible admin cleanup.** Hide the "Try Gutenberg"/welcome nags and the WordPress logo in
the admin bar for a calmer dashboard.

```php
add_action( 'wp_before_admin_bar_render', function () {
    global $wp_admin_bar;
    $wp_admin_bar->remove_node( 'wp-logo' );
} );
```

**Increase the autosave interval and cap post revisions** so the editor writes to the DB less
often and revisions don't balloon table size. In `wp-config.php`:

```php
define( 'AUTOSAVE_INTERVAL', 120 ); // seconds
define( 'WP_POST_REVISIONS', 10 );  // keep the last 10 per post
```

**Raise the "Howdy" and default email sender** to something branded — small touches, but they
stop the site looking like a stock install. Filter `wp_mail_from` and `wp_mail_from_name`:

```php
add_filter( 'wp_mail_from', function () { return 'no-reply@example.com'; } );
add_filter( 'wp_mail_from_name', function () { return get_bloginfo( 'name' ); } );
```

### SEO

**Trim the `wp_head` clutter** — shortlinks, WLW manifest, and adjacent-post `rel` links are
rarely useful and add markup.

```php
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_shortlink_wp_head' );
remove_action( 'wp_head', 'adjacent_posts_rel_link_wp_head' );
```

**Keep core sitemaps on (or hand off to your SEO plugin).** Core ships `wp-sitemap.xml`; make
sure exactly one system owns sitemaps to avoid conflicting signals. If an SEO plugin handles
it, disable core's:

```php
add_filter( 'wp_sitemaps_enabled', '__return_false' );
```

**Set a canonical, and noindex thin archives.** Redirecting attachment/author pages (above)
already helps; consider `noindex` on internal search results and paginated tag archives via
your SEO plugin's defaults.

### Performance

**Throttle the Heartbeat API** *(setting key `throttle_heartbeat`, default `no`)* so autosave/lock polling doesn't hammer `admin-ajax.php`,
especially on shared hosting.

```php
add_filter( 'heartbeat_settings', function ( $settings ) {
    $settings['interval'] = 60; // default is 15–60s; 60 is gentle
    return $settings;
} );

// Optionally disable Heartbeat on the dashboard home where it's least needed.
add_action( 'init', function () {
    if ( is_admin() ) {
        global $pagenow;
        if ( 'index.php' === $pagenow ) {
            wp_deregister_script( 'heartbeat' );
        }
    }
} );
```

**Defer non-critical scripts** — *no plugin setting, because core does this properly now.*

Since **WordPress 6.3**, `wp_enqueue_script()` takes a loading strategy, so deferral belongs on
the script being enqueued rather than in a filter that rewrites everyone's `<script>` tags:

```php
wp_enqueue_script(
    'my-theme-front',
    get_theme_file_uri( 'build/front.js' ),
    array(),
    '1.0.0',
    array( 'strategy' => 'defer' ) // or 'async'
);
```

The older pattern — hooking `script_loader_tag` and string-replacing ` src=` with ` defer src=`
across every handle — predates that API and is worth retiring. It cannot know which scripts are
safe to defer, so it breaks anything expecting synchronous jQuery or a particular execution
order, and it hands you a blunt on/off switch where core now gives you per-script control. If
you inherit a site that still does it, replacing it with `strategy` is a real improvement rather
than a lateral move.

**Remove query strings from static assets** for better proxy/CDN caching (many CDNs skip
querystring'd URLs by default).

```php
add_filter( 'style_loader_src', 'wpyeg_strip_asset_ver', 15 );
add_filter( 'script_loader_src', 'wpyeg_strip_asset_ver', 15 );
function wpyeg_strip_asset_ver( $src ) {
    if ( strpos( $src, 'ver=' ) ) {
        $src = remove_query_arg( 'ver', $src );
    }
    return $src;
}
```
> **Caveat:** stripping `ver` weakens cache-busting on deploys. Prefer versioning assets by
> filename/hash if you use this.

---

### Warn When the From Address Looks Undeliverable
- **Setting key:** `mail_deliverability_notice`
- **Default:** `yes`
- **Why:** WordPress sends mail from `wordpress@yourdomain` unless something changes it. On a
  domain that cannot actually send — a staging host, a `.local` address, a domain with no mail
  records — password resets and notifications fail *silently*: `wp_mail()` returns false and
  nothing surfaces it. This shows an admin notice when the address looks undeliverable.

```php
function wpyeg_defaults_mail_is_risky() {
    $email  = wpyeg_defaults_mail_from_address();   // core's default, run through wp_mail_from
    $domain = strtolower( substr( strrchr( $email, '@' ), 1 ) );

    if ( ! is_email( $email ) ) {
        return true;
    }
    if ( in_array( $domain, array( 'example.com', 'localhost', 'local' ), true ) ) {
        return true;
    }

    // Reserved TLDs that never resolve publicly (RFC 2606, RFC 6762).
    return (bool) preg_match( '/\.(local|test|invalid|example)$/i', $domain );
}
```

> **It is a shape check, not a delivery test.** Proving mail works needs an SPF/DMARC lookup and
> an actual send — far more than a settings screen should do on page load. These are the cases
> where the answer is knowable for free. It never blocks or alters mail, and it stays quiet on
> local environments, where an undeliverable address is the correct state.


## 7. Filters and actions

Everything above is a toggle. These are the code-level hooks — no setting, no UI. They exist
where a value is a judgement call that varies by site, and where adding a control would cost
more screen than the choice is worth.

Put them in a small plugin or an mu-plugin, not the theme, so they survive a theme switch.

| Filter | Default | What it changes |
| --- | --- | --- |
| `wpyeg_minimum_password_length` | `15` | The length rule. Lower it and you are trading away the main defence, since there is no composition rule to fall back on. |
| `wpyeg_weak_roles` | `array( 'subscriber' )` | Roles exempt from the length, blocklist and personal-context rules. **Never exempt from breach screening.** |
| `wpyeg_password_blocklist` | six obvious strings | The offline fallback list, used when the breach API is unreachable. |
| `wpyeg_smtp_plugin_recommendation` | a generic SMTP suggestion | The remedy named in the mail-deliverability notice, so an agency can point at the plugin it actually supports. |
| `wpyeg_disable_hibp` | `false` | Switches off the breach lookup. Also honoured as the `WPYEG_DISABLE_HIBP` constant. |
| `wpyeg_hibp_max_response_bytes` | `131072` | Transport cap on a range response. Floored at 1 KB, so a filter cannot make every response look truncated. |
| `wpyeg_password_is_pwned` | the lookup's verdict | The final say on whether a password counts as breached. Return `true` from a local blocklist and no request is made. |
| `wpyeg_allowed_comment_types` | internal types only | Comment types that survive the teardown — Block Notes and similar, which are not public comments. |
| `wpyeg_comment_blocks` | the core comment block list | Which editor blocks the comment teardown removes from the inserter. |
| `wpyeg_keep_attachment_page` | `false` | Keeps a specific attachment page reachable instead of redirecting it. |
| `wpyeg_feed_author_name` | `Site Contributor` | The name printed in feeds in place of the real author. |

One action, not a filter — it returns nothing and exists purely as a place to hang your own
teardown:

| Action | Fires | What it is for |
| --- | --- | --- |
| `wpyeg_disable_ai_connectors` | `init`, priority 20, only when the AI-connector default is on | Unregistering AI providers core does not know about — a plugin's own connector, say. It fires on `init` rather than at plugin load precisely so the providers it is meant to remove have registered by the time it runs. |

### Scoping the password policy by role

`wpyeg_weak_roles` is the one worth explaining, because it is easy to implement as a hole.

A 15-character minimum is right for an account that can publish or configure, and
disproportionate for one that can only read — on a membership or commerce site it is signup
friction that protects nothing. So subscriber-only accounts skip the length, blocklist and
personal-context rules by default.

Two things keep it from being a loophole:

**Breach screening is never waived.** It runs *before* the role check, for every account.
A password already published in a breach costs its owner nothing to avoid, whatever the
account can do — and the accounts most likely to reuse a password from somewhere else are
exactly the low-privilege ones. Get this order wrong and the one free rule becomes the one
you waived for the people who needed it.

**A user is exempt only if every role they hold is exempt.** Someone who is both a Subscriber
and an Editor is an Editor for this purpose. An unknown or empty role set enforces too: when
the code cannot tell who this is, the safe answer is the strict one.

```php
// Exempt authors as well as subscribers.
add_filter( 'wpyeg_weak_roles', function ( $roles ) {
    $roles[] = 'author';

    return $roles;
} );

// Or enforce the full policy on everyone, whatever their role.
add_filter( 'wpyeg_weak_roles', '__return_empty_array' );
```

---

## Quick-Reference Table

| Setting | Setting key | Default | Schema group |
| --- | --- | --- | --- |
| Restrict REST API user discovery | `restrict_rest_user_discovery` | `yes` | Security |
| Require auth for all REST requests | `disable_rest` | `no` | Security |
| XML-RPC: accept incoming pingbacks | `xmlrpc_allow_pingbacks` | `no` | Security |
| XML-RPC: allow remote publishing | `xmlrpc_allow_remote_publishing` | `no` | Security |
| XML-RPC: allow `system.multicall` | `xmlrpc_allow_multicall` | `no` | Security |
| XML-RPC: block the endpoint | `block_xmlrpc_endpoint` | `no` | Security |
| Prohibit Application Passwords | `disable_application_passwords` | `no` | Security |
| Require strong passwords | `require_strong_passwords` | `yes` | Security |
| Remove WordPress version fingerprint | `remove_version` | `no` | Security |
| Send baseline security headers | `security_headers` | `yes` | Security |
| Set `X-Frame-Options` | `frame_options` | `SAMEORIGIN` | Security |
| Disable AI connectors | `disable_ai_connectors` | `yes` | Security |
| Automatic WordPress core updates | `core_update_policy` | `minor` | Updates |
| Disable comments, trackbacks, and pingbacks | `disable_comments` | `yes` | Content |
| Default new posts to pings closed | `disable_pingbacks` | `yes` | Content |
| Disable self-pingbacks | `disable_self_pingbacks` | `yes` | Content |
| Disable public author archives | `disable_author_archives` | `yes` | Content |
| Redirect attachment pages | `redirect_attachment_pages` | `yes` | Content |
| Disable emoji script | `disable_emojis` | `yes` | Content |
| Limit unfiltered HTML to administrators | `limit_unfiltered_html_to_admins` | `yes` | Security |
| Hide post-password protection | `disable_post_passwords` | `no` | Content |
| Force the classic editor | `force_classic_editor` | `no` | Content |
| Lowercase upload filenames | `lowercase_upload_filenames` | `yes` | UX |
| Show generated image sizes | `media_sizes_panel` | `yes` | UX |
| Warn when the From address looks undeliverable | `mail_deliverability_notice` | `yes` | Email |
| Title-only admin search | `title_only_admin_search` | `no` | UX |
| Front-end admin bar | `frontend_admin_bar_behavior` | `''` | UX |
| Disable Remember Me | `disable_remember_me` | `no` | Login |
| Regular session length (days) | `session_regular_days` | `2` | Login |
| Remember Me length (days) | `remember_me_days` | `14` | Login |
| Login logo | `login_logo_behavior` | `keep_default` | Branding |
| Throttle the Heartbeat API | `throttle_heartbeat` | `no` | Performance |

---

### Implementation notes
- Load these from an **mu-plugin** or a dedicated plugin, not the theme, so policy survives
  theme switches.
- Gate every snippet behind its `get_option()` toggle so site owners keep control.
- Put each toggle's descriptive title immediately after its checkbox inside the same clickable
  label; never substitute a generic `Enabled` label. Select and number fields retain descriptive
  row labels, and all help text is connected with `aria-describedby`. Let WordPress's classic
  `form-table` styles control label and description typography. Help text permits only
  attribute-free `<code>` for machine-facing identifiers and `<a href>` for authoritative
  references, all filtered through `wp_kses()`. External claims should name and link the specific
  publication or directive and section when one exists.
- `wp-config.php` constants (`DISALLOW_FILE_EDIT`, `AUTOSAVE_INTERVAL`, `WP_POST_REVISIONS`)
  can't be toggled from options — surface them in your docs as recommended manual settings.
- Explicit update constants remain operator-owned: the settings screen reports
  `WP_AUTO_UPDATE_CORE`, `AUTOMATIC_UPDATER_DISABLED`, and `DISALLOW_FILE_MODS` rather than
  silently fighting them.
- Test REST and comment changes against the block editor before shipping; those two touch the
  most core functionality.
