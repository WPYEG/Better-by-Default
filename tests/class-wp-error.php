<?php
/**
 * Minimal WP_Error test double.
 *
 * Mirrors the parts of the core API the plugin relies on: construction with or
 * without an initial error, accumulating errors via add(), and reading errors
 * back. The admin password validators receive a WP_Error by reference and call
 * add() on it, so the double has to accumulate rather than hold a single error.
 *
 * @package BetterByDefault
 */

/** Error test double. */
class WP_Error {
	/**
	 * Error messages keyed by error code.
	 *
	 * @var array
	 */
	private $errors = array();

	/**
	 * Error data keyed by error code.
	 *
	 * @var array
	 */
	private $error_data = array();

	/**
	 * Construct the error, optionally seeding the first error.
	 *
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 * @param mixed  $data    Error data, such as an HTTP status.
	 */
	public function __construct( $code = '', $message = '', $data = '' ) {
		if ( '' !== $code ) {
			$this->add( $code, $message, $data );
		}
	}

	/**
	 * Add an error.
	 *
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 * @param mixed  $data    Error data, such as an HTTP status.
	 */
	public function add( $code, $message = '', $data = '' ) {
		$this->errors[ $code ][] = $message;
		if ( '' !== $data ) {
			$this->error_data[ $code ] = $data;
		}
	}

	/**
	 * Return the data attached to a code, defaulting to the first error.
	 *
	 * @param string $code Error code.
	 * @return mixed|null
	 */
	public function get_error_data( $code = '' ) {
		if ( '' === $code ) {
			$code = $this->get_error_code();
		}
		return isset( $this->error_data[ $code ] ) ? $this->error_data[ $code ] : null;
	}

	/**
	 * Return every registered error code.
	 *
	 * @return array
	 */
	public function get_error_codes() {
		return array_keys( $this->errors );
	}

	/**
	 * Return the first error code, or an empty string when there are none.
	 *
	 * @return string
	 */
	public function get_error_code() {
		$codes = $this->get_error_codes();
		return empty( $codes ) ? '' : $codes[0];
	}

	/**
	 * Return the first message for a code, defaulting to the first error.
	 *
	 * @param string $code Error code.
	 * @return string
	 */
	public function get_error_message( $code = '' ) {
		if ( '' === $code ) {
			$code = $this->get_error_code();
		}
		return isset( $this->errors[ $code ][0] ) ? $this->errors[ $code ][0] : '';
	}

	/**
	 * Whether any error has been registered.
	 *
	 * @return bool
	 */
	public function has_errors() {
		return ! empty( $this->errors );
	}
}
