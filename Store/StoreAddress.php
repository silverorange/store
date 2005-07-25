<?php

/**
 * Belongs to a customer.
 */
class StoreAddress {
	public var $country;
	public var $provstate;
	public var $zipcode;
}

class StoreAddressView {

	private var $address;

	public function __construct($address);

	public function display();

	public function getAddress();
	public function setAddress($address);
}

/* 
 * use same pattern as for customer to load addresses and then use the load
 * methods in the customer methods
 */
 
class StoreShipMethod {

	/**
	 * An approximation of how long it takes to ship items with this method.
	 */
	public function getTimeToDeliver($address);
}

?>
