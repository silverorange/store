<?php

require_once('SwatDB/SwatDB.php');
require_once('SwatDB/SwatDBDataObject.php');

/**
 * Do database loading in StoreDB::loadCustomer($customerId) method
 * Do database saving in  StoreDB::saveCustomer($customer) method
 *
 * contains data like name. Can have multiple StoreAddress objects.
 */
class StoreCustomer extends SwatDBDataObject
{

	/**
	 * An array of StoreAddress objects. The array is associative of the form
	 *     id => StoreAddress
	 *
	 * @arrray
	 * @access private
	 */
	private $addresses;
	
	public $id;
	public $fullname;
	public $email;
	public $phone;
	public $emailupdate;

	/**
	 * @Date
	 * @access public
	 */
	public $createdate;
	
	/**
	 * @Date
	 * @access public
	 */
	public $lastlogin;
	public $simple;


	private $db_field_blacklist = array('addresses');

	/**
	 * loads a StoreCustomer from the database into this object
	 */
	public function loadFromDB($id)
	{
		$fields = array_diff(array_keys($this->getProperties()), $this->db_field_blacklist);
		$values = SwatDB::queryRow($this->app->db, 'customers', $fields, 'customer_id', $id);
		$this->setValues($values);
		$this->generatePropertyHashes();

		// TODO: load complex properties here (like $addresses)
	}

	public function saveToDB()
	{
	}
}

?>
