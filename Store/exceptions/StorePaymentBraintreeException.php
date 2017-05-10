<?php

/**
 * @package   Store
 * @copyright 2015-2016 silverorange
 */
class StorePaymentBraintreeException extends StorePaymentException
{
	// {{{ protected properties

	/**
	 * @param Braintree\Base
	 */
	protected $response = null;

	// }}}
	// {{{ public function __construct()

	/**
	 * @param string $message
	 * @param integer $code
	 * @param Braintree\Base $response
	 */
	public function __construct($message, $code, Braintree\Base $response)
	{
		parent::__construct($message, $code);
		$this->response = $response;
	}

	// }}}
	// {{{ public function getResponse()

	/**
	 * @return Braintree\Base
	 */
	public function getResponse()
	{
		return $this->response;
	}

	// }}}
}

?>
