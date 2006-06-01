<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Store/dataobjects/StoreDataObject.php';

/**
 * A customer for an e-commerce web application
 *
 * StoreAccount objects contain data like name and email that correspond
 * directly to database fields. StoreAccount objects can have multiple
 * StoreAddress objects.
 *
 * There are three typical ways to use a StoreAccount object:
 *
 * - Create a new StoreAccount object with a blank constructor. Modify some
 *   properties of the customer object and call the StoreAccount::saveToDB()
 *   method. A new row is inserted into the database.
 *
 * <code>
 * $new_customer = new StoreAccount();
 * $new_cusotmer->email = 'customer@example.com';
 * $new_customer->fullname = 'Example Customer';
 * $new_customer->saveToDB();
 * </code>
 *
 * - Create a new StoreAccount object with a blank constructor. Call the
 *   StoreAccount::loadFromDB() method on the object instance passing in a
 *   database id. Modify some properties and call the saveToDB() method. The
 *   modified properties are updated in the database.
 *
 * <code>
 * $customer = new StoreAccount();
 * $customer->loadFromDB(123);
 * echo 'Hello ' . $customer->fullname;
 * $customer->email = 'new_address@example.com';
 * $customer->saveToDB();
 * </code>
 *
 * - Create a new StoreAccount object passing a record set into the
 *   constructor. The first row of the record set will be loaded as the data
 *   for the object instance. Modify some properties and call the
 *   StoreAccount::saveToDB() method. The modified properties are updated
 *   in the database.
 *
 * Example usage as an MDB wrapper:
 *
 * <code>
 * $sql = '-- select a customer here';
 * $customer = $db->query($sql, null, true, 'Account');
 * echo 'Hello ' . $customer->fullname;
 * $customer->email = 'new_address@example.com';
 * $customer->saveToDB();
 * </code>
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreAccountWrapper
 */
class StoreAccount extends StoreDataObject
{
	/**
	 * The database id of this customer
	 *
	 * If this property is null or 0 when StoreAccount::saveToDB() method is
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
	 * Loads an acount from the database with account credentials
	 *
	 * @param string $email the email address of the account.
	 * @param string $password the password of the account.
	 *
	 * @return boolean true if the loading was successful and false if it was
	 *                  not.
	 */
	public function loadFromDBWithCredentials($email, $password)
	{
		$this->checkDB();

		$sql = sprintf('select * from %s
			where email = %s and password = %s',
			$this->table,
			$this->db->quote($email_address->value, 'text'),
			$this->db->quote(md5($password->value), 'text'));

		$row = SwatDB::queryRow($this->app->db, $sql);
		if ($row === null)
			return false;

		$this->initFromRow($row);
		return true;
	}
}

?>
