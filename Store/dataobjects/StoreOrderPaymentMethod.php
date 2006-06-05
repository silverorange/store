<?php

require_once 'Store/dataobjects/StorePaymentMethod.php';

/**
 * @package   Store
 * @copyright 2006 silverorange
 */
class StoreOrderPaymentMethod extends StorePaymentMethod
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->table = 'OrderPaymentMethod';
	}

	// }}}
}

?>
