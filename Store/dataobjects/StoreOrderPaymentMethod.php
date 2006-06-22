<?php

require_once 'Store/dataobjects/StorePaymentMethod.php';

/**
 * A payment method for an order for an e-commerce web application 
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StorePaymentMethod
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
