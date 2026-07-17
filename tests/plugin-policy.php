<?php
/**
 * Lightweight regression tests for Better by Default policies.
 *
 * @package BetterByDefault
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'HOUR_IN_SECONDS', 3600 );

$GLOBALS['wpyeg_test_hooks']         = array();
$GLOBALS['wpyeg_test_filter_values'] = array();
$GLOBALS['wpyeg_test_users']         = array();
$GLOBALS['wpyeg_test_current_user']  = 0;

/**
 * Translation test double.
 *
 * @param string $text Source text.
 * @return string
 */
function __( $text ) { // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
	return $text;
}

/**
 * Option test double.
 *
 * Returns the schema defaults unless a test has staged a stored option, which
 * is how the policies that ship disabled get exercised.
 *
 * @param string $name          Option name.
 * @param mixed  $default_value Default value.
 * @return mixed
 */
function get_option( $name, $default_value = false ) {
	unset( $name );
	return isset( $GLOBALS['wpyeg_test_option'] ) ? $GLOBALS['wpyeg_test_option'] : $default_value;
}

/**
 * Current-user test double.
 *
 * @return bool
 */
function is_user_logged_in() {
	return (bool) $GLOBALS['wpyeg_test_current_user'];
}

/**
 * Current-user-id test double.
 *
 * @return int
 */
function get_current_user_id() {
	return (int) $GLOBALS['wpyeg_test_current_user'];
}

/**
 * User-lookup test double.
 *
 * @param int $user_id User ID.
 * @return object|false
 */
function get_userdata( $user_id ) {
	return isset( $GLOBALS['wpyeg_test_users'][ $user_id ] ) ? $GLOBALS['wpyeg_test_users'][ $user_id ] : false;
}

/**
 * Action-registration test double.
 *
 * @param string   $hook          Hook name.
 * @param callable $callback      Callback.
 * @param int      $priority      Hook priority.
 * @param int      $accepted_args Accepted argument count.
 */
function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['wpyeg_test_hooks'][] = array(
		'type'          => 'action',
		'hook'          => $hook,
		'callback'      => $callback,
		'priority'      => $priority,
		'accepted_args' => $accepted_args,
	);
}

/**
 * Filter-registration test double.
 *
 * @param string   $hook          Hook name.
 * @param callable $callback      Callback.
 * @param int      $priority      Hook priority.
 * @param int      $accepted_args Accepted argument count.
 */
function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
	$GLOBALS['wpyeg_test_hooks'][] = array(
		'type'          => 'filter',
		'hook'          => $hook,
		'callback'      => $callback,
		'priority'      => $priority,
		'accepted_args' => $accepted_args,
	);
}

/**
 * Hook-removal test double.
 *
 * @param string   $hook     Hook name.
 * @param callable $callback Callback.
 * @param int      $priority Hook priority.
 */
function remove_action( $hook, $callback, $priority = 10 ) {
	unset( $hook, $callback, $priority );
}

/**
 * Activation-hook test double.
 *
 * @param string   $file     Plugin file.
 * @param callable $callback Activation callback.
 */
function register_activation_hook( $file, $callback ) {
	unset( $file, $callback );
}

/**
 * Filter test double.
 *
 * Returns the unfiltered value unless a test has staged an override in
 * $GLOBALS['wpyeg_test_filter_values'], which stands in for a site adding its
 * own filter callback.
 *
 * @param string $hook  Hook name.
 * @param mixed  $value Filtered value.
 * @return mixed
 */
function apply_filters( $hook, $value ) {
	if ( isset( $GLOBALS['wpyeg_test_filter_values'][ $hook ] ) ) {
		return $GLOBALS['wpyeg_test_filter_values'][ $hook ];
	}
	return $value;
}

/**
 * Slash-stripping test double.
 *
 * @param mixed $value Input value.
 * @return mixed
 */
function wp_unslash( $value ) {
	return is_string( $value ) ? stripslashes( $value ) : $value;
}

/**
 * Integer sanitizer test double.
 *
 * @param mixed $value Input value.
 * @return int
 */
function absint( $value ) {
	return abs( (int) $value );
}

/**
 * Test whether a value is an error.
 *
 * @param mixed $value Input value.
 * @return bool
 */
function is_wp_error( $value ) {
	return $value instanceof WP_Error;
}

require_once __DIR__ . '/class-wp-error.php';
require_once __DIR__ . '/class-wp-rest-request.php';
require_once dirname( __DIR__ ) . '/plugin/better-by-default/better-by-default.php';

/**
 * Assert a condition or stop.
 *
 * @param bool   $condition Assertion condition.
 * @param string $message   Failure message.
 */
function wpyeg_test_assert( $condition, $message ) {
	if ( ! $condition ) {
		fwrite( STDERR, "Assertion failed: {$message}\n" );
		exit( 1 );
	}
}

$schema = wpyeg_defaults_schema();
wpyeg_test_assert( 'no' === $schema['disable_application_passwords']['default'], 'Application Passwords remain available by default.' );
wpyeg_test_assert( 'no' === $schema['redirect_attachment_pages']['default'], 'Legacy attachment redirects are opt-in.' );
wpyeg_test_assert( 'no' === $schema['remove_version']['default'], 'Generator-tag removal is not presented as hardening.' );
wpyeg_test_assert( ! isset( $schema['security_headers'], $schema['defer_scripts'], $schema['disable_ai_connectors'] ), 'Unsafe or no-op generic policies are absent.' );

$sanitized = wpyeg_defaults_sanitize(
	array(
		'disable_xmlrpc'      => 'yes',
		'remember_me_days'    => -30,
		'login_logo_behavior' => 'invalid',
	)
);
wpyeg_test_assert( 'yes' === $sanitized['disable_xmlrpc'], 'Enabled toggles sanitize to yes.' );
wpyeg_test_assert( 30 === $sanitized['remember_me_days'], 'Numeric settings sanitize to non-negative integers.' );
wpyeg_test_assert( 'default' === $sanitized['login_logo_behavior'], 'Invalid select values fall back to schema defaults.' );

$short = wpyeg_defaults_validate_password( 'too-short' );
wpyeg_test_assert( is_wp_error( $short ) && 'wpyeg_password_too_short' === $short->get_error_code(), 'Short passwords are rejected.' );
wpyeg_test_assert( true === wpyeg_defaults_validate_password( 'a long passphrase with spaces' ), 'Long passphrases are accepted without composition rules.' );

/**
 * Reduce a validator result to a comparable error code.
 *
 * @param true|WP_Error $result Validator result.
 * @return string Error code, or an empty string when the password was accepted.
 */
function wpyeg_test_password_error( $result ) {
	return is_wp_error( $result ) ? $result->get_error_code() : '';
}

/**
 * Run an admin-side validator against a raw, still-slashed $_POST['pass1'].
 *
 * @param string      $validator Validator function name.
 * @param string      $raw_post  Raw $_POST['pass1'] value, as core receives it.
 * @param object|null $user      User context.
 * @return string First error code, or an empty string when accepted.
 */
function wpyeg_test_posted_password_error( $validator, $raw_post, $user = null ) {
	$_POST['pass1'] = $raw_post;
	$errors         = new WP_Error();

	if ( 'reset' === $validator ) {
		wpyeg_defaults_validate_reset_password( $errors, $user );
	} else {
		wpyeg_defaults_validate_profile_password( $errors, true, $user );
	}

	unset( $_POST['pass1'] );
	return $errors->get_error_code();
}

// Blocklist. Entries are compared case-insensitively, after normalization.
wpyeg_test_assert( 'wpyeg_password_common' === wpyeg_test_password_error( wpyeg_defaults_validate_password( 'wordpresswordpress' ) ), 'Blocklisted passwords at or above the minimum length are rejected.' );
wpyeg_test_assert( 'wpyeg_password_common' === wpyeg_test_password_error( wpyeg_defaults_validate_password( 'WordPressWordPress' ) ), 'The blocklist comparison is case-insensitive.' );

/*
 * Entries shorter than the default 15-character minimum are unreachable at the
 * default settings, but become load-bearing as soon as a site filters the
 * minimum down. They are deliberately kept in the list for this case.
 */
$GLOBALS['wpyeg_test_filter_values']['wpyeg_minimum_password_length'] = 8;
wpyeg_test_assert( 'wpyeg_password_common' === wpyeg_test_password_error( wpyeg_defaults_validate_password( 'password' ) ), 'Short blocklist entries apply when a site lowers wpyeg_minimum_password_length.' );
$GLOBALS['wpyeg_test_filter_values'] = array();

// Personal-context rejections.
$user_context                = new stdClass();
$user_context->user_login    = 'administrator';
$user_context->user_nicename = 'administrator';
$user_context->user_email    = 'admin@example.com';
wpyeg_test_assert( 'wpyeg_password_personal' === wpyeg_test_password_error( wpyeg_defaults_validate_password( 'administratorlonghorse', $user_context ) ), 'Passwords containing the username are rejected.' );
wpyeg_test_assert( 'wpyeg_password_personal' === wpyeg_test_password_error( wpyeg_defaults_validate_password( 'adminXXXXXXXXXXXXXXXX', $user_context ) ), 'Passwords containing the email name are rejected.' );
wpyeg_test_assert( '' === wpyeg_test_password_error( wpyeg_defaults_validate_password( 'correct horse battery staple', $user_context ) ), 'Unrelated passphrases pass the personal-context check.' );

$short_context             = new stdClass();
$short_context->user_login = 'bob';
$short_context->user_email = 'bob@example.com';
wpyeg_test_assert( '' === wpyeg_test_password_error( wpyeg_defaults_validate_password( 'bobbobbobbobbob', $short_context ) ), 'Context values under four characters are not substring-matched.' );

/*
 * Core's edit_user() trims $_POST['pass1'] and stores the trimmed value
 * (wp-admin/includes/user.php), while firing user_profile_update_errors with
 * $_POST untouched. A validator reading $_POST raw therefore measures a
 * different string than core saves, and whitespace padding slips past it.
 */
wpyeg_test_assert( 'wpyeg_password_too_short' === wpyeg_test_posted_password_error( 'profile', str_repeat( ' ', 15 ) . 'a' ), 'Whitespace padding cannot inflate a short password to the minimum length.' );
wpyeg_test_assert( 'wpyeg_password_common' === wpyeg_test_posted_password_error( 'profile', 'wordpresswordpress ' ), 'Whitespace padding cannot smuggle a blocklisted password past the blocklist.' );
wpyeg_test_assert( 'wpyeg_password_too_short' === wpyeg_test_posted_password_error( 'reset', str_repeat( ' ', 15 ) . 'a' ), 'The reset screen rejects whitespace-padded short passwords too.' );

// Internal whitespace is part of the password and must survive trimming.
wpyeg_test_assert( '' === wpyeg_test_posted_password_error( 'profile', 'a long passphrase with spaces' ), 'Passphrases with internal spaces remain acceptable.' );

// An omitted or whitespace-only field means "no password change"; core owns that case.
wpyeg_test_assert( '' === wpyeg_test_posted_password_error( 'profile', '' ), 'An empty password field is left alone.' );
wpyeg_test_assert( '' === wpyeg_test_posted_password_error( 'profile', '   ' ), 'A whitespace-only field is treated as empty, as core does.' );

/*
 * REST authentication gate.
 *
 * The value core hands this filter is not a plain boolean: rest_cookie_check_errors()
 * returns true after calling wp_set_current_user( 0 ) when a cookie arrives without an
 * X-WP-Nonce. Treating any truthy result as "authenticated" would let that request
 * through to dispatch as user 0, so only a WP_Error may short-circuit.
 */
$GLOBALS['wpyeg_test_current_user'] = 0;
$anonymous                          = wpyeg_defaults_require_rest_auth( null );
wpyeg_test_assert( is_wp_error( $anonymous ) && 'rest_not_logged_in' === $anonymous->get_error_code(), 'Anonymous REST requests are rejected.' );
wpyeg_test_assert( array( 'status' => 401 ) === $anonymous->get_error_data(), 'The REST rejection carries a 401 status.' );

$nonceless = wpyeg_defaults_require_rest_auth( true );
wpyeg_test_assert( is_wp_error( $nonceless ), 'A cookie without a nonce resolves to user 0 and is still rejected.' );

$GLOBALS['wpyeg_test_current_user'] = 7;
wpyeg_test_assert( null === wpyeg_defaults_require_rest_auth( null ), 'Authenticated requests pass through untouched.' );
wpyeg_test_assert( true === wpyeg_defaults_require_rest_auth( true ), 'A successful auth result is preserved.' );

$prior = new WP_Error( 'rest_cookie_invalid_nonce', 'Cookie check failed' );
wpyeg_test_assert( wpyeg_defaults_require_rest_auth( $prior ) === $prior, 'An existing authentication error is returned unchanged.' );

/**
 * Stand-in for the core users controller's check_user_password() sanitize callback.
 *
 * @param mixed  $value   Submitted password.
 * @param object $request REST request.
 * @param string $param   Parameter name.
 * @return string|WP_Error
 */
function wpyeg_test_core_check_user_password( $value, $request, $param ) {
	unset( $request, $param );
	$password = (string) $value;
	if ( false !== strpos( $password, '\\' ) ) {
		return new WP_Error( 'rest_user_invalid_password', 'Passwords cannot contain the "\\" character.', array( 'status' => 400 ) );
	}
	return $password;
}

/**
 * Build an endpoint map shaped like the routes core registers.
 *
 * @return array
 */
function wpyeg_test_rest_endpoints() {
	$password_arg = array(
		'password' => array(
			'type'              => 'string',
			'sanitize_callback' => 'wpyeg_test_core_check_user_password',
		),
	);

	return array(
		'/wp/v2/users'               => array(
			array(
				'methods' => 'POST',
				'args'    => $password_arg,
			),
		),
		'/wp/v2/users/(?P<id>[\d]+)' => array(
			array(
				'methods' => 'POST',
				'args'    => $password_arg,
			),
		),
		'/wp/v2/users/me'            => array(
			array(
				'methods' => 'POST',
				'args'    => $password_arg,
			),
		),
		'/wp/v2/users/(?P<user_id>[\d]+)/application-passwords' => array(
			array(
				'methods' => 'POST',
				'args'    => $password_arg,
			),
		),
		// Posts carry an unrelated `password` argument that must not be policed.
		'/wp/v2/posts'               => array(
			array(
				'methods' => 'POST',
				'args'    => $password_arg,
			),
		),
	);
}

/**
 * Run a route's (possibly wrapped) password sanitize callback.
 *
 * @param array           $endpoints Endpoint map.
 * @param string          $route     Route key.
 * @param string          $value     Submitted password.
 * @param WP_REST_Request $request   REST request.
 * @return mixed
 */
function wpyeg_test_sanitize_rest_password( $endpoints, $route, $value, $request ) {
	return call_user_func( $endpoints[ $route ][0]['args']['password']['sanitize_callback'], $value, $request, 'password' );
}

/*
 * REST password argument guard.
 *
 * rest_pre_insert_user cannot report a policy failure — the controller never checks its
 * return for an error — so the policy runs as an argument sanitize callback, where
 * sanitize_params() converts a WP_Error into a 400 before the callback is reached.
 */
$guarded = wpyeg_defaults_guard_rest_password_arg( wpyeg_test_rest_endpoints() );

$create_request = new WP_REST_Request( array( 'username' => 'newuser' ), '/wp/v2/users' );

$weak = wpyeg_test_sanitize_rest_password( $guarded, '/wp/v2/users', 'short', $create_request );
wpyeg_test_assert( is_wp_error( $weak ) && 'wpyeg_password_too_short' === $weak->get_error_code(), 'A weak REST password is rejected at the argument level.' );
wpyeg_test_assert( array( 'status' => 400 ) === $weak->get_error_data(), 'The REST password rejection carries a 400 status.' );

$strong = wpyeg_test_sanitize_rest_password( $guarded, '/wp/v2/users', 'correct horse battery staple', $create_request );
wpyeg_test_assert( 'correct horse battery staple' === $strong, 'A compliant REST password sanitizes through unchanged.' );

// Core sanitizes first, and its rejection must survive rather than be replaced.
$backslashed = wpyeg_test_sanitize_rest_password( $guarded, '/wp/v2/users', 'a long password with \\ in it', $create_request );
wpyeg_test_assert( is_wp_error( $backslashed ) && 'rest_user_invalid_password' === $backslashed->get_error_code(), "Core's own password rejection is preserved." );

// The policy applies to a create using only what the request carries.
$named = wpyeg_test_sanitize_rest_password( $guarded, '/wp/v2/users', 'newuserlongpassword', $create_request );
wpyeg_test_assert( is_wp_error( $named ) && 'wpyeg_password_personal' === $named->get_error_code(), 'A new user cannot embed their submitted username in the password.' );

// On an update the context is the stored user the route id points at.
$stored                          = new stdClass();
$stored->user_login              = 'administrator';
$stored->user_email              = 'admin@example.com';
$stored->user_nicename           = 'administrator';
$GLOBALS['wpyeg_test_users'][12] = $stored;

$update_request = new WP_REST_Request( array( 'id' => 12 ), '/wp/v2/users/12' );
$update         = wpyeg_test_sanitize_rest_password( $guarded, '/wp/v2/users/(?P<id>[\d]+)', 'administratorlonghorse', $update_request );
wpyeg_test_assert( is_wp_error( $update ) && 'wpyeg_password_personal' === $update->get_error_code(), 'An update is checked against the stored user the route points at.' );

// /wp/v2/users/me carries no id; the context is the authenticated user.
$GLOBALS['wpyeg_test_current_user'] = 12;
$me_request                         = new WP_REST_Request( array(), '/wp/v2/users/me' );
$me                                 = wpyeg_test_sanitize_rest_password( $guarded, '/wp/v2/users/me', 'administratorlonghorse', $me_request );
wpyeg_test_assert( is_wp_error( $me ) && 'wpyeg_password_personal' === $me->get_error_code(), 'The /users/me route resolves context from the current user.' );
$GLOBALS['wpyeg_test_current_user'] = 0;

// Application Passwords are core-generated; a human policy must not touch them.
$app_password = wpyeg_test_sanitize_rest_password( $guarded, '/wp/v2/users/(?P<user_id>[\d]+)/application-passwords', 'short', $create_request );
wpyeg_test_assert( 'short' === $app_password, 'Application Password routes are left alone.' );

// A post password is a different concept that happens to share the argument name.
$post_password = wpyeg_test_sanitize_rest_password( $guarded, '/wp/v2/posts', 'short', $create_request );
wpyeg_test_assert( 'short' === $post_password, 'Non-user routes with a password argument are left alone.' );

wpyeg_defaults_bootstrap();
$registered_hooks = array_column( $GLOBALS['wpyeg_test_hooks'], 'hook' );
wpyeg_test_assert( in_array( 'xmlrpc_methods', $registered_hooks, true ), 'XML-RPC method removal is registered.' );
wpyeg_test_assert( in_array( 'rest_pre_insert_user', $registered_hooks, true ), 'REST password validation is registered.' );
wpyeg_test_assert( ! in_array( 'script_loader_tag', $registered_hooks, true ), 'Blanket script-tag mutation is not registered.' );

// Assert the callback, not the hook name: restrict_rest_user_discovery also
// filters rest_endpoints, so a name-only check passes with the guard removed.
$registered_callbacks = array_column( $GLOBALS['wpyeg_test_hooks'], 'callback' );
wpyeg_test_assert( in_array( 'wpyeg_defaults_guard_rest_password_arg', $registered_callbacks, true ), 'The REST password argument guard is registered.' );

/**
 * Find the first registration for a hook.
 *
 * @param string $hook Hook name.
 * @return array|null
 */
function wpyeg_test_find_hook( $hook ) {
	foreach ( $GLOBALS['wpyeg_test_hooks'] as $entry ) {
		if ( $entry['hook'] === $hook ) {
			return $entry;
		}
	}
	return null;
}

// disable_rest ships off, so stage it on to check how it registers.
$GLOBALS['wpyeg_test_option'] = array( 'disable_rest' => 'yes' );
$GLOBALS['wpyeg_test_hooks']  = array();
wpyeg_defaults_bootstrap();
$auth_gate = wpyeg_test_find_hook( 'rest_authentication_errors' );
unset( $GLOBALS['wpyeg_test_option'] );

wpyeg_test_assert( null !== $auth_gate, 'Enabling disable_rest registers the authentication gate.' );
wpyeg_test_assert( PHP_INT_MAX === $auth_gate['priority'], 'The gate runs after core resolves cookie and Application Password auth.' );

fwrite( STDOUT, "Better by Default policy tests passed.\n" );
