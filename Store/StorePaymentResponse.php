<?php

/**
 * Base class for online financial transaction responses
 *
 * StorePaymentResponse objects are returned as the result of a
 * {@link StorePaymentRequest::process()} method call.
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePaymentRequest
 */
abstract class StorePaymentResponse
{
	// {{{ abstract public function __construct()

	/**
	 * Builds a payment response
	 *
	 * @param mixed $response_data the data to build the response object from.
	 */
	abstract public function __construct($response_data);

	// }}}
	// {{{ abstract public function getField()

	/**
	 * Gets a protocol-specific response field
	 *
	 * @param string $name the name of the field to get.
	 *
	 * @return mixed the value of the field.
	 */
	abstract public function getField($name);

	// }}}
	// {{{ abstract public function hasField()

	/**
	 * Gets whether or not protocol-specific response field exists
	 *
	 * @param string $name the name of the field to check.
	 *
	 * @return boolean true if the field <i>name</i> exists and false if the
	 *                       field does not exist.
	 */
	abstract public function hasField($name);

	// }}}
	// {{{ abstract protected function __toString()

	/**
	 * Gets a string representation of this payment response
	 *
	 * This is primarily useful for debugging and/or logging.
	 *
	 * @return string a string representation of this payment response.
	 */
	abstract protected function __toString();

	// }}}
}

?>
