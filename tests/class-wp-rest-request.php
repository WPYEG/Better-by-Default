<?php
/**
 * Minimal WP_REST_Request test double.
 *
 * Mirrors the parts the plugin relies on: the route, and reading parameters
 * through get_param() or array access. Core's offsetGet() delegates to
 * get_param(), and both answer null for a parameter that was not sent — the
 * REST password context depends on that distinction.
 *
 * @package BetterByDefault
 */

/** REST request test double. */
class WP_REST_Request implements ArrayAccess {
	/**
	 * Request parameters.
	 *
	 * @var array
	 */
	private $params;

	/**
	 * Request route.
	 *
	 * @var string
	 */
	private $route;

	/**
	 * Construct the request.
	 *
	 * @param array  $params Request parameters.
	 * @param string $route  Request route.
	 */
	public function __construct( $params = array(), $route = '/wp/v2/users' ) {
		$this->params = $params;
		$this->route  = $route;
	}

	/**
	 * Return the request route.
	 *
	 * @return string
	 */
	public function get_route() {
		return $this->route;
	}

	/**
	 * Return a parameter, or null when it was not sent.
	 *
	 * @param string $key Parameter name.
	 * @return mixed|null
	 */
	public function get_param( $key ) {
		return isset( $this->params[ $key ] ) ? $this->params[ $key ] : null;
	}

	/**
	 * Whether a parameter was sent.
	 *
	 * @param mixed $offset Parameter name.
	 * @return bool
	 */
	#[\ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		return isset( $this->params[ $offset ] );
	}

	/**
	 * Read a parameter.
	 *
	 * @param mixed $offset Parameter name.
	 * @return mixed|null
	 */
	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		return $this->get_param( $offset );
	}

	/**
	 * Set a parameter.
	 *
	 * @param mixed $offset Parameter name.
	 * @param mixed $value  Parameter value.
	 */
	#[\ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {
		$this->params[ $offset ] = $value;
	}

	/**
	 * Remove a parameter.
	 *
	 * @param mixed $offset Parameter name.
	 */
	#[\ReturnTypeWillChange]
	public function offsetUnset( $offset ) {
		unset( $this->params[ $offset ] );
	}
}
