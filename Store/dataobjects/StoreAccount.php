<?php

require_once 'SwatDB/SwatDB.php';
require_once 'Swat/SwatString.php';
require_once 'Store/StoreResetPasswordMailMessage.php';
require_once 'Store/dataobjects/StoreDataObject.php';
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
 * $new_cusotmer->email = 'account@example.com';
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
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreAccountWrapper
 */
abstract class StoreAccount extends StoreDataObject
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
	 * By design, there is no way to get the actual password of this account
	 * through the StoreAccount object.
	 *
	 * @var string
	 */
	public $password;

	/**
	 * Hashed password tag for reseting the account password
	 *
	 * @var text
	 */
	public $password_tag;

	/**
	 * The date this account was created on
	 *
	 * @var Date
	 */
	public $createdate;

	/**
	 * The last date on which this account was logged into
	 *
	 * @var Date
	 */
	public $last_login;

	/**
	 * Id of the default payment method
	 *
	 * @var integer
	 */
	public $default_payment_method;

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

		$sql = sprintf('select id from %s
			where lower(email) = lower(%s) and password = %s',
			$this->table,
			$this->db->quote($email, 'text'),
			$this->db->quote(md5($password), 'text'));

		$id = SwatDB::queryOne($this->db, $sql);

		if ($id === null)
			return false;

		return $this->load($id);
	}

	// }}}
	// {{{ protected function init()

	protected function init()
	{
		$this->table = 'Account';
		$this->id_field = 'integer:id';

		$this->registerDateProperty('createdate');
	}

	// }}}
	// {{{ protected function getSerializableSubDataObjects()

	protected function getSerializableSubDataObjects()
	{
		return array('addresses', 'payment_methods');
	}

	// }}}

	// password methods
	// {{{ public function setPassword()

	/**
	 * Sets this account's password
	 */
	public function setPassword($password)
	{
		$this->password = md5($password);
	}

	// }}}
	// {{{ public function resetPassword()

	/**
	 * Resets this account's password
	 *
	 * Creates a unique tag and emails this account's holder a tagged URL to
	 * update his or her password.
	 *
	 * @param SiteApplication $app the application resetting this account's
	 *                              password.
	 *
	 * @return string $password_tag a hashed tag to verify the account
	 */
	public function resetPassword(SiteApplication $app)
	{
		$this->checkDB();

		$password_tag = SwatString::hash(uniqid(rand(), true));

		/*
		 * Update the database with new password tag.
		 *
		 * Don't use the regular dataobject saving here in case other fields
		 * have changed.
		 */
		$id_field = new SwatDBField($this->id_field, 'integer');
		$sql = sprintf('update %s set password_tag = %s where %s = %s',
			$this->table,
			$this->db->quote($password_tag, 'text'),
			$id_field->name,
			$this->db->quote($this->{$id_field->name}, $id_field->type));

		SwatDB::exec($this->db, $sql);

		return $password_tag;
	}

	// }}}
	// {{{ public static function generateNewPassword()

	/**
	 * Generates a new password for this account, saves it, and emails it to
	 * this account's holder
	 *
	 * @param SiteApplication $app the application generating the new password.
	 *
	 * @see StoreAccount::resetPassword()
	 */
	public function generateNewPassword(SiteApplication $app)
	{
		require_once 'Text/Password.php';

		$new_password = Text_Password::Create();

		/*
		 * Update the database with new password.
		 *
		 * Don't use the regular dataobject saving here in case other fields
		 * have changed.
		 */
		$id_field = new SwatDBField($this->id_field, 'integer');
		$sql = sprintf('update %s set password = %s where %s = %s',
			$this->table,
			$app->db->quote(md5($new_password), 'text'),
			$id_field->name,
			$this->db->quote($this->{$id_field->name}, $id_field->type));

		SwatDB::exec($app->db, $sql);

		// email the new password to the account holder 
		$this->sendNewPasswordMailMessage($app, $new_password);
	}

	// }}}
	// {{{ abstract public function sendResetPasswordMailMessage()

	/**
	 * Emails this account's holder with instructions on how to finish
	 * resetting his or her password
	 *
	 * @param SiteApplication $app the application sending mail.
	 * @param string $password_link a URL indicating the page at which the
	 *                               account holder may complete the reset-
	 *                               password process.
	 *
	 * @see StoreAccount::resetPassword()
	 */
	abstract public function sendResetPasswordMailMessage(
		SiteApplication $app, $password_link);

	// }}}
	// {{{ abstract protected function sendNewPasswordMailMessage()

	/**
	 * Emails this account's holder with his or her new generated password
	 *
	 * @param SiteApplication $app the application sending mail.
	 * @param string $new_password this account's new password.
	 *
	 * @see StoreAccount::generateNewPassword()
	 */
	abstract protected function sendNewPasswordMailMessage(
		SiteApplication $app, $new_password);

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
			$this->class_map->resolveClass('StoreAccountAddressWrapper'));
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
			$this->class_map->resolveClass('StoreAccountPaymentMethodWrapper'));
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
			$this->class_map->resolveClass('StoreOrderWrapper'));
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
			$this->class_map->resolveClass('StoreInvoiceWrapper'));
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
