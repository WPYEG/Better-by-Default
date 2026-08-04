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
$GLOBALS['wpyeg_test_transients']    = array();
$GLOBALS['wpyeg_test_did_actions']   = array();

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
 * Action-dispatch test double.
 *
 * @param string $hook Hook name.
 */
function do_action( $hook ) {
	$GLOBALS['wpyeg_test_did_actions'][] = $hook;
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

/**
 * Transient test double. The breach check caches each HIBP range response.
 *
 * @param string $key Transient key.
 * @return mixed
 */
function get_transient( $key ) {
	return isset( $GLOBALS['wpyeg_test_transients'][ $key ] ) ? $GLOBALS['wpyeg_test_transients'][ $key ] : false;
}

/**
 * Transient setter test double.
 *
 * @param string $key   Transient key.
 * @param mixed  $value Value to store.
 * @param int    $ttl   Ignored.
 * @return bool
 */
function set_transient( $key, $value, $ttl = 0 ) {
	unset( $ttl );
	$GLOBALS['wpyeg_test_transients'][ $key ] = $value;
	return true;
}

/**
 * Transient deleter test double.
 *
 * The breach check clears a cached range response that no longer validates, so
 * the next call refetches instead of reusing bad data for the rest of its TTL.
 *
 * @param string $key Transient key.
 * @return bool
 */
function delete_transient( $key ) {
	unset( $GLOBALS['wpyeg_test_transients'][ $key ] );
	return true;
}

/**
 * HTTP test double.
 *
 * Tests stage $GLOBALS['wpyeg_test_http'] to control what the Have I Been Pwned
 * range endpoint returns. The suite never touches the network: unstaged calls
 * answer with an empty 200, i.e. "this prefix matched nothing".
 *
 * @param string $url  Requested URL.
 * @param array  $args Request arguments.
 * @return array|WP_Error
 */
function wp_remote_get( $url, $args = array() ) {
	$GLOBALS['wpyeg_test_last_http_url']  = $url;
	$GLOBALS['wpyeg_test_last_http_args'] = $args;

	return isset( $GLOBALS['wpyeg_test_http'] )
		? $GLOBALS['wpyeg_test_http']
		: array(
			'response' => array( 'code' => 200 ),
			'body'     => '',
		);
}

/**
 * Response-code test double.
 *
 * @param array|WP_Error $response HTTP response.
 * @return int
 */
function wp_remote_retrieve_response_code( $response ) {
	return isset( $response['response']['code'] ) ? $response['response']['code'] : 0;
}

/**
 * Response-body test double.
 *
 * @param array|WP_Error $response HTTP response.
 * @return string
 */
function wp_remote_retrieve_body( $response ) {
	return isset( $response['body'] ) ? $response['body'] : '';
}

/**
 * Home-URL test double.
 *
 * Present so a regression that sends attachment pages back to the homepage
 * fails as an assertion rather than an undefined-function fatal.
 *
 * @param string $path Optional path.
 * @return string
 */
function home_url( $path = '/' ) {
	return 'https://example.test' . $path;
}

/**
 * Template-lookup test double.
 *
 * @param array $templates Template names.
 * @return string Path when the theme provides one, '' otherwise.
 */
function locate_template( $templates ) {
	unset( $templates );
	return isset( $GLOBALS['wpyeg_test_template'] ) ? $GLOBALS['wpyeg_test_template'] : '';
}

/**
 * Post-parent test double.
 *
 * @param int $post_id Post ID.
 * @return int
 */
function wp_get_post_parent_id( $post_id ) {
	unset( $post_id );
	return isset( $GLOBALS['wpyeg_test_parent'] ) ? (int) $GLOBALS['wpyeg_test_parent'] : 0;
}

/**
 * Permalink test double.
 *
 * @param int $post_id Post ID.
 * @return string
 */
function get_permalink( $post_id ) {
	return 'https://example.test/parent-post-' . (int) $post_id . '/';
}

/**
 * Attachment-URL test double.
 *
 * @param int $attachment_id Attachment ID.
 * @return string
 */
function wp_get_attachment_url( $attachment_id ) {
	unset( $attachment_id );
	return isset( $GLOBALS['wpyeg_test_file_url'] ) ? $GLOBALS['wpyeg_test_file_url'] : 'https://example.test/wp-content/uploads/photo.jpg';
}

require_once __DIR__ . '/class-wp-error.php';
require_once __DIR__ . '/class-wp-rest-request.php';
require_once dirname( __DIR__ ) . '/plugin/sane-defaults/sane-defaults.php';

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
wpyeg_test_assert( 'minor' === $schema['core_update_policy']['default'], 'Core maintenance and security releases update automatically by default.' );
wpyeg_test_assert( ! isset( $schema['auto_update_translations'] ), 'WordPress retains ownership of translation-file updates.' );
wpyeg_test_assert(
	array(
		'code' => array(),
		'a'    => array( 'href' => true ),
	) === wpyeg_defaults_help_allowed_html(),
	'Help text permits only attribute-free code markup and href-only reference links.'
);
wpyeg_test_assert( false !== strpos( $schema['xmlrpc_allow_pingbacks']['help'], '<code>pingback.ping</code>' ), 'Machine-facing XML-RPC identifiers use code markup.' );
wpyeg_test_assert( false !== strpos( $schema['require_strong_passwords']['help'], 'NIST SP 800-63B-4 § 3.1.1.2' ), 'External guidance names the specific publication and section.' );
wpyeg_test_assert( false !== strpos( $schema['require_strong_passwords']['help'], 'https://pages.nist.gov/800-63-4/sp800-63b/authenticators/#passwordver' ), 'External guidance links to its authoritative source section.' );
wpyeg_test_assert( false !== strpos( $schema['require_strong_passwords']['help'], 'https://haveibeenpwned.com/API/v3#SearchingPwnedPasswordsByRange' ), 'The breach-screening explanation links to the authoritative HIBP range API documentation.' );
wpyeg_test_assert( false !== strpos( $schema['require_strong_passwords']['help'], 'first five characters' ), 'The breach-screening explanation states exactly what leaves the site.' );
wpyeg_test_assert( false !== strpos( $schema['require_strong_passwords']['help'], 'password and full hash never leave the site' ), 'The breach-screening explanation states the privacy boundary.' );
wpyeg_test_assert( false === strpos( $schema['login_logo_behavior']['help'], 'trust leak' ), 'Login-logo guidance does not overstate an external destination as a trust leak.' );
wpyeg_test_assert( false !== strpos( $schema['login_logo_behavior']['help'], 'organizationally consistent' ), 'Login-logo guidance describes the actual branding and destination benefit.' );

foreach ( $schema as $field ) {
	$help_without_safe_markup = preg_replace(
		'#</?code>|<a href="https://[A-Za-z0-9./_\#?&=%:+~-]+">|</a>#',
		'',
		isset( $field['help'] ) ? $field['help'] : ''
	);
	wpyeg_test_assert( false === strpos( $help_without_safe_markup, '<' ) && false === strpos( $help_without_safe_markup, '>' ), 'Schema help contains no markup outside the narrow allowlist.' );
}

// Settings controls use their descriptive schema labels, never a generic
// checkbox label. Keep the source-level convention covered without needing a
// WordPress admin renderer in this lightweight policy test.
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local test fixture.
$plugin_source = file_get_contents( dirname( __DIR__ ) . '/plugin/sane-defaults/sane-defaults.php' );
wpyeg_test_assert( false === strpos( $plugin_source, "esc_html_e( 'Enabled'" ), 'Checkboxes do not use a generic Enabled label.' );
wpyeg_test_assert( false !== strpos( $plugin_source, '<table class="form-table" role="presentation">' ), 'The settings screen uses WordPress classic form-table styling.' );
wpyeg_test_assert( false !== strpos( $plugin_source, 'class="description"' ), 'Settings help uses WordPress classic description styling.' );
wpyeg_test_assert( false !== strpos( $plugin_source, 'label for="<?php echo esc_attr( $field_id ); ?>"' ), 'Schema labels are explicitly connected to settings controls.' );
wpyeg_test_assert( false !== strpos( $plugin_source, '<td colspan="2">' ), 'Toggle controls and their descriptive labels span the settings row.' );
wpyeg_test_assert( false !== strpos( $plugin_source, 'aria-describedby="<?php echo esc_attr( $help_id ); ?>"' ), 'Settings controls reference their help text.' );
wpyeg_test_assert( false !== strpos( $plugin_source, "wp_kses( \$field['help'], wpyeg_defaults_help_allowed_html() )" ), 'Settings help is rendered through the narrow markup allowlist.' );

// The explicit update policy is stable across the installation-age defaults
// WordPress stores for major core updates.
wpyeg_test_assert( true === wpyeg_defaults_allow_minor_core_updates( false ), 'The default policy enables maintenance/security core releases.' );
wpyeg_test_assert( false === wpyeg_defaults_allow_major_core_updates( true ), 'The default policy blocks automatic major core releases.' );
wpyeg_test_assert( false === wpyeg_defaults_allow_dev_core_updates( true ), 'The default stable policy blocks development builds.' );

$GLOBALS['wpyeg_test_option'] = array( 'core_update_policy' => 'all' );
wpyeg_test_assert( true === wpyeg_defaults_allow_minor_core_updates( false ), 'The all-stable policy enables maintenance releases.' );
wpyeg_test_assert( true === wpyeg_defaults_allow_major_core_updates( false ), 'The all-stable policy enables major releases.' );
wpyeg_test_assert( false === wpyeg_defaults_allow_dev_core_updates( true ), 'All stable releases does not mean development builds.' );

$GLOBALS['wpyeg_test_option'] = array( 'core_update_policy' => 'manual' );
wpyeg_test_assert( false === wpyeg_defaults_allow_minor_core_updates( true ), 'The manual policy disables maintenance releases.' );
wpyeg_test_assert( false === wpyeg_defaults_allow_major_core_updates( true ), 'The manual policy disables major releases.' );

$GLOBALS['wpyeg_test_option'] = array( 'core_update_policy' => 'inherit' );
wpyeg_test_assert( true === wpyeg_defaults_allow_minor_core_updates( true ), 'The inherit policy preserves an enabled core decision.' );
wpyeg_test_assert( false === wpyeg_defaults_allow_major_core_updates( false ), 'The inherit policy preserves a disabled core decision.' );

unset( $GLOBALS['wpyeg_test_option'] );

/*
 * Policy snapshot, not endorsement.
 *
 * remove_version now ships OFF, per PR #1: stripping the generator tag is
 * obscurity, not hardening. It does not make an out-of-date site safer, and the
 * version still leaks from asset query strings and feeds — so it is offered as
 * noise reduction you opt into, not a security default.
 *
 * defer_scripts is gone, also per PR #1, but for a sharper reason than "generic":
 * WordPress 6.3 added a per-script loading strategy
 * ( wp_enqueue_script( ..., array( 'strategy' => 'defer' ) ) ), so blanket
 * script_loader_tag rewriting is teaching a superseded technique.
 *
 * security_headers was not dropped. It was split: nosniff and Referrer-Policy
 * are low-risk and stay on, while X-Frame-Options — the only one of the three
 * that can break a working site — became its own setting.
 *
 * Still open from PR #1: whether attachment redirects should also be opt-in.
 *
 * PR #1 also wanted disable_ai_connectors dropped as a no-op, which it was — it
 * only fired an action nobody listened to. It now does real work against core's
 * wp_supports_ai gate (WordPress 7.0), so it stays, and is covered below.
 */
wpyeg_test_assert( 'yes' === $schema['redirect_attachment_pages']['default'], 'Attachment redirects ship on (PR #1 proposed opt-in).' );
wpyeg_test_assert( 'no' === $schema['remove_version']['default'], 'Generator-tag removal is opt-in, not presented as hardening.' );
wpyeg_test_assert( ! isset( $schema['defer_scripts'] ), 'Blanket script deferral is gone; core has a per-script strategy since 6.3.' );
wpyeg_test_assert( isset( $schema['security_headers'], $schema['frame_options'] ), 'Security headers are split, so framing can be changed without giving up nosniff.' );
wpyeg_test_assert( 'SAMEORIGIN' === $schema['frame_options']['default'], 'Framing still defaults to SAMEORIGIN.' );
wpyeg_test_assert( array_key_exists( '', $schema['frame_options']['choices'] ), 'Framing can be left to the host or CDN.' );

$sanitized = wpyeg_defaults_sanitize(
	array(
		'xmlrpc_allow_pingbacks' => 'yes',
		'remember_me_days'       => -30,
		'login_logo_behavior'    => 'invalid',
	)
);
wpyeg_test_assert( 'yes' === $sanitized['xmlrpc_allow_pingbacks'], 'Enabled toggles sanitize to yes.' );
wpyeg_test_assert( 30 === $sanitized['remember_me_days'], 'Numeric settings sanitize to non-negative integers.' );
wpyeg_test_assert( 'keep_default' === $sanitized['login_logo_behavior'], 'Invalid select values fall back to schema defaults.' );

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
wpyeg_test_assert( in_array( 'allow_minor_auto_core_updates', $registered_hooks, true ), 'The maintenance/security core update policy is registered.' );
wpyeg_test_assert( in_array( 'allow_major_auto_core_updates', $registered_hooks, true ), 'The major core update policy is registered.' );
wpyeg_test_assert( in_array( 'allow_dev_auto_core_updates', $registered_hooks, true ), 'Development core builds are kept out of stable policies.' );
wpyeg_test_assert( ! in_array( 'auto_update_translation', $registered_hooks, true ), 'Translation updates remain on WordPress defaults.' );
wpyeg_test_assert( ! in_array( 'auto_update_plugin', $registered_hooks, true ), 'Plugin code updates remain on WordPress per-item settings.' );
wpyeg_test_assert( ! in_array( 'auto_update_theme', $registered_hooks, true ), 'Theme code updates remain on WordPress per-item settings.' );
wpyeg_test_assert( ! in_array( 'script_loader_tag', $registered_hooks, true ), 'Blanket script-tag mutation is not registered.' );

// XML-RPC stays granular: remove unused core families without erasing methods
// registered by integrations such as Jetpack.
$xmlrpc_hook    = wpyeg_test_find_hook( 'xmlrpc_methods' );
$xmlrpc_methods = call_user_func(
	$xmlrpc_hook['callback'],
	array(
		'pingback.ping'    => 'core-pingback',
		'wp.getUsersBlogs' => 'core-publishing',
		'demo.sayHello'    => 'core-demo',
		'jetpack.jsonAPI'  => 'third-party',
	)
);
wpyeg_test_assert( ! isset( $xmlrpc_methods['pingback.ping'] ), 'Default XML-RPC policy removes incoming pingbacks.' );
wpyeg_test_assert( ! isset( $xmlrpc_methods['wp.getUsersBlogs'] ), 'Default XML-RPC policy removes core remote publishing.' );
wpyeg_test_assert( ! isset( $xmlrpc_methods['demo.sayHello'] ), 'Default XML-RPC policy removes inert demo methods.' );
wpyeg_test_assert( isset( $xmlrpc_methods['jetpack.jsonAPI'] ), 'Default XML-RPC policy preserves third-party methods.' );

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

/*
 * Accuracy guard for the repository's teaching copy. WordPress 4.4 made the
 * old "thousands of guesses per multicall" description obsolete; keep the
 * correction synchronized across every workshop representation.
 */
$accuracy_files = array(
	dirname( __DIR__ ) . '/docs/wordpress-default-settings.md',
	dirname( __DIR__ ) . '/plugin/sane-defaults/README.md',
	dirname( __DIR__ ) . '/plugin/sane-defaults/readme.txt',
	dirname( __DIR__ ) . '/plugin/sane-defaults/sane-defaults.php',
	dirname( __DIR__ ) . '/workshop/Better-by-Default.ia.md',
	dirname( __DIR__ ) . '/workshop/better-by-default.iapresenter/text.md',
	dirname( __DIR__ ) . '/workshop/build_deck.js',
);

foreach ( $accuracy_files as $accuracy_file ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local test fixture.
	$accuracy_copy = file_get_contents( $accuracy_file );
	wpyeg_test_assert( false === strpos( $accuracy_copy, 'amplifier that batches thousands of login guesses' ), basename( $accuracy_file ) . ' does not repeat the obsolete multicall claim.' );
	wpyeg_test_assert( false === strpos( $accuracy_copy, 'Gate the whole endpoint off' ), basename( $accuracy_file ) . ' does not misdescribe xmlrpc_enabled.' );
}

// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local test fixture.
$xmlrpc_reference = file_get_contents( dirname( __DIR__ ) . '/docs/wordpress-default-settings.md' );
wpyeg_test_assert( false !== strpos( $xmlrpc_reference, 'core.trac.wordpress.org/ticket/34336' ), 'The XML-RPC reference cites the WordPress 4.4 authentication fix.' );

// disable_rest ships off, so stage it on to check how it registers.
$GLOBALS['wpyeg_test_option'] = array( 'disable_rest' => 'yes' );
$GLOBALS['wpyeg_test_hooks']  = array();
wpyeg_defaults_bootstrap();
$auth_gate = wpyeg_test_find_hook( 'rest_authentication_errors' );
unset( $GLOBALS['wpyeg_test_option'] );

wpyeg_test_assert( null !== $auth_gate, 'Enabling disable_rest registers the authentication gate.' );
wpyeg_test_assert( PHP_INT_MAX === $auth_gate['priority'], 'The gate runs after core resolves cookie and Application Password auth.' );

// disable_ai_connectors ships on. It used to only fire its own action, which is
// what made it a no-op; check it now reaches core's WP 7.0 AI gate.
$GLOBALS['wpyeg_test_hooks']       = array();
$GLOBALS['wpyeg_test_did_actions'] = array();
wpyeg_defaults_bootstrap();
$ai_gate = wpyeg_test_find_hook( 'wp_supports_ai' );

wpyeg_test_assert( null !== $ai_gate, 'Disabling AI connectors filters core wp_supports_ai.' );
wpyeg_test_assert( '__return_false' === $ai_gate['callback'], 'The AI gate returns false, so provider connectors never register.' );
wpyeg_test_assert( null !== wpyeg_test_find_hook( 'admin_menu' ), 'The core Connectors screen is removed from the menu.' );
wpyeg_test_assert( null !== wpyeg_test_find_hook( 'admin_init' ), 'The Connectors screen is closed, not merely unlinked.' );
wpyeg_test_assert( in_array( 'wpyeg_disable_ai_connectors', $GLOBALS['wpyeg_test_did_actions'], true ), 'The seam still fires for AI integrations core does not know about.' );

/**
 * Run every registered wp_headers filter over a starting set of headers.
 *
 * @param array $headers Starting headers.
 * @return array
 */
function wpyeg_test_send_headers( $headers ) {
	foreach ( $GLOBALS['wpyeg_test_hooks'] as $entry ) {
		if ( 'wp_headers' === $entry['hook'] ) {
			$headers = call_user_func( $entry['callback'], $headers );
		}
	}
	return $headers;
}

// Header defaults: the two low-risk ones ship on, framing is its own setting.
$GLOBALS['wpyeg_test_hooks'] = array();
wpyeg_defaults_bootstrap();
$sent = wpyeg_test_send_headers( array() );

wpyeg_test_assert( 'nosniff' === $sent['X-Content-Type-Options'], 'nosniff ships on.' );
wpyeg_test_assert( isset( $sent['Referrer-Policy'] ), 'Referrer-Policy ships on.' );
wpyeg_test_assert( 'SAMEORIGIN' === $sent['X-Frame-Options'], 'Framing ships as SAMEORIGIN.' );

// A host or CDN may already own these; do not fight that layer.
$preset = wpyeg_test_send_headers(
	array(
		'X-Frame-Options'        => 'DENY',
		'X-Content-Type-Options' => 'set-by-the-cdn',
	)
);
wpyeg_test_assert( 'DENY' === $preset['X-Frame-Options'], 'An X-Frame-Options set elsewhere is left alone.' );
wpyeg_test_assert( 'set-by-the-cdn' === $preset['X-Content-Type-Options'], 'A nosniff header set elsewhere is left alone.' );

// The point of splitting them: framing can be handed back to the host without
// also giving up nosniff, which the old single toggle made impossible.
$GLOBALS['wpyeg_test_option'] = array( 'frame_options' => '' );
$GLOBALS['wpyeg_test_hooks']  = array();
wpyeg_defaults_bootstrap();
$unframed = wpyeg_test_send_headers( array() );
unset( $GLOBALS['wpyeg_test_option'] );

wpyeg_test_assert( ! isset( $unframed['X-Frame-Options'] ), 'Choosing "leave unchanged" sends no X-Frame-Options.' );
wpyeg_test_assert( 'nosniff' === $unframed['X-Content-Type-Options'], 'Turning framing off does not also give up nosniff.' );

// Attachment pages: where the redirect actually points.
$GLOBALS['wpyeg_test_template'] = '';
$GLOBALS['wpyeg_test_parent']   = 42;
wpyeg_test_assert(
	'https://example.test/parent-post-42/' === wpyeg_defaults_attachment_redirect_target( 7 ),
	'An attached file redirects to its parent post.'
);

// The case the old code got wrong: unattached media has no parent, and sending
// every one of those to the homepage is a soft 404.
$GLOBALS['wpyeg_test_parent'] = 0;
$target                       = wpyeg_defaults_attachment_redirect_target( 7 );
wpyeg_test_assert( 'https://example.test/wp-content/uploads/photo.jpg' === $target, 'Unattached media falls back to the file, as core does.' );
wpyeg_test_assert( false === strpos( $target, 'example.test/?' ) && '/' !== substr( $target, -1 ), 'Unattached media is never sent to the homepage.' );

// A theme that ships an attachment template meant to render these pages.
$GLOBALS['wpyeg_test_template'] = '/themes/portfolio/attachment.php';
wpyeg_test_assert( '' === wpyeg_defaults_attachment_redirect_target( 7 ), 'A theme attachment template suppresses the redirect.' );

// ...and a site can overrule that either way.
$GLOBALS['wpyeg_test_filter_values']['wpyeg_keep_attachment_page'] = false;
wpyeg_test_assert( '' !== wpyeg_defaults_attachment_redirect_target( 7 ), 'wpyeg_keep_attachment_page can force the redirect anyway.' );
unset( $GLOBALS['wpyeg_test_filter_values']['wpyeg_keep_attachment_page'] );
unset( $GLOBALS['wpyeg_test_template'], $GLOBALS['wpyeg_test_parent'] );

/*
 * Breach screening (Have I Been Pwned, k-anonymity).
 *
 * The hash is derived here rather than hard-coded so the fixtures cannot drift
 * from what the helper actually sends. The staged responses stand in for the
 * range endpoint; the suite never touches the network.
 */
$hibp_password = 'correct horse battery staple';
$hibp_hash     = strtoupper( sha1( $hibp_password ) );
$hibp_suffix   = substr( $hibp_hash, 5 );

// A real match: the suffix is present with a non-zero count.
$GLOBALS['wpyeg_test_transients'] = array();
$GLOBALS['wpyeg_test_http']       = array(
	'response' => array( 'code' => 200 ),
	'body'     => "0000000000000000000000000000000000A:3\r\n{$hibp_suffix}:42\r\n",
);
wpyeg_test_assert( true === wpyeg_password_is_pwned( $hibp_password ), 'A hash suffix returned with a non-zero count is treated as breached.' );
wpyeg_test_assert( 128 * 1024 === $GLOBALS['wpyeg_test_last_http_args']['limit_response_size'], 'HIBP responses are capped at 128 KiB through the WordPress HTTP API.' );
wpyeg_test_assert( 'true' === $GLOBALS['wpyeg_test_last_http_args']['headers']['Add-Padding'], 'HIBP range requests ask for padded responses.' );

// Add-Padding rows come back with a count of 0 and must not count as a match.
$GLOBALS['wpyeg_test_transients'] = array();
$GLOBALS['wpyeg_test_http']       = array(
	'response' => array( 'code' => 200 ),
	'body'     => "{$hibp_suffix}:0\r\n",
);
wpyeg_test_assert( false === wpyeg_password_is_pwned( $hibp_password ), 'Padded rows (count 0) are ignored rather than read as a breach.' );

// An unreachable API must fail open, never lock out a password change.
$GLOBALS['wpyeg_test_transients'] = array();
$GLOBALS['wpyeg_test_http']       = new WP_Error( 'http_request_failed', 'offline' );
wpyeg_test_assert( false === wpyeg_password_is_pwned( $hibp_password ), 'A transport failure fails open.' );

$GLOBALS['wpyeg_test_transients'] = array();
$GLOBALS['wpyeg_test_http']       = array(
	'response' => array( 'code' => 503 ),
	'body'     => '',
);
wpyeg_test_assert( false === wpyeg_password_is_pwned( $hibp_password ), 'A non-200 response fails open.' );

// A body that reaches the transport limit may be truncated and is unavailable.
$GLOBALS['wpyeg_test_transients'] = array();
$GLOBALS['wpyeg_test_http']       = array(
	'response' => array( 'code' => 200 ),
	'body'     => str_repeat( 'A', 128 * 1024 ),
);
wpyeg_test_assert( false === wpyeg_password_is_pwned( $hibp_password ), 'A response reaching the 128 KiB transport cap fails open.' );
wpyeg_test_assert( array() === $GLOBALS['wpyeg_test_transients'], 'A capped response is never cached.' );

/*
 * The cap guard has to hold for *well-formed* data too. The check above is
 * satisfied by the format regex alone — 128 KiB of "A" has no colon — so it
 * cannot tell whether the length check works. This body is structurally valid
 * and reaches the cap on a row boundary, which is the case that only the length
 * check can catch: measured after trim() it slips under the cap, validates, and
 * would then be cached for 12 hours with its tail silently missing.
 */
$hibp_cap = 39 * 27; // 35 hex + ":" + 1 digit + CRLF, times a whole number of rows.
$GLOBALS['wpyeg_test_filter_values']['wpyeg_hibp_max_response_bytes'] = $hibp_cap;
$hibp_rows = array();
for ( $i = 0; $i < 26; $i++ ) {
	$hibp_rows[] = strtoupper( substr( sha1( 'row' . $i ), 5 ) ) . ':' . ( $i % 7 );
}
// The real suffix rides in this truncated body: if the cap guard misses, the
// call returns true instead of failing open, so the assertion below is real.
$hibp_rows[] = $hibp_suffix . ':4';

$hibp_capped_body                 = implode( "\r\n", $hibp_rows ) . "\r\n";
$GLOBALS['wpyeg_test_transients'] = array();
$GLOBALS['wpyeg_test_http']       = array(
	'response' => array( 'code' => 200 ),
	'body'     => $hibp_capped_body,
);
wpyeg_test_assert( strlen( $hibp_capped_body ) === $hibp_cap, 'The truncation fixture reaches the cap exactly.' );
wpyeg_test_assert( false === wpyeg_password_is_pwned( $hibp_password ), 'A well-formed response reaching the cap fails open rather than trusting a truncated range.' );
wpyeg_test_assert( array() === $GLOBALS['wpyeg_test_transients'], 'A well-formed capped response is never cached.' );
unset( $GLOBALS['wpyeg_test_filter_values']['wpyeg_hibp_max_response_bytes'] );

// A cached response that no longer validates is cleared, not reused for 12h.
$GLOBALS['wpyeg_test_transients'] = array( 'wpyeg_hibp_' . substr( $hibp_hash, 0, 5 ) => 'CORRUPTED CACHE ENTRY' );
$GLOBALS['wpyeg_test_http']       = new WP_Error( 'http_request_failed', 'network down' );
wpyeg_test_assert( false === wpyeg_password_is_pwned( $hibp_password ), 'An invalid cached range response fails open.' );
wpyeg_test_assert( array() === $GLOBALS['wpyeg_test_transients'], 'An invalid cached range response is deleted so the next call refetches.' );

// One invalid row invalidates the response, even if another row looks like a match.
$GLOBALS['wpyeg_test_transients'] = array();
$GLOBALS['wpyeg_test_http']       = array(
	'response' => array( 'code' => 200 ),
	'body'     => "MALFORMED\r\n{$hibp_suffix}:42\r\n",
);
wpyeg_test_assert( false === wpyeg_password_is_pwned( $hibp_password ), 'A malformed range response fails open instead of trusting partial data.' );
wpyeg_test_assert( array() === $GLOBALS['wpyeg_test_transients'], 'A malformed range response is never cached.' );

// The range response is cached per prefix, so a second call makes no request.
$GLOBALS['wpyeg_test_transients'] = array();
$GLOBALS['wpyeg_test_http']       = array(
	'response' => array( 'code' => 200 ),
	'body'     => "{$hibp_suffix}:9\r\n",
);
wpyeg_password_is_pwned( $hibp_password );
$GLOBALS['wpyeg_test_http'] = new WP_Error( 'http_request_failed', 'must not be called' );
wpyeg_test_assert( true === wpyeg_password_is_pwned( $hibp_password ), 'The cached range response is reused instead of refetching.' );

// The valid-response scan remains allocation-light and exits on the first match.
wpyeg_test_assert( false !== strpos( $plugin_source, "foreach ( preg_split( '/\\r\\n|\\n/', \$body ) as \$line )" ), 'HIBP suffixes are scanned directly rather than copied into another result array.' );
wpyeg_test_assert( false !== strpos( $plugin_source, "\t\t\tbreak;" ), 'The HIBP scan exits immediately after a matching suffix.' );

/*
 * Cross-artifact parity. The schema is the source of truth for the reference
 * table and the workshop schema map, so a setting cannot silently drift out of
 * learner-facing material.
 */
$repo_root = dirname( __DIR__ );
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local test fixtures.
$reference_doc = file_get_contents( $repo_root . '/docs/wordpress-default-settings.md' );
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local test fixtures.
$workshop_source = file_get_contents( $repo_root . '/workshop/Better-by-Default.ia.md' );
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local test fixtures.
$presenter_source = file_get_contents( $repo_root . '/workshop/better-by-default.iapresenter/text.md' );
// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local test fixtures.
$deck_source = file_get_contents( $repo_root . '/workshop/build_deck.js' );

$group_labels = array(
	'security'    => 'Security',
	'updates'     => 'Updates',
	'content'     => 'Content',
	'ux'          => 'UX',
	'login'       => 'Login',
	'branding'    => 'Branding',
	'performance' => 'Performance',
);

foreach ( $schema as $key => $field ) {
	$default = (string) $field['default'];
	$display = '' === $default ? "''" : $default;
	$doc_row = '/^\\| [^|]+ \\| `' . preg_quote( $key, '/' ) . '` \\| `' . preg_quote( $display, '/' ) . '` \\| ' . preg_quote( $group_labels[ $field['group'] ], '/' ) . ' \\|$/m';
	$map_row = '/^\\| `' . preg_quote( $key, '/' ) . '` \\| `' . preg_quote( $display, '/' ) . '` \\|/m';
	$js_row  = '["' . $key . '", "' . $display . '",';

	wpyeg_test_assert( 1 === preg_match( $doc_row, $reference_doc ), "Reference table matches schema key {$key}." );
	wpyeg_test_assert( 1 === preg_match( $map_row, $workshop_source ), "Workshop schema map matches schema key {$key}." );
	wpyeg_test_assert( false !== strpos( $deck_source, $js_row ), "Deck source matches schema key {$key}." );
}

wpyeg_test_assert( $workshop_source === $presenter_source, 'The iA Presenter source is identical to the canonical workshop source.' );
wpyeg_test_assert( false === strpos( $reference_doc . $workshop_source . $deck_source, 'remember_me_policy' ), 'Removed setting names do not survive in learner materials.' );
wpyeg_test_assert( false === strpos( $reference_doc . $workshop_source . $deck_source, 'PMP-specific' ), 'BBD settings are not mislabeled as PMP-specific.' );
wpyeg_test_assert( false === strpos( $deck_source, 'OPTION   ' ), 'Workshop chips identify schema keys rather than separate options.' );

/*
 * The setting count is prose, so the per-key row checks above never saw it. It
 * sat at 27 through two commits that took the schema to 31.
 */
$setting_count = count( $schema );

foreach ( array( 'workshop script' => $workshop_source, 'deck generator' => $deck_source ) as $count_label => $count_source ) {
	wpyeg_test_assert( false !== strpos( $count_source, "We have {$setting_count} settings" ), "The {$count_label} states the current setting count." );
	wpyeg_test_assert( false !== strpos( $count_source, "activates {$setting_count} sensible" ), "The {$count_label} opens with the current setting count." );
}

/*
 * The rendered deck. Everything above reads build_deck.js; nothing read what it
 * produces, which is how a corrected sentence sat in the generator for two days
 * while Better-by-Default.pptx still carried the wrong one. A .pptx is a zip of
 * XML, so pull the text runs out of the slides and the speaker notes and hold
 * the artifact to the same standard as its sources.
 *
 * This is also the only check that exercises the generator. A key added to
 * build_deck.js but never built into the deck fails here, and the only way to
 * fix it is to run the generator — which is the one thing that proves the
 * generator still runs at all. It did not, for one commit, and nothing noticed.
 */

/**
 * Extract slide text and speaker notes from a .pptx.
 *
 * @param string $pptx_path Path to the presentation.
 * @return string Every text run, one slide or notes page per line.
 */
function wpyeg_test_pptx_text( $pptx_path ) {
	$zip = new ZipArchive();

	if ( true !== $zip->open( $pptx_path ) ) {
		return '';
	}

	$text = '';

	for ( $i = 0; $i < $zip->numFiles; $i++ ) {
		if ( ! preg_match( '#^ppt/(slides|notesSlides)/[^/]+\.xml$#', $zip->getNameIndex( $i ) ) ) {
			continue;
		}

		preg_match_all( '#<a:t>(.*?)</a:t>#s', $zip->getFromIndex( $i ), $runs );
		$text .= implode( ' ', $runs[1] ) . "\n";
	}

	$zip->close();

	return $text;
}

$deck_artifact = $repo_root . '/workshop/Better-by-Default.pptx';

if ( ! class_exists( 'ZipArchive' ) ) {
	echo "Skipped the rendered-deck guard: this PHP build has no ZipArchive.\n";
} else {
	wpyeg_test_assert( is_readable( $deck_artifact ), 'The rendered deck is committed alongside its generator.' );

	$deck_text = wpyeg_test_pptx_text( $deck_artifact );
	wpyeg_test_assert( '' !== $deck_text, 'The rendered deck yields readable slide text.' );

	foreach ( $schema as $key => $field ) {
		wpyeg_test_assert( false !== strpos( $deck_text, $key ), "The rendered deck carries schema key {$key}." );
	}

	wpyeg_test_assert( false !== strpos( $deck_text, "We have {$setting_count} settings" ), 'The rendered deck states the current setting count.' );
	wpyeg_test_assert( false === strpos( $deck_text, 'amplifier that batches thousands of login guesses' ), 'The rendered deck does not repeat the obsolete multicall claim.' );
	wpyeg_test_assert( false === strpos( $deck_text, 'stopped testing credentials after the first failed login' ), 'The rendered deck carries the corrected multicall wording.' );
	wpyeg_test_assert( false === strpos( $deck_text, 'Gate the whole endpoint off' ), 'The rendered deck does not misdescribe xmlrpc_enabled.' );
}

foreach ( array( 'README.md', 'plugin/sane-defaults/README.md', 'plugin/sane-defaults/readme.txt' ) as $readme_path ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Local test fixtures.
	$readme = file_get_contents( $repo_root . '/' . $readme_path );
	wpyeg_test_assert( false !== stripos( $readme, 'AI connectors' ), "{$readme_path} documents the enabled AI-connector policy." );
	wpyeg_test_assert( false !== strpos( $readme, 'SAMEORIGIN' ), "{$readme_path} documents the enabled frame policy." );
	wpyeg_test_assert( false !== strpos( $readme, '14 days' ), "{$readme_path} documents the remembered-session length." );
}

unset( $GLOBALS['wpyeg_test_http'] );
$GLOBALS['wpyeg_test_transients'] = array();

/*
 * Login & sessions: unit consistency (days), per-field floors, and the
 * Remember Me >= regular guardrail. These three controls carried inherited
 * confusions — mixed units (days vs hours), a "0 = core default" sentinel that
 * read as a real value, and no floor, so a short Remember Me length could make
 * ticking the box *shorten* the login. All three are fixed here.
 */
$login_schema = wpyeg_defaults_schema();

// Both length fields are day-based numbers now; the legacy hours field is gone.
wpyeg_test_assert( isset( $login_schema['session_regular_days'] ), 'session_regular_days field exists.' );
wpyeg_test_assert( ! isset( $login_schema['session_regular_hours'] ), 'Legacy session_regular_hours field is gone.' );
wpyeg_test_assert( 'number' === $login_schema['session_regular_days']['type'], 'Regular session is a number field.' );
wpyeg_test_assert( 'number' === $login_schema['remember_me_days']['type'], 'Remember Me length is a number field.' );

// Defaults are prefilled with WordPress's real values — no "0 = core default" sentinel.
wpyeg_test_assert( 2 === $login_schema['session_regular_days']['default'], 'Regular default is WordPress\'s 2 days.' );
wpyeg_test_assert( 14 === $login_schema['remember_me_days']['default'], 'Remember Me default is WordPress\'s 14 days.' );
wpyeg_test_assert( 1 === (int) $login_schema['session_regular_days']['min'], 'Regular session has a 1-day floor.' );
wpyeg_test_assert( 1 === (int) $login_schema['remember_me_days']['min'], 'Remember Me length has a 1-day floor.' );

// A per-field minimum clamps a below-floor submission up to the floor.
$floored = wpyeg_defaults_sanitize(
	array(
		'session_regular_days' => '0',
		'remember_me_days'     => '0',
	)
);
wpyeg_test_assert( 1 === $floored['session_regular_days'], 'Regular session clamps 0 up to its 1-day floor.' );

// The guardrail: a remembered login can never be shorter than a regular one.
$guardrail = wpyeg_defaults_sanitize(
	array(
		'session_regular_days' => '10',
		'remember_me_days'     => '3',
	)
);
wpyeg_test_assert( 10 === $guardrail['remember_me_days'], 'Remember Me is clamped up to the regular session length (10).' );

// A coherent pair (remember >= regular) is left untouched.
$coherent = wpyeg_defaults_sanitize(
	array(
		'session_regular_days' => '2',
		'remember_me_days'     => '30',
	)
);
wpyeg_test_assert( 2 === $coherent['session_regular_days'] && 30 === $coherent['remember_me_days'], 'A valid remember >= regular pair passes through unchanged.' );

/**
 * Run the registered auth_cookie_expiration filter for a given login type.
 *
 * @param bool $remember Whether the login ticked "Remember Me".
 * @return int Cookie lifetime in seconds.
 */
function wpyeg_test_auth_cookie_expiration( $remember ) {
	$hook = wpyeg_test_find_hook( 'auth_cookie_expiration' );
	return (int) call_user_func( $hook['callback'], 7 * DAY_IN_SECONDS, 1, $remember );
}

// Out of the box the filter returns the day-based lengths: 2 days regular, 14
// remembered — the visible no-op defaults, not core's untouched value.
$GLOBALS['wpyeg_test_hooks']  = array();
$GLOBALS['wpyeg_test_option'] = array();
wpyeg_defaults_bootstrap();
wpyeg_test_assert( 2 * DAY_IN_SECONDS === wpyeg_test_auth_cookie_expiration( false ), 'A regular login lasts the regular length (2 days).' );
wpyeg_test_assert( 14 * DAY_IN_SECONDS === wpyeg_test_auth_cookie_expiration( true ), 'A remembered login lasts the remembered length (14 days).' );

// Disabling Remember Me routes every login — even a remembered one — through the
// regular length, and strips the forged flag server-side (not just via JS/CSS).
$GLOBALS['wpyeg_test_hooks']  = array();
$GLOBALS['wpyeg_test_option'] = array(
	'disable_remember_me'  => 'yes',
	'session_regular_days' => 3,
);
wpyeg_defaults_bootstrap();
wpyeg_test_assert( 3 * DAY_IN_SECONDS === wpyeg_test_auth_cookie_expiration( true ), 'Disabling Remember Me makes even a remembered login use the regular length.' );
wpyeg_test_assert( null !== wpyeg_test_find_hook( 'login_init' ), 'Disabling Remember Me strips the rememberme POST server-side, not only via the checkbox-hiding script.' );

unset( $GLOBALS['wpyeg_test_option'] );
$GLOBALS['wpyeg_test_hooks'] = array();

/*
 * Comment teardown: the parts that reach past the theme template.
 *
 * The REST case is the one that matters. Core's comments controller declares
 * `'type' => array( 'default' => 'comment' )`, so every GET /wp/v2/comments
 * arrives asking for that type — an implementation that exempts `comment`
 * queries leaves the main exposure open while looking careful.
 */

/**
 * Minimal WP_Comment_Query stand-in: only query_vars is consulted.
 *
 * @param array $vars Query vars.
 * @return object
 */
function wpyeg_test_comment_query( $vars ) {
	$query             = new stdClass();
	$query->query_vars = $vars;
	return $query;
}

wpyeg_test_assert( array( 'note' ) === wpyeg_defaults_allowed_comment_types(), 'Block Notes are exempt from the comment teardown by default.' );

wpyeg_test_assert(
	array() === wpyeg_defaults_empty_comment_queries( null, wpyeg_test_comment_query( array( 'type' => 'comment' ) ) ),
	'A type=comment query is answered empty — that is every GET /wp/v2/comments.'
);
wpyeg_test_assert(
	array() === wpyeg_defaults_empty_comment_queries( null, wpyeg_test_comment_query( array() ) ),
	'An untyped comment query is answered empty.'
);
wpyeg_test_assert(
	null === wpyeg_defaults_empty_comment_queries( null, wpyeg_test_comment_query( array( 'type' => 'note' ) ) ),
	'A note query runs normally.'
);
wpyeg_test_assert(
	null === wpyeg_defaults_empty_comment_queries( null, wpyeg_test_comment_query( array( 'type__in' => array( 'comment', 'note' ) ) ) ),
	'A query naming notes among other types still runs.'
);
wpyeg_test_assert(
	0 === wpyeg_defaults_empty_comment_queries( null, wpyeg_test_comment_query( array( 'count' => true ) ) ),
	'A count query is answered with 0, not an array.'
);
wpyeg_test_assert(
	array() === wpyeg_defaults_empty_comment_queries( null, null ),
	'A query object without query vars is answered empty rather than let through.'
);

wpyeg_test_assert( false === wpyeg_defaults_remove_comment_blocks( false ), 'A prior deny-all (false) is preserved, not expanded.' );
wpyeg_test_assert( array() === wpyeg_defaults_remove_comment_blocks( array() ), 'An empty allowlist is a deliberate deny-all and stays empty.' );

$wpyeg_test_blocks = wpyeg_defaults_remove_comment_blocks( array( 'core/paragraph', 'core/comments', 'core/latest-comments', 'core/image' ) );
wpyeg_test_assert( array( 'core/paragraph', 'core/image' ) === array_values( $wpyeg_test_blocks ), 'Comment blocks are removed from an explicit allowlist; everything else survives.' );

/*
 * Jetpack and the XML-RPC endpoint block.
 *
 * Jetpack talks to WordPress.com over XML-RPC, so the blanket 403 breaks the
 * connection. The plugin warns and then obeys: it never quietly refuses to do
 * what the toggle says, because a control that lies is worse than one that
 * warns. The default is already off, so nothing breaks unattended.
 */
$jetpack_schema = wpyeg_defaults_schema();
wpyeg_test_assert( 'no' === $jetpack_schema['block_xmlrpc_endpoint']['default'], 'The endpoint block ships off, so activating the plugin cannot break a Jetpack connection.' );
wpyeg_test_assert(
	false !== strpos( $jetpack_schema['block_xmlrpc_endpoint']['help'], 'unless connection and feature testing proves' ),
	'The help text states the rule: leave XML-RPC reachable while Jetpack is active until testing proves otherwise.'
);

wpyeg_test_assert( '' === wpyeg_defaults_jetpack_warning( 'block_xmlrpc_endpoint' ), 'With Jetpack absent, no warning is shown.' );
wpyeg_test_assert( '' === wpyeg_defaults_jetpack_warning( 'disable_rest' ), 'The warning belongs to the endpoint block, not to every setting.' );

define( 'JETPACK__VERSION', '14.0' );
wpyeg_test_assert( '' !== wpyeg_defaults_jetpack_warning( 'block_xmlrpc_endpoint' ), 'With Jetpack active, the endpoint block carries a warning.' );
wpyeg_test_assert( '' === wpyeg_defaults_jetpack_warning( 'disable_rest' ), 'Even with Jetpack active the warning stays on its own setting.' );

fwrite( STDOUT, "Better by Default policy tests passed.\n" );
