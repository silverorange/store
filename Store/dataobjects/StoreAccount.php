<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Store/dataobjects/StoreDataObject.php';
require_once 'Store/dataobjects/StoreAccountAddressWrapper.php';
require_once 'Store/dataobjects/StoreAccountPaymentMethodWrapper.php';
require_once 'Store/dataobjects/StoreOrderWrapper.php';

/**
 * A account for an e-commerce web application
 *
 * StoreAccount objects contain data like name and email that correspond
 * directly to database fields. StoreAccount objects can have multiple
 * StoreAccountAddress objects.
 *
 * There are three typical ways to use a StoreAccount object:
 *
 * - Create a new StoreAccount object with a blank constructor. Modify some
 *   properties of the account object and call the StoreAccount::save()
 *   method. A new row is inserted into the database.
 *
 * <code>
 * $new_account = new StoreAccount();
 * $new_cusotmer->email = 'account@example.com';
 * $new_account->fullname = 'Example Customer';
 * $new_account->save();
 * </code>
 *
 * - Create a new StoreAccount object with a blank constructor. Call the
 *   StoreAccount::load() method on the object instance passing in a
 *   database id. Modify some properties and call the save() method. The
 *   modified properties are updated in the database.
 *
 * <code>
 * $account = new StoreAccount();
 * $account->load(123);
 * echo 'Hello ' . $account->fullname;
 * $account->email = 'new_address@example.com';
 * $account->save();
 * </code>
 *
 * - Create a new StoreAccount object passing a record set into the
 *   constructor. The first row of the record set will be loaded as the data
 *   for the object instance. Modify some properties and call the
 *   StoreAccount::save() method. The modified properties are updated
 *   in the database.
 *
 * Example usage as an MDB wrapper:
 *
 * <code>
 * $sql = '-- select a account here';
 * $account = $db->query($sql, null, true, 'Account');
 * echo 'Hello ' . $account->fullname;
 * $account->email = 'new_address@example.com';
 * $account->save();
 * </code>
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreAccountWrapper
 */
class StoreAccount extends StoreDataObject
{
	// {{{ public properties

	/**
	 * The database id of this account 
	 *
	 * If this property is null or 0 when StoreAccount::save() method is
	 * called, a new account is inserted in the database.
	 *
	 * @var string
	 */
	public $id;

	/**
	 * The full name of this account
	 *
	 * @var string
	 */
	public $fullname;

	/**
	 * The email address of this account
	 *
	 * @var string
	 */
	public $email;

	/**
	 * The phone number of this account
	 *
	 * @var string
	 */
	public $phone;

	/**
	 * The md5() of this account's password
	 *
	 * @var string
	 */
	public $password;

	/**
	 * The date this account was created on
	 *
	 * @var Date
	 */
	public $createdate;

	/**
	 * Id of the default payment method
	 *
	 * @var integer
	 */
	public $default_payment_method;

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'Account';
		$this->id_field = 'integer:id';

		$this->registerDateProperty('createdate');
	}

	// }}}
	// {{{ public function loadWithCredentials()

	/**
	 * Loads an acount from the database with account credentials
	 *
	 * @param string $email the email address of the account.
	 * @param string $password the password of the account.
	 *
	 * @return boolean true if the loading was successful and false if it was
	 *                  not.
	 */
	public function loadWithCredentials($email, $password)
	{
		$this->checkDB();

		$sql = sprintf('select * from %s
			where email = %s and password = %s',
			$this->table,
			$this->db->quote($email, 'text'),
			$this->db->quote(md5($password), 'text'));

		$row = SwatDB::queryRow($this->db, $sql);
		if ($row === null)
			return false;

		$this->initFromRow($row);
		return true;
	}

	// }}}

	// loader methods
	// {{{ protected function loadAddresses()

	protected function loadAddresses()
	{
		$sql= 'select * from AccountAddress where account = %s order by id asc';
		$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));
		return SwatDB::query($this->db, $sql,
			$this->class_map->resolveClass('StoreAccountAddressWrapper'));
	}

	// }}}
	// {{{ protected function loadPaymentMethods()

	protected function loadPaymentMethods()
	{
		$sql= 'select * from AccountPaymentMethod where account = %s order by id asc';
		$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));
		return SwatDB::query($this->db, $sql,
			$this->class_map->resolveClass('StoreAccountPaymentMethodWrapper'));
	}

	// }}}
	// {{{ protected function loadOrders()

	protected function loadOrders()
	{
		$sql= 'select * from Orders where account = %s order by id asc';
		$sql = sprintf($sql, $this->db->quote($this->id, 'integer'));
		return SwatDB::query($this->db, $sql,
			$this->class_map->resolveClass('StoreOrderWrapper'));
	}

	// }}}

	// saver methods
	// {{{ protected function saveAddresses()

	protected function saveAddresses()
	{
		foreach ($this->addresses as $address)
			$address->account = $this;

		$this->addresses->setDatabase($this->db);
		$this->addresses->save();
	}

	// }}}
	// {{{ protected function savePayementMethods()

	protected function savePayementMethods()
	{
		foreach ($this->payment_methods as $payment_method)
			$payment_method->account = $this;

		$this->payment_methods->setDatabase($this->db);
		$this->payment_methods->save();
	}

	// }}}
	// {{{ protected function saveOrders()

	protected function saveOrders()
	{
		foreach ($this->orders as $order)
			$order->account = $this;

		$this->orders->setDatabase($this->db);
		$this->orders->save();
	}

	// }}}
}

?>
