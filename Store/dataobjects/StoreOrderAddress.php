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
	// {{{ protected properties

	/**
	 * Id of the account address this order address was created from
	 *
	 * @var integer
	 */
	protected $account_address_id = null;

	// }}}
	// {{{ public function getAccountAddressId()

	public function getAccountAddressId()
	{
		return $this->account_address_id;
	}

	// }}}
	// {{{ public function copyFrom()

	public function copyFrom(StoreAddress $address)
	{
		parent::copyFrom($address);

		if ($address instanceof StoreAccountAddress)
			$this->account_address_id = $address->id;
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		parent::init();
		$this->table = 'OrderAddress';
	}

	// }}}
	// {{{ protected function getSerializablePrivateProperties()

	protected function getSerializablePrivateProperties()
	{
		$properties = parent::getSerializablePrivateProperties();
		$properties[] = 'account_address_id';

		return $properties;
	}

	// }}}
}

?>
