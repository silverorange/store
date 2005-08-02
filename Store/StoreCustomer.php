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
 * - Create a new StoreCustomer object with a blank constructor. Modify some
 *   properties of the customer object and call the saveToDB() method. A new
 *   row is inserted into the database.
 *
 * <code>
 * $new_customer = new StoreCustomer();
 * $new_cusotmer->email = 'customer@example.com';
 * $new_customer->fullname = 'Example Customer';
 * $new_customer->saveToDB();
 * </code>
 *
 * - Create a new StoreCustomer object with a blank constructor. Call the
 *   loadFromDB() method on the object instance passing in a database id.
 *   Modify some properties and call the saveToDB() method. The modified 
 *   properties are updated in the database.
 *
 * <code>
 * $customer = new StoreCustomer();
 * $customer->loadFromDB(123);
 * echo 'Hello ' . $customer->fullname;
 * $customer->email = 'new_address@example.com;
 * $customer->saveToDB();
 * </code>
 *
 * - Create a new StoreCustomer object passing a record set into the
 *   constructor. The first row of the record set will be loaded as the data
 *   for the object instance. Modify some properties and call the saveToDB()
 *   method. The modified properties are updated in the database.
 *
 * Example usage as an MDB wrapper:
 *
 * <code>
 * $sql = '-- select a customer here';
 * $customer = $db->query($sql, null, true, 'StoreCustomer');
 * echo 'Hello ' . $customer->fullname;
 * $customer->email = 'new_address@example.com;
 * $customer->saveToDB();
 * </code>
 *
 * @package   Store
 * @copyright 2005 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreCustomerWrapper
 */
class StoreCustomer extends SwatDBDataObject
{
	/**
	 * The database id of this customer
	 *
	 * If this property is null or 0 when StoreCustoemr::saveToDB() method is
	 * called, a new customer is inserted in the database.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * The full name of this customer
	 *
	 * @var string
	 */
	public $fullname;

	/**
	 * The email address of this customer
	 *
	 * @var string
	 */
	public $email;

	/**
	 * The phone number of this customer
	 *
	 * @var string
	 */
	public $phone;

	/**
	 * Whether or not this customer should receive email updates
	 *
	 * @var boolean
	 */
	public $emailupdate;

	/**
	 * The date this customer was created on
	 *
	 * @var Date
	 */
	public $createdate;
	
	/**
	 * The time this customer last logged in
	 *
	 * @var Date
	 */
	public $lastlogin;

	/**
	 * Whether or not this is a simple account
	 *
	 * @var boolean
	 */
	public $simple;

	/**
	 * An array of StoreAddress objects. The array is associative of the form
	 *     id => StoreAddress
	 *
	 * @var arrray
	 */
	private $addresses;

	/**
	 * An array of property names of this object that are not database fields
	 *
	 * @var array
	 */
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

	/**
	 * Saves this customer object to the database
	 *
	 * Only modified properties are updated and if this customer does not have
	 * an id set or the id is 0 then it is inserted instead of updated.
	 *
	 * @return boolean true on successfully saving and false on failure
	 *                  to save.
	 */
	public function saveToDB()
	{
	}
}

?>
