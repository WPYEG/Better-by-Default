<?php
/**
 * XML-RPC parent-server double; records calls delegated by the plugin.
 *
 * @package BetterByDefault
 */

// phpcs:disable PEAR.NamingConventions.ValidClassName -- Match core's class name.
/** Parent server test double. */
class wp_xmlrpc_server {
	/**
	 * Calls forwarded to core.
	 *
	 * @var array
	 */
	public static $calls = array();

	/**
	 * Record the unchanged batch and return a recognizable core response.
	 *
	 * @param array $methodcalls Batched method calls.
	 * @return array
	 */
	public function multiCall( $methodcalls ) { // phpcs:ignore WordPress.NamingConventions.ValidFunctionName.MethodNameInvalid -- Core API.
		self::$calls[] = $methodcalls;
		return array( array( 'core response' ) );
	}
}
// phpcs:enable PEAR.NamingConventions.ValidClassName
