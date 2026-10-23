<?php
/**
 * XML-RPC fault double.
 *
 * @package BetterByDefault
 */

/** XML-RPC fault test double. */
class IXR_Error {
	/**
	 * Fault code.
	 *
	 * @var int
	 */
	public $code;

	/**
	 * Fault message.
	 *
	 * @var string
	 */
	public $message;

	/**
	 * Construct a fault.
	 *
	 * @param int    $code    Fault code.
	 * @param string $message Fault message.
	 */
	public function __construct( $code, $message ) {
		$this->code    = $code;
		$this->message = $message;
	}
}
