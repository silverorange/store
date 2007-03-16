<?php

require_once 'SwatDB/SwatDBTransaction.php';

require_once 'Site/SiteApplicationModule.php';

require_once 'Store/StoreSessionModule.php';
require_once 'Store/StoreClassMap.php';
require_once 'Store/StoreCart.php';
require_once 'Store/StoreSavedCart.php';
require_once 'Store/StoreCheckoutCart.php';
require_once 'Store/exceptions/StoreException.php';
require_once 'Store/dataobjects/StoreCartEntryWrapper.php';
require_once 'Store/dataobjects/StoreItemWrapper.php';
require_once 'Store/dataobjects/StoreProductWrapper.php';
require_once 'Store/dataobjects/StoreCategoryWrapper.php';

/**
 * Manages the carts used by a web-store application
 *
 * Most web stores will have at least two carts. This class handles loading
 * carts and moving objects between carts.
 *
 * Depends on the StoreSessionModule module and thus should be specified after
 * the StoreSessionModule in the application's
 * {@link SiteApplication::getDefaultModuleList()} method.
 *
 * @package   Store
 * @copyright 2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreCart
 */
class StoreCartModule extends SiteApplicationModule
{
	// {{{ protected properties

	/**
	 * A collection of carts managed by this module
	 *
	 * The array is of the form 'id' => StoreCart object
	 *
	 * @var array
	 */
	protected $carts = array();

	/**
	 * Entries used by the carts managed by this cart module
	 *
	 * This is initialized to an array iterator by default but may be set to
	 * a cart entry recordset wrapper in the loadEntries() method.
	 *
	 * @var StoreCartEntryWrapper|ArrayIteraror
	 *
	 * @see StoreCartModule::loadEntries()
	 */
	protected $entries = null;

	/**
	 * An array of cart entries that were removed from the carts managed by
	 * this module
	 *
	 * After the carts are loaded and before they are saved, this array keeps
	 * track of entries there were removed from carts. The array is unindexed.
	 *
	 * @var array
	 *
	 * @see StoreCartModule::registerRemovedEntry()
	 */
	protected $removed_entries = array();

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new cart module
	 *
	 * When the cart module is created, the default carts are loaded. See
	 * {@link StoreCartModule::getDefaultModuleList()}.
	 *
	 * @param SiteApplication $app the application this module belongs to.
	 *
	 * @throws StoreException if there is no session module loaded the cart
	 *                         module throws an exception.
	 *
	 * @see StoreCartModule::getDefaultCartList()
	 */
	public function __construct(SiteApplication $app)
	{
		if (!(isset($app->session) &&
			$app->session instanceof StoreSessionModule))
			throw new StoreException('The StoreCartModule requires a '.
				'StoreSessionModule to be loaded. Please either explicitly '.
				'add a session module to the application before instantiating '.
				'the cart module, or specify the session module before the '.
				'cart module in the application’s getDefaultModuleList() '.
				'method.');

		parent::__construct($app);

		$this->entries = new ArrayIterator(array());
	}

	// }}}
	// {{{ public function init()

	/**
	 * Initializes this cart module
	 *
	 * This initializes all the carts this module contains and registers a
	 * callback for when the application's session module logs in.
	 */
	public function init()
	{
		foreach ($this->carts as $cart)
			$cart->init();

		if ($this->getCheckoutCart() !== null &&
			$this->getSavedCart() !== null) {
			$this->app->session->registerLoginCallback(
				array($this, 'handleLogin'));

			$this->app->session->registerRegenerateIdCallback(
				array($this, 'handleRegenerateId'));
		}
	}

	// }}}
	// {{{ public function load()

	/**
	 * Loads this cart module
	 *
	 * By default, this populates the checkout and saved carts from the
	 * database. Subclasses may reimplement this method to provide their own
	 * behaviour.
	 */
	public function load()
	{
		$this->loadEntries();

		foreach ($this->carts as $cart)
			$cart->load();
	}

	// }}}
	// {{{ public function save()

	/**
	 * Saves this cart module
	 *
	 * @see StoreCartModule::load()
	 */
	public function save()
	{
		$transaction = new SwatDBTransaction($this->app->db);

		try {
			foreach ($this->carts as $cart)
				$cart->save();

			$this->deleteRemovedEntries();
		} catch (Exception $e) {
			$transaction->rollback();
			throw $e;
		}

		$transaction->commit();
	}

	// }}}
	// {{{ public function registerRemovedEntry()

	/**
	 * Notifies this cart module that an entry was removed from a cart
	 *
	 * The cart module is responsible for deleting removed entries.
	 *
	 * @param StoreCartEntry $entry the entry that was added.
	 *
	 * @see StoreCartModule::deleteRemovedEntries()
	 */
	public function registerRemovedEntry(StoreCartEntry $entry)
	{
		if (!in_array($entry, $this->removed_entries, true))
			$this->removed_entries[] = $entry;
	}

	// }}}
	// {{{ public function registerAddedEntry()

	/**
	 * Notifies this cart module that an entry was added to a cart
	 *
	 * @param StoreCartEntry $entry the entry that was added.
	 */
	public function registerAddedEntry(StoreCartEntry $entry)
	{
		if (in_array($entry, $this->removed_entries, true)) {
			foreach ($this->removed_entries as $key => $removed_entry) {
				if ($removed_entry === $entry) {
					unset($this->removed_entries[$key]);
					break;
				}
			}
		}
	}

	// }}}
	// {{{ public function addCart()

	/**
	 * Adds a cart to be managed by this cart module
	 *
	 * @param StoreCart $cart the cart to add to this cart module.
	 * @param string $id the identifier of the cart
	 *
	 * @throws StoreException if the identifier is already used for another
	 *                         cart or if the identifier collides with a
	 *                         property name an exception is thrown.
	 */
	public function addCart(StoreCart $cart, $id)
	{
		if (isset($this->carts[$id]))
			throw new StoreException("A cart with the id '{$id}' already ".
				'exists in this module.');

		$properties = get_object_vars($this);
		if (array_key_exists($id, $properties))
			throw new SiteException("Invalid cart identifier '{$id}'. ".
				'Cart identifiers must not be the same as any of the '.
				'property names of this cart module.');

		$this->carts[$id] = $cart;
	}

	// }}}
	// {{{ public function handleLogin()

	/**
	 * Manages moving around cart entries when a user logs into an account
	 *
	 * By default, if the uses has cart entries before logging in, any entries
	 * in the user's account cart are moved to the user's saved cart and
	 * entries from the user's session cart are moved to the logged-in account
	 * cart.
	 */
	public function handleLogin()
	{
		$checkout_cart = $this->getCheckoutCart();
		$saved_cart = $this->getSavedCart();

		if ($checkout_cart !== null && $saved_cart !== null &&
			$this->checkout->getEntryCount() > 0) {

			// reload to get account cart entries
			$this->load();

			// move account cart entries to saved cart
			$entries = $checkout_cart->removeAllEntries();
			foreach ($entries as $entry)
				$saved_cart->addEntry($entry);

			// move session cart entries to account cart
			$account_id = $this->app->session->getAccountId();
			foreach ($this->entries as $entry) {
				if ($entry->sessionid == $this->app->session->getSessionId()) {
					$entry->sessionid = null;
					$entry->account = $account_id;
					$checkout_cart->addEntry($entry);
				}
			}

		} else {
			$this->load();
		}
	}

	// }}}
	// {{{ public function handleRegenerateId()

	/**
	 * Updates the session ID in the database when it changes
	 */
	public function handleRegenerateId($old_id, $new_id)
	{
		$sql = 'update CartEntry set sessionid = %s where sessionid = %s';

		$sql = sprintf($sql,
				$this->app->db->quote($new_id, 'text'),
				$this->app->db->quote($old_id, 'text'));

		SwatDB::exec($this->app->db, $sql);
	}

	// }}}
	// {{{ public function getEntries()

	/**
	 * Gets all cart entries belonging to this cart module
	 *
	 * This is <em>not</em> guaranteed to be all the cart entries of all the
	 * carts managed by this cart module.
	 *
	 * If no entries were set in the loadEntries() method, this will return an
	 * empty array iterator object.
	 *
	 * @return StoreCartEntryWrapper|ArrayIterator the cart entries belonging
	 *                                              to this cart module.
	 */
	public function getEntries()
	{
		return $this->entries;
	}

	// }}}
	// {{{ public function getCartsByClass()

	/**
	 * Gets all carts registered with this module of a particular class
	 *
	 * @param string $class_name the name of the class.
	 *
	 * @return array an array of StoreCart objects of the given class that are
	 *                registered with this module. The array is indexed as
	 *                $name => $cart.
	 */
	public function getCartsByClass($class_name)
	{
		$carts = array();
		foreach ($this->carts as $name => $cart)
			if ($cart instanceof $class_name)
				$carts[$name] = $cart;

		return $carts;
	}

	// }}}
	// {{{ protected function loadEntries()

	/**
	 * Loads the entries used by this cart module
	 *
	 * Carts managed by this cart module may ask this module for its entries in
	 * their load() methods.
	 *
	 * @see StoreCart::load()
	 */
	protected function loadEntries()
	{
		// make sure default carts exist
		if ($this->getCheckoutCart() === null || $this->getSavedCart() === null)
			return;

		// make sure we're browsing a request with a region
		if ($this->app->getRegion() === null)
			return;

		if ($this->app->session->isLoggedIn()) {
			$account_id = $this->app->session->getAccountId();
			$where_clause = sprintf('account = %s or sessionid = %s',
				$this->app->db->quote($account_id, 'integer'),
				$this->app->db->quote($this->app->session->getSessionId(), 'text'));
		} elseif ($this->app->session->isActive()) {
			$where_clause = sprintf('sessionid = %s',
				$this->app->db->quote($this->app->session->getSessionId(), 'text'));
		} else {
			// not logged in, and no active session, so no cart entries
			return;
		}

		$class_mapper = StoreClassMap::instance();

		$entry_sql = $this->getEntrySql($where_clause);

		$this->entries = SwatDB::query($this->app->db, $entry_sql,
			$class_mapper->resolveClass('StoreCartEntryWrapper'));

		if (count($this->entries) == 0)
			return;

		// for implodeArray()
		$this->app->db->loadModule('Datatype', null, true);
		$item_ids = $this->entries->getInternalValues('item');
		$class = $class_mapper->resolveClass('StoreItemWrapper');

		$quoted_item_ids =
			$this->app->db->datatype->implodeArray($item_ids, 'integer');

		$items = call_user_func(array($class, 'loadSetFromDBWithRegion'),
			$this->app->db, $quoted_item_ids, $this->app->getRegion(),
			false);

		$product_sql = 'select id, shortname, title, primary_category, catalog
			from Product left outer join ProductPrimaryCategoryView
			on product = id where id in (%s)';

		$products = $items->loadAllSubDataObjects('product', $this->app->db,
			$product_sql, $class_mapper->resolveClass('StoreProductWrapper'));

		$category_sql = 'select id, getCategoryPath(id) as path
			from Category where id in (%s)';

		$categories = $products->loadAllSubDataObjects('primary_category',
			$this->app->db, $category_sql,
			$class_mapper->resolveClass('StoreCategoryWrapper'));

		$this->entries->attachSubDataObjects('item', $items);
	}

	// }}}
	// {{{ protected function getEntrySql()
	
	protected function getEntrySql($where_clause)
	{
		$entry_sql = 'select CartEntry.*
			from CartEntry
				inner join Item on CartEntry.item = Item.id
			where %s
			order by Item.product, Item.displayorder';

		return sprintf($entry_sql, $where_clause);
	}

	// }}}
	// {{{ protected function deleteRemovedEntries()

	/**
	 * Cleans up cart entries that were removed from this cart
	 */
	protected function deleteRemovedEntries()
	{
		if (count($this->removed_entries) > 0) {
			$ids = array();
			foreach ($this->removed_entries as $entry)
				$ids[] = $this->app->db->quote($entry->id, 'integer');

			$sql = sprintf('delete from CartEntry where id in (%s)',
				implode(',', $ids));

			SwatDB::query($this->app->db, $sql);
		}
	}

	// }}}
	// {{{ protected function getCheckoutCart()

	/**
	 * Convenience method to make a best guess at getting the checkout
	 * cart registered with this module
	 *
	 * This method is used internally.
	 *
	 * @return StoreCheckoutCart the checkout cart registered with this module
	 *                            or null if no appropriate cart object is
	 *                            found.
	 */
	protected function getCheckoutCart()
	{
		$cart = null;

		// first check conventional named cart
		if (isset($this->checkout)) {
			$cart = $this->checkout;
		} else {
			$carts = $this->getCartsByClass('StoreCheckoutCart');
			// we found one cart so it must be the checkout cart
			if (count($carts) == 1)
				$cart = reset($carts);
		}

		return $cart;
	}

	// }}}
	// {{{ protected function getSavedCart()

	/**
	 * Convenience method to make a best guess at getting the saved
	 * cart registered with this module
	 *
	 * This method is used internally.
	 *
	 * @return StoreSavedCart the saved cart registered with this module
	 *                         or null if no appropriate cart object is
	 *                         found.
	 */
	protected function getSavedCart()
	{
		$cart = null;

		// first check conventional named cart
		if (isset($this->saved)) {
			$cart = $this->saved;
		} else {
			$carts = $this->getCartsByClass('StoreSavedCart');
			// we found one cart so it must be the saved cart
			if (count($carts) == 1)
				$cart = reset($carts);
		}

		return $cart;
	}

	// }}}
	// {{{ private function __get()

	/**
	 * Gets a cart from this cart module
	 *
	 * @param string $name the name of the cart to get. If no such cart exists
	 *                      an exception is thrown.
	 *
	 * @throws StoreException
	 */
	private function __get($name)
	{
		if (isset($this->carts[$name]))
			return $this->carts[$name];

		throw new SiteException('Cart module does not have a property with '.
			"the name '{$name}', and no cart with the identifier '{$name}' ".
			'is loaded.');
	}

	// }}}
	// {{{ private function __isset()

	/**
	 * Checks if a property of this cart module is set
	 *
	 * This magic method allows carts managed by this cart module to act as
	 * read-only public properties of this module.
	 *
	 * @param string $name the name of the property to check for existance.
	 *
	 * @return boolean true if the property or cart exists in this object and
	 *                  false if it does not.
	 */
	private function __isset($name)
	{
		$isset = isset($this->$name);

		if (!$isset)
			$isset = isset($this->carts[$name]);

		return $isset;
	}

	// }}}
}

?>
