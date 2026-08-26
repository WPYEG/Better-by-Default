<?php
/**
 * Plugin Name:       Better by Default
 * Plugin URI:        https://github.com/WPYEG/Better-by-Default
 * Description:        Sane defaults for every new WordPress site. Applies a menu of sensible security, update, UX, SEO, and performance defaults — each one individually toggleable from Settings → Better by Default. Built for the WPYEG Edmonton WordPress meetup.
 * Version:           1.2.3
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            WPYEG
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       sane-defaults
 *
 * ---------------------------------------------------------------------------
 * WORKSHOP NOTE
 * Every policy below is gated behind an option (prefix: wpyeg_). The common
 * shape, and the one to reach for first, is:
 *
 *     if ( wpyeg_defaults_enabled( 'some_key' ) ) { add_filter( ... ); }
 *
 * That single idea — "a default is just an opinionated filter behind a toggle"
 * — is the whole talk. Read wpyeg_defaults_schema() first; it's the map.
 *
 * Three variations appear below, each for a stated reason. Recognising why a
 * policy needs one is most of the lesson:
 *
 *   1. Gate at bootstrap (above). The hook is only registered when the setting
 *      is on, so a disabled policy costs one array lookup and nothing else.
 *   2. Register always, decide inside. Needed when one hook serves several
 *      settings, so no single toggle governs whether it should run at all
 *      (xmlrpc_methods answers to four).
 *   3. Compare a value rather than a yes/no. Select and number settings have no
 *      "enabled" state — frame_options, login_logo_behavior and the session
 *      lengths test what was chosen.
 *
 * Prefer shape 1. Reach for 2 or 3 only when 1 cannot express the policy, and
 * say why in a comment at the site.
 *
 * auth_cookie_expiration used to be the example for shape 2 and is now shape 3,
 * which is worth more than the correction. Its callback needs the $remember
 * argument the bootstrap cannot see, so "decide inside" looked forced. But the
 * question of what a login's length should BE is not the question of whether
 * this plugin should answer it at all, and the second one is answerable at
 * bootstrap. Needing a runtime argument does not oblige you to register.
 * ---------------------------------------------------------------------------
 *
 * @package BetterByDefault
 */

// Bail if called directly.
defined( 'ABSPATH' ) || exit;

/**
 * The single source of truth: every setting, its default, type, and label.
 *
 * Type is 'toggle' (yes/no), 'select', or 'number'. Group is the fieldset it
 * renders under on the settings screen.
 *
 * Labels and help text are deliberately NOT wrapped in __(). The bootstrap
 * reads this schema on plugins_loaded, and since WordPress 6.7 a translation
 * call before init triggers a _doing_it_wrong notice about just-in-time
 * loading. Everything this plugin prints runs after init — the settings screen,
 * admin notices, validation errors — and every one of those strings is
 * translated. The rule is the hook, not the string: after init, use __().
 */
function wpyeg_defaults_schema() {
	/*
	 * Built once per request. Unlike the stored option — see wpyeg_defaults_get()
	 * for why that one is deliberately uncached — this array is a literal in the
	 * source, so nothing can change it between calls and a static cannot go
	 * stale. It is read around fifty times on an ordinary request and once per
	 * field again on the settings screen, all of it rebuilding the same
	 * thirty-odd entries and their help text.
	 */
	static $schema = null;

	if ( null !== $schema ) {
		return $schema;
	}

	$schema = array(

		// --- Security ---------------------------------------------------
		'restrict_rest_user_discovery'    => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'security',
			'section' => 'rest',
			'label'   => 'Restrict REST API user discovery',
			'help'    => 'Hides <code>/wp/v2/users</code> from logged-out requests (stops username enumeration).',
		),
		'disable_rest'                    => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'security',
			'section' => 'rest',
			'label'   => 'Require auth for all REST requests',
			'help'    => 'Blocks anonymous REST entirely. The logged-in block editor still works, but public blocks, embeds, search, and integrations may not.',
		),
		'xmlrpc_allow_pingbacks'          => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'security',
			'section' => 'xmlrpc',
			'label'   => 'Accept incoming pingbacks',
			'help'    => 'OFF (default) removes <code>pingback.ping</code> — a spam/reflection-DDoS vector — and the <code>X-Pingback</code> header.',
		),
		'xmlrpc_allow_remote_publishing'  => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'security',
			'section' => 'xmlrpc',
			'label'   => 'Allow remote publishing (blogging apps)',
			'help'    => 'OFF (default) removes credential-authenticated <code>wp.*</code>, <code>metaWeblog.*</code>, <code>mt.*</code>, and <code>blogger.*</code> methods plus the RSD link. Leave ON while Jetpack is active unless connection and feature testing proves it unnecessary.',
		),
		'xmlrpc_allow_multicall'          => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'security',
			'section' => 'xmlrpc',
			'label'   => 'Allow system.multicall',
			'help'    => 'OFF (default) refuses <code>system.multicall</code>, a general batching wrapper with little established modern use. WordPress 4.4 prevented it from being used as a password-guessing multiplier, so refusing it now is modest defence-in-depth against batching, not a password control.',
		),
		'block_xmlrpc_endpoint'           => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'security',
			'section' => 'xmlrpc',
			'label'   => 'Block the endpoint entirely (returns 403)',
			'help'    => 'Strictest tier — <code>xmlrpc.php</code> returns <code>403</code> for every request. Leave XML-RPC reachable while Jetpack is active, unless connection and feature testing proves Jetpack no longer needs it. Prefer an edge block when possible; this plugin-level block still boots PHP.',
		),
		'disable_application_passwords'   => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'security',
			'section' => 'credentials',
			'label'   => 'Prohibit Application Passwords',
			'help'    => 'OFF (default) keeps them available — separate, revocable integration credentials for REST and XML-RPC. They inherit the owning user\'s access and bypass interactive 2FA, so use a least-privileged account.',
		),
		'require_strong_passwords'        => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'security',
			'section' => 'credentials',
			'label'   => 'Require strong passwords',
			'help'    => 'Server-side rule: 15+ characters, screened against known breaches, checked against a small blocklist, and not built from your username or the name part of your email. No forced uppercase, number, or symbol rules. The readme describes exactly what the breach check sends.',
		),
		'limit_unfiltered_html_to_admins' => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'security',
			'section' => 'capabilities',
			'label'   => 'Limit unfiltered HTML to administrators',
			'help'    => 'Editors hold <code>unfiltered_html</code> on single-site installs, which is enough to save a raw <code>&lt;script&gt;</code> into a post. This removes it from everyone except administrators (and Super Admins on multisite).',
		),
		'remove_version'                  => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'security',
			'section' => 'disclosure',
			'label'   => 'Remove WordPress version fingerprint',
			'help'    => 'Strips the generator meta tag. Obscurity, not hardening: it trims scanner noise but does not make an out-of-date site any safer, and the version still leaks from asset query strings and feeds. Off by default — patch instead. Turn on if you want the noise reduction.',
		),
		'security_headers'                => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'security',
			'section' => 'headers',
			'label'   => 'Send baseline security headers',
			'help'    => '<code>X-Content-Type-Options: nosniff</code> and <code>Referrer-Policy: strict-origin-when-cross-origin</code>. Both are low-risk. Framing is controlled separately below, because that is the one that can break a site. Already-set headers are never overwritten.',
		),
		'frame_options'                   => array(
			'default' => 'SAMEORIGIN',
			'type'    => 'select',
			'group'   => 'security',
			'section' => 'headers',
			'label'   => 'X-Frame-Options (clickjacking)',
			'help'    => 'Controls who may embed this site in an iframe. <code>SAMEORIGIN</code> blocks cross-origin framing, which stops clickjacking but also breaks legitimate embeds — an intranet dashboard, a screenshot or visual-review service, a kiosk or signage screen — usually as a silent blank frame. Leave unchanged if your host or CDN already sets <code>X-Frame-Options</code>, or if the site is meant to be embedded elsewhere.',
			'choices' => array(
				'SAMEORIGIN' => 'SAMEORIGIN — only this site may frame it',
				'DENY'       => 'DENY — nobody may frame it',
				''           => 'Leave unchanged (host/CDN sets it, or the site is embedded elsewhere)',
			),
		),
		'disable_ai_connectors'           => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'security',
			'section' => 'ai',
			'label'   => 'Disable AI connectors',
			'help'    => 'Turns off WordPress 7.0 AI provider connectors via the <code>wp_supports_ai</code> gate and closes the core Connectors screen. Also fires <code>wpyeg_disable_ai_connectors</code> for AI integrations core does not know about.',
		),

		// --- Updates ----------------------------------------------------
		'core_update_policy'              => array(
			'default' => 'minor',
			'type'    => 'select',
			'group'   => 'updates',
			'label'   => 'Automatic WordPress Core Updates',
			'help'    => 'Maintenance and security releases install automatically by default. Major releases should be tested and deployed within 30 days. An explicit <code>wp-config.php</code> policy takes precedence.',
			'choices' => array(
				'minor'   => 'Maintenance/security releases only — recommended',
				'all'     => 'All stable releases',
				'manual'  => 'No automatic core releases',
				'inherit' => 'Leave unchanged (WordPress, host, or another plugin decides)',
			),
		),
		// --- Content and public surfaces ---------------------------------
		'disable_comments'                => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'content',
			'section' => 'comments',
			'label'   => 'Disable comments, trackbacks and pingbacks',
			'help'    => 'Closes comments everywhere, hides existing threads, removes the admin menu.',
		),
		'disable_pingbacks'               => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'content',
			'section' => 'comments',
			'label'   => 'Default new posts to pings closed',
			'help'    => 'Sets the "allow pingbacks" default to off for newly created content.',
		),
		'disable_self_pingbacks'          => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'content',
			'section' => 'comments',
			'label'   => 'Disable self-pingbacks',
			'help'    => 'Stops internal links from generating pingback noise.',
		),
		'disable_author_archives'         => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'content',
			'section' => 'thin_pages',
			'label'   => 'Disable public author archives',
			'help'    => 'Redirects <code>/author/{slug}/</code> to home (another enumeration + thin-content fix).',
		),
		'redirect_attachment_pages'       => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'content',
			'section' => 'thin_pages',
			'label'   => 'Redirect attachment pages',
			'help'    => 'Sends thin attachment pages to the parent post, or to the file itself when the media is unattached. Skipped automatically when the theme provides <code>attachment.php</code> or <code>image.php</code>, since that theme meant to render them. Core has its own switch since 6.4 (<code>wp_attachment_pages_enabled</code>); this prefers the parent post over the bare file.',
		),
		'disable_emojis'                  => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'content',
			'section' => 'frontend',
			'label'   => 'Disable emoji script',
			'help'    => 'Removes the emoji detection script + inline CSS from every page.',
		),

		'disable_post_passwords'          => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'content',
			'section' => 'editor',
			'label'   => 'Hide post-password protection',
			'help'    => 'Hides the "Password protected" visibility option in the editor. Post passwords are weak and full-page caches bypass them. Cosmetic and non-destructive: it changes no data, and a post that already has a password keeps its field so it stays editable.',
		),
		'force_classic_editor'            => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'content',
			'section' => 'editor',
			'label'   => 'Force the classic editor',
			'help'    => 'Restores the pre-block editing experience for posts, pages, and custom post types, plus the classic Widgets screen. Front-end display of existing block content is unaffected.',
		),

		// --- Admin and front-end UX --------------------------------------
		'title_only_admin_search'         => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'ux',
			'section' => 'admin_search',
			'label'   => 'Title-only admin search',
			'help'    => 'Speeds up admin list-table search on big sites by matching titles only.',
		),
		'frontend_admin_bar_behavior'     => array(
			'default' => '',
			'type'    => 'select',
			'group'   => 'ux',
			'label'   => 'Front-End Admin Bar',
			'help'    => 'The WordPress toolbar shown across the top of the site for logged-in users. Hide it for non-admins, or for everyone.',
			'choices' => array(
				''                => 'Leave unchanged (WordPress default)',
				'hide_non_admins' => 'Hide for non-admins',
				'hide_all'        => 'Hide for everyone',
			),
		),

		'lowercase_upload_filenames'      => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'ux',
			'section' => 'media',
			'label'   => 'Lowercase upload filenames',
			'help'    => 'Lowercases new upload filenames, so a case-sensitive server and a case-insensitive one agree about what a file is called. Only new uploads are affected.',
		),
		'media_sizes_panel'               => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'ux',
			'section' => 'media',
			'label'   => 'Show generated image sizes',
			'help'    => 'Adds a read-only panel to the attachment edit screen listing the resized files WordPress generated, with their dimensions. Useful for confirming what exists without a media-management plugin.',
		),

		// --- Login and sessions ------------------------------------------
		'disable_remember_me'             => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'login',
			'section' => 'sessions',
			'label'   => 'Disable "Remember Me"',
			'help'    => 'Removes the "Remember Me" checkbox from the login form, so every login uses the regular session length below. Good for shared/kiosk machines.',
		),
		'session_regular_days'            => array(
			'default' => 2,
			'type'    => 'number',
			'group'   => 'login',
			'section' => 'sessions',
			'label'   => 'Regular session length (days)',
			'help'    => 'How long a normal (non-remembered) login stays signed in. WordPress\'s default is 2 days.',
			'min'     => 1,
		),
		'remember_me_days'                => array(
			'default' => 14,
			'type'    => 'number',
			'group'   => 'login',
			'section' => 'sessions',
			'label'   => 'Remember Me length (days)',
			'help'    => 'How long a remembered login stays signed in. WordPress\'s default is 14 days. It cannot be shorter than the regular session length above.',
			'min'     => 1,
		),

		// --- Branding ---------------------------------------------------
		'login_logo_behavior'             => array(
			'default' => 'keep_default',
			'type'    => 'select',
			'group'   => 'branding',
			'label'   => 'Login Logo',
			'help'    => 'The default logo links to wordpress.org. Left untouched by default, since changing the login screen out of the box is intrusive. Removing, unlinking, or replacing it keeps the login screen organizationally consistent and prevents the logo from sending users to an unexpected external site; those choices point the header link at your home page.',
			'choices' => array(
				'keep_default' => 'Keep the WordPress logo and wp.org link (WordPress default)',
				'remove_logo'  => 'Remove the logo and the wp.org link',
				'unlink_logo'  => 'Keep the logo, drop the wp.org link',
				'replace_logo' => 'Replace the logo with the site icon',
			),
		),

		// --- Email ----------------------------------------------------------
		'mail_deliverability_notice'      => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'email',
			'section' => 'deliverability',
			'label'   => 'Warn when the site\'s From address looks undeliverable',
			'help'    => 'WordPress sends mail from <code>wordpress@yourdomain</code> unless something changes it. On a domain that cannot actually send — a staging host, a <code>.local</code> address, a domain with no mail records — password resets and order receipts fail silently. This shows an admin notice when the address looks undeliverable. It never blocks or alters mail, and it stays quiet on local environments.',
		),

		// --- Performance ------------------------------------------------
		'throttle_heartbeat'              => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'performance',
			'section' => 'heartbeat',
			'label'   => 'Throttle the Heartbeat API',
			'help'    => 'Slows admin polling to 60s and drops it on the dashboard home.',
		),
	);

	return $schema;
}

/** Human-friendly group titles for the settings screen. */
function wpyeg_defaults_groups() {
	return array(
		'security'    => 'Security and Attack Surface',
		'updates'     => 'Updates',
		'content'     => 'Content and Public Surfaces',
		'ux'          => 'Admin and Front-End UX',
		'login'       => 'Login and Sessions',
		'branding'    => 'Branding',
		'performance' => 'Performance',
		'email'       => 'Email',
	);
}

/**
 * Safe inline markup permitted in settings help text.
 *
 * Keep this deliberately narrow: help copy only needs semantic code styling
 * for machine-facing identifiers and links to authoritative references.
 *
 * @return array<string,array<string,bool>>
 */
function wpyeg_defaults_help_allowed_html() {
	return array(
		'code' => array(),
		'a'    => array(
			'href' => true,
		),
	);
}

/** The single option name that stores all settings as one array. */
const WPYEG_DEFAULTS_OPTION = 'wpyeg_better_by_default';

/**
 * Read one setting, falling back to its schema default.
 *
 * @param string $key Schema key (without the wpyeg_ prefix).
 * @return mixed
 */
function wpyeg_defaults_get( $key ) {
	$schema = wpyeg_defaults_schema();
	if ( ! isset( $schema[ $key ] ) ) {
		return null;
	}

	// Deliberately uncached: the option is autoloaded, so get_option() answers
	// from the options cache without a query. A static here would only add a
	// second cache that goes stale the moment anything calls update_option()
	// — which is exactly what saving the settings screen does.
	$stored = get_option( WPYEG_DEFAULTS_OPTION, array() );
	if ( ! is_array( $stored ) ) {
		$stored = array();
	}

	return array_key_exists( $key, $stored ) ? $stored[ $key ] : $schema[ $key ]['default'];
}

/**
 * Convenience boolean check for toggle settings.
 *
 * @param string $key Schema key.
 * @return bool
 */
function wpyeg_defaults_enabled( $key ) {
	return 'yes' === wpyeg_defaults_get( $key );
}

/**
 * Apply Better by Default's maintenance/security core update policy.
 *
 * @param bool $enabled WordPress's current decision.
 * @return bool
 */
function wpyeg_defaults_allow_minor_core_updates( $enabled ) {
	$policy = wpyeg_defaults_get( 'core_update_policy' );

	if ( 'inherit' === $policy ) {
		return $enabled;
	}

	return in_array( $policy, array( 'minor', 'all' ), true );
}

/**
 * Apply Better by Default's stable major core update policy.
 *
 * @param bool $enabled WordPress's current decision.
 * @return bool
 */
function wpyeg_defaults_allow_major_core_updates( $enabled ) {
	$policy = wpyeg_defaults_get( 'core_update_policy' );

	if ( 'inherit' === $policy ) {
		return $enabled;
	}

	return 'all' === $policy;
}

/**
 * Keep development builds out of every explicit stable-release policy.
 *
 * @param bool $enabled WordPress's current decision.
 * @return bool
 */
function wpyeg_defaults_allow_dev_core_updates( $enabled ) {
	return 'inherit' === wpyeg_defaults_get( 'core_update_policy' ) ? $enabled : false;
}

/*
 * =======================================================================
 * BOOTSTRAP — wire each enabled policy to its hook.
 * =====================================================================
 */

add_action( 'plugins_loaded', 'wpyeg_defaults_bootstrap' );

/**
 * A comment count of zero, as the string core would have returned.
 *
 * The type is the point. core's get_comments_number() returns
 * `$post->comment_count`, which comes off the database as a string, and its own
 * docblock says `string|int`. Most consumers cast; core's own Comments Title
 * block does not — wp-includes/blocks/comments-title.php compares
 * `'0' === $comments_count` and returns early. Handing it an integer meant that
 * early return never fired, and a comments heading rendered over a thread with
 * nothing in it, on block themes.
 *
 * '0' is falsy and casts to 0, so every consumer that casts or tests truthiness
 * is unaffected.
 *
 * @return string
 */
function wpyeg_defaults_zero_comment_count() {
	return '0';
}

/**
 * The site home, for the login screen's header link.
 *
 * Replaces `add_filter( 'login_headerurl', 'home_url' )`. A filter hands its
 * callback the value being filtered, and home_url() takes its first argument as
 * a *path* — so the incoming `https://wordpress.org/` was appended to the site
 * URL and the logo linked to `https://example.com/https://wordpress.org/`. Every
 * install with the logo removed, unlinked or replaced had a broken link.
 *
 * @return string
 */
function wpyeg_defaults_login_header_url() {
	return home_url();
}

/**
 * Wire every enabled policy to its WordPress hook.
 *
 * Runs once on plugins_loaded. Each policy is an `if ( option )` around the
 * add_filter or add_action that implements it, so a disabled setting costs
 * nothing beyond the check itself.
 *
 * @return void
 */
function wpyeg_defaults_bootstrap() {

	/* ----- Updates ----- */

	/*
	 * wp-config.php is the operator's highest-level declaration. Do not make a
	 * settings-screen choice silently overrule it. Without that constant, these
	 * documented filters make the site's policy independent of its install age
	 * and of the major-update choice previously stored by core.
	 */
	if ( ! defined( 'WP_AUTO_UPDATE_CORE' ) && 'inherit' !== wpyeg_defaults_get( 'core_update_policy' ) ) {
		add_filter( 'allow_minor_auto_core_updates', 'wpyeg_defaults_allow_minor_core_updates' );
		add_filter( 'allow_major_auto_core_updates', 'wpyeg_defaults_allow_major_core_updates' );
		add_filter( 'allow_dev_auto_core_updates', 'wpyeg_defaults_allow_dev_core_updates' );
	}

	/* ----- Security ----- */

	if ( wpyeg_defaults_enabled( 'restrict_rest_user_discovery' ) ) {
		add_filter(
			'rest_endpoints',
			function ( $endpoints ) {
				if ( ! is_user_logged_in() ) {
					unset( $endpoints['/wp/v2/users'] );
					unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
				}
				return $endpoints;
			}
		);
	}

	if ( wpyeg_defaults_enabled( 'disable_rest' ) ) {
		add_filter( 'rest_authentication_errors', 'wpyeg_defaults_require_rest_auth', PHP_INT_MAX );

		/*
		 * oEmbed stays reachable past the gate so other sites can still embed
		 * this one — but it must not answer with what the gate was closed to
		 * protect. Left alone it returns author_name and an author_url carrying
		 * the account nicename, to exactly the anonymous caller who has just
		 * been refused /wp/v2/users.
		 *
		 * The same filter runs for hidden author archives (below). Registering
		 * it twice is harmless — it unsets keys that are already gone — and the
		 * two reasons are genuinely independent: one is about the archive, this
		 * one is about the gate.
		 */
		add_filter( 'oembed_response_data', 'wpyeg_defaults_strip_oembed_author' );

		/*
		 * Closing a door and taking down the sign are two separate jobs. With the
		 * filter above in place every anonymous request gets a 401, and the page
		 * carries on advertising the endpoint anyway — core prints the discovery
		 * link three different ways, so all three have to go.
		 */
		remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
		remove_action( 'template_redirect', 'rest_output_link_header', 11 );
		remove_action( 'xmlrpc_rsd_apis', 'rest_output_rsd' );
	}

	/*
	 * XML-RPC is per-category, not all-or-nothing. Each category is off by
	 * default (locked down) and opt-in to re-enable — the same shape PMP uses.
	 * pingbacks and remote publishing come off via the xmlrpc_methods filter;
	 * system.multicall and a full endpoint block need a server-class swap,
	 * because IXR re-adds multicall after the filter runs.
	 */
	add_filter(
		'xmlrpc_methods',
		function ( $methods ) {
			// demo.* are inert core test methods with no legitimate use. Always drop
			// them (no toggle) so a locked-down endpoint stops answering scanner
			// probes like demo.sayHello with a cheerful "Hello!".
			unset( $methods['demo.sayHello'], $methods['demo.addTwoNumbers'] );

			if ( ! wpyeg_defaults_enabled( 'xmlrpc_allow_pingbacks' ) ) {
				unset( $methods['pingback.ping'], $methods['pingback.extensions.getPingbacks'] );
			}
			if ( ! wpyeg_defaults_enabled( 'xmlrpc_allow_remote_publishing' ) ) {
				foreach ( array_keys( $methods ) as $name ) {
					if ( preg_match( '/^(wp|metaWeblog|mt|blogger)\./', (string) $name ) ) {
						unset( $methods[ $name ] );
					}
				}
			}
			return $methods;
		},
		PHP_INT_MAX
	);

	// Disable core methods that require authentication when remote publishing is
	// off. Despite its name, xmlrpc_enabled does not disable the endpoint,
	// pingbacks, or custom unauthenticated methods.
	add_filter(
		'xmlrpc_enabled',
		function ( $enabled ) {
			return wpyeg_defaults_enabled( 'xmlrpc_allow_remote_publishing' ) ? $enabled : false;
		}
	);

	// Strip the pingback discovery header when pingbacks are off.
	add_filter(
		'wp_headers',
		function ( $headers ) {
			if ( ! wpyeg_defaults_enabled( 'xmlrpc_allow_pingbacks' ) ) {
				unset( $headers['X-Pingback'] );
			}
			return $headers;
		}
	);

	// Drop the RSD link (blogging-client discovery) when remote publishing is off.
	if ( ! wpyeg_defaults_enabled( 'xmlrpc_allow_remote_publishing' ) ) {
		remove_action( 'wp_head', 'rsd_link' );
	}

	/*
	 * The replacement server class is defined lazily inside this filter: it only
	 * runs from xmlrpc.php, where the parent wp_xmlrpc_server is already loaded,
	 * so extending it at plugin-load time (on every ordinary request) is avoided.
	 */
	add_filter(
		'wp_xmlrpc_server_class',
		function ( $class ) {
			if ( wpyeg_defaults_enabled( 'block_xmlrpc_endpoint' ) ) {
				if ( ! class_exists( 'Wpyeg_Blocked_XMLRPC_Server' ) ) {
					/**
					 * Refuses every XML-RPC request with a 403.
					 *
					 * Deliberately does not extend wp_xmlrpc_server: nothing should be
					 * reachable when the endpoint is blocked outright.
					 */
					class Wpyeg_Blocked_XMLRPC_Server {
						/**
						 * Answer every request with 403 and stop.
						 *
						 * @return void
						 */
						public function serve_request() {
							status_header( 403 );
							exit( 'XML-RPC services are disabled on this site.' );
						}
					}
				}
				return 'Wpyeg_Blocked_XMLRPC_Server';
			}

			if ( ! wpyeg_defaults_enabled( 'xmlrpc_allow_multicall' ) ) {
				if ( ! class_exists( 'Wpyeg_Multicall_Disabled_Server' ) ) {
					/**
					 * Serves XML-RPC normally but refuses system.multicall.
					 *
					 * WordPress 4.4 stopped testing credentials after the first failed
					 * authentication in one XML-RPC request. Refusing multicall is now
					 * modest defence-in-depth against general batching, not a fix for
					 * the obsolete "thousands of password guesses" claim.
					 */
					class Wpyeg_Multicall_Disabled_Server extends wp_xmlrpc_server {
						/**
						 * Refuse the batching wrapper.
						 *
						 * @param array $methodcalls Batched calls, ignored.
						 * @return IXR_Error
						 */
						public function multiCall( $methodcalls ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Overrides a core method name.
							return new IXR_Error( 405, 'system.multicall is disabled on this site.' );
						}
					}
				}
				return 'Wpyeg_Multicall_Disabled_Server';
			}

			return $class;
		}
	);

	if ( wpyeg_defaults_enabled( 'disable_application_passwords' ) ) {
		add_filter( 'wp_is_application_passwords_available', '__return_false' );
	}

	if ( wpyeg_defaults_enabled( 'require_strong_passwords' ) ) {
		add_action( 'user_profile_update_errors', 'wpyeg_defaults_validate_profile_password', 10, 3 );
		add_action( 'validate_password_reset', 'wpyeg_defaults_validate_reset_password', 10, 2 );
		add_filter( 'rest_endpoints', 'wpyeg_defaults_guard_rest_password_arg' );
		add_filter( 'rest_pre_insert_user', 'wpyeg_defaults_validate_rest_password', 10, 2 );
	}

	if ( wpyeg_defaults_enabled( 'limit_unfiltered_html_to_admins' ) ) {
		// Very late, so it has the final say over other user_has_cap filters.
		add_filter( 'user_has_cap', 'wpyeg_defaults_limit_unfiltered_html', PHP_INT_MAX - 1, 4 );
	}

	if ( wpyeg_defaults_enabled( 'remove_version' ) ) {
		remove_action( 'wp_head', 'wp_generator' );
		add_filter( 'the_generator', '__return_empty_string' );
	}

	if ( wpyeg_defaults_enabled( 'security_headers' ) ) {
		add_filter( 'wp_headers', 'wpyeg_defaults_set_content_type_header' );
		add_filter( 'wp_headers', 'wpyeg_defaults_set_referrer_policy_header' );
	}

	// Framing is its own setting: it is the only one of the three that can break
	// a working site, so it must be switchable without also giving up nosniff.
	if ( '' !== wpyeg_defaults_get( 'frame_options' ) ) {
		add_filter( 'wp_headers', 'wpyeg_defaults_set_frame_option_header' );
	}

	if ( wpyeg_defaults_enabled( 'disable_ai_connectors' ) ) {
		// WordPress 7.0 gates AI provider connectors behind wp_supports_ai
		// (default true). Returning false stops them registering.
		add_filter( 'wp_supports_ai', '__return_false' );

		// Settings → Connectors is where those providers get configured.
		add_action(
			'admin_menu',
			function () {
				remove_submenu_page( 'options-general.php', 'options-connectors.php' );
			},
			11
		);

		// Hiding a menu is not access control — the URL still resolves — so
		// close the screen itself, not just the link to it.
		add_action(
			'admin_init',
			function () {
				global $pagenow;
				if ( 'options-connectors.php' === $pagenow ) {
					wp_die(
						esc_html__( 'AI connectors are disabled on this site.', 'sane-defaults' ),
						'',
						array( 'response' => 403 )
					);
				}
			}
		);

		/*
		 * Seam for AI integrations core does not know about — hook it to
		 * unregister your own providers or hide their UI.
		 *
		 * Fired on init, not here. This bootstrap runs on plugins_loaded, and a
		 * plugin that registers its hooks from inside its own plugins_loaded
		 * callback — an extremely common shape — has not run yet if it loads
		 * after this one, so it would never see the action. Worse, the providers
		 * this seam exists to unregister are themselves registered on init, so
		 * firing at plugin load would arrive before there was anything to remove.
		 * Priority 20 puts it after the ordinary init registrations.
		 */
		add_action(
			'init',
			function () {
				/**
				 * Unregister AI providers core does not know about.
				 *
				 * Fires only when the disable_ai_connectors default is on.
				 */
				do_action( 'wpyeg_disable_ai_connectors' );
			},
			20
		);
	}

	/* ----- Content and public surfaces ----- */

	if ( wpyeg_defaults_enabled( 'disable_comments' ) ) {
		add_filter( 'comments_open', '__return_false', 20 );
		add_filter( 'pings_open', '__return_false', 20 );
		add_filter( 'comments_array', '__return_empty_array', 20 );

		add_action(
			'init',
			function () {
				foreach ( get_post_types() as $type ) {
					if ( post_type_supports( $type, 'comments' ) ) {
						remove_post_type_support( $type, 'comments' );
						remove_post_type_support( $type, 'trackbacks' );
					}
				}
			}
		);

		add_action(
			'admin_menu',
			function () {
				remove_menu_page( 'edit-comments.php' );
			}
		);

		add_action(
			'wp_before_admin_bar_render',
			function () {
				global $wp_admin_bar;
				if ( $wp_admin_bar ) {
					$wp_admin_bar->remove_node( 'comments' );
				}
			}
		);

		// New content defaults to comments closed, not merely filtered closed.
		add_filter(
			'get_default_comment_status',
			function () {
				return 'closed';
			}
		);

		// Report zero comments. wp_count_comments() answers zero once the query
		// filter below is in place, but get_comments_number() reads the post's
		// cached comment_count and does not — so the theme prints "1 Comment" as
		// a heading over a thread that renders nothing.
		//
		// A named callback returning the *string* "0" rather than __return_zero:
		// see wpyeg_defaults_zero_comment_count() for why the type matters.
		add_filter( 'get_comments_number', 'wpyeg_defaults_zero_comment_count', 20 );

		// Stop advertising comment feeds that lead nowhere, then stop serving
		// them. Removing the link is not removing the feed: /comments/feed/ and
		// <post>/feed/ keep answering 200 to anyone who types the URL, and a
		// crawler that saw one once keeps asking for it.
		add_filter( 'feed_links_show_comments_feed', '__return_false' );
		add_filter( 'feed_links_extra_show_post_comments_feed', '__return_false' );
		add_action( 'template_redirect', 'wpyeg_defaults_block_comment_feeds', 9 );

		// Answer comment queries as empty. The three filters above cover the theme's
		// comment template; everything else that reads comments — /wp/v2/comments
		// most of all — goes to the database and answers normally without this.
		add_filter( 'comments_pre_query', 'wpyeg_defaults_empty_comment_queries', 10, 2 );

		// Take the comment blocks out of the inserter: on a site with comments off
		// they can only render nothing.
		add_filter( 'allowed_block_types_all', 'wpyeg_defaults_remove_comment_blocks', PHP_INT_MAX );

		// And stop the ones already saved in a block theme's templates from
		// rendering. The inserter filter decides what an editor may add next; it
		// has no say over markup that is already in the template.
		add_filter( 'render_block', 'wpyeg_defaults_suppress_comment_blocks', 10, 2 );
	}

	if ( wpyeg_defaults_enabled( 'disable_pingbacks' ) ) {
		add_filter( 'pre_option_default_pingback_flag', '__return_zero' );
		add_filter(
			'pre_option_default_ping_status',
			function () {
				return 'closed';
			}
		);
	}

	if ( wpyeg_defaults_enabled( 'disable_self_pingbacks' ) ) {
		add_action(
			'pre_ping',
			function ( &$links ) {
				$home = home_url();
				foreach ( (array) $links as $key => $link ) {
					if ( 0 === strpos( $link, $home ) ) {
						unset( $links[ $key ] );
					}
				}
			}
		);
	}

	if ( wpyeg_defaults_enabled( 'disable_author_archives' ) ) {
		add_action(
			'template_redirect',
			function () {
				if ( is_author() ) {
					wp_safe_redirect( home_url( '/' ), 301 );
					exit;
				}
			}
		);
		// Redirecting /author/ pages does not stop feeds naming the author in
		// every <dc:creator> element, so the login names this default exists to
		// hide were still published — just one URL over.
		add_filter( 'the_author', 'wpyeg_defaults_mask_feed_author' );

		// Two more routes do the same thing, and neither is called an author
		// archive. oEmbed returns author_name and author_url for every post,
		// the URL carrying the nicename; core's users sitemap is a list of
		// author archive URLs by construction. Both measured live with the
		// redirect working correctly.
		add_filter( 'oembed_response_data', 'wpyeg_defaults_strip_oembed_author' );
		add_filter( 'wp_sitemaps_add_provider', 'wpyeg_defaults_drop_users_sitemap', 10, 2 );
	}

	if ( wpyeg_defaults_enabled( 'redirect_attachment_pages' ) ) {
		add_action(
			'template_redirect',
			function () {
				if ( ! is_attachment() ) {
					return;
				}

				$target = wpyeg_defaults_attachment_redirect_target( get_queried_object_id() );

				if ( '' === $target ) {
					return; // Nothing better to offer — let WordPress render the page.
				}

				// wp_safe_redirect() bounces to wp-admin when the host is not on the
				// allowlist, which offloaded media (S3, a CDN) would trigger. Allow
				// this one host rather than dumping visitors on the dashboard.
				add_filter(
					'allowed_redirect_hosts',
					function ( $hosts ) use ( $target ) {
						$host = wp_parse_url( $target, PHP_URL_HOST );
						if ( $host ) {
							$hosts[] = $host;
						}
						return $hosts;
					}
				);

				wp_safe_redirect( $target, 301 );
				exit;
			}
		);
	}

	if ( wpyeg_defaults_enabled( 'disable_emojis' ) ) {
		add_action(
			'init',
			function () {
				remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
				remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
				remove_action( 'wp_print_styles', 'print_emoji_styles' );
				remove_action( 'admin_print_styles', 'print_emoji_styles' );
				remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
				remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
				remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
				add_filter( 'emoji_svg_url', '__return_false' );
				// The classic editor loads emoji support as a TinyMCE plugin, which
				// none of the removals above reach — they cover the front end, the
				// admin head, feeds and mail. Without this, the editor still loads
				// wp-emoji-release.min.js on a site that asked for no emojis.
				add_filter( 'tiny_mce_plugins', 'wpyeg_defaults_remove_emoji_tinymce_plugin' );
			}
		);
	}

	/* ----- Admin and front-end UX ----- */

	if ( wpyeg_defaults_enabled( 'title_only_admin_search' ) ) {
		// Narrow the search COLUMNS, don't rewrite the whole clause. The
		// post_search_columns filter (WP 6.2+) keeps core's term parsing,
		// -exclusions, and the logged-out post_password guard intact — the
		// blunt posts_search rewrite throws all of that away.
		add_filter(
			'post_search_columns',
			function ( $columns, $search, $query ) {
				if ( is_admin() && $query->is_main_query() ) {
					return array( 'post_title' );
				}
				return $columns;
			},
			10,
			3
		);
	}

	$bar = wpyeg_defaults_get( 'frontend_admin_bar_behavior' );
	if ( 'hide_all' === $bar ) {
		add_filter( 'show_admin_bar', '__return_false' );
	} elseif ( 'hide_non_admins' === $bar ) {
		add_filter(
			'show_admin_bar',
			function ( $show ) {
				return current_user_can( 'manage_options' ) ? $show : false;
			}
		);
	}

	if ( wpyeg_defaults_enabled( 'disable_post_passwords' ) ) {
		add_action( 'admin_print_footer_scripts', 'wpyeg_defaults_hide_post_password_ui' );
	}

	if ( wpyeg_defaults_enabled( 'force_classic_editor' ) ) {
		wpyeg_defaults_force_classic_editor();
	}

	if ( wpyeg_defaults_enabled( 'lowercase_upload_filenames' ) ) {
		add_filter( 'sanitize_file_name', 'wpyeg_defaults_lowercase_filename', 20 );
	}

	if ( wpyeg_defaults_enabled( 'media_sizes_panel' ) ) {
		add_action( 'add_meta_boxes_attachment', 'wpyeg_defaults_media_sizes_meta_box' );
	}

	/* ----- Email ----- */

	if ( wpyeg_defaults_enabled( 'mail_deliverability_notice' ) ) {
		add_action( 'admin_notices', 'wpyeg_defaults_render_mail_config_notice' );
	}

	/* ----- Login and sessions ----- */

	if ( wpyeg_defaults_enabled( 'disable_remember_me' ) ) {
		// Strip the submitted value server-side as well as hiding the checkbox, so
		// a forged POST cannot opt back into a persistent session. login_init fires
		// before wp-login.php reads $_POST['rememberme'].
		add_action(
			'login_init',
			function () {
				unset( $_POST['rememberme'], $_REQUEST['rememberme'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
			}
		);
		// CSS at login_head, not a script at login_footer. The script approach
		// leaves the checkbox visible and tickable with JavaScript off, and under
		// a strict script-src CSP that blocks inline scripts. The $_POST strip
		// above is what actually enforces the policy either way; this is the part
		// that has to survive a browser that will not run our JavaScript.
		add_action(
			'login_head',
			function () {
				echo '<style id="wpyeg-hide-remember-me">.login form .forgetmenot { display: none; }</style>';
			}
		);
	}

	/*
	 * Session length is set through a REPLACING filter — the callback returns
	 * its own number and throws away the one it was handed. That kind of filter
	 * does not compose: when two plugins register one, WordPress keeps whichever
	 * ran last, the other silently does nothing, and both settings screens go on
	 * displaying their own number. See
	 * docs/when-two-plugins-set-the-same-default.md, where this plugin is one of
	 * the three measured doing it.
	 *
	 * A number filter cannot be made additive, so the honest move is to stay out
	 * of a fight there is nothing to win. Both defaults here are WordPress's own
	 * values, so on a site that has not changed them, registering would assert
	 * core's answer over another plugin's deliberate one. Register only when
	 * these settings actually say something WordPress does not already do.
	 */
	if ( wpyeg_defaults_session_policy_is_custom() ) {
		/*
		 * Priority 50, not the default 10, and the reasoning is the other half of
		 * the paragraph above. Declining a fight worth nothing is only half a
		 * policy; the other half is winning the one that is worth something. On a
		 * replacing filter the LAST callback to run is the one that counts, so
		 * registering early does not merely fail to help — it guarantees losing to
		 * anything at a default priority. A session length someone deliberately
		 * set, silently overridden by whichever plugin happened to load later, is
		 * precisely the outcome setting it was meant to prevent.
		 *
		 * 50 leaves headroom in both directions: a site that wants the last word
		 * over this can still register above it, which is a decision it makes
		 * rather than one load order makes for it.
		 *
		 * A named function, not a closure, for a related reason: this is the one
		 * hook here likely to be contested, and `wp_filter` can only name a
		 * callback that has a name. A plugin nobody can identify in $wp_filter is
		 * a plugin nobody can diagnose.
		 */
		add_filter( 'auth_cookie_expiration', 'wpyeg_defaults_auth_cookie_expiration', 50, 3 );
	}

	/* ----- Branding ----- */

	$login_logo = wpyeg_defaults_get( 'login_logo_behavior' );

	if ( 'remove_logo' === $login_logo ) {
		add_action(
			'login_head',
			function () {
				echo '<style>#login h1 a, .login h1 a { display:none; }</style>';
			}
		);
	} elseif ( 'replace_logo' === $login_logo ) {
		add_action(
			'login_head',
			function () {
				$icon = function_exists( 'get_site_icon_url' ) ? get_site_icon_url( 84 ) : '';
				if ( $icon ) {
					echo '<style>#login h1 a, .login h1 a { background-image:url(' . esc_url( $icon ) . '); background-size:contain; }</style>';
				}
			}
		);
	}

	// Removing, unlinking, or replacing the logo all repoint the header link
	// at the site home instead of wordpress.org. There is no separate toggle:
	// a replacement/removed logo linking back to wp.org makes no sense.
	if ( in_array( $login_logo, array( 'remove_logo', 'unlink_logo', 'replace_logo' ), true ) ) {
		add_filter( 'login_headerurl', 'wpyeg_defaults_login_header_url' );
		add_filter(
			'login_headertext',
			function () {
				return get_bloginfo( 'name' );
			}
		);
	}

	/* ----- Performance ----- */

	if ( wpyeg_defaults_enabled( 'throttle_heartbeat' ) ) {
		add_filter(
			'heartbeat_settings',
			function ( $settings ) {
				$settings['interval'] = 60;
				return $settings;
			}
		);
		add_action(
			'init',
			function () {
				if ( is_admin() ) {
					global $pagenow;
					if ( 'index.php' === $pagenow ) {
						wp_deregister_script( 'heartbeat' );
					}
				}
			}
		);
	}
}

/**
 * Whether the session settings say anything WordPress does not already do.
 *
 * Both length defaults are WordPress's own — 2 days and 14 days — so a site
 * that has never touched them wants exactly core's behaviour, and the filter
 * that would assert it is one no plugin can share. Disabling Remember Me is a
 * real policy whatever the numbers say, so it counts on its own.
 *
 * @return bool
 */
function wpyeg_defaults_session_policy_is_custom() {
	if ( wpyeg_defaults_enabled( 'disable_remember_me' ) ) {
		return true;
	}

	$schema = wpyeg_defaults_schema();

	foreach ( array( 'session_regular_days', 'remember_me_days' ) as $key ) {
		if ( (int) wpyeg_defaults_get( $key ) !== (int) $schema[ $key ]['default'] ) {
			return true;
		}
	}

	return false;
}

/**
 * Apply the configured session lengths.
 *
 * One callback covers both, because core asks the same filter for each and
 * distinguishes them with $remember. When Remember Me is disabled every login
 * gets the regular length.
 *
 * The max() is a second belt, and the reason is that sanitize is not enough on
 * its own. Sanitize does keep a stored remembered length at or above the regular
 * one — but it only runs when the settings form is saved. An option written by
 * WP-CLI, a migration, or another plugin never passes through it, and this is
 * where that would otherwise surface: ticking Remember Me shortening a login,
 * which is the one thing this setting exists to prevent.
 *
 * Only registered when wpyeg_defaults_session_policy_is_custom() — see the
 * comment at the registration site for why that matters more than it looks.
 *
 * @param int  $expiration Length WordPress or another plugin arrived at.
 * @param int  $user_id    User the cookie is for (unused).
 * @param bool $remember   Whether the login ticked Remember Me.
 * @return int
 */
function wpyeg_defaults_auth_cookie_expiration( $expiration, $user_id, $remember ) {
	unset( $user_id );

	$regular_days = (int) wpyeg_defaults_get( 'session_regular_days' );
	$regular      = $regular_days > 0 ? $regular_days * DAY_IN_SECONDS : $expiration;

	if ( wpyeg_defaults_enabled( 'disable_remember_me' ) ) {
		return $regular;
	}

	if ( $remember ) {
		$remember_days = (int) wpyeg_defaults_get( 'remember_me_days' );
		$remembered    = $remember_days > 0 ? $remember_days * DAY_IN_SECONDS : $expiration;

		return max( $regular, $remembered );
	}

	return $regular;
}

/**
 * Remove `unfiltered_html` from everyone except administrators.
 *
 * Editors hold this capability on single-site installs, which is enough to save
 * a raw `<script>` into a post. This runs on `user_has_cap`, so it sees the
 * capability map WordPress already resolved.
 *
 * The recursion trap is why it reads roles and the resolved map directly, and
 * why `is_super_admin()` sits behind `is_multisite()`. This runs *inside* the
 * `user_has_cap` filter, so any capability check made here re-enters the same
 * filter and recurses until the stack blows. On single site `is_super_admin()`
 * calls `has_cap( 'delete_users' )`; on multisite it reads the network list,
 * which is safe. Never call `current_user_can()` or `user_can()` from here.
 *
 * @param array    $allcaps Resolved capabilities.
 * @param array    $caps    Required primitive caps (unused).
 * @param array    $args    Context args (unused).
 * @param \WP_User $user    The user being checked.
 * @return array
 */
function wpyeg_defaults_limit_unfiltered_html( $allcaps, $caps, $args, $user ) {
	if ( empty( $allcaps['unfiltered_html'] ) ) {
		return $allcaps;
	}

	$roles = ( isset( $user->roles ) && is_array( $user->roles ) ) ? $user->roles : array();

	// `manage_options` is already resolved in $allcaps, so reading it does not recurse.
	if ( in_array( 'administrator', $roles, true ) || ! empty( $allcaps['manage_options'] ) ) {
		return $allcaps;
	}

	if ( is_multisite() && isset( $user->ID ) && is_super_admin( $user->ID ) ) {
		return $allcaps;
	}

	$allcaps['unfiltered_html'] = false;

	return $allcaps;
}

/**
 * Force the classic editing experience.
 *
 * Four filters, because core asks the question four ways. The post-type gate is
 * a separate decision from the per-post one — a custom post type registered
 * with `show_in_rest` is measured against it directly — so filtering only the
 * per-post gate leaves those post types on the block editor.
 */
function wpyeg_defaults_force_classic_editor() {
	add_filter( 'use_block_editor_for_post', '__return_false' );
	add_filter( 'use_block_editor_for_post_type', '__return_false' );
	add_filter( 'gutenberg_can_edit_post', '__return_false' );  // Standalone Gutenberg plugin.
	add_filter( 'use_widgets_block_editor', '__return_false' ); // Classic Widgets screen.
}

/**
 * Lowercase a new upload filename.
 *
 * Hooked at priority 20, after core has sanitized, so only new uploads are
 * affected. UTF-8 aware where mbstring is available.
 *
 * @param string $filename Sanitized filename.
 * @return string
 */
function wpyeg_defaults_lowercase_filename( $filename ) {
	return function_exists( 'mb_strtolower' ) ? mb_strtolower( $filename, 'UTF-8' ) : strtolower( $filename );
}

/**
 * Hide the "Password protected" visibility option in the editor.
 *
 * Cosmetic and non-destructive: it hides UI, changes no data, and leaves the
 * field in place on a post that already has a password so that post stays
 * editable. It depends on editor DOM selectors, so re-check after major
 * WordPress editor changes — if a selector goes stale the field reappears and
 * nothing breaks.
 */
function wpyeg_defaults_hide_post_password_ui() {
	global $pagenow, $post;

	if ( empty( $pagenow ) || ( 'post.php' !== $pagenow && 'post-new.php' !== $pagenow ) ) {
		return;
	}

	if ( ! empty( $post->post_password ) ) {
		return;
	}
	?>
	<style id="wpyeg-hide-post-password">
		#visibility-radio-password,
		label[for="visibility-radio-password"],
		#editor-post-password-0,
		label[for="editor-post-password-0"],
		#editor-post-password-0-description {
			display: none;
		}
	</style>
	<?php
}

/**
 * Add a read-only panel listing the generated sizes for an attachment.
 *
 * Runs on add_meta_boxes_attachment, which passes the post — but registering a
 * meta box does not need it, so the parameter is not accepted.
 */
function wpyeg_defaults_media_sizes_meta_box() {
	add_meta_box(
		'wpyeg-media-sizes',
		__( 'Generated Sizes', 'sane-defaults' ),
		'wpyeg_defaults_render_media_sizes',
		null,
		'side',
		'low'
	);
}

/**
 * Render the generated-sizes panel.
 *
 * @param \WP_Post $post Attachment post.
 */
function wpyeg_defaults_render_media_sizes( $post ) {
	$meta = wp_get_attachment_metadata( $post->ID );

	if ( empty( $meta['sizes'] ) || ! is_array( $meta['sizes'] ) ) {
		echo '<p>' . esc_html__( 'No generated sizes.', 'sane-defaults' ) . '</p>';
		return;
	}

	echo '<ul style="margin:0">';
	foreach ( $meta['sizes'] as $name => $size ) {
		printf(
			'<li><strong>%s</strong> — %d&times;%d</li>',
			esc_html( $name ),
			(int) $size['width'],
			(int) $size['height']
		);
	}
	echo '</ul>';
}

/**
 * Whether Jetpack is active on this site.
 *
 * Jetpack reaches WordPress.com over XML-RPC, so blocking the endpoint breaks
 * the connection and everything downstream of it. Nothing here acts on that by
 * itself: a toggle that quietly refuses to do what it says is worse than one
 * that warns and then obeys. This only decides whether the warning is shown.
 *
 * @return bool
 */
function wpyeg_defaults_jetpack_active() {
	return defined( 'JETPACK__VERSION' ) || class_exists( 'Automattic\\Jetpack\\Connection\\Manager' );
}

/**
 * The Jetpack warning for the XML-RPC endpoint block, when it applies.
 *
 * Returned for the settings screen rather than written into the help text,
 * because it is only true on some sites — and a warning that is always on
 * teaches people to stop reading warnings.
 *
 * @param string $key Schema key being rendered.
 * @return string Markup, or '' when the warning does not apply.
 */
function wpyeg_defaults_jetpack_warning( $key ) {
	if ( 'block_xmlrpc_endpoint' !== $key || ! wpyeg_defaults_jetpack_active() ) {
		return '';
	}

	// Rendered on the settings screen, which is well after init, so unlike the
	// schema's help text this one is translated. See wpyeg_defaults_schema().
	return __( '<strong>Jetpack is active on this site.</strong> It uses XML-RPC for its WordPress.com connection, so blocking the endpoint will break it. Leave this off unless connection and feature testing proves Jetpack no longer needs XML-RPC.', 'sane-defaults' );
}

/**
 * Comment types that keep working while comments are disabled.
 *
 * WordPress 6.9's Notes feature stores editorial notes as comments of type
 * `note`. They are not public commentary and have nothing to do with the comment
 * form, so switching comments off must not take them with it.
 *
 * Deliberately does NOT include `comment`: see wpyeg_defaults_empty_comment_queries().
 *
 * @return string[]
 */
function wpyeg_defaults_allowed_comment_types() {
	return array_map( 'strval', (array) apply_filters( 'wpyeg_allowed_comment_types', array( 'note' ) ) );
}

/**
 * Short-circuit comment queries while comments are disabled.
 *
 * The teardown's other filters cover the theme's comment template. Everything
 * else that reads comments — `/wp/v2/comments`, a Recent Comments widget,
 * `wp_count_comments()`, a plugin's own WP_Comment_Query — goes straight to the
 * database and answers normally, so a site with comments "disabled" still hands
 * them to anyone who asks the REST API. This answers the query instead.
 *
 * The `comment` type is not exempt, and that is the whole point. It is tempting
 * to let a query that explicitly asks for comments run, on the grounds that code
 * asking for them deliberately should get them — but core's REST controller
 * declares `'type' => array( 'default' => 'comment' )`, so *every*
 * `GET /wp/v2/comments` arrives asking for exactly that type. Exempting it would
 * leave the main exposure open while looking careful.
 *
 * Nothing is deleted. Turn the default off and every comment is queryable again.
 *
 * @param array|int|null    $comment_data Short-circuit value, null to run the query.
 * @param \WP_Comment_Query $query        The query being run.
 * @return array|int|null
 */
function wpyeg_defaults_empty_comment_queries( $comment_data, $query ) {
	// Without query vars there is no way to tell an allowed type from a
	// disallowed one, so answer empty rather than guess open.
	if ( ! is_object( $query ) || ! isset( $query->query_vars ) || ! is_array( $query->query_vars ) ) {
		return array();
	}

	$allowed = wpyeg_defaults_allowed_comment_types();

	// Both query vars accept a string or an array.
	$requested = array();
	foreach ( array( 'type', 'type__in' ) as $var ) {
		$value = isset( $query->query_vars[ $var ] ) ? $query->query_vars[ $var ] : '';

		if ( '' === $value || null === $value || array() === $value ) {
			continue;
		}

		$requested = array_merge( $requested, array_map( 'strval', (array) $value ) );
	}

	if ( ! empty( $requested ) && ! empty( array_intersect( $requested, $allowed ) ) ) {
		return $comment_data;
	}

	// A count query expects a number, not a list.
	if ( ! empty( $query->query_vars['count'] ) ) {
		return 0;
	}

	return array();
}

/**
 * Comment blocks removed from the inserter while comments are disabled.
 *
 * @return string[]
 */
function wpyeg_defaults_comment_blocks() {
	return (array) apply_filters(
		'wpyeg_comment_blocks',
		array(
			'core/comment-author-name',
			'core/comment-content',
			'core/comment-date',
			'core/comment-edit-link',
			'core/comment-reply-link',
			'core/comment-template',
			'core/comments',
			'core/comments-pagination',
			'core/comments-pagination-next',
			'core/comments-pagination-numbers',
			'core/comments-pagination-previous',
			'core/comments-title',
			'core/latest-comments',
			'core/post-comments',
			'core/post-comments-form',
		)
	);
}

/**
 * Drop the comment blocks from the editor while comments are disabled.
 *
 * Both deny-all shapes have to survive untouched. `false` means another filter
 * has already disallowed every block, and expanding it to "all registered blocks
 * minus comments" would re-enable everything that filter just turned off. An
 * empty array is also a deny-all — WordPress treats `array()` the same as
 * `false` — so it must not be expanded either; the loop below returns it
 * unchanged, which is correct by construction.
 *
 * @param bool|string[] $allowed_block_types Allowed block types, or true/false.
 * @return bool|string[]
 */
function wpyeg_defaults_remove_comment_blocks( $allowed_block_types ) {
	if ( false === $allowed_block_types ) {
		return false;
	}

	if ( ! is_array( $allowed_block_types ) ) {
		if ( ! class_exists( 'WP_Block_Type_Registry' ) ) {
			return $allowed_block_types;
		}

		$allowed_block_types = array_keys( WP_Block_Type_Registry::get_instance()->get_all_registered() );
	}

	$disallowed = wpyeg_defaults_comment_blocks();
	$filtered   = array();

	foreach ( $allowed_block_types as $block ) {
		if ( ! in_array( $block, $disallowed, true ) ) {
			$filtered[] = $block;
		}
	}

	return $filtered;
}

/**
 * Stop the comment blocks rendering on the front end.
 *
 * The lesson this one teaches is the gap between an editor filter and the page a
 * visitor sees. wpyeg_defaults_remove_comment_blocks() governs the inserter,
 * which decides what an editor can add *next*. A block theme has already put the
 * comment blocks in its single/post templates, and that markup is untouched — so
 * with only the inserter filter, every post still prints a "Comments" heading
 * above an empty block wrapper. The site reads as broken rather than as one that
 * deliberately has no comments.
 *
 * Returning an empty string rather than unregistering the block types is what
 * keeps the default reversible: the blocks stay registered, the theme's template
 * markup stays as the theme author wrote it, and turning the setting off brings
 * the whole thing back with nothing to undo.
 *
 * @param string $block_content Rendered block markup.
 * @param array  $block         Parsed block, including its blockName.
 * @return string
 */
function wpyeg_defaults_suppress_comment_blocks( $block_content, $block ) {
	if ( ! isset( $block['blockName'] ) ) {
		return $block_content;
	}

	if ( in_array( $block['blockName'], wpyeg_defaults_comment_blocks(), true ) ) {
		return '';
	}

	return $block_content;
}

/**
 * Answer comment feed requests with a 404.
 *
 * Removing the <link rel="alternate"> markup stops the feed being advertised; it
 * does not stop it being served. /comments/feed/ and <post>/feed/ keep answering
 * 200 for anyone who types the URL. Because comment queries are already
 * short-circuited the feed comes back empty, which is the worst of both: a live,
 * crawlable endpoint whose only purpose is to say nothing.
 *
 * set_404() re-runs init_query_flags(), which clears is_feed() along with
 * everything else — so the template loader stops routing to do_feed() and renders
 * the theme's 404 instead. That is why is_comment_feed() has to be tested first:
 * a moment later there is nothing left to test.
 *
 * redirect_canonical() has to go with it, and this is the part worth remembering.
 * It does not bail on a 404 — it calls redirect_guess_404_permalink(), and
 * against the query this function has just emptied it answers /post-name/feed/
 * with a 301 to /post-name/feed/feed/. Leaving it hooked turns a clean 404 into a
 * redirect to a URL that has never existed, which is worse than the bug being
 * fixed. Every filter-level test still passes; only a real request catches it.
 *
 * @return void
 */
function wpyeg_defaults_block_comment_feeds() {
	if ( ! is_comment_feed() ) {
		return;
	}

	global $wp_query;

	if ( $wp_query instanceof WP_Query ) {
		$wp_query->set_404();
	}

	remove_action( 'template_redirect', 'redirect_canonical' );

	status_header( 404 );
	nocache_headers();
}

/**
 * Decide where an attachment page should send visitors, if anywhere.
 *
 * Kept separate from the redirect itself so the decision can be tested without
 * a request: the hook does the exiting, this answers the question.
 *
 * WordPress 6.4 added a `wp_attachment_pages_enabled` option — new installs get
 * `0` and core redirects attachment requests to the file, while sites upgraded
 * from earlier get `1` and keep rendering the pages. This default overrides that
 * destination, preferring the parent post, because landing on a real article
 * beats landing on a bare JPEG. Where there is no parent it matches core and
 * falls back to the file.
 *
 * @param int $attachment_id Attachment post ID.
 * @return string Redirect target, or '' to leave the page alone.
 */
function wpyeg_defaults_attachment_redirect_target( $attachment_id ) {
	/**
	 * Let a theme that deliberately builds attachment pages keep them.
	 *
	 * A theme shipping attachment.php or image.php has opted into rendering
	 * these, and quietly redirecting past it would delete a feature its author
	 * wrote on purpose — the photography and portfolio case. Filter this to
	 * force the redirect anyway, or to skip it on your own terms.
	 *
	 * @param bool $keep          Whether to leave the attachment page alone.
	 * @param int  $attachment_id Attachment post ID.
	 */
	if ( (bool) apply_filters( 'wpyeg_keep_attachment_page', (bool) locate_template( array( 'attachment.php', 'image.php' ) ), $attachment_id ) ) {
		return '';
	}

	$parent = wp_get_post_parent_id( $attachment_id );

	if ( $parent ) {
		return (string) get_permalink( $parent );
	}

	// Unattached media has no parent, and that is common — anything uploaded
	// straight into the Media Library lands here. Sending all of those to the
	// homepage is a soft-404 pattern search engines read badly, so fall back to
	// the file itself, which is what core does when attachment pages are off.
	return (string) wp_get_attachment_url( $attachment_id );
}

/**
 * Find a header by name regardless of how it was capitalised.
 *
 * HTTP header names are case-insensitive, and PHP arrays are not. Another plugin
 * setting `x-frame-options` is the same header as `X-Frame-Options` to every
 * browser, but `isset()` misses it — so a "only set what is not already set"
 * check adds a second, conflicting header line instead of deferring.
 *
 * @param array  $headers Headers, keyed by name.
 * @param string $name    Header name to find.
 * @return string|null The existing key as written, or null when absent.
 */
function wpyeg_defaults_find_header_key( $headers, $name ) {
	if ( ! is_array( $headers ) ) {
		return null;
	}
	foreach ( array_keys( $headers ) as $key ) {
		if ( 0 === strcasecmp( (string) $key, $name ) ) {
			return (string) $key;
		}
	}
	return null;
}

/**
 * Relative strength of an X-Frame-Options value.
 *
 * Only the two values browsers actually honour are ranked. Anything else returns
 * null so the caller leaves the response alone — which keeps a deprecated
 * `ALLOW-FROM`'s permissive intent intact rather than silently tightening it.
 *
 * @param mixed $value Header value.
 * @return int|null 2 = DENY, 1 = SAMEORIGIN, null = unrecognised.
 */
function wpyeg_defaults_frame_option_strength( $value ) {
	switch ( strtoupper( trim( (string) $value ) ) ) {
		case 'DENY':
			return 2;
		case 'SAMEORIGIN':
			return 1;
		default:
			return null;
	}
}

/**
 * Set X-Frame-Options without ever downgrading a stronger existing value.
 *
 * "Set only if unset" sounds polite and is the wrong rule here: if something
 * upstream already sent a *weaker* value, deferring to it means the weaker
 * header wins purely because it arrived first. Compare instead — a host's DENY
 * is never reduced to SAMEORIGIN, and a deliberately configured DENY still
 * tightens a weaker existing value.
 *
 * Writes back to the existing key so a differently cased header does not emit a
 * second line.
 *
 * @param array $headers Headers.
 * @return array
 */
function wpyeg_defaults_set_frame_option_header( $headers ) {
	$value        = wpyeg_defaults_get( 'frame_options' );
	$existing_key = wpyeg_defaults_find_header_key( $headers, 'X-Frame-Options' );

	if ( null !== $existing_key ) {
		$existing_strength   = wpyeg_defaults_frame_option_strength( $headers[ $existing_key ] );
		$configured_strength = wpyeg_defaults_frame_option_strength( $value );

		// One of them is a value browsers do not honour. Leave it alone rather
		// than guess what was intended.
		if ( null === $existing_strength || null === $configured_strength ) {
			return $headers;
		}

		if ( $configured_strength <= $existing_strength ) {
			return $headers;
		}

		$headers[ $existing_key ] = $value;
		return $headers;
	}

	$headers['X-Frame-Options'] = $value;
	return $headers;
}

/**
 * Set X-Content-Type-Options: nosniff.
 *
 * `nosniff` is this header's only effective value, so there is no such thing as
 * a stronger one already being present. An existing value that is anything else
 * — empty, "off", a typo — is not a policy to defer to, it is a header doing
 * nothing. Corrected in place.
 *
 * @param array $headers Headers.
 * @return array
 */
function wpyeg_defaults_set_content_type_header( $headers ) {
	$existing_key = wpyeg_defaults_find_header_key( $headers, 'X-Content-Type-Options' );

	if ( null !== $existing_key ) {
		if ( 'nosniff' === strtolower( trim( (string) $headers[ $existing_key ] ) ) ) {
			return $headers;
		}
		$headers[ $existing_key ] = 'nosniff';
		return $headers;
	}

	$headers['X-Content-Type-Options'] = 'nosniff';
	return $headers;
}

/**
 * Set a baseline Referrer-Policy.
 *
 * Unlike the other two, Referrer-Policy has no single strictness axis across its
 * tokens — `same-origin` and `strict-origin-when-cross-origin` are not
 * comparable — so an existing policy is deferred to rather than second-guessed.
 * Case-insensitively, which is the part "set only if unset" got wrong.
 *
 * @param array $headers Headers.
 * @return array
 */
function wpyeg_defaults_set_referrer_policy_header( $headers ) {
	if ( null !== wpyeg_defaults_find_header_key( $headers, 'Referrer-Policy' ) ) {
		return $headers;
	}

	$headers['Referrer-Policy'] = 'strict-origin-when-cross-origin';
	return $headers;
}

/**
 * Whether the strong-password policy is enforced for a given user.
 *
 * A hard length rule adds signup friction for accounts that can only read,
 * without protecting anything worth protecting — so subscriber-only accounts
 * are skipped by default. Any privileged or editorial role enforces, and so
 * does an unknown or empty role set: when we cannot tell who this is, the safe
 * answer is to enforce.
 *
 * Two rules keep this scoping honest rather than a loophole:
 *
 * 1. **Breach screening is never waived.** It runs before this gate is
 *    consulted. A password already published in a breach costs its owner
 *    nothing to avoid, whatever the account can do. Get the order wrong and the
 *    one free rule becomes the one waived for the accounts most likely to reuse
 *    a password from somewhere else.
 * 2. **A user is exempt only if *every* role they hold is exempt.** Someone who
 *    is both a Subscriber and an Editor is an Editor for this purpose.
 *
 * Filter-only, with no setting: Better by Default has no multi-select field
 * type, and adding one to expose this would cost more surface than the control
 * is worth here. Sites that want it wire the filter in a small plugin.
 *
 * @param WP_User|stdClass|null $user User context, when available.
 * @return bool
 */
function wpyeg_defaults_password_enforced_for_user( $user ) {
	$weak_roles = array_map( 'strval', (array) apply_filters( 'wpyeg_weak_roles', array( 'subscriber' ) ) );

	$roles = array();
	if ( isset( $user->roles ) && is_array( $user->roles ) ) {
		$roles = $user->roles;
	} elseif ( ! empty( $user->role ) && is_string( $user->role ) ) {
		$roles = array( $user->role );
	}

	// Unknown role set, or no user context at all, enforces.
	if ( empty( $roles ) ) {
		return true;
	}

	foreach ( $roles as $role ) {
		if ( ! in_array( (string) $role, $weak_roles, true ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Validate a password against the policy.
 *
 * One reusable validator behind every entry point — profile screen, password
 * reset, and the REST users controller — so a password cannot get in through a
 * door the policy does not watch.
 *
 * @param string                $password Proposed password.
 * @param WP_User|stdClass|null $user     User context, when available.
 * @return true|WP_Error True when acceptable, WP_Error describing the failure.
 */
function wpyeg_defaults_validate_password( $password, $user = null ) {
	/*
	 * Breach screening runs first, for every account, exempt or not. The role
	 * gate below deliberately sits after it — see that function's docblock for
	 * why the order is the whole design.
	 */
	if ( wpyeg_defaults_password_is_pwned( $password ) ) {
		return new WP_Error(
			'wpyeg_password_pwned',
			__( '<strong>Error:</strong> Choose a password that has not appeared in a known data breach.', 'sane-defaults' )
		);
	}

	// Everything below is role-scoped: length, the blocklist and the
	// personal-context rule are the parts a read-only account can skip.
	if ( ! wpyeg_defaults_password_enforced_for_user( $user ) ) {
		return true;
	}

	/*
	 * NIST SP 800-63B-4 § 3.1.1.2: favour length and screening over forced
	 * composition rules, which push users toward predictable shapes like
	 * Password1!.
	 *
	 * "Without adding entropy" is what this comment used to say, and it concedes  better-by-default-retired-ok
	 * the argument. Demanding a symbol does enlarge the alphabet, so on paper it
	 * adds entropy; what it fails to add is guessing resistance, because people
	 * satisfy the rule the same few ways. Entropy is also the term NIST stopped
	 * using for user-chosen secrets — it does not appear in § 3.1.1.2 at all.
	 */
	$minimum = (int) apply_filters( 'wpyeg_minimum_password_length', 15 );

	// Count characters, not bytes: strlen() would read eight emoji as 32 and
	// wave through a password far shorter than the rule intends.
	$length = function_exists( 'mb_strlen' ) ? mb_strlen( $password ) : strlen( $password );

	if ( $length < $minimum ) {
		return new WP_Error(
			'wpyeg_password_too_short',
			sprintf(
				/* translators: %d: minimum password length. */
				__( '<strong>Error:</strong> Password must be at least %d characters.', 'sane-defaults' ),
				$minimum
			)
		);
	}

	$normalize = static function ( $value ) {
		$value = (string) $value;
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $value ) : strtolower( $value );
	};

	// A small local blocklist still catches the obvious cases when the breach
	// API below is unreachable (that check deliberately fails open).
	$blocklist = (array) apply_filters(
		'wpyeg_password_blocklist',
		array( 'password', 'password123', '123456789012345', 'qwertyuiopasdfg', 'letmeinletmeinletmein', 'wordpresswordpress' )
	);

	if ( in_array( $normalize( $password ), array_map( $normalize, $blocklist ), true ) ) {
		return new WP_Error(
			'wpyeg_password_common',
			__( '<strong>Error:</strong> Choose a password that is not commonly used.', 'sane-defaults' )
		);
	}

	// NIST SP 800-63B-4 § 3.1.1.2 includes context-specific terms in password blocklists.
	if ( $user ) {
		$context = array_filter(
			array(
				isset( $user->user_login ) ? $user->user_login : '',
				isset( $user->user_nicename ) ? $user->user_nicename : '',
				isset( $user->user_email ) ? strtok( $user->user_email, '@' ) : '',
			)
		);

		foreach ( $context as $value ) {
			$value = $normalize( $value );
			if ( strlen( $value ) >= 4 && false !== strpos( $normalize( $password ), $value ) ) {
				return new WP_Error(
					'wpyeg_password_personal',
					__( '<strong>Error:</strong> Password must not contain your username or email name.', 'sane-defaults' )
				);
			}
		}
	}

	return true;
}

/**
 * Validate a password submitted from a user profile screen.
 *
 * @param WP_Error         $errors Validation errors.
 * @param bool             $update Whether this is an update.
 * @param WP_User|stdClass $user   User context.
 */
function wpyeg_defaults_validate_profile_password( $errors, $update, $user ) {
	unset( $update );

	// Core's edit_user() trims the password and stores the TRIMMED value, but
	// fires this hook with $_POST untouched. Validate the untrimmed string and
	// "              a" sails past the length rule while core saves a
	// one-character password. Measure exactly what core will store.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Passwords must not be sanitized.
	$password = isset( $_POST['pass1'] ) ? trim( (string) wp_unslash( $_POST['pass1'] ) ) : '';

	if ( '' === $password ) {
		return; // No password change requested (or whitespace-only).
	}

	$result = wpyeg_defaults_validate_password( $password, $user );

	if ( is_wp_error( $result ) ) {
		$errors->add( $result->get_error_code(), $result->get_error_message() );
	}
}

/**
 * Validate a password submitted from the password-reset screen.
 *
 * @param WP_Error $errors Validation errors.
 * @param WP_User  $user   User context.
 */
function wpyeg_defaults_validate_reset_password( $errors, $user ) {
	// wp-login.php already trims $_POST['pass1'] in place before firing this
	// hook; trimming again keeps both entry points measuring the same string.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Passwords must not be sanitized.
	$password = isset( $_POST['pass1'] ) ? trim( (string) wp_unslash( $_POST['pass1'] ) ) : '';

	if ( '' === $password ) {
		return;
	}

	$result = wpyeg_defaults_validate_password( $password, $user );

	if ( is_wp_error( $result ) ) {
		$errors->add( $result->get_error_code(), $result->get_error_message() );
	}
}

/**
 * Validate a password submitted through the core REST users controller.
 *
 * Backstop for any route the argument guard below does not reach.
 *
 * @param object          $prepared_user Prepared user object.
 * @param WP_REST_Request $request       REST request.
 * @return object|WP_Error
 */
function wpyeg_defaults_validate_rest_password( $prepared_user, $request ) {
	$password = $request->get_param( 'password' );

	if ( null === $password || '' === $password ) {
		return $prepared_user;
	}

	$user   = ! empty( $prepared_user->ID ) ? get_userdata( $prepared_user->ID ) : $prepared_user;
	$result = wpyeg_defaults_validate_password( (string) $password, $user );

	return is_wp_error( $result ) ? $result : $prepared_user;
}

/**
 * REST route prefixes that stay reachable when anonymous REST is closed.
 *
 * The oEmbed route is the carve-out worth making. It is served over REST, so
 * closing REST closes it — and the consequence lands on *other people's* sites: every
 * post of yours they have embedded degrades to a bare link, silently, with
 * nothing here to show it happened.
 *
 * Measuring the field found nobody handling this. Of the four plugins that close
 * REST outright, none allowlists oembed/1.0. The trade was always defensible;
 * being unable to opt out of it was not.
 *
 * The carve-out is only safe because of what sits in front of it. oEmbed returns
 * author_name and an author_url carrying the account nicename, so opening this
 * route without that fix would reopen the username enumeration the users-endpoint
 * removal exists to close. wpyeg_defaults_strip_oembed_author() is therefore
 * registered by this gate as well as by hidden author archives — the two reasons
 * are independent, and registering the same filter twice is harmless because it
 * unsets keys that are already gone.
 *
 * @return string[] Route prefixes, each with a leading slash.
 */
function wpyeg_defaults_public_rest_routes() {
	/**
	 * REST route prefixes that stay reachable when anonymous REST is closed.
	 *
	 * @param string[] $routes Route prefixes, each with a leading slash.
	 */
	return (array) apply_filters( 'wpyeg_public_rest_routes', array( '/oembed/1.0' ) );
}

/**
 * Whether the route being requested is one of the public carve-outs.
 *
 * `rest_authentication_errors` fires before dispatch, so the route is not on the
 * server object yet — but `rest_api_loaded()` has already parsed it into the
 * query vars, which is where this reads it. REQUEST_URI is deliberately not
 * used: it carries whatever the client sent, traversal included, and a gate that
 * trusts it can be walked around.
 *
 * @return bool
 */
function wpyeg_defaults_rest_route_is_public() {
	$route = isset( $GLOBALS['wp']->query_vars['rest_route'] ) ? (string) $GLOBALS['wp']->query_vars['rest_route'] : '';

	if ( '' === $route ) {
		return false;
	}

	$route = '/' . ltrim( $route, '/' );

	foreach ( wpyeg_defaults_public_rest_routes() as $prefix ) {
		$prefix = '/' . trim( (string) $prefix, '/' );

		// Match on a path boundary, so /oembed/1.0 does not also admit a route
		// called /oembed/1.0-internal.
		if ( $route === $prefix || 0 === strpos( $route, $prefix . '/' ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Require an authenticated user for every REST request except the carve-outs.
 *
 * Registered at PHP_INT_MAX on purpose. Core resolves Application Password auth
 * at priority 90 and cookie auth at 100, and rest_cookie_check_errors() returns
 * true after calling wp_set_current_user( 0 ) when a cookie carries no
 * X-WP-Nonce. Deciding before core has finished — or treating any truthy $result
 * as success — would read that true as "authenticated" and let the request
 * dispatch as user 0. Only an existing WP_Error short-circuits.
 *
 * @param WP_Error|true|null $result Authentication result so far.
 * @return WP_Error|true|null
 */
function wpyeg_defaults_require_rest_auth( $result ) {
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	if ( wpyeg_defaults_rest_route_is_public() ) {
		return $result;
	}

	if ( ! is_user_logged_in() ) {
		return new WP_Error(
			'rest_not_logged_in',
			__( 'REST API restricted to authenticated users.', 'sane-defaults' ),
			array( 'status' => 401 )
		);
	}

	return $result;
}

/**
 * Enforce the password policy on the users controller's `password` argument.
 *
 * The rest_pre_insert_user hook is the documented seam, but the controller never checks
 * its return for an error: update_item() assigns ID onto the WP_Error and hands
 * it to wp_update_user(), which finds no user_pass and answers 200 OK with the
 * user unchanged; create_item() casts it to an array with no user_login and
 * answers 500 "empty login name". Either way the policy message is lost.
 *
 * An argument-level error is different. WP_REST_Request::sanitize_params()
 * turns it into rest_invalid_param, which dispatch() returns as a 400 before
 * the callback runs, so the caller sees the actual reason.
 *
 * @param array $endpoints Registered REST endpoints, keyed by route.
 * @return array
 */
function wpyeg_defaults_guard_rest_password_arg( $endpoints ) {
	foreach ( $endpoints as $route => $handlers ) {
		// Application Passwords are core-generated and carry a readonly password
		// field, so they are never in scope for a human password policy.
		if ( ! preg_match( '#^/wp/v2/users(?:/|$)#', $route ) || false !== strpos( $route, 'application-password' ) ) {
			continue;
		}

		if ( ! is_array( $handlers ) ) {
			continue;
		}

		foreach ( $handlers as $index => $handler ) {
			if ( ! is_array( $handler ) || ! isset( $handler['args']['password'] ) ) {
				continue;
			}

			$inner = isset( $handler['args']['password']['sanitize_callback'] )
				? $handler['args']['password']['sanitize_callback']
				: null;

			$endpoints[ $route ][ $index ]['args']['password']['sanitize_callback'] = function ( $value, $request, $param ) use ( $inner ) {
				// Let core sanitize first; it rejects empty and backslashed passwords.
				if ( $inner ) {
					$value = call_user_func( $inner, $value, $request, $param );
					if ( is_wp_error( $value ) ) {
						return $value;
					}
				}

				$result = wpyeg_defaults_validate_password(
					(string) $value,
					wpyeg_defaults_rest_password_context( $request )
				);

				if ( is_wp_error( $result ) ) {
					return new WP_Error(
						$result->get_error_code(),
						$result->get_error_message(),
						array( 'status' => 400 )
					);
				}

				return $value;
			};
		}
	}

	return $endpoints;
}

/**
 * Resolve the user a REST password change applies to.
 *
 * Argument sanitizing runs before the controller prepares a user, so the
 * context has to come from the request: update_item() takes the id from the
 * route, and update_current_item() only assigns it after dispatch. A create
 * has no stored user at all, so the submitted fields are the only context.
 *
 * @param WP_REST_Request $request REST request.
 * @return WP_User|stdClass
 */
function wpyeg_defaults_rest_password_context( $request ) {
	$user_id = 0;

	if ( preg_match( '#/wp/v2/users/me$#', (string) $request->get_route() ) ) {
		$user_id = get_current_user_id();
	} elseif ( null !== $request['id'] ) {
		$user_id = (int) $request['id'];
	}

	if ( $user_id ) {
		$existing = get_userdata( $user_id );
		if ( $existing ) {
			return $existing;
		}
	}

	$context                = new stdClass();
	$context->user_login    = (string) $request['username'];
	$context->user_email    = (string) $request['email'];
	$context->user_nicename = (string) $request['slug'];

	return $context;
}

/**
 * Check a password against the Have I Been Pwned range API using k-anonymity.
 *
 * Only the first five characters of the SHA-1 hash ever leave the site. HIBP
 * returns every hash suffix sharing that prefix and the comparison happens
 * locally, so the password itself is never transmitted. `Add-Padding` asks HIBP
 * to pad the response so its size cannot reveal how many real matches it held;
 * padded rows carry a count of 0 and are ignored.
 *
 * The response is capped at 128 KiB. A response that reaches that cap may be
 * truncated, so capped, empty, and malformed responses all fail OPEN, just as
 * an unreachable HIBP service does. This avoids locking everyone out of
 * password changes when the remote evidence is unavailable or invalid.
 *
 * The function carries the wpyeg_defaults_ prefix every other function here
 * uses; the filter it applies keeps the shorter wpyeg_ prefix all this plugin's
 * public hooks use. They are two namespaces, not one inconsistency — renaming
 * the filter would break every site already using it.
 *
 * @param string $password Plain-text password to screen.
 * @return bool True when the password appears in a known breach.
 */
function wpyeg_defaults_password_is_pwned( $password ) {
	/*
	 * An off switch for the one thing this plugin does that leaves the server.
	 *
	 * The lookup is already k-anonymous — only the first five characters of a
	 * locally computed SHA-1 are sent, and the response is padded — but "it is
	 * safe" is not the same as "you have no choice". Three situations need it:
	 * an air-gapped or intranet site where the request can only ever fail and
	 * add four seconds to every password change; a privacy review that has to
	 * be able to point at a switch rather than an argument; and a site that
	 * would rather refuse passwords on its own terms than fail open when the
	 * API is unreachable.
	 *
	 * Both forms because they answer different questions: the constant is an
	 * operator's declaration in wp-config.php, the filter lets a plugin decide
	 * per password.
	 */
	$disabled = ( defined( 'WPYEG_DISABLE_HIBP' ) && WPYEG_DISABLE_HIBP )
		|| (bool) apply_filters( 'wpyeg_disable_hibp', false, $password );

	if ( $disabled ) {
		// Same answer as an unreachable API: not known to be breached. The
		// length, blocklist and personal-context rules still apply.
		return false;
	}

	$hash   = strtoupper( sha1( $password ) );
	$prefix = substr( $hash, 0, 5 );
	$suffix = substr( $hash, 5 );
	$limit  = max( 1024, (int) apply_filters( 'wpyeg_hibp_max_response_bytes', 128 * 1024 ) );

	$cache_key = 'wpyeg_hibp_' . $prefix;
	$body      = get_transient( $cache_key );
	$cache_hit = false !== $body;

	if ( false === $body ) {
		$response = wp_remote_get(
			'https://api.pwnedpasswords.com/range/' . $prefix,
			array(
				'timeout'             => 4,
				'limit_response_size' => $limit,
				'headers'             => array( 'Add-Padding' => 'true' ),
			)
		);

		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			// Fail open — never block a password change because HIBP is down.
			return (bool) apply_filters( 'wpyeg_password_is_pwned', false, $password );
		}

		$body = (string) wp_remote_retrieve_body( $response );
	}

	// Measure before trimming. A response cut at exactly the cap can end on a
	// row boundary, and trimming that trailing CRLF would drop it back under the
	// cap — leaving a truncated range that still parses as well-formed.
	$raw_length = strlen( (string) $body );
	$body       = trim( (string) $body );

	// A response at the transport cap may be truncated. Empty, oversized, or
	// structurally invalid range data is unavailable data, so fail open.
	if (
		'' === $body ||
		$raw_length >= $limit ||
		! preg_match( '/\A[0-9A-F]{35}:[0-9]+(?:\r?\n[0-9A-F]{35}:[0-9]+)*\z/i', $body )
	) {
		// A cached entry that no longer validates would otherwise keep failing
		// open for the rest of its TTL. Drop it so the next call refetches.
		if ( $cache_hit ) {
			delete_transient( $cache_key );
		}

		return (bool) apply_filters( 'wpyeg_password_is_pwned', false, $password );
	}

	if ( ! $cache_hit ) {
		set_transient( $cache_key, $body, 12 * HOUR_IN_SECONDS );
	}

	$pwned = false;

	// Every line is known to be SUFFIX:COUNT because the guard above rejected
	// anything else, which is what makes $parts[1] safe without padding.
	foreach ( preg_split( '/\r\n|\n/', $body ) as $line ) {
		$parts = explode( ':', $line, 2 );

		// Padded rows report a count of 0 and are not real matches.
		if ( (int) $parts[1] > 0 && 0 === strcasecmp( $parts[0], $suffix ) ) {
			$pwned = true;
			break;
		}
	}

	/**
	 * Filter the breach-screening verdict (e.g. to use a local blocklist, or to
	 * skip the network call on an air-gapped site).
	 *
	 * @param bool   $pwned    Whether the password appeared in a known breach.
	 * @param string $password The password being screened.
	 */
	return (bool) apply_filters( 'wpyeg_password_is_pwned', $pwned, $password );
}


/*
 * =======================================================================
 * SETTINGS SCREEN — Settings → Better by Default
 * =====================================================================
 */

add_action(
	'admin_menu',
	function () {
		add_options_page(
			__( 'Better by Default', 'sane-defaults' ),
			__( 'Better by Default', 'sane-defaults' ),
			'manage_options',
			'sane-defaults',
			'wpyeg_defaults_render_settings_page'
		);
	}
);

add_action(
	'admin_init',
	function () {
		register_setting(
			'wpyeg_better_by_default_group',
			WPYEG_DEFAULTS_OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => 'wpyeg_defaults_sanitize',
				'default'           => array(),
			)
		);
	}
);

/**
 * Sanitize the whole settings array against the schema.
 *
 * @param mixed $input Raw submitted settings, straight from the form.
 * @return array Values coerced to the schema; unknown keys dropped.
 */
function wpyeg_defaults_sanitize( $input ) {
	$schema = wpyeg_defaults_schema();
	$clean  = array();
	$input  = is_array( $input ) ? $input : array();

	foreach ( $schema as $key => $field ) {
		switch ( $field['type'] ) {
			case 'toggle':
				$clean[ $key ] = ! empty( $input[ $key ] ) ? 'yes' : 'no';
				break;

			case 'number':
				$min           = isset( $field['min'] ) ? (int) $field['min'] : 0;
				$val           = isset( $input[ $key ] ) ? absint( $input[ $key ] ) : (int) $field['default'];
				$clean[ $key ] = max( $min, $val );
				break;

			case 'select':
				$choices       = isset( $field['choices'] ) ? array_keys( $field['choices'] ) : array();
				$value         = isset( $input[ $key ] ) ? (string) $input[ $key ] : $field['default'];
				$clean[ $key ] = in_array( $value, $choices, true ) ? $value : $field['default'];
				break;
		}
	}

	// A remembered login must never be shorter than a regular one — otherwise
	// ticking "Remember Me" would *shorten* the session. Clamp it up to match.
	if ( isset( $clean['remember_me_days'], $clean['session_regular_days'] )
		&& $clean['remember_me_days'] < $clean['session_regular_days'] ) {
		$clean['remember_me_days'] = $clean['session_regular_days'];
	}

	return $clean;
}

/**
 * If a wp-config.php constant supersedes a setting, return a short inline note
 * explaining it (the note may contain <code>). The control is then disabled and
 * the note shown, so the screen never offers a switch that cannot take effect.
 * Returns null when the setting is fully under the dashboard's control.
 *
 * @param string $key Schema key.
 * @return string|null
 */
function wpyeg_defaults_config_lock( $key ) {
	// Either of these means WordPress performs no background updates at all,
	// which supersedes the core-update policy.
	$updates_off = null;
	if ( defined( 'AUTOMATIC_UPDATER_DISABLED' ) && AUTOMATIC_UPDATER_DISABLED ) {
		$updates_off = 'AUTOMATIC_UPDATER_DISABLED';
	} elseif ( defined( 'DISALLOW_FILE_MODS' ) && DISALLOW_FILE_MODS ) {
		$updates_off = 'DISALLOW_FILE_MODS';
	}

	switch ( $key ) {
		case 'core_update_policy':
			if ( defined( 'WP_AUTO_UPDATE_CORE' ) ) {
				return __( 'Locked by <code>WP_AUTO_UPDATE_CORE</code> in <code>wp-config.php</code>. Remove that constant to manage core releases here.', 'sane-defaults' );
			}
			if ( $updates_off ) {
				/* translators: %s: a wp-config.php constant name. */
				return sprintf( __( 'Overridden by <code>%s</code> in <code>wp-config.php</code>: WordPress installs no background updates.', 'sane-defaults' ), $updates_off );
			}
			break;

		case 'limit_unfiltered_html_to_admins':
			// Core's own constant strips unfiltered_html from every role,
			// administrators included, so this setting cannot add anything on
			// top of it. Saying so is the difference between a control that is
			// redundant and a control that looks like it is doing something.
			if ( defined( 'DISALLOW_UNFILTERED_HTML' ) && DISALLOW_UNFILTERED_HTML ) {
				return __( '<code>DISALLOW_UNFILTERED_HTML</code> in <code>wp-config.php</code> already removes unfiltered HTML from every role, so this restriction has no additional effect.', 'sane-defaults' );
			}
			break;
	}

	return null;
}

/**
 * The effective "From" address WordPress will use for site mail.
 *
 * Mirrors core's default — wordpress@<host>, with any leading www. dropped —
 * and then runs it through wp_mail_from, so whatever an SMTP plugin or a theme
 * has set is what gets judged, not the theoretical default.
 *
 * @return string
 */
function wpyeg_defaults_mail_from_address() {
	$sitename = wp_parse_url( network_home_url(), PHP_URL_HOST );
	$sitename = $sitename ? strtolower( $sitename ) : '';

	if ( 0 === strpos( $sitename, 'www.' ) ) {
		$sitename = substr( $sitename, 4 );
	}

	$default_from = $sitename ? 'wordpress@' . $sitename : '';

	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Reading core's own filter to learn the effective From address, not registering a hook.
	return (string) apply_filters( 'wp_mail_from', $default_from );
}

/**
 * Whether that address looks like mail sent from it will not arrive.
 *
 * Deliberately a shape check, not a delivery test. Proving mail works needs an
 * SPF/DMARC lookup and a send, which is far more than a settings screen should
 * do on page load. These are the cases where the answer is knowable for free.
 *
 * @return bool
 */
function wpyeg_defaults_mail_is_risky() {
	$email       = wpyeg_defaults_mail_from_address();
	$domain_part = strrchr( $email, '@' );
	$domain      = $domain_part ? strtolower( substr( $domain_part, 1 ) ) : '';

	if ( ! is_email( $email ) ) {
		return true;
	}

	if ( '' === $domain || in_array( $domain, array( 'example.com', 'localhost', 'local' ), true ) ) {
		return true;
	}

	// Reserved TLDs that never resolve publicly (RFC 2606, RFC 6762).
	return (bool) preg_match( '/\.(local|test|invalid|example)$/i', $domain );
}

/**
 * Whether this request should carry the mail-deliverability warning.
 *
 * A notice, never an intervention. WordPress failing to deliver a password
 * reset is silent by design — wp_mail() returns false and nothing surfaces it —
 * so the entire value here is saying so out loud, once, to someone who can fix
 * it.
 *
 * @return bool
 */
function wpyeg_defaults_should_warn_about_mail() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}

	// A local environment is *meant* to have an undeliverable address. Warning
	// there is how a notice trains people to dismiss notices.
	if ( function_exists( 'wp_get_environment_type' ) && 'local' === wp_get_environment_type() ) {
		return false;
	}

	return wpyeg_defaults_mail_is_risky();
}

/**
 * Warn administrators when a non-local site cannot plausibly send mail.
 *
 * Prints only. Whether to print is wpyeg_defaults_should_warn_about_mail(),
 * kept separate for the same reason as the attachment redirect above: a
 * decision can be tested without standing up a request, and the environment
 * gate is otherwise unreachable from a test — a local dev site reports
 * 'local' and nothing in-process will convince it otherwise.
 *
 * @return void
 */
function wpyeg_defaults_render_mail_config_notice() {
	if ( ! wpyeg_defaults_should_warn_about_mail() ) {
		return;
	}

	/**
	 * The remedy suggested in the deliverability notice.
	 *
	 * Filterable so an agency can name the SMTP plugin it actually supports
	 * rather than the three this plugin happens to know.
	 *
	 * @param string $recommendation Suggested remedy.
	 */
	$recommendation = apply_filters(
		'wpyeg_smtp_plugin_recommendation',
		__( 'Send through a transactional mail service using an SMTP plugin, or your host\'s own mail integration.', 'sane-defaults' )
	);
	?>
	<div class="notice notice-warning">
		<p>
			<strong><?php esc_html_e( 'Site email may not be delivered.', 'sane-defaults' ); ?></strong>
			<?php
			printf(
				/* translators: %s: the site's From email address. */
				esc_html__( 'WordPress will send mail from %s, which does not look like an address that can deliver. Password resets and notifications may fail without any error.', 'sane-defaults' ),
				'<code>' . esc_html( wpyeg_defaults_mail_from_address() ) . '</code>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inline.
			);
			?>
		</p>
		<p><?php echo esc_html( $recommendation ); ?></p>
	</div>
	<?php
}

/**
 * Titles for sections that stack several related controls under one table row.
 *
 * Every `section` named in the schema needs an entry here; see
 * wpyeg_defaults_settings_rows() for how a run of them becomes one row.
 *
 * @return array Section slug => title.
 */
function wpyeg_defaults_section_labels() {
	/*
	 * Every setting belongs to a section, so every row has a left-hand heading.
	 * Before this, a toggle drew as a full-width row with an empty left column
	 * and only the four select and number fields had one — twenty bare rows
	 * interleaved with seven labelled ones, which read as ragged rather than as
	 * a deliberate shape.
	 *
	 * The title names the *category*; the text beside a checkbox stays the
	 * specific claim ("Capabilities" over "Limit unfiltered HTML to
	 * administrators"), so a section holding one setting still says something
	 * its control does not. That is the shape core's own Discussion screen uses.
	 *
	 * Titles are Title Case, matching the group headings above them ("Admin and
	 * Front-End UX") and the sibling plugins' settings screens. Short
	 * conjunctions, articles and prepositions stay lowercase unless they lead.
	 * Sentence case in the left column and Title Case in the heading directly
	 * over it is the kind of mixed convention nobody decides on — it just
	 * happens, one setting at a time.
	 */
	return array(
		'rest'           => __( 'REST API', 'sane-defaults' ),
		'xmlrpc'         => __( 'XML-RPC', 'sane-defaults' ),
		'credentials'    => __( 'Passwords and Credentials', 'sane-defaults' ),
		'capabilities'   => __( 'Capabilities', 'sane-defaults' ),
		'disclosure'     => __( 'Version Disclosure', 'sane-defaults' ),
		'headers'        => __( 'Response Headers', 'sane-defaults' ),
		'ai'             => __( 'AI Features', 'sane-defaults' ),
		'comments'       => __( 'Comments and Pings', 'sane-defaults' ),
		'thin_pages'     => __( 'Thin Public Pages', 'sane-defaults' ),
		'frontend'       => __( 'Front-End Output', 'sane-defaults' ),
		'editor'         => __( 'Editor', 'sane-defaults' ),
		'admin_search'   => __( 'Admin Search', 'sane-defaults' ),
		'media'          => __( 'Media', 'sane-defaults' ),
		// Not "Session length" any more: the section now carries the Remember Me
		// toggle as well, and those three settings are one policy — which is why
		// wpyeg_defaults_session_policy_is_custom() weighs them together.
		'sessions'       => __( 'Sessions', 'sane-defaults' ),
		'deliverability' => __( 'Deliverability', 'sane-defaults' ),
		'heartbeat'      => __( 'Admin Polling', 'sane-defaults' ),
	);
}

/**
 * The DOM id for a setting's control.
 *
 * @param string $key Schema key.
 * @return string
 */
function wpyeg_defaults_field_id( $key ) {
	return 'wpyeg-defaults-' . str_replace( '_', '-', $key );
}

/**
 * Group the schema into the rows the settings table draws.
 *
 * The screen has exactly two row shapes: one setting on its own, or a run of
 * settings sharing a `section` stacked inside one row under a shared heading.
 * Working that out here, once, is what keeps the rendering below free of the
 * open/close bookkeeping it used to carry — a `$section_open` variable threaded
 * through a foreach, three `if ( $stacked )` branches deciding which closing tag
 * to emit, and a final "did a section run to the end of the group" check.
 *
 * A section is presentation only. It changes how a run of settings is drawn,
 * never what they do.
 *
 * @return array Group key => list of rows, each `array( 'section' => slug|null, 'fields' => array of keys )`.
 */
function wpyeg_defaults_settings_rows() {
	$rows = array();

	foreach ( wpyeg_defaults_schema() as $key => $field ) {
		$group   = $field['group'];
		$section = isset( $field['section'] ) ? $field['section'] : null;

		if ( ! isset( $rows[ $group ] ) ) {
			$rows[ $group ] = array();
		}

		$last = $rows[ $group ] ? count( $rows[ $group ] ) - 1 : null;

		// A section continues only while consecutive fields keep naming it, so a
		// group can hold two runs of the same section without them merging.
		if ( null !== $section && null !== $last && $section === $rows[ $group ][ $last ]['section'] ) {
			$rows[ $group ][ $last ]['fields'][] = $key;
			continue;
		}

		$rows[ $group ][] = array(
			'section' => $section,
			'fields'  => array( $key ),
		);
	}

	return $rows;
}

/**
 * Render one control: its input, whatever supersedes it, and its help text.
 *
 * Everything a control needs to say about itself lives here, so the row shapes
 * above do not have to know which type they are drawing.
 *
 * @param string $key     Schema key.
 * @param array  $field   Schema entry.
 * @param bool   $stacked Whether the control sits inside a section fieldset.
 * @return void
 */
function wpyeg_defaults_render_control( $key, $field, $stacked ) {
	$name     = WPYEG_DEFAULTS_OPTION . '[' . $key . ']';
	$value    = wpyeg_defaults_get( $key );
	$field_id = wpyeg_defaults_field_id( $key );
	$help_id  = $field_id . '-description';
	$describe = empty( $field['help'] ) ? '' : ' aria-describedby="' . esc_attr( $help_id ) . '"';

	// A wp-config.php constant may supersede this setting; if so the control is
	// disabled and the reason shown next to it.
	$lock   = wpyeg_defaults_config_lock( $key );
	$locked = null !== $lock;

	/*
	 * A disabled control is not submitted, so a save under the constant would
	 * read as "unset" and quietly overwrite the stored preference. Carry it in a
	 * hidden field. For a toggle only 'yes' is carried, because sanitize treats
	 * any non-empty submitted value as checked.
	 */
	if ( $locked && ( 'toggle' !== $field['type'] || 'yes' === $value ) ) {
		printf( '<input type="hidden" name="%s" value="%s" />', esc_attr( $name ), esc_attr( 'toggle' === $field['type'] ? 'yes' : $value ) );
	}

	if ( 'toggle' === $field['type'] ) {
		// The descriptive label sits next to the checkbox inside one clickable
		// label, never a generic "Enabled".
		printf(
			'<label for="%1$s"><input type="checkbox" id="%1$s" name="%2$s" value="yes"%3$s%4$s%5$s /> %6$s</label>',
			esc_attr( $field_id ),
			esc_attr( $name ),
			$describe, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from esc_attr() above.
			disabled( $locked, true, false ),
			checked( 'yes', $value, false ),
			esc_html( $field['label'] )
		);
	} else {
		// Select and number fields keep a descriptive row label; a stacked one
		// has no row header to name it, so it carries its own.
		if ( $stacked ) {
			printf( '<label for="%s">%s</label> ', esc_attr( $field_id ), esc_html( $field['label'] ) );
		}

		if ( 'select' === $field['type'] ) {
			printf(
				'<select id="%1$s" name="%2$s"%3$s%4$s>',
				esc_attr( $field_id ),
				esc_attr( $name ),
				$describe, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from esc_attr() above.
				disabled( $locked, true, false )
			);

			foreach ( $field['choices'] as $choice => $choice_label ) {
				printf(
					'<option value="%s"%s>%s</option>',
					esc_attr( $choice ),
					selected( $choice, $value, false ),
					esc_html( $choice_label )
				);
			}

			echo '</select>';
		} else {
			printf(
				'<input type="number" class="small-text" step="1" min="%1$s" id="%2$s" name="%3$s" value="%4$s"%5$s%6$s />',
				esc_attr( isset( $field['min'] ) ? (int) $field['min'] : 0 ),
				esc_attr( $field_id ),
				esc_attr( $name ),
				esc_attr( $value ),
				$describe, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Built from esc_attr() above.
				disabled( $locked, true, false )
			);
		}
	}

	if ( $locked ) {
		echo '<p class="description">' . wp_kses( $lock, array( 'code' => array() ) ) . '</p>';
	}

	$jetpack_note = wpyeg_defaults_jetpack_warning( $key );

	if ( '' !== $jetpack_note ) {
		echo '<p class="description">' . wp_kses( $jetpack_note, wpyeg_defaults_help_allowed_html() ) . '</p>';
	}

	if ( ! empty( $field['help'] ) ) {
		printf(
			'<p id="%s" class="description">%s</p>',
			esc_attr( $help_id ),
			wp_kses( $field['help'], wpyeg_defaults_help_allowed_html() ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Sanitized by wp_kses().
		);
	}
}

/**
 * Render one table row: either a single setting or a stacked section of them.
 *
 * @param array $row One entry from wpyeg_defaults_settings_rows().
 * @return void
 */
function wpyeg_defaults_render_row( $row ) {
	$schema = wpyeg_defaults_schema();

	if ( null !== $row['section'] ) {
		$labels = wpyeg_defaults_section_labels();
		$title  = isset( $labels[ $row['section'] ] ) ? $labels[ $row['section'] ] : $row['section'];

		printf(
			'<tr><th scope="row">%1$s</th><td><fieldset><legend class="screen-reader-text"><span>%1$s</span></legend>',
			esc_html( $title )
		);

		foreach ( $row['fields'] as $key ) {
			echo '<div class="wpyeg-defaults-stacked">';
			wpyeg_defaults_render_control( $key, $schema[ $key ], true );
			echo '</div>';
		}

		echo '</fieldset></td></tr>';

		return;
	}

	$key   = $row['fields'][0];
	$field = $schema[ $key ];

	// A toggle already carries its label beside the checkbox, so it spans both
	// columns rather than repeating itself in a row header.
	if ( 'toggle' === $field['type'] ) {
		echo '<tr><td colspan="2">';
		wpyeg_defaults_render_control( $key, $field, false );
		echo '</td></tr>';

		return;
	}

	printf(
		'<tr><th scope="row"><label for="%s">%s</label></th><td>',
		esc_attr( wpyeg_defaults_field_id( $key ) ),
		esc_html( $field['label'] )
	);
	wpyeg_defaults_render_control( $key, $field, false );
	echo '</td></tr>';
}

/** Render the settings page. */
function wpyeg_defaults_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$rows = wpyeg_defaults_settings_rows();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Better by Default', 'sane-defaults' ); ?></h1>
		<p><?php esc_html_e( 'Each switch below is one opinionated default, and they are independent: turning one on does not turn on anything else, and a switch left off means this plugin does not apply that default at all.', 'sane-defaults' ); ?></p>

		<form method="post" action="options.php">
			<?php settings_fields( 'wpyeg_better_by_default_group' ); ?>

			<?php foreach ( wpyeg_defaults_groups() as $group_key => $group_label ) : ?>
				<h2><?php echo esc_html( $group_label ); ?></h2>
				<table class="form-table" role="presentation">
					<tbody>
					<?php
					$group_rows = isset( $rows[ $group_key ] ) ? $rows[ $group_key ] : array();

					foreach ( $group_rows as $row ) {
						wpyeg_defaults_render_row( $row );
					}
					?>
					</tbody>
				</table>
			<?php endforeach; ?>

			<?php submit_button(); ?>
		</form>

		<hr />
		<p><em><?php esc_html_e( 'Three hardening moves live in wp-config.php and cannot be toggled here:', 'sane-defaults' ); ?></em></p>
		<pre style="background:#f6f7f7;padding:12px;border:1px solid #dcdcde;max-width:640px;">define( 'DISALLOW_FILE_EDIT', true );
define( 'AUTOSAVE_INTERVAL', 120 );
define( 'WP_POST_REVISIONS', 10 );</pre>
	</div>
	<?php
}

/*
 * There is no activation hook, and that is deliberate.
 *
 * This plugin used to seed the option with every schema default on activation.
 * It changed nothing: wpyeg_defaults_get() already falls back to the schema
 * default for any key the stored array does not carry, so a fresh install
 * behaved identically with or without it. What the seeding did do was freeze a
 * site at its activation-time defaults — a setting whose default improved in a
 * later release never reached it, because the old value was sitting in the
 * database looking like a choice somebody made.
 *
 * Without it there is one rule, and it is the one the upgrade notices already
 * describe: a setting you have saved is yours and is never touched; a setting
 * you have never saved follows the current default.
 */

/**
 * Replace the author name in feeds.
 *
 * `the_author` also runs on the front end, so the feed check is what keeps this
 * from renaming bylines on the site itself — the default hides login names from
 * automated harvesting, not from readers.
 *
 * @param string $author Author display name.
 * @return string
 */
function wpyeg_defaults_mask_feed_author( $author ) {
	if ( ! is_feed() ) {
		return $author;
	}

	return (string) apply_filters( 'wpyeg_feed_author_name', 'Site Contributor' );
}

/**
 * Drop the emoji plugin from the TinyMCE plugin list.
 *
 * Core registers `wpemoji` for the classic editor separately from the front-end
 * and admin-head emoji output, so removing those actions leaves this one
 * loading.
 *
 * @param mixed $plugins TinyMCE plugin list.
 * @return array
 */
function wpyeg_defaults_remove_emoji_tinymce_plugin( $plugins ) {
	return is_array( $plugins ) ? array_values( array_diff( $plugins, array( 'wpemoji' ) ) ) : array();
}

/**
 * Remove author identity from oEmbed responses.
 *
 * `oembed/1.0/embed` returns `author_name` and `author_url` for every post, and
 * the URL carries the account's nicename. Redirecting `/author/` archives does
 * nothing about it, so a site that had hidden its authors was still handing out
 * login names through a route nobody thinks of as a user endpoint.
 *
 * Measured on a live install before the fix: with `disable_author_archives` on
 * and `/author/admin/` answering 301, oEmbed still returned `admin` and
 * `http://example.test/author/admin/`.
 *
 * Title, provider and embed markup are left alone, so embeds on other sites
 * keep working — this hides the name, it does not break the embed.
 *
 * @param mixed $data oEmbed response data.
 * @return mixed
 */
function wpyeg_defaults_strip_oembed_author( $data ) {
	if ( ! is_array( $data ) ) {
		return $data;
	}

	unset( $data['author_name'], $data['author_url'] );

	return $data;
}

/**
 * Drop core's users sitemap when author archives are hidden.
 *
 * `wp-sitemap-users-1.xml` lists every author's archive URL by nicename. With
 * the archives redirected those URLs go nowhere, but the sitemap is still a
 * published list of account names — an enumeration source by construction, and
 * one search engines are explicitly invited to read.
 *
 * Only the users provider is dropped; posts, pages and taxonomies stay listed.
 *
 * @param mixed  $provider Sitemap provider instance, or false.
 * @param string $name     Provider name.
 * @return mixed
 */
function wpyeg_defaults_drop_users_sitemap( $provider, $name ) {
	return ( 'users' === $name ) ? false : $provider;
}
