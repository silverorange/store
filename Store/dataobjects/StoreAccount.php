<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatString.php';
require_once 'SwatDB/SwatDBDataObject.php';
require_once 'Store/dataobjects/StoreAccountAddressWrapper.php';
require_once 'Store/dataobjects/StoreAccountPaymentMethodWrapper.php';
require_once 'Store/dataobjects/StoreInvoiceWrapper.php';
require_once 'Store/dataobjects/StoreOrderWrapper.php';
require_once 'Store/dataobjects/StoreAccountWrapper.php';

/**
 * A account for an e-commerce web application
 *
 * StoreAccount objects contain data like name and email that correspond
 * directly to database fields. StoreAccount objects may have one or more 
 * StoreAccountAddress objects, one or more StoreAccountPaymentMethod objects
 * and one or more StoreOrder objects all accessed as sub-data-objects.
 *
 * There are three typical ways to use a StoreAccount object:
 *
 * - Create a new StoreAccount object with a blank constructor. Modify some
 *   properties of the account object and call the StoreAccount::save()
 *   method. A new row is inserted into the database.
 *
 * <code>
 * $new_account = new StoreAccount();
 * $new_account->email = 'account@example.com';
 * $new_account->fullname = 'Example Customer';
 * $new_account->save();
 * </code>
 *
 * Using this technique, you may also add addresses and payment methods as sub-
 * data-objects and have them save automatically when you call
 * {@link StoreAccount::save()}.
 *
 * - Create a new StoreAccount object with a blank constructor. Call the
 *   {@link StoreAccount::load()} or {@link StoreAccount::loadWithCredentials}
 *   method on the object instance. Modify some properties and call the save()
 *   method. The modified properties are updated in the database.
 *
 * <code>
 * // using regular data-object load() method
 * $account = new StoreAccount();
 * $account->load(123);
 * echo 'Hello ' . $account->fullname;
 * $account->email = 'new_address@example.com';
 * $account->save();
 *
 * // using loadWithCredentials()
 * $account = new StoreAccount();
 * if ($account->loadWithCredentials('test@example.com', 'secretpassword')) {
 *     echo 'Hello ' . $account->fullname;
 *     $account->email = 'new_address@example.com';
 *     $account->save();
 * }
 * </code>
 *
 * - Create a new StoreAccount object passing a record set into the
 *   constructor. The first row of the record set will be loaded as the data
 *   for the object instance. Modify some properties and call the
 *   StoreAccount::save() method. The modified properties are updated
 *   in the database.
 *
 * Example usage as an MDB2 wrapper:
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
 * @copyright 2005-2007 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreAccountWrapper
 */
class StoreAccount extends SiteAccount
{
	// {{{ public properties

	/**
	 * The phone number of this account
	 *
	 * @var string
	 */
	public $phone;

	/**
	 * Id of the default payment method
	 *
	 * @var integer
	 */
	public $default_payment_method;

	// }}}

	// loader methods
	// {{{ protected function loadAddresses()

	/**
	 * Loads StoreAccountAddress sub-data-objects for this StoreAccount
	 */
	protected function loadAddresses()
	{
		$sql = sprintf('select * from AccountAddress
			where account = %s
			order by id asc',
			$this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('StoreAccountAddressWrapper'));
	}

	// }}}
	// {{{ protected function loadPaymentMethods()

	/**
	 * Loads StoreAccountPaymentMethod sub-data-objects for this StoreAccount
	 */
	protected function loadPaymentMethods()
	{
		$sql = sprintf('select * from AccountPaymentMethod
			where account = %s
			order by id asc',
			$this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('StoreAccountPaymentMethodWrapper'));
	}

	// }}}
	// {{{ protected function loadOrders()

	/**
	 * Loads StoreOrder sub-data-objects for this StoreAccount
	 *
	 * This represents a set of past orders made with this account.
	 */
	protected function loadOrders()
	{
		$sql = sprintf('select * from Orders
			where account = %s
			order by id asc',
			$this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('StoreOrderWrapper'));
	}

	// }}}
	// {{{ protected function loadInvoices()

	/**
	 * Loads StoreInvoice sub-data-objects for this StoreAccount
	 *
	 * This represents a set of invoices associated with this account.
	 */
	protected function loadInvoices()
	{
		$sql = sprintf('select * from Invoice
			where account = %s
			order by id asc',
			$this->db->quote($this->id, 'integer'));

		return SwatDB::query($this->db, $sql,
			SwatDBClassMap::get('StoreInvoiceWrapper'));
	}

	// }}}

	// saver methods
	// {{{ protected function saveAddresses()

	/**
	 * Automatically saves StoreAccontAddress sub-data-objects when this
	 * StoreAccount object is saved
	 */
	protected function saveAddresses()
	{
		foreach ($this->addresses as $address)
			$address->account = $this;

		$this->addresses->setDatabase($this->db);
		$this->addresses->save();
	}

	// }}}
	// {{{ protected function savePaymentMethods()

	/**
	 * Automatically saves StoreAccontPaymentMethod sub-data-objects when this
	 * StoreAccount object is saved
	 */
	protected function savePaymentMethods()
	{
		foreach ($this->payment_methods as $payment_method)
			$payment_method->account = $this;

		$this->payment_methods->setDatabase($this->db);
		$this->payment_methods->save();
	}

	// }}}
}

?>
