<?php

require_once 'Store/dataobjects/StoreAddress.php';

/**
 * An address belonging to an order for an e-commerce web application
 *
 * This could represent either a billing or a shipping address.
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreOrder::$billing_address, StoreOrder::$shipping_address
 */
class StoreOrderAddress extends StoreAddress
{
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->table = 'OrderAddress';
	}

	// }}}
}

?>
