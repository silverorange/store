<?php

/**
 * An address for an e-commerce web application
 *
 * Addresses usually belongs to customers but can be used in other instances.
 *
 * @package   Store
 * @copyright 2005 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 */
class StoreAddress
{
	public $country;
	public $provstate;
	public $zipcode;
}

/*
 * Implementation note:
 *  use same pattern as for customer to load addresses and then use the load
 *  methods in the customer methods
 */

class StoreAddressView
{
	private var $address;

	public function __construct($address);

	public function display();

	public function getAddress();
	public function setAddress($address);
}

?>
