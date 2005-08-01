<?php

require_once 'SwatDB/SwatDB.php';
require_once 'SwatDB/SwatDBDataObject.php';

/**
 * A customer for an e-commerce web application
 *
 * StoreCustomer objects contain data like name and email that correspond
 * directly to database fields. StoreCustomer objects can have multiple
 * StoreAddress objects.
 *
 * There are three typical ways to use a StoreCustomer object:
 *
 * 1. Create a new StoreCustomer object with a blank constructor. Modify some
 *    properties of the customer object and call the saveToDB() method. A new
 *    row is inserted into the database.
 * 2. Create a new StoreCustomer object with a blank constructor. Call the
 *    loadFromDB() method on the object instance passing in a database id.
 *    Modify some properties and call the saveToDB() method. The modified 
 *    properties are updated in the database.
 * 3. Create a new StoreCustomer object passing a record set into the
 *    constructor. The first row of the record set will be loaded as the data
 *    for the object instance. Modify some properties and call the saveToDB()
 *    method. The modified properties are updated in the database.
 *
 *    Example usage as an MDB wrapper:
 *    <code>
 *    $customer = $db->query($sql, null, true, 'StoreCustomer');
 *    </code>
 *
 * @package   Store
 * @copyright 2005 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreCustomerWrapper
 */
class StoreCustomer extends SwatDBDataObject
{
	public $id;
	public $fullname;
	public $email;
	public $phone;
	public $emailupdate;

	/**
	 * @var Date
	 */
	public $createdate;
	
	/**
	 * @var Date
	 */
	public $lastlogin;
	public $simple;

	/**
	 * An array of StoreAddress objects. The array is associative of the form
	 *     id => StoreAddress
	 *
	 * @var arrray
	 */
	private $addresses;
	private $db_field_blacklist = array('addresses');

	/**
	 * Loads a customer from the database into this object
	 *
	 * @param integer $id the database id of the customer to load.
	 *
	 * @return boolean true if the customer was found in the database and false
	 *                  if the customer was not found in the database.
	 */
	public function loadFromDB($id)
	{
		$fields = array_diff(array_keys($this->getProperties()), $this->db_field_blacklist);
		$row = SwatDB::queryRow($this->app->db, 'customers', $fields, 'customer_id', $id);
		$this->initFromRow($row);
		$this->generatePropertyHashes();

		// TODO: load complex properties here (like $addresses)
	}

	public function saveToDB()
	{
	}
}

?>
