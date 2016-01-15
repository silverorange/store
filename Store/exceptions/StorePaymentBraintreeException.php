<?php

require_once 'Store/exceptions/StorePaymentException.php';

// Support loading Braintree through an autoloader via composer or through the
// PEAR include path. When PEAR support is dropped, this code can be dropped.
$file = stream_resolve_include_path('Braintree.php');
if ($file !== false) {
	require_once $file;
}

/**
 * @package   Store
 * @copyright 2015 silverorange
 */
class StorePaymentBraintreeException extends StorePaymentException
{
	// {{{ protected properties

	/**
	 * @param Braintree_Base
	 */
	protected $response = null;

	// }}}
	// {{{ public function __construct()

	/**
	 * @param string $message
	 * @param integer $code
	 * @param Braintree_Base $response
	 */
	public function __construct($message, $code, Braintree_Base $response)
	{
		parent::__construct($message, $code);
		$this->response = $response;
	}

	// }}}
	// {{{ public function getResponse()

	/**
	 * @return Braintree_Base
	 */
	public function getResponse()
	{
		return $this->response;
	}

	// }}}
}

?>
