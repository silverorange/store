<?php

require_once 'Swat/SwatMessage.php';
require_once 'Swat/SwatObject.php';

require_once 'Site/SiteApplication.php';

require_once 'Store/StoreCartModule.php';
require_once 'Store/exceptions/StoreException.php';

/**
 * A cart object
 *
 * Carts are containers for cart entry objects. This class contains cart
 * functionality common to all sites. Most site code will want to extend either
 * {@link StoreCheckoutCart} or {@link StoreSavedCart}.
 *
 * There is intentionally no getEntryById() method because cart entries are
 * un-indexed. When an item is added to the cart, it does not necessarily have
 * a cart id to index by. Only after the cart is saved do all entries have
 * unique ids.
 *
 * @package   Store
 * @copyright 2005-2006 silverorange
 * @license   http://www.gnu.org/copyleft/lesser.html LGPL License 2.1
 * @see       StoreCheckoutCart, StoreSavedCart
 */
abstract class StoreCart extends SwatObject
{
	// {{{ protected properties

	/**
	 * The entries in this cart
	 *
	 * This is an array of StoreCartEntry data objects. The array is
	 * intentionally unindexed.
	 *
	 * @var array
	 */
	protected $entries = array();

	/**
	 * An array of cart entries that were removed from the cart
	 *
	 * After the cart is loaded and before it is saved, this array keeps track
	 * of entries there were removed from the cart. The array is unindexed.
	 *
	 * @var array
	 */
	protected $removed_entries = array();

	/**
	 * A cache of cart totals
	 *
	 * Cart totalling methods may optionally use this array to cache their
	 * values. When the setChanged() method is called, the cache is cleared.
	 *
	 * @var boolean
	 * @see StoreCartModule::setChanged()
	 */
	protected $totals = array();

	/**
	 * The cart module this cart belongs to
	 *
	 * @var StoreCartModule
	 */
	protected $module;

	/**
	 * The application this cart belongs to
	 *
	 * @var SiteApplication
	 */
	protected $app;

	// }}}
	// {{{ private properties

	/**
	 * An array of SwatMessages used to display cart entry status messages
	 *
	 * @var array
	 */
	private $messages = array();

	// }}}
	// {{{ public function __construct()

	/**
	 * Creates a new cart
	 *
	 * @param StoreCartModule $module the cart module this cart belongs to.
	 * @param SiteApplication $app the application this cart belongs to.
	 *
	 * @see StoreCartModule::__construct()
	 */
	public function __construct(StoreCartModule $module, SiteApplication $app)
	{
		$this->module = $module;
		$this->app = $app;
	}

	// }}}
	// {{{ public function init()

	public function init()
	{
	}

	// }}}
	// {{{ abstract public function load()

	/**
	 * Loads this cart
	 *
	 * Subclasses may load this cart from the database, from the session or
	 * from this cart's cart module.
	 *
	 * @see StoreCartModule::getEntries()
	 */
	abstract public function load();

	// }}}
	// {{{ public function save()

	/**
	 * Saves this cart to the database
	 */
	public function save()
	{
		foreach ($this->entries as $entry) {
			$this->preSaveEntry($entry);
			$entry->save();
		}

		$this->cleanUpRemovedEntries();
	}

	// }}}
	// {{{ public function isEmpty()

	/**
	 * Checks if this cart is empty
	 *
	 * @return boolean true if this cart is empty and false if it is not.
	 */
	public function isEmpty()
	{
		return count($this->entries) ? false : true;
	}

	// }}}
	// {{{ public function addEntry()

	/**
	 * Adds a StoreCartEntry to this cart
	 *
	 * If an equivalent entry already exists in the cart, the two entries are
	 * combined.
	 *
	 * @param StoreCartEntry $entry the StoreCartEntry to add.
	 */
	public function addEntry(StoreCartEntry $cart_entry)
	{
		$cart_entry->setDatabase($this->app->db);
		$already_in_cart = false;

		// check for item
		foreach ($this->entries as $entry) {
			if ($entry->compare($cart_entry) == 0) {
				$already_in_cart = true;
				$entry->combine($cart_entry);
				break;
			}
		}

		if (!$already_in_cart)
			$this->entries[] = $cart_entry;

		$this->setChanged();
	}

	// }}}
	// {{{ public function addEntryValidate()

	/**
	 * Adds a StoreCartEntry to this cart after validating the entry
	 *
	 * Validity of an entry is defined in the {@link validateEntry()} method.
	 * The entry is only added if it is valid.
	 *
	 * @param StoreCartEntry $entry the StoreCartEntry to add.
	 *
	 * @return boolean true if the entry is valid and was added and false if
	 *                       the entry is not valid and was not added.
	 *
	 * @see StoreCart::addEntry(), StoreCart::validateEntry()
	 */
	public function addEntryValidate(StoreCartEntry $entry)
	{
		$entry->setDatabase($this->app->db);

		if ($valid = $this->validateEntry($entry))
			$this->addEntry($entry);

		return $valid;
	}

	// }}}
	// {{{ public function removeEntryById()

	/**
	 * Removes a StoreCartEntry from this cart
	 *
	 * @param integer $entry_id the index value of the StoreCartEntry object
	 *                           to remove.
	 *
	 * @return StoreCartEntry the entry that was removed or null if no entry
	 *                         was removed.
	 */
	public function removeEntryById($entry_id)
	{
		$old_entry = null;

		foreach ($this->entries as $entry) {
			if ($entry->id == $entry_id) {
				$key = key($this->entries);
				$old_entry = $this->entries[$key];
				unset($this->entries[$key]);
				$this->removed_entries[] = $old_entry;
				$this->setChanged();
				break;
			}
		}

		return $old_entry;
	}

	// }}}
	// {{{ public function removeEntry()

	/**
	 * Removes a StoreCartEntry from this cart
	 *
	 * @param StoreCartEntry $entry the StoreCartEntry object to remove.
	 *
	 * @return StoreCartEntry the entry that was removed or null if no entry
	 *                         was removed.
	 */
	public function removeEntry($entry)
	{
		$old_entry = null;

		if (in_array($entry, $this->entries)) {
			foreach ($this->entries as $key => $cart_entry) {
				if ($cart_entry === $entry) {
					$old_entry = $this->entries[$key];
					unset($this->entries[$key]);
					$this->removed_entries[] = $old_entry;
					$this->setChanged();
					break;
				}
			}
		}

		return $old_entry;
	}

	// }}}
	// {{{ public function removeAllEntries()

	/**
	 * Removes all entries from this cart
	 *
	 * @return array the array of StoreCartEntry objects that were removed from
	 *                this cart.
	 */
	public function &removeAllEntries()
	{
		$entries = $this->entries;
		$this->entries = array();
		$this->setChanged();
		return $entries;
	}

	// }}}
	// {{{ public function getEntries()

	/**
	 * Gets a reference to the internal array of StoreCartEntry objects
	 *
	 * @return array an array of StoreCartEntry objects.
	 */
	public function &getEntries()
	{
		return $this->entries;
	}

	// }}}
	// {{{ public function getEntriesByItemId()

	/**
	 * Returns an array of entries in the cart based on the database item id
	 *
	 * An array is returned because database ids are not required to be unique
	 * across StoreCartItems in a single cart.
	 *
	 * @param integer $item_id the database id of the StoreItem in the cart to
	 *                          be returned.
	 *
	 * @return array an array of StoreCartEntry objects.
	 */
	public function &getEntriesByItemId($item_id)
	{
		$entries = array();
		foreach ($this->entries as $entry) {
			if ($entry->getItemId() == $item_id)
				$entries[] = $entry;
		}
		return $entries;
	}

	// }}}
	// {{{ public function getEntryCount()	

	/**
	 * Gets the number of StoreCartEntry objects in this cart
	 *
	 * @return integer the number of StoreCartEntry objects in this cart
	 */
	public function getEntryCount()
	{
		return count($this->entries);
	}

	// }}}
	// {{{ public function getItemCount()

	/**
	 * Returns the number of StoreItems in this cart
	 *
	 * The number is calculated based based on StoreCartEntry quantities.
	 *
	 * @return integer the number of StoreItems in this cart.
	 */
	public function getItemCount()
	{
		$total_quantity = 0;

		foreach ($this->entries as $entry)
			$total_quantity += $entry->getQuantity();

		return $total_quantity;
	}

	// }}}
	// {{{ public function setEntryQuantity()

	/**
	 * Updates the quantity of an entry in this cart
	 *
	 * @param StoreCartEntry $entry the entry to update.
	 * @param integer $value the new entry value.
	 */
	public function setEntryQuantity(StoreCartEntry $entry, $value)
	{
		if (in_array($entry, $this->entries)) {
			if ($value <= 0) {
				$this->removeEntry($entry);
			} else {
				$entry->setQuantity($value);
				$this->setChanged();
			}
		}
	}

	// }}}
	// {{{ public function addMessage()

	/**
	 * Adds a status messages to this cart
	 *
	 * @param SwatMessage $message Status message.
	 */
	public function addMessage(SwatMessage $message)
	{
		$this->messages[] = $message;
	}

	// }}}
	// {{{ public function getMessages()

	/**
	 * Gets the status messages of this cart
	 *
	 * @return array an array of SwatMessages.
	 */
	public function getMessages()
	{
		return $this->messages;
	}

	// }}}
	// {{{ public function hasMessages()

	/**
	 * Returns whether or not this cart has messages
	 *
	 * @return boolean whether or not this cart has messages.
	 */
	public function hasMessages()
	{
		return count($this->messages > 0);
	}

	// }}}
	// {{{ protected function validateEntry()

	/**
	 * Checks to see if the entry is valid
	 *
	 * Used to verify that the entry exists and is available for purchase.
	 *
	 * @param StoreCartEntry $cartEntry the StoreCartEntry to validate.
	 */
	protected function validateEntry(StoreCartEntry $cart_entry)
	{
		return true;
	}

	// }}}
	// {{{ protected function cleanUpRemovedEntries()

	/**
	 * Cleans up cart entries that were removed from this cart
	 */
	protected function cleanUpRemovedEntries()
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
	// {{{ protected function preSaveEntry()

	/**
	 * Performs pre-save processing on a single entry
	 *
	 * This is meant to be called on all entries before they are saved.
	 *
	 * @param StoreCartEntry $entry the entry to process.
	 */
	protected function preSaveEntry(StoreCartEntry $entry)
	{
	}

	// }}}

	// caching methods
	// {{{ protected function setChanged()

	/**
	 * Sets this cart as modified
	 *
	 * This clears the totals cache if it has entries.
	 */
	protected function setChanged()
	{
		$this->totals = array();
	}

	// }}}
	// {{{ protected function cachedValueExists()

	protected function cachedValueExists($name)
	{
		return isset($this->totals[$name]);
	}

	// }}}
	// {{{ protected function getCachedValue()

	protected function getCachedValue($name)
	{
		return $this->totals[$name];
	}

	// }}}
	// {{{ protected function setCachedValue()

	protected function setCachedValue($name, $value)
	{
		if (isset($this->totals[$name]))
			throw new StoreException('Overwriting cached cart value '.
				"'{$name}'.");

		$this->totals[$name] = $value;
	}

	// }}}
}

?>
