<?php
/**
 * Lightweight regression tests for Better by Default policies.
 *
 * @package BetterByDefault
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'DAY_IN_SECONDS', 86400 );
define( 'HOUR_IN_SECONDS', 3600 );

$GLOBALS['wpyeg_test_hooks'] = array();

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
 * @param string $name          Option name.
 * @param mixed  $default_value Default value.
 * @return mixed
 */
function get_option( $name, $default_value = false ) {
	unset( $name );
	return $default_value;
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
 * @param string $hook  Hook name.
 * @param mixed  $value Filtered value.
 * @return mixed
 */
function apply_filters( $hook, $value ) {
	unset( $hook );
	return $value;
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

wpyeg_defaults_bootstrap();
$registered_hooks = array_column( $GLOBALS['wpyeg_test_hooks'], 'hook' );
wpyeg_test_assert( in_array( 'xmlrpc_methods', $registered_hooks, true ), 'XML-RPC method removal is registered.' );
wpyeg_test_assert( in_array( 'rest_pre_insert_user', $registered_hooks, true ), 'REST password validation is registered.' );
wpyeg_test_assert( ! in_array( 'script_loader_tag', $registered_hooks, true ), 'Blanket script-tag mutation is not registered.' );

fwrite( STDOUT, "Better by Default policy tests passed.\n" );
