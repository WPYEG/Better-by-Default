<?php
/**
 * Minimal WP_Error test double.
 *
 * @package BetterByDefault
 */

/** Error test double. */
class WP_Error {
	/**
	 * Error code.
	 *
	 * @var string
	 */
	private $code;

	/**
	 * Error message.
	 *
	 * @var string
	 */
	private $message;

	/**
	 * Construct the error.
	 *
	 * @param string $code    Error code.
	 * @param string $message Error message.
	 */
	public function __construct( $code, $message ) {
		$this->code    = $code;
		$this->message = $message;
	}

	/** Return the error code. */
	public function get_error_code() {
		return $this->code;
	}

	/** Return the error message. */
	public function get_error_message() {
		return $this->message;
	}
}
