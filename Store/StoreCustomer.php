<?php

/**
 * Do database loading in StoreDB::loadCustomer($customerId) method
 * Do database saving in  StoreDB::saveCustomer($customer) method
 *
 * contains data like name. Can have multiple StoreAddress objects.
 */
class StoreCustomer {

	/**
	 * An array of StoreAddress objects. The array is associative of the form
	 *     id => StoreAddress
	 *
	 * @var arrray
	 * @access private
	 */
	private var $addresses;
	
	public var $id;
	public var $fullname;
	public var $email;
	public var $phone;
	public var $emailupdate;

	/**
	 * @var Date
	 * @access public
	 */
	public var $createdate;
	
	/**
	 * @var Date
	 * @access public
	 */
	public var $lastlogin;
	public var $simple;

	public function save() {
		StoreCustomerWrapper::saveToDB($this);
	}
}

class StoreCustomerWrapper {

	/**
	 * returns a StoreCustomer object loaded from the database
	 */
	public static function loadFromDB($id);

	
	public static function saveToDB($customer);
}

$customer = StoreCustomerWrapper::loadFromDB(1234);
$customer->name = 'smith';
$customer->save();
//StoreCustomerWrapper::saveToDB($customer);

?>
