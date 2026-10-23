<?php
/**
 * Jetpack multicall regression tests in isolated absent, legacy and modern modes.
 *
 * @package BetterByDefault
 */

$jetpack_mode = isset( $argv[1] ) ? $argv[1] : 'modern';

require __DIR__ . '/plugin-policy.php';
require __DIR__ . '/class-wp-xmlrpc-server.php';
require __DIR__ . '/class-ixr-error.php';

$GLOBALS['wpyeg_test_hooks']  = array();
$GLOBALS['wpyeg_test_option'] = array();
wpyeg_defaults_bootstrap();
$server_hook  = wpyeg_test_find_hook( 'wp_xmlrpc_server_class' );
$server_class = call_user_func( $server_hook['callback'], 'wp_xmlrpc_server' );
$server       = new $server_class();
$calls        = array(
	array(
		'methodName' => 'jetpack.jsonAPI',
		'params'     => array( 'example' ),
	),
);

// Neither a spoofed query argument nor Jetpack merely being installed is proof.
$_GET['for'] = 'jetpack';
$fault       = $server->multiCall( $calls );
wpyeg_test_assert( $fault instanceof IXR_Error && -32601 === $fault->code, 'Unsigned multicall returns XML-RPC -32601, not an HTTP status.' );
wpyeg_test_assert( array() === wp_xmlrpc_server::$calls, 'An unsigned batch never reaches core.' );
unset( $_GET['for'] );

if ( 'absent' === $jetpack_mode ) {
	// plugin-policy.php defines JETPACK__VERSION for its warning tests; no manager
	// is loaded here, so signature verification is still unavailable.
	wpyeg_test_assert( false === wpyeg_defaults_jetpack_request_verified(), 'Jetpack without a manager cannot verify requests.' );
	wpyeg_test_assert( 'recommended' === wpyeg_defaults_site_health_jetpack_xmlrpc()['status'], 'Unavailable verification is reported rather than treated as compatible.' );
} elseif ( 'legacy' === $jetpack_mode ) {
	$manager = new class() {
		/** Report a connected older Jetpack without a verifier. */
		public function is_connected() {
			return true;
		}
	};
	class_alias( get_class( $manager ), 'Automattic\\Jetpack\\Connection\\Manager' );
	wpyeg_test_assert( false === wpyeg_defaults_jetpack_request_verified(), 'An older Jetpack without a verifier fails closed.' );
	wpyeg_test_assert( $server->multiCall( $calls ) instanceof IXR_Error, 'Missing verification never delegates to core.' );
	$health = wpyeg_defaults_site_health_jetpack_xmlrpc();
	wpyeg_test_assert( 'recommended' === $health['status'], 'A connected old Jetpack reports unavailable verification.' );
} else {
	$manager       = new class() {
		/**
		 * Result returned by the verifier.
		 *
		 * @var mixed
		 */
		public static $verified = false;
		/**
		 * Whether Jetpack is connected.
		 *
		 * @var bool
		 */
		public static $connected = true;
		/**
		 * Simulate a verifier exception.
		 *
		 * @var bool
		 */
		public static $throws = false;
		/**
		 * Simulate a constructor exception.
		 *
		 * @var bool
		 */
		public static $constructor_throws = false;
		/**
		 * Construct a manager.
		 *
		 * @throws RuntimeException When construction fails.
		 */
		public function __construct() {
			if ( self::$constructor_throws ) {
				throw new RuntimeException( 'Manager unavailable' );
			}
		}
		/** Report the connection status. */
		public function is_connected() {
			return self::$connected;
		}
		/**
		 * Verify a request using the Jetpack API contract.
		 *
		 * @throws RuntimeException When verification fails.
		 */
		public function verify_xml_rpc_signature() {
			if ( self::$throws ) {
				throw new RuntimeException( 'Verification failed' );
			}
			return self::$verified;
		}
	};
	$manager_class = get_class( $manager );
	class_alias( $manager_class, 'Automattic\\Jetpack\\Connection\\Manager' );

	foreach ( array( false, null, array(), true, 'signed', new WP_Error( 'bad_signature', 'Invalid signature' ) ) as $unverified ) {
		$manager_class::$verified = $unverified;
		wpyeg_test_assert( $server->multiCall( $calls ) instanceof IXR_Error, 'Every non-verification result fails closed, including a truthy WP_Error.' );
	}
	wpyeg_test_assert( array() === wp_xmlrpc_server::$calls, 'Rejected verification never calls core.' );
	$manager_class::$verified = array(
		'type'    => 'blog',
		'user_id' => 0,
	);
	foreach ( array( false, true ) as $query_flag ) {
		if ( $query_flag ) {
			$_GET['for'] = 'jetpack';
		}
		wpyeg_test_assert( array( array( 'core response' ) ) === $server->multiCall( $calls ), 'Jetpack-verified requests delegate to core, with or without the query flag.' );
		unset( $_GET['for'] );
	}
	wpyeg_test_assert( array( $calls, $calls ) === wp_xmlrpc_server::$calls, 'Core receives exactly the original batches.' );
	$manager_class::$throws = true;
	wpyeg_test_assert( $server->multiCall( $calls ) instanceof IXR_Error, 'Verifier exceptions refuse the request without crashing.' );
	$manager_class::$throws             = false;
	$manager_class::$constructor_throws = true;
	wpyeg_test_assert( $server->multiCall( $calls ) instanceof IXR_Error, 'Manager-construction exceptions also fail closed.' );
	$manager_class::$constructor_throws = false;

	$health_tests = wpyeg_defaults_site_health_tests( array( 'direct' => array( 'existing' => array() ) ) );
	wpyeg_test_assert( isset( $health_tests['direct']['existing'], $health_tests['direct']['wpyeg_defaults_jetpack_xmlrpc'] ), 'A connected Jetpack gets a Site Health check without replacing other checks.' );
	wpyeg_test_assert( 'good' === wpyeg_defaults_site_health_jetpack_xmlrpc()['status'], 'Available signature verification passes the policy check.' );
	$manager_class::$connected = false;
	wpyeg_test_assert( ! isset( wpyeg_defaults_site_health_tests( array() )['direct']['wpyeg_defaults_jetpack_xmlrpc'] ), 'An unconnected Jetpack does not add a Site Health check.' );
	$manager_class::$connected = true;
}

$GLOBALS['wpyeg_test_option'] = array( 'xmlrpc_allow_multicall' => 'yes' );
wpyeg_test_assert( 'wp_xmlrpc_server' === call_user_func( $server_hook['callback'], 'wp_xmlrpc_server' ), 'Explicitly allowing multicall keeps the original server.' );
wpyeg_test_assert( 'good' === wpyeg_defaults_site_health_jetpack_xmlrpc()['status'], 'Allowed multicall passes the compatibility check.' );
$GLOBALS['wpyeg_test_option'] = array(
	'block_xmlrpc_endpoint'  => 'yes',
	'xmlrpc_allow_multicall' => 'yes',
);
wpyeg_test_assert( 'Wpyeg_Blocked_XMLRPC_Server' === call_user_func( $server_hook['callback'], 'wp_xmlrpc_server' ), 'The endpoint block wins over multicall and verified requests.' );
wpyeg_test_assert( 'critical' === wpyeg_defaults_site_health_jetpack_xmlrpc()['status'], 'A connected Jetpack with its endpoint blocked is critical.' );
fwrite( STDOUT, "XML-RPC multicall tests passed ({$jetpack_mode}).\n" );
