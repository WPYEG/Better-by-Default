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
 * @package BetterByDefault
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
 * Type is 'toggle' (yes/no), 'select', or 'number'. Group determines which
 * fieldset renders the setting on the settings screen.
 */
function wpyeg_defaults_schema() {
	return array(

		// --- Security ---------------------------------------------------
		'restrict_rest_user_discovery'  => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'security',
			'label'   => __( 'Restrict public REST API user discovery', 'better-by-default' ),
			'help'    => __( 'Removes core /wp/v2/users routes for logged-out requests. Other public author data may still exist.', 'better-by-default' ),
		),
		'disable_rest'                  => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'security',
			'label'   => __( 'Require authentication for all REST requests', 'better-by-default' ),
			'help'    => __( 'Compatibility-sensitive. Public blocks, forms, search, oEmbed, and integrations may require anonymous REST access.', 'better-by-default' ),
		),
		'disable_xmlrpc'                => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'security',
			'label'   => __( 'Disable XML-RPC methods', 'better-by-default' ),
			'help'    => __( 'Removes all registered XML-RPC methods and discovery hints. The endpoint may still return an XML-RPC fault.', 'better-by-default' ),
		),
		'disable_application_passwords' => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'security',
			'label'   => __( 'Disable Application Passwords', 'better-by-default' ),
			'help'    => __( 'Optional site policy. Application Passwords are hashed, per-application, revocable credentials for integrations.', 'better-by-default' ),
		),
		'require_strong_passwords'      => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'security',
			'label'   => __( 'Enforce the workshop password policy', 'better-by-default' ),
			'help'    => __( 'Requires 15+ characters and rejects a filterable common-password blocklist in wp-admin, reset, and REST user flows.', 'better-by-default' ),
		),
		'remove_version'                => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'security',
			'label'   => __( 'Remove the generator version tag', 'better-by-default' ),
			'help'    => __( 'Output hygiene only; this does not prevent reliable WordPress or version fingerprinting.', 'better-by-default' ),
		),

		// --- Content & public surfaces ---------------------------------
		'disable_comments'              => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'content',
			'label'   => __( 'Disable comments, trackbacks, and pingbacks', 'better-by-default' ),
			'help'    => __( 'Appropriate for sites that do not use discussion. Closes comments and hides existing threads from public templates.', 'better-by-default' ),
		),
		'disable_pingbacks'             => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'content',
			'label'   => __( 'Default new posts to pings closed', 'better-by-default' ),
			'help'    => __( 'Sets the pingback and trackback default to closed for newly created content.', 'better-by-default' ),
		),
		'disable_self_pingbacks'        => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'content',
			'label'   => __( 'Disable self-pingbacks', 'better-by-default' ),
			'help'    => __( 'Stops internal links from generating pingback notifications.', 'better-by-default' ),
		),
		'disable_author_archives'       => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'content',
			'label'   => __( 'Disable public author archives', 'better-by-default' ),
			'help'    => __( 'Returns a 404 for author archives and suppresses numeric-author canonical redirects. Enable archives on intentional multi-author sites.', 'better-by-default' ),
		),
		'redirect_attachment_pages'     => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'content',
			'label'   => __( 'Redirect legacy attachment pages', 'better-by-default' ),
			'help'    => __( 'WordPress 6.4+ disables attachment pages on new sites. Enable this only to redirect pages retained by an upgraded site.', 'better-by-default' ),
		),
		'disable_emojis'                => array(
			'default' => 'yes',
			'type'    => 'toggle',
			'group'   => 'content',
			'label'   => __( 'Disable emoji compatibility support', 'better-by-default' ),
			'help'    => __( 'Removes core emoji compatibility scripts, styles, feed conversion, email conversion, and TinyMCE support.', 'better-by-default' ),
		),

		// --- Admin & front-end UX --------------------------------------
		'title_only_admin_search'       => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'ux',
			'label'   => __( 'Title-only admin search', 'better-by-default' ),
			'help'    => __( 'Changes editor expectations. Uses core search-column filtering so term parsing and exclusions remain intact.', 'better-by-default' ),
		),
		'frontend_admin_bar_behavior'   => array(
			'default' => '',
			'type'    => 'select',
			'group'   => 'ux',
			'label'   => __( 'Front-end admin bar', 'better-by-default' ),
			'help'    => __( 'Control the front-end toolbar for authenticated users.', 'better-by-default' ),
			'choices' => array(
				''                => __( 'Leave unchanged (WordPress default)', 'better-by-default' ),
				'hide_non_admins' => __( 'Hide for users without manage_options', 'better-by-default' ),
				'hide_all'        => __( 'Hide for everyone', 'better-by-default' ),
			),
		),

		// --- Login & sessions ------------------------------------------
		'disable_remember_me'           => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'login',
			'label'   => __( 'Disable Remember Me', 'better-by-default' ),
			'help'    => __( 'Hides the checkbox and removes submitted rememberme values before authentication.', 'better-by-default' ),
		),
		'remember_me_days'              => array(
			'default' => 0,
			'type'    => 'number',
			'group'   => 'login',
			'label'   => __( 'Remember Me length (days)', 'better-by-default' ),
			'help'    => __( 'Set 0 to keep the WordPress default of 14 days. Applies to new logins only.', 'better-by-default' ),
		),
		'session_regular_hours'         => array(
			'default' => 0,
			'type'    => 'number',
			'group'   => 'login',
			'label'   => __( 'Regular session length (hours)', 'better-by-default' ),
			'help'    => __( 'Set 0 to keep the WordPress default of 48 hours. Applies to new logins only.', 'better-by-default' ),
		),

		// --- Branding ---------------------------------------------------
		'login_logo_behavior'           => array(
			'default' => 'default',
			'type'    => 'select',
			'group'   => 'branding',
			'label'   => __( 'Login logo', 'better-by-default' ),
			'help'    => __( 'Branding preference, not a security control.', 'better-by-default' ),
			'choices' => array(
				'default'     => __( 'Leave the WordPress logo', 'better-by-default' ),
				'remove_logo' => __( 'Remove the logo', 'better-by-default' ),
			),
		),
		'login_logo_link_home'          => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'branding',
			'label'   => __( 'Point the login logo link at site home', 'better-by-default' ),
			'help'    => __( 'Optional branding preference for the login header link.', 'better-by-default' ),
		),

		// --- Performance ------------------------------------------------
		'throttle_heartbeat'            => array(
			'default' => 'no',
			'type'    => 'toggle',
			'group'   => 'performance',
			'label'   => __( 'Throttle the Heartbeat API', 'better-by-default' ),
			'help'    => __( 'Raises the requested polling interval to 60 seconds. Test editorial locking, autosave, and plugin workflows.', 'better-by-default' ),
		),
	);
}

/** Human-friendly group titles for the settings screen. */
function wpyeg_defaults_groups() {
	return array(
		'security'    => __( 'Security & Attack Surface', 'better-by-default' ),
		'content'     => __( 'Content & Public Surfaces', 'better-by-default' ),
		'ux'          => __( 'Admin & Front-End UX', 'better-by-default' ),
		'login'       => __( 'Login & Sessions', 'better-by-default' ),
		'branding'    => __( 'Branding', 'better-by-default' ),
		'performance' => __( 'Performance', 'better-by-default' ),
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


/*
 * ======================================================================
 * BOOTSTRAP — wire each enabled policy to its hook.
 * ======================================================================
 */

add_action( 'init', 'wpyeg_defaults_bootstrap', 1 );

/** Register the hooks for every enabled policy. */
function wpyeg_defaults_bootstrap() {

	/* ----- Security ----- */

	if ( wpyeg_defaults_enabled( 'restrict_rest_user_discovery' ) ) {
		add_filter(
			'rest_endpoints',
			function ( $endpoints ) {
				if ( ! is_user_logged_in() ) {
					foreach ( array_keys( $endpoints ) as $route ) {
						if ( preg_match( '#^/wp/v2/users(?:/|$)#', $route ) ) {
							unset( $endpoints[ $route ] );
						}
					}
				}
				return $endpoints;
			}
		);
	}

	if ( wpyeg_defaults_enabled( 'disable_rest' ) ) {
		add_filter(
			'rest_authentication_errors',
			function ( $result ) {
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
			}
		);
	}

	if ( wpyeg_defaults_enabled( 'disable_xmlrpc' ) ) {
		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter( 'xmlrpc_methods', '__return_empty_array', PHP_INT_MAX );
		add_filter(
			'wp_headers',
			function ( $headers ) {
				unset( $headers['X-Pingback'] );
				return $headers;
			}
		);
		remove_action( 'wp_head', 'rsd_link' );
	}

	if ( wpyeg_defaults_enabled( 'disable_application_passwords' ) ) {
		add_filter( 'wp_is_application_passwords_available', '__return_false' );
	}

	if ( wpyeg_defaults_enabled( 'require_strong_passwords' ) ) {
		add_action( 'user_profile_update_errors', 'wpyeg_defaults_validate_profile_password', 10, 3 );
		add_action( 'validate_password_reset', 'wpyeg_defaults_validate_reset_password', 10, 2 );
		add_filter( 'rest_pre_insert_user', 'wpyeg_defaults_validate_rest_password', 10, 2 );
	}

	if ( wpyeg_defaults_enabled( 'remove_version' ) ) {
		remove_action( 'wp_head', 'wp_generator' );
		add_filter( 'the_generator', '__return_empty_string' );
	}

	/* ----- Content & public surfaces ----- */

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
			},
			100
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
				$home_host = wp_parse_url( home_url(), PHP_URL_HOST );
				foreach ( (array) $links as $key => $link ) {
					if ( $home_host && wp_parse_url( $link, PHP_URL_HOST ) === $home_host ) {
						unset( $links[ $key ] );
					}
				}
			}
		);
	}

	if ( wpyeg_defaults_enabled( 'disable_author_archives' ) ) {
		add_filter(
			'redirect_canonical',
			function ( $redirect_url ) {
				if ( is_author() || get_query_var( 'author' ) ) {
					return false;
				}
				return $redirect_url;
			}
		);
		add_action(
			'template_redirect',
			function () {
				if ( is_author() ) {
					global $wp_query;
					$wp_query->set_404();
					status_header( 404 );
					nocache_headers();
				}
			},
			0
		);
	}

	if ( wpyeg_defaults_enabled( 'redirect_attachment_pages' ) ) {
		add_action(
			'template_redirect',
			function () {
				if ( is_attachment() ) {
					$attachment_id = get_queried_object_id();
					$parent        = wp_get_post_parent_id( $attachment_id );
					$target        = $parent ? get_permalink( $parent ) : wp_get_attachment_url( $attachment_id );
					$target        = $target ? $target : home_url( '/' );
					wp_safe_redirect( $target, 301 );
					exit;
				}
			},
			1
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
				add_filter(
					'tiny_mce_plugins',
					function ( $plugins ) {
						return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : array();
					}
				);
			}
		);
	}

	/* ----- Admin & front-end UX ----- */

	if ( wpyeg_defaults_enabled( 'title_only_admin_search' ) ) {
		add_filter(
			'post_search_columns',
			function ( $search_columns, $search, $query ) {
				if ( ! is_admin() || ! $query->is_main_query() || ! $query->is_search() ) {
					return $search_columns;
				}
				return array( 'post_title' );
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

	/* ----- Login & sessions ----- */

	if ( wpyeg_defaults_enabled( 'disable_remember_me' ) ) {
		add_action(
			'login_init',
			function () {
				unset( $_POST['rememberme'], $_REQUEST['rememberme'] );
			}
		);
		add_action(
			'login_head',
			function () {
				echo '<style>.login .forgetmenot { display: none; }</style>';
			}
		);
	}

	// One filter handles both the remember and regular session lengths.
	add_filter(
		'auth_cookie_expiration',
		function ( $expiration, $user_id, $remember ) {
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
		},
		10,
		3
	);

	/* ----- Branding ----- */

	if ( 'remove_logo' === wpyeg_defaults_get( 'login_logo_behavior' ) ) {
		add_action(
			'login_head',
			function () {
				echo '<style>#login h1 a, .login h1 a { display:none; }</style>';
			}
		);
	}

	if ( wpyeg_defaults_enabled( 'login_logo_link_home' ) ) {
		add_filter( 'login_headerurl', 'home_url' );
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
	}
}

/**
 * Validate a password against the workshop policy.
 *
 * @param string                $password Proposed password.
 * @param WP_User|stdClass|null $user     User context, when available.
 * @return true|WP_Error
 */
function wpyeg_defaults_validate_password( $password, $user = null ) {
	$minimum_length = (int) apply_filters( 'wpyeg_minimum_password_length', 15 );
	$length         = function_exists( 'mb_strlen' ) ? mb_strlen( $password ) : strlen( $password );

	if ( $length < $minimum_length ) {
		return new WP_Error(
			'wpyeg_password_too_short',
			sprintf(
				/* translators: %d: minimum password length. */
				__( 'Password must be at least %d characters.', 'better-by-default' ),
				$minimum_length
			)
		);
	}

	$blocklist           = (array) apply_filters(
		'wpyeg_password_blocklist',
		array( 'password', 'password123', '123456789012345', 'qwertyuiopasdfg', 'letmeinletmeinletmein', 'wordpresswordpress' )
	);
	$normalize           = static function ( $value ) {
		$value = (string) $value;
		return function_exists( 'mb_strtolower' ) ? mb_strtolower( $value ) : strtolower( $value );
	};
	$normalized_password = $normalize( $password );
	$normalized_list     = array_map( $normalize, $blocklist );

	if ( in_array( $normalized_password, $normalized_list, true ) ) {
		return new WP_Error( 'wpyeg_password_common', __( 'Choose a password that is not commonly used.', 'better-by-default' ) );
	}

	if ( $user ) {
		$context_values = array_filter(
			array(
				isset( $user->user_login ) ? $user->user_login : '',
				isset( $user->user_nicename ) ? $user->user_nicename : '',
				isset( $user->user_email ) ? strtok( $user->user_email, '@' ) : '',
			)
		);
		foreach ( $context_values as $context_value ) {
			$context_value = $normalize( $context_value );
			if ( strlen( $context_value ) >= 4 && false !== strpos( $normalized_password, $context_value ) ) {
				return new WP_Error( 'wpyeg_password_personal', __( 'Password must not contain your username or email name.', 'better-by-default' ) );
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
	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Passwords must not be sanitized.
	$password = isset( $_POST['pass1'] ) ? (string) wp_unslash( $_POST['pass1'] ) : '';
	if ( '' !== $password ) {
		$result = wpyeg_defaults_validate_password( $password, $user );
		if ( is_wp_error( $result ) ) {
			$errors->add( $result->get_error_code(), $result->get_error_message() );
		}
	}
}

/**
 * Validate a password submitted from the password-reset screen.
 *
 * @param WP_Error $errors Validation errors.
 * @param WP_User  $user   User context.
 */
function wpyeg_defaults_validate_reset_password( $errors, $user ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Passwords must not be sanitized.
	$password = isset( $_POST['pass1'] ) ? (string) wp_unslash( $_POST['pass1'] ) : '';
	if ( '' !== $password ) {
		$result = wpyeg_defaults_validate_password( $password, $user );
		if ( is_wp_error( $result ) ) {
			$errors->add( $result->get_error_code(), $result->get_error_message() );
		}
	}
}

/**
 * Validate a password submitted through the core REST users controller.
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


/*
 * ======================================================================
 * SETTINGS SCREEN — Settings → Better by Default
 * ======================================================================
 */

add_action(
	'admin_menu',
	function () {
		add_options_page(
			__( 'Better by Default', 'better-by-default' ),
			__( 'Better by Default', 'better-by-default' ),
			'manage_options',
			'better-by-default',
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
 * @param mixed $input Submitted settings.
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
				$choices       = isset( $field['choices'] ) ? array_keys( $field['choices'] ) : array();
				$value         = isset( $input[ $key ] ) ? (string) $input[ $key ] : $field['default'];
				$clean[ $key ] = in_array( $value, $choices, true ) ? $value : $field['default'];
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
						<?php
						if ( $field['group'] !== $group_key ) {
							continue; }
						?>
						<?php $name = WPYEG_DEFAULTS_OPTION . '[' . $key . ']'; ?>
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
		<p><em><?php esc_html_e( 'Deployment-level defaults are usually clearest in wp-config.php. Review these examples for your workflow:', 'better-by-default' ); ?></em></p>
		<pre style="background:#f6f7f7;padding:12px;border:1px solid #dcdcde;max-width:640px;">define( 'DISALLOW_FILE_EDIT', true );
define( 'DISALLOW_FILE_MODS', true ); // Managed deployments only.
define( 'AUTOSAVE_INTERVAL', 120 );
define( 'WP_POST_REVISIONS', 10 );</pre>
	</div>
	<?php
}

/**
 * On activation, seed the option with schema defaults so a fresh install
 * behaves as documented out of the box.
 */
register_activation_hook(
	__FILE__,
	function () {
		if ( false === get_option( WPYEG_DEFAULTS_OPTION, false ) ) {
			$schema   = wpyeg_defaults_schema();
			$defaults = array();
			foreach ( $schema as $key => $field ) {
				$defaults[ $key ] = $field['default'];
			}
			add_option( WPYEG_DEFAULTS_OPTION, $defaults );
		}
	}
);
