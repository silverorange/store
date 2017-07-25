<?php

// Support loading Authorize.net through an autoloader via composer or through
// the PEAR include path. When PEAR support is dropped, this code can be
// dropped.
$file = stream_resolve_include_path('AuthorizeNet.php');
if ($file !== false) {
	require_once $file;
}

/**
 * @package   Store
 * @copyright 2011-2016 silverorange
 */
class StorePaymentAuthorizeNetException extends StorePaymentException
{
	// {{{ protected properties

	/**
	 * @param AuthorizeNetAIM_Response
	 */
	protected $response = null;

	/**
	 * @var integer
	 */
	protected $reason_code = 0;

	// }}}
	// {{{ public function __construct()

	/**
	 * @param string $message
	 * @param integer $code
	 * @param integer $reason_code
	 * @param AuthorizeNetAIM_Response $response
	 */
	public function __construct(
		$message,
		$code,
		$reason_code,
		AuthorizeNetAIM_Response $response
	) {
		parent::__construct($message, $code);
		$this->reason_code = $reason_code;
		$this->response    = $response;
	}

	// }}}
	// {{{ public function getReasonCode()

	/**
	 * @return integer
	 */
	public function getReasonCode()
	{
		return $this->reason_code;
	}

	// }}}
	// {{{ public function getResponse()

	/**
	 * @return AuthorizeNetAIM_Response
	 */
	public function getResponse()
	{
		return $this->response;
	}

	// }}}
}

?>
