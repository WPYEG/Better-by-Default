# WordPress "Sane Defaults" Reference

A menu of default settings that can be applied to just about any WordPress install to
tighten security, trim attack surface, clean up UX, and shave weight off the front end.

Each item lists a suggested **option key** (using a `wpyeg_` prefix for the WPYEG workshop
plugin), a recommended **default value**, a short **description**, and a **code snippet** you
can drop into a plugin, an mu-plugin, or a theme's `functions.php`.

> Built for the **WPYEG — Edmonton WordPress Meetup** hands-on workshop. The companion
> `better-by-default` plugin wires every one of these behind a toggle.

**How to read this:** the option key/default columns assume these are user-toggleable
settings. The snippet under each item is the "on" behavior — the code that runs when the
default is active. In a real plugin you'd wrap each snippet in a
`get_option( 'wpyeg_...' ) === 'yes'` check so site owners can flip it. Snippets are shown
unwrapped for clarity.

A few items are flagged **plugin-specific** — they have no stable WordPress core equivalent
and depend on your own plugin's logic.

---

## 1. Security & Attack-Surface Reduction

### Restrict REST API User Discovery
- **Option:** `wpyeg_restrict_rest_user_discovery`
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
- **Option:** `wpyeg_disable_rest`
- **Default:** `no` *(leave off unless the site is a pure brochure site — the block editor,
  many blocks, and core AJAX rely on REST)*
- **Why:** Fully disabling REST is a blunt instrument. The safer posture is to require
  authentication for all REST calls, which blocks anonymous scraping without breaking the
  editor for logged-in users.

```php
add_filter( 'rest_authentication_errors', function ( $result ) {
    if ( ! empty( $result ) ) {
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
} );
```

### Disable XML-RPC
- **Option:** `wpyeg_disable_xmlrpc`
- **Default:** `yes`
- **Why:** `xmlrpc.php` is a classic amplification vector for brute-force and pingback-DDoS
  attacks. Unless you run the legacy Jetpack/mobile-app path over XML-RPC, kill it.

```php
add_filter( 'xmlrpc_enabled', '__return_false' );

// Also strip the RSD/pingback discovery header so bots stop probing.
add_filter( 'wp_headers', function ( $headers ) {
    unset( $headers['X-Pingback'] );
    return $headers;
} );
remove_action( 'wp_head', 'rsd_link' );
```

### Disable Application Passwords
- **Option:** `wpyeg_disable_application_passwords`
- **Default:** `yes`
- **Why:** Application Passwords create long-lived credentials for the REST API. If you
  aren't using headless clients or external integrations, remove the feature so there's
  nothing to leak.

```php
add_filter( 'wp_is_application_passwords_available', '__return_false' );
```

### Require Strong Passwords
- **Option:** `wpyeg_require_strong_passwords`
- **Default:** `yes`
- **Why:** Core ships a password meter but won't *enforce* strength. This rejects weak
  passwords server-side on profile updates and user creation, so a warning becomes a wall.

```php
add_action( 'user_profile_update_errors', 'wpyeg_enforce_strong_password', 10, 3 );
add_action( 'validate_password_reset', function ( $errors, $user ) {
    wpyeg_enforce_strong_password( $errors, true, $user );
}, 10, 2 );

function wpyeg_enforce_strong_password( $errors, $update, $user ) {
    $password = isset( $_POST['pass1'] ) ? (string) $_POST['pass1'] : '';

    if ( '' === $password ) {
        return; // No password change requested.
    }

    $long_enough = strlen( $password ) >= 12;
    $has_mixed   = preg_match( '/[A-Z]/', $password )
                && preg_match( '/[a-z]/', $password )
                && preg_match( '/[0-9]/', $password )
                && preg_match( '/[^A-Za-z0-9]/', $password );

    if ( ! $long_enough || ! $has_mixed ) {
        $errors->add(
            'pass_too_weak',
            __( '<strong>Error:</strong> Password must be at least 12 characters and include upper, lower, number, and symbol.' )
        );
    }
}
```
> **Note:** Server-side validation is the enforcement layer. Pair it with the core JS meter
> for good UX, but never trust the client alone.

### Disable AI Connectors
- **Option:** `wpyeg_disable_ai_connectors`
- **Default:** `yes`
- **Description:** Disables the plugin's built-in AI connector recommendations and related
  WordPress AI support.
- **Plugin-specific** — no stable core equivalent. This is plugin-policy logic; there's no
  core hook to switch off "AI support" generically as of now, so it lives entirely in your
  plugin. The workshop plugin exposes a `wpyeg_disable_ai_connectors` action as the seam.

---

## 2. Content, Comments & Public Surfaces

### Disable Comments, Trackbacks, and Pingbacks
- **Option:** `wpyeg_disable_comments`
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
```

### Disable Pingbacks and Trackbacks (defaults for new posts)
- **Option:** `wpyeg_disable_pingbacks`
- **Default:** `yes`
- **Why:** Even with comments on, pingbacks/trackbacks are low-value and spammy. This forces
  the "closed" default for any newly created content.

```php
add_filter( 'pre_option_default_pingback_flag', '__return_zero' );
add_filter( 'pre_option_default_ping_status', function () {
    return 'closed';
} );
```

### Disable Public Author Archives
- **Option:** `wpyeg_disable_author_archives`
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
- **Option:** `wpyeg_redirect_attachment_pages`
- **Default:** `yes`
- **Why:** Standalone attachment pages (`?attachment_id=…`) are thin, index-bloating pages
  that expose media out of context. Redirect them to the parent or home.

```php
add_action( 'template_redirect', function () {
    if ( is_attachment() ) {
        $parent = wp_get_post_parent_id( get_queried_object_id() );
        $target = $parent ? get_permalink( $parent ) : home_url( '/' );
        wp_safe_redirect( $target, 301 );
        exit;
    }
} );
```

### Disable Emojis
- **Option:** `wpyeg_disable_emojis`
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
} );
```

---

## 3. Admin & Front-End UX

### Title-Only Admin Search
- **Option:** `wpyeg_title_only_admin_search`
- **Default:** `no`
- **Why:** On big sites, the admin list-table search scans post content and can be painfully
  slow. Restricting it to titles is much faster — but it changes editor expectations, so it's
  off by default. Scope the filter to admin list tables only so front-end search is untouched.

```php
add_filter( 'posts_search', function ( $search, WP_Query $query ) {
    if ( ! is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
        return $search;
    }

    global $wpdb;
    $term = $query->get( 's' );
    if ( '' === $term ) {
        return $search;
    }

    $like = '%' . $wpdb->esc_like( $term ) . '%';
    return $wpdb->prepare( " AND {$wpdb->posts}.post_title LIKE %s ", $like );
}, 10, 2 );
```

### Disable Front-End Admin Bar
- **Option:** `wpyeg_frontend_admin_bar_behavior`
- **Default:** `''` (unchanged) — or `hide_for_non_admins` as a common hardening default
- **Why:** The floating admin bar on the front end nudges layout, leaks that a user is logged
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
- **Option:** `wpyeg_disable_remember_me`
- **Default:** `no`
- **Why:** On shared or kiosk machines, a persistent "Remember Me" cookie is a risk. Removing
  the checkbox forces short-lived sessions. Off by default because it hurts convenience.

```php
add_action( 'login_footer', function () {
    ?>
    <script>
        (function () {
            var wrap = document.querySelector('.login form #rememberme');
            if (wrap && wrap.closest('p')) { wrap.closest('p').style.display = 'none'; }
        })();
    </script>
    <?php
} );

// Belt-and-suspenders: never honor a "remember" flag server-side.
add_filter( 'auth_cookie_expiration', function ( $length, $user_id, $remember ) {
    return $remember ? 2 * DAY_IN_SECONDS : $length;
}, 10, 3 );
```

### Change Remember Me Session Length
- **Option:** `wpyeg_remember_me_policy` / `wpyeg_remember_me_days` / `wpyeg_session_regular_hours`
- **Defaults:** `default` / `5` / `0`
- **Why:** Core's "Remember Me" is 14 days — often too long. This lets you cap the persistent
  session (e.g. 5 days) and, optionally, shorten the regular (non-remembered) session too.

```php
add_filter( 'auth_cookie_expiration', function ( $expiration, $user_id, $remember ) {
    if ( $remember ) {
        return 5 * DAY_IN_SECONDS;   // wpyeg_remember_me_days
    }

    return 12 * HOUR_IN_SECONDS;     // wpyeg_session_regular_hours
}, 10, 3 );
```

### Login Logo & Link
- **Option:** `wpyeg_login_logo_behavior` / `wpyeg_login_logo_link_home`
- **Defaults:** `remove_logo` / `yes`
- **Why:** The default WordPress "W" logo on `wp-login.php` links to wordpress.org — a subtle
  brand and trust leak. Remove it (or swap it) and point the link at the site home.

```php
// Remove the default logo (or replace the background-image URL to use your own).
add_action( 'login_head', function () {
    echo '<style>#login h1 a, .login h1 a { display: none; }</style>';
} );

// Point the logo link at the site home and use the site name as its text.
add_filter( 'login_headerurl', 'home_url' );
add_filter( 'login_headertext', function () {
    return get_bloginfo( 'name' );
} );
```

---

## 5. Additional Recommended Defaults

Beyond your list, these are the defaults I'd reach for on nearly every build.

### Security

**Disable the theme/plugin file editor.** Removes the in-dashboard code editor so a
compromised admin account can't rewrite PHP on the fly. Set in `wp-config.php`:

```php
define( 'DISALLOW_FILE_EDIT', true );
```

**Remove the WordPress version fingerprint.** Stops broadcasting your exact core version to
vulnerability scanners.

```php
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'the_generator', '__return_empty_string' );
```

**Send baseline security headers.** Reasonable defaults that don't usually break sites.

```php
add_filter( 'wp_headers', function ( $headers ) {
    $headers['X-Content-Type-Options'] = 'nosniff';
    $headers['X-Frame-Options']        = 'SAMEORIGIN';
    $headers['Referrer-Policy']        = 'strict-origin-when-cross-origin';
    return $headers;
} );
```

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

**Throttle the Heartbeat API** so autosave/lock polling doesn't hammer `admin-ajax.php`,
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

**Defer non-critical scripts.** Add `defer` to enqueued front-end scripts so they don't block
render.

```php
add_filter( 'script_loader_tag', function ( $tag, $handle ) {
    if ( is_admin() ) {
        return $tag;
    }
    // Skip anything that must run inline/early (e.g. jQuery core).
    $skip = array( 'jquery-core' );
    if ( in_array( $handle, $skip, true ) ) {
        return $tag;
    }
    if ( false === strpos( $tag, ' defer' ) && false !== strpos( $tag, ' src=' ) ) {
        $tag = str_replace( ' src=', ' defer src=', $tag );
    }
    return $tag;
}, 10, 2 );
```

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

## Quick-Reference Table

| Setting | Option key | Default | Category |
| --- | --- | --- | --- |
| Disable Comments/Trackbacks/Pingbacks | `wpyeg_disable_comments` | `yes` | Content |
| Disable Pingbacks (new-post default) | `wpyeg_disable_pingbacks` | `yes` | Content |
| Restrict REST User Discovery | `wpyeg_restrict_rest_user_discovery` | `yes` | Security |
| Disable REST (anon) | `wpyeg_disable_rest` | `no` | Security |
| Disable XML-RPC | `wpyeg_disable_xmlrpc` | `yes` | Security |
| Disable Application Passwords | `wpyeg_disable_application_passwords` | `yes` | Security |
| Require Strong Passwords | `wpyeg_require_strong_passwords` | `yes` | Security |
| Disable AI Connectors *(PMP-specific)* | `wpyeg_disable_ai_connectors` | `yes` | Security |
| Disable Public Author Archives | `wpyeg_disable_author_archives` | `yes` | Content |
| Disable Emojis | `wpyeg_disable_emojis` | `yes` | Performance |
| Redirect Attachment Pages | `wpyeg_redirect_attachment_pages` | `yes` | SEO |
| Title-Only Admin Search | `wpyeg_title_only_admin_search` | `no` | UX |
| Front-End Admin Bar Behavior | `wpyeg_frontend_admin_bar_behavior` | `''` | UX |
| Disable Remember Me | `wpyeg_disable_remember_me` | `no` | Login |
| Remember Me Policy / Days | `wpyeg_remember_me_policy` / `_days` | `default` / `5` | Login |
| Regular Session Hours | `wpyeg_session_regular_hours` | `0` | Login |
| Login Logo Behavior | `wpyeg_login_logo_behavior` | `remove_logo` | Branding |
| Login Logo Link Home | `wpyeg_login_logo_link_home` | `yes` | Branding |

---

### Implementation notes
- Load these from an **mu-plugin** or a dedicated plugin, not the theme, so policy survives
  theme switches.
- Gate every snippet behind its `get_option()` toggle so site owners keep control.
- `wp-config.php` constants (`DISALLOW_FILE_EDIT`, `AUTOSAVE_INTERVAL`, `WP_POST_REVISIONS`)
  can't be toggled from options — surface them in your docs as recommended manual settings.
- Test REST and comment changes against the block editor before shipping; those two touch the
  most core functionality.
