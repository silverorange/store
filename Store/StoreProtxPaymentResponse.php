<?php

require_once 'StorePaymentResponse.php';

/**
 * Response object returned by {@link StoreProtxPaymentRequest::process()}
 *
 * The transaction protocol used is VSP Direct. See the Protx VSP Direct
 * integration guidelines at:
 * {@link http://www.protx.com/downloads/docs/VSPDirectProtocolandIntegrationGuideline.pdf}.
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreProtxPaymentRequest
 */
class StoreProtxPaymentResponse extends StorePaymentResponse
{
	// {{{ private properties

	/**
	 * Protocol specific response data
	 *
	 * This is an array with index names representing protocol fields and
	 * values representing protocol values.
	 *
	 * @var array
	 */
	private $response = array();

	/**
	 * The raw response text
	 *
	 * @var string
	 */
	private $response_text;

	// }}}
	// {{{ public function __construct()

	/**
	 * Builds a Protx payment response
	 *
	 * @param string $response_text the text contents of the VSP Direct server
	 *                               response.
	 */
	public function __construct($response_text)
	{
		$this->parseResponse($response_text);
		$this->response_text = str_replace("\r\n", "\n", $response_text);
	}

	// }}}
	// {{{ public function getField()

	/**
	 * Gets a protocol-specific response field
	 *
	 * @param string $name the name of the field to get.
	 *
	 * @return mixed the value of the field.
	 */
	public function getField($name)
	{
		if (isset($this->response[$name]))
			return $this->response[$name];
	}

	// }}}
	// {{{ public function hasField()

	/**
	 * Gets whether or not protocol-specific response field exists
	 *
	 * @param string $name the name of the field to check.
	 *
	 * @return boolean true if the field <i>name</i> exists and false if the
	 *                       field does not exist.
	 */
	public function hasField($name)
	{
		return (isset($this->response[$name]));
	}

	// }}}
	// {{{ protected function __toString()

	/**
	 * Gets a string representation of this payment response
	 *
	 * This is primarily useful for debugging and/or logging.
	 *
	 * @return string a string representation of this payment response.
	 */
	protected function __toString()
	{
		return $this->response_text;
	}

	// }}}
	// {{{ private function parseResponse()

	/**
	 * Parses name-value pairs from raw response text
	 *
	 * The name-value pairs are stored in
	 * {@link StoreProtxPaymentResponse::$response}.
	 *
	 * Fields are parsed according the the Protx VSP Direct integration
	 * guidelines.
	 *
	 * @param string $response_text the raw response text.
	 */
	private function parseResponse($response_text)
	{
		$lines = explode("\r\n", $response_text);
		foreach ($lines as $line) {
			list($name, $value) = explode('=', $line, 2);
			$this->response[$name] = $value;
		}
	}

	// }}}
}

?>
