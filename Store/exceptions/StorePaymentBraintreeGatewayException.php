<?php

/**
 * @package   Store
 * @copyright 2018 silverorange
 */
class StorePaymentBraintreeGatewayException extends StorePaymentException
{
	// {{{ protected properties

	protected $reason;

	// }}}
	// {{{ public function setReason()

	public function setReason($reason)
	{
		$this->reason = $reason;
	}

	// }}}
	// {{{ public function getReason()

	public function getReason()
	{
		return $this->reason;
	}

	// }}}
}

?>
