<?php

require_once 'Store/StoreAdress.php';

/**
 * A viewer for an address object.
 *
 * @package   Store
 * @copyright 2005 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */

class StoreAddressView
{
	private $address = null;

	public function __construct(StoreAddress $address)
	{
		$this->setAddress($address);
	}

	public function display();

	public function getAddress()
	{
		return $this->address
	}

	public function setAddress(StoreAddress $address)
	{
		$this->address = $address;
	}
}

?>
