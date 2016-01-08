<?php

require_once 'Store/exceptions/StorePaymentException.php';

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
