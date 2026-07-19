<?php
/**
 * Plugin Name:       Better by Default
 * Plugin URI:        https://github.com/WPYEG/Better-by-Default
 * Description:        Sane defaults for every new WordPress site. Applies a menu of sensible security, UX, SEO, and performance defaults — each one individually toggleable from Settings → Better by Default. Built for the WPYEG Edmonton WordPress meetup.
 * Version:           1.0.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            WPYEG
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       better-by-default
 *
 * ---------------------------------------------------------------------------
 * WORKSHOP NOTE
 * Every policy below is gated behind an option (prefix: wpyeg_). The pattern is
 * always the same:
 *
 *     if ( wpyeg_defaults_enabled( 'some_key' ) ) { add_filter( ... ); }
 *
 * That single idea — "a default is just an opinionated filter behind a toggle"
 * — is the whole talk. Read the SETTINGS_SCHEMA array first; it's the map.
 * ---------------------------------------------------------------------------
 */

// Bail if called directly.
defined( 'ABSPATH' ) || exit;

/**
 * The single source of truth: every setting, its default, type, and label.
 *
 * type:  'toggle' (yes/no), 'select', or 'number'
 * group: which fieldset it renders under on the settings screen
 */
function wpyeg_defaults_schema() {
	return array(

		// --- Security ---------------------------------------------------
		'restrict_rest_user_discovery' => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'security',
			'label'   => 'Restrict REST API user discovery',
			'help'    => 'Hides /wp/v2/users from logged-out requests (stops username enumeration).',
		),
		'disable_rest' => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'security',
			'label'   => 'Require auth for all REST requests',
			'help'    => 'Blocks anonymous REST entirely. Leave OFF unless this is a pure brochure site — the block editor needs REST.',
		),
		'xmlrpc_allow_pingbacks' => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'security',
			'label'   => 'XML-RPC: accept incoming pingbacks',
			'help'    => 'OFF (default) removes pingback.ping — a spam/reflection-DDoS vector — and the X-Pingback header.',
		),
		'xmlrpc_allow_remote_publishing' => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'security',
			'label'   => 'XML-RPC: allow remote publishing (blogging apps)',
			'help'    => 'OFF (default) removes the credential-authenticated wp.*/metaWeblog/MT/blogger methods (a brute-force target) and the RSD link.',
		),
		'xmlrpc_allow_multicall' => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'security',
			'label'   => 'XML-RPC: allow system.multicall',
			'help'    => 'OFF (default) refuses system.multicall — the amplification lever for batched brute force — via a replacement server. A filter cannot remove it.',
		),
		'block_xmlrpc_endpoint' => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'security',
			'label'   => 'XML-RPC: block the endpoint entirely',
			'help'    => 'Strictest tier — xmlrpc.php returns 403 for every request. Do NOT enable on a Jetpack site; it breaks the WordPress.com connection.',
		),
		'disable_application_passwords' => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'security',
			'label'   => 'Prohibit Application Passwords',
			'help'    => 'OFF (default) keeps them available — they are the safer, revocable integration credential, and core accepts only them for REST Basic Auth. Turn ON only if site policy forbids non-interactive credentials.',
		),
		'require_strong_passwords' => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'security',
			'label'   => 'Require strong passwords',
			'help'    => 'Server-side rule: 15+ characters, screened for known breaches — length + screening, not forced composition (per NIST).',
		),
		'remove_version' => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'security',
			'label'   => 'Remove WordPress version fingerprint',
			'help'    => 'Strips the generator meta tag so scanners can\'t read your exact version.',
		),
		'security_headers' => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'security',
			'label'   => 'Send baseline security headers',
			'help'    => 'X-Content-Type-Options, X-Frame-Options, Referrer-Policy.',
		),
		'disable_ai_connectors' => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'security',
			'label'   => 'Disable AI connectors',
			'help'    => 'Fires the wpyeg_disable_ai_connectors action. No stable WP core hook yet — this is a policy stub for your own plugin logic.',
		),

		// --- Content & public surfaces ---------------------------------
		'disable_comments' => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'content',
			'label'   => 'Disable comments, trackbacks & pingbacks',
			'help'    => 'Closes comments everywhere, hides existing threads, removes the admin menu.',
		),
		'disable_pingbacks' => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'content',
			'label'   => 'Default new posts to pings closed',
			'help'    => 'Sets the "allow pingbacks" default to off for newly created content.',
		),
		'disable_self_pingbacks' => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'content',
			'label'   => 'Disable self-pingbacks',
			'help'    => 'Stops internal links from generating pingback noise.',
		),
		'disable_author_archives' => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'content',
			'label'   => 'Disable public author archives',
			'help'    => 'Redirects /author/{slug}/ to home (another enumeration + thin-content fix).',
		),
		'redirect_attachment_pages' => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'content',
			'label'   => 'Redirect attachment pages',
			'help'    => 'Sends thin attachment pages to the parent post or home.',
		),
		'disable_emojis' => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'content',
			'label'   => 'Disable emoji script',
			'help'    => 'Removes the emoji detection script + inline CSS from every page.',
		),

		// --- Admin & front-end UX --------------------------------------
		'title_only_admin_search' => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'ux',
			'label'   => 'Title-only admin search',
			'help'    => 'Speeds up admin list-table search on big sites by matching titles only.',
		),
		'frontend_admin_bar_behavior' => array(
			'default' => '',
			'type'    => 'select',
			'group'   => 'ux',
			'label'   => 'Front-end admin bar',
			'help'    => 'Control the floating admin bar on the front of the site.',
			'choices' => array(
				''               => 'Leave unchanged (WordPress default)',
				'hide_non_admins' => 'Hide for non-admins',
				'hide_all'        => 'Hide for everyone',
			),
		),

		// --- Login & sessions ------------------------------------------
		'disable_remember_me' => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'login',
			'label'   => 'Disable "Remember Me"',
			'help'    => 'Hides the checkbox and caps sessions short. Good for shared/kiosk machines.',
		),
		'remember_me_days' => array(
			'default' => 5,
			'type'    => 'number',
			'group'   => 'login',
			'label'   => 'Remember Me length (days)',
			'help'    => 'Caps the persistent session. Core default is 14. Set 0 to leave core alone.',
		),
		'session_regular_hours' => array(
			'default' => 0,
			'type'    => 'number',
			'group'   => 'login',
			'label'   => 'Regular session length (hours)',
			'help'    => 'Length of a non-remembered login. 0 = leave the core default (2 days).',
		),

		// --- Branding ---------------------------------------------------
		'login_logo_behavior' => array(
			'default' => 'keep_default',
			'type'    => 'select',
			'group'   => 'branding',
			'label'   => 'Login logo',
			'help'    => 'The default logo links to wordpress.org — a small trust leak. Left untouched by default, since changing the login screen out of the box is intrusive. Removing, unlinking, or replacing the logo always points the header link at your home page.',
			'choices' => array(
				'keep_default' => 'Keep the WordPress logo and wp.org link (WordPress default)',
				'remove_logo'  => 'Remove the logo and the wp.org link',
				'unlink_logo'  => 'Keep the logo, drop the wp.org link',
				'replace_logo' => 'Replace the logo with the site icon',
			),
		),

		// --- Performance ------------------------------------------------
		'throttle_heartbeat' => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'performance',
			'label'   => 'Throttle the Heartbeat API',
			'help'    => 'Slows admin polling to 60s and drops it on the dashboard home.',
		),
		'defer_scripts' => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'performance',
			'label'   => 'Defer front-end scripts',
			'help'    => 'Adds defer to enqueued front-end scripts (skips jQuery core).',
		),
	);
}

/** Human-friendly group titles for the settings screen. */
function wpyeg_defaults_groups() {
	return array(
		'security'    => 'Security & Attack Surface',
		'content'     => 'Content & Public Surfaces',
		'ux'          => 'Admin & Front-End UX',
		'login'       => 'Login & Sessions',
		'branding'    => 'Branding',
		'performance' => 'Performance',
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
	static $stored = null;
	if ( null === $stored ) {
		$stored = get_option( WPYEG_DEFAULTS_OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
	}

	$schema = wpyeg_defaults_schema();
	if ( ! isset( $schema[ $key ] ) ) {
		return null;
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


/* =======================================================================
 * BOOTSTRAP — wire each enabled policy to its hook.
 * ===================================================================== */

add_action( 'plugins_loaded', 'wpyeg_defaults_bootstrap' );

function wpyeg_defaults_bootstrap() {

	/* ----- Security ----- */

	if ( wpyeg_defaults_enabled( 'restrict_rest_user_discovery' ) ) {
		add_filter( 'rest_endpoints', function ( $endpoints ) {
			if ( ! is_user_logged_in() ) {
				unset( $endpoints['/wp/v2/users'] );
				unset( $endpoints['/wp/v2/users/(?P<id>[\d]+)'] );
			}
			return $endpoints;
		} );
	}

	if ( wpyeg_defaults_enabled( 'disable_rest' ) ) {
		add_filter( 'rest_authentication_errors', function ( $result ) {
			if ( ! empty( $result ) ) {
				return $result;
			}
			if ( ! is_user_logged_in() ) {
				return new WP_Error(
					'rest_not_logged_in',
					__( 'REST API restricted to authenticated users.', 'better-by-default' ),
					array( 'status' => 401 )
				);
			}
			return $result;
		} );
	}

	/*
	 * XML-RPC is per-category, not all-or-nothing. Each category is off by
	 * default (locked down) and opt-in to re-enable — the same shape PMP uses.
	 * pingbacks and remote publishing come off via the xmlrpc_methods filter;
	 * system.multicall and a full endpoint block need a server-class swap,
	 * because IXR re-adds multicall after the filter runs.
	 */
	add_filter( 'xmlrpc_methods', function ( $methods ) {
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
	}, PHP_INT_MAX );

	// Gate the whole endpoint off unless remote publishing is allowed.
	add_filter( 'xmlrpc_enabled', function ( $enabled ) {
		return wpyeg_defaults_enabled( 'xmlrpc_allow_remote_publishing' ) ? $enabled : false;
	} );

	// Strip the pingback discovery header when pingbacks are off.
	add_filter( 'wp_headers', function ( $headers ) {
		if ( ! wpyeg_defaults_enabled( 'xmlrpc_allow_pingbacks' ) ) {
			unset( $headers['X-Pingback'] );
		}
		return $headers;
	} );

	// Drop the RSD link (blogging-client discovery) when remote publishing is off.
	if ( ! wpyeg_defaults_enabled( 'xmlrpc_allow_remote_publishing' ) ) {
		remove_action( 'wp_head', 'rsd_link' );
	}

	/*
	 * The replacement server class is defined lazily inside this filter: it only
	 * runs from xmlrpc.php, where the parent wp_xmlrpc_server is already loaded,
	 * so extending it at plugin-load time (on every ordinary request) is avoided.
	 */
	add_filter( 'wp_xmlrpc_server_class', function ( $class ) {
		if ( wpyeg_defaults_enabled( 'block_xmlrpc_endpoint' ) ) {
			if ( ! class_exists( 'Wpyeg_Blocked_XMLRPC_Server' ) ) {
				class Wpyeg_Blocked_XMLRPC_Server {
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
				class Wpyeg_Multicall_Disabled_Server extends wp_xmlrpc_server {
					public function multiCall( $methodcalls ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Overrides a core method name.
						return new IXR_Error( 405, 'system.multicall is disabled on this site.' );
					}
				}
			}
			return 'Wpyeg_Multicall_Disabled_Server';
		}

		return $class;
	} );

	if ( wpyeg_defaults_enabled( 'disable_application_passwords' ) ) {
		add_filter( 'wp_is_application_passwords_available', '__return_false' );
	}

	if ( wpyeg_defaults_enabled( 'require_strong_passwords' ) ) {
		add_action( 'user_profile_update_errors', 'wpyeg_enforce_strong_password', 10, 3 );
		add_action( 'validate_password_reset', function ( $errors, $user ) {
			wpyeg_enforce_strong_password( $errors, true, $user );
		}, 10, 2 );
	}

	if ( wpyeg_defaults_enabled( 'remove_version' ) ) {
		remove_action( 'wp_head', 'wp_generator' );
		add_filter( 'the_generator', '__return_empty_string' );
	}

	if ( wpyeg_defaults_enabled( 'security_headers' ) ) {
		add_filter( 'wp_headers', function ( $headers ) {
			$headers['X-Content-Type-Options'] = 'nosniff';
			$headers['X-Frame-Options']        = 'SAMEORIGIN';
			$headers['Referrer-Policy']        = 'strict-origin-when-cross-origin';
			return $headers;
		} );
	}

	if ( wpyeg_defaults_enabled( 'disable_ai_connectors' ) ) {
		/**
		 * No stable WordPress core hook exists to switch off "AI support"
		 * generically yet. This action is the seam for your own plugin
		 * policy — hook it to unregister providers, hide UI, etc.
		 */
		do_action( 'wpyeg_disable_ai_connectors' );
	}

	/* ----- Content & public surfaces ----- */

	if ( wpyeg_defaults_enabled( 'disable_comments' ) ) {
		add_filter( 'comments_open', '__return_false', 20 );
		add_filter( 'pings_open', '__return_false', 20 );
		add_filter( 'comments_array', '__return_empty_array', 20 );

		add_action( 'init', function () {
			foreach ( get_post_types() as $type ) {
				if ( post_type_supports( $type, 'comments' ) ) {
					remove_post_type_support( $type, 'comments' );
					remove_post_type_support( $type, 'trackbacks' );
				}
			}
		} );

		add_action( 'admin_menu', function () {
			remove_menu_page( 'edit-comments.php' );
		} );

		add_action( 'wp_before_admin_bar_render', function () {
			global $wp_admin_bar;
			if ( $wp_admin_bar ) {
				$wp_admin_bar->remove_node( 'comments' );
			}
		} );
	}

	if ( wpyeg_defaults_enabled( 'disable_pingbacks' ) ) {
		add_filter( 'pre_option_default_pingback_flag', '__return_zero' );
		add_filter( 'pre_option_default_ping_status', function () {
			return 'closed';
		} );
	}

	if ( wpyeg_defaults_enabled( 'disable_self_pingbacks' ) ) {
		add_action( 'pre_ping', function ( &$links ) {
			$home = home_url();
			foreach ( (array) $links as $key => $link ) {
				if ( 0 === strpos( $link, $home ) ) {
					unset( $links[ $key ] );
				}
			}
		} );
	}

	if ( wpyeg_defaults_enabled( 'disable_author_archives' ) ) {
		add_action( 'template_redirect', function () {
			if ( is_author() ) {
				wp_safe_redirect( home_url( '/' ), 301 );
				exit;
			}
		} );
	}

	if ( wpyeg_defaults_enabled( 'redirect_attachment_pages' ) ) {
		add_action( 'template_redirect', function () {
			if ( is_attachment() ) {
				$parent = wp_get_post_parent_id( get_queried_object_id() );
				$target = $parent ? get_permalink( $parent ) : home_url( '/' );
				wp_safe_redirect( $target, 301 );
				exit;
			}
		} );
	}

	if ( wpyeg_defaults_enabled( 'disable_emojis' ) ) {
		add_action( 'init', function () {
			remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
			remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
			remove_action( 'wp_print_styles', 'print_emoji_styles' );
			remove_action( 'admin_print_styles', 'print_emoji_styles' );
			remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
			remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
			remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
			add_filter( 'emoji_svg_url', '__return_false' );
		} );
	}

	/* ----- Admin & front-end UX ----- */

	if ( wpyeg_defaults_enabled( 'title_only_admin_search' ) ) {
		// Narrow the search COLUMNS, don't rewrite the whole clause. The
		// post_search_columns filter (WP 6.2+) keeps core's term parsing,
		// -exclusions, and the logged-out post_password guard intact — the
		// blunt posts_search rewrite throws all of that away.
		add_filter( 'post_search_columns', function ( $columns, $search, $query ) {
			if ( is_admin() && $query->is_main_query() ) {
				return array( 'post_title' );
			}
			return $columns;
		}, 10, 3 );
	}

	$bar = wpyeg_defaults_get( 'frontend_admin_bar_behavior' );
	if ( 'hide_all' === $bar ) {
		add_filter( 'show_admin_bar', '__return_false' );
	} elseif ( 'hide_non_admins' === $bar ) {
		add_filter( 'show_admin_bar', function ( $show ) {
			return current_user_can( 'manage_options' ) ? $show : false;
		} );
	}

	/* ----- Login & sessions ----- */

	if ( wpyeg_defaults_enabled( 'disable_remember_me' ) ) {
		add_action( 'login_footer', function () {
			echo "<script>(function(){var c=document.getElementById('rememberme');if(c&&c.closest('p')){c.closest('p').style.display='none';}})();</script>";
		} );
	}

	// One filter handles both the remember and regular session lengths.
	add_filter( 'auth_cookie_expiration', function ( $expiration, $user_id, $remember ) {
		if ( wpyeg_defaults_enabled( 'disable_remember_me' ) ) {
			return $remember ? 2 * DAY_IN_SECONDS : $expiration;
		}
		if ( $remember ) {
			$days = (int) wpyeg_defaults_get( 'remember_me_days' );
			if ( $days > 0 ) {
				return $days * DAY_IN_SECONDS;
			}
		} else {
			$hours = (int) wpyeg_defaults_get( 'session_regular_hours' );
			if ( $hours > 0 ) {
				return $hours * HOUR_IN_SECONDS;
			}
		}
		return $expiration;
	}, 10, 3 );

	/* ----- Branding ----- */

	$login_logo = wpyeg_defaults_get( 'login_logo_behavior' );

	if ( 'remove_logo' === $login_logo ) {
		add_action( 'login_head', function () {
			echo '<style>#login h1 a, .login h1 a { display:none; }</style>';
		} );
	} elseif ( 'replace_logo' === $login_logo ) {
		add_action( 'login_head', function () {
			$icon = function_exists( 'get_site_icon_url' ) ? get_site_icon_url( 84 ) : '';
			if ( $icon ) {
				echo '<style>#login h1 a, .login h1 a { background-image:url(' . esc_url( $icon ) . '); background-size:contain; }</style>';
			}
		} );
	}

	// Removing, unlinking, or replacing the logo all repoint the header link
	// at the site home instead of wordpress.org. There is no separate toggle:
	// a replacement/removed logo linking back to wp.org makes no sense.
	if ( in_array( $login_logo, array( 'remove_logo', 'unlink_logo', 'replace_logo' ), true ) ) {
		add_filter( 'login_headerurl', 'home_url' );
		add_filter( 'login_headertext', function () {
			return get_bloginfo( 'name' );
		} );
	}

	/* ----- Performance ----- */

	if ( wpyeg_defaults_enabled( 'throttle_heartbeat' ) ) {
		add_filter( 'heartbeat_settings', function ( $settings ) {
			$settings['interval'] = 60;
			return $settings;
		} );
		add_action( 'init', function () {
			if ( is_admin() ) {
				global $pagenow;
				if ( 'index.php' === $pagenow ) {
					wp_deregister_script( 'heartbeat' );
				}
			}
		} );
	}

	if ( wpyeg_defaults_enabled( 'defer_scripts' ) ) {
		add_filter( 'script_loader_tag', function ( $tag, $handle ) {
			if ( is_admin() ) {
				return $tag;
			}
			$skip = array( 'jquery-core' );
			if ( in_array( $handle, $skip, true ) ) {
				return $tag;
			}
			if ( false === strpos( $tag, ' defer' ) && false !== strpos( $tag, ' src=' ) ) {
				$tag = str_replace( ' src=', ' defer src=', $tag );
			}
			return $tag;
		}, 10, 2 );
	}
}

/**
 * Shared strong-password validator (used for profile update + reset).
 *
 * @param WP_Error         $errors
 * @param bool             $update
 * @param stdClass|WP_User $user
 */
function wpyeg_enforce_strong_password( $errors, $update, $user ) {
	$password = isset( $_POST['pass1'] ) ? (string) wp_unslash( $_POST['pass1'] ) : '';

	if ( '' === $password ) {
		return; // No password change requested.
	}

	// NIST 800-63B / OWASP: favour length + breach screening over forced
	// composition rules (upper/lower/number/symbol). Composition rules push
	// users toward predictable patterns (Password1!) without adding entropy.
	if ( strlen( $password ) < 15 ) {
		$errors->add(
			'wpyeg_pass_too_short',
			__( '<strong>Error:</strong> Password must be at least 15 characters.', 'better-by-default' )
		);
		return;
	}

	/*
	 * Screening stand-in. A production build should plug in a real strength
	 * estimator (a zxcvbn PHP port) and a breach check — the Have I Been Pwned
	 * range API, queried by k-anonymity (send only the first 5 SHA-1 hex chars,
	 * never the password). The wpyeg_password_is_pwned filter is that seam.
	 */
	$obvious = array( 'passwordpassword', '123456789012345', 'qwertyuiopasdfg', 'administratoradmin' );
	$pwned   = in_array( strtolower( $password ), $obvious, true )
		|| (bool) apply_filters( 'wpyeg_password_is_pwned', false, $password );

	if ( $pwned ) {
		$errors->add(
			'wpyeg_pass_pwned',
			__( '<strong>Error:</strong> Choose a password that has not appeared in a known data breach.', 'better-by-default' )
		);
	}
}


/* =======================================================================
 * SETTINGS SCREEN — Settings → Better by Default
 * ===================================================================== */

add_action( 'admin_menu', function () {
	add_options_page(
		__( 'Better by Default', 'better-by-default' ),
		__( 'Better by Default', 'better-by-default' ),
		'manage_options',
		'better-by-default',
		'wpyeg_defaults_render_settings_page'
	);
} );

add_action( 'admin_init', function () {
	register_setting(
		'wpyeg_better_by_default_group',
		WPYEG_DEFAULTS_OPTION,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'wpyeg_defaults_sanitize',
			'default'           => array(),
		)
	);
} );

/**
 * Sanitize the whole settings array against the schema.
 *
 * @param mixed $input
 * @return array
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
				$clean[ $key ] = isset( $input[ $key ] ) ? max( 0, absint( $input[ $key ] ) ) : $field['default'];
				break;

			case 'select':
				$choices        = isset( $field['choices'] ) ? array_keys( $field['choices'] ) : array();
				$value          = isset( $input[ $key ] ) ? (string) $input[ $key ] : $field['default'];
				$clean[ $key ]  = in_array( $value, $choices, true ) ? $value : $field['default'];
				break;
		}
	}

	return $clean;
}

/** Render the settings page. */
function wpyeg_defaults_render_settings_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$schema = wpyeg_defaults_schema();
	$groups = wpyeg_defaults_groups();
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Better by Default', 'better-by-default' ); ?></h1>
		<p><?php esc_html_e( 'Each switch below is one opinionated default. Flip what you want; the rest of WordPress is untouched.', 'better-by-default' ); ?></p>

		<form method="post" action="options.php">
			<?php settings_fields( 'wpyeg_better_by_default_group' ); ?>

			<?php foreach ( $groups as $group_key => $group_label ) : ?>
				<h2><?php echo esc_html( $group_label ); ?></h2>
				<table class="form-table" role="presentation">
					<tbody>
					<?php foreach ( $schema as $key => $field ) : ?>
						<?php if ( $field['group'] !== $group_key ) { continue; } ?>
						<?php $name  = WPYEG_DEFAULTS_OPTION . '[' . $key . ']'; ?>
						<?php $value = wpyeg_defaults_get( $key ); ?>
						<tr>
							<th scope="row"><?php echo esc_html( $field['label'] ); ?></th>
							<td>
								<?php if ( 'toggle' === $field['type'] ) : ?>
									<label>
										<input type="checkbox"
											name="<?php echo esc_attr( $name ); ?>"
											value="yes"
											<?php checked( 'yes', $value ); ?> />
										<?php esc_html_e( 'Enabled', 'better-by-default' ); ?>
									</label>
								<?php elseif ( 'select' === $field['type'] ) : ?>
									<select name="<?php echo esc_attr( $name ); ?>">
										<?php foreach ( $field['choices'] as $ck => $cl ) : ?>
											<option value="<?php echo esc_attr( $ck ); ?>" <?php selected( $ck, $value ); ?>>
												<?php echo esc_html( $cl ); ?>
											</option>
										<?php endforeach; ?>
									</select>
								<?php elseif ( 'number' === $field['type'] ) : ?>
									<input type="number" min="0" step="1"
										name="<?php echo esc_attr( $name ); ?>"
										value="<?php echo esc_attr( $value ); ?>"
										class="small-text" />
								<?php endif; ?>

								<?php if ( ! empty( $field['help'] ) ) : ?>
									<p class="description"><?php echo esc_html( $field['help'] ); ?></p>
								<?php endif; ?>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endforeach; ?>

			<?php submit_button(); ?>
		</form>

		<hr />
		<p><em><?php esc_html_e( 'Three hardening moves live in wp-config.php and cannot be toggled here:', 'better-by-default' ); ?></em></p>
		<pre style="background:#f6f7f7;padding:12px;border:1px solid #dcdcde;max-width:640px;">define( 'DISALLOW_FILE_EDIT', true );
define( 'AUTOSAVE_INTERVAL', 120 );
define( 'WP_POST_REVISIONS', 10 );</pre>
	</div>
	<?php
}

/**
 * On activation, seed the option with schema defaults so a fresh install
 * behaves as documented out of the box.
 */
register_activation_hook( __FILE__, function () {
	if ( false === get_option( WPYEG_DEFAULTS_OPTION, false ) ) {
		$schema   = wpyeg_defaults_schema();
		$defaults = array();
		foreach ( $schema as $key => $field ) {
			$defaults[ $key ] = $field['default'];
		}
		add_option( WPYEG_DEFAULTS_OPTION, $defaults );
	}
} );
