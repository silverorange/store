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
 * Cart entries are automatically indexed by their id. When adding new cart
 * entries to this cart, the entries are assigned an id and indexed.
 *
 * Cart entries may be accessed through their entry id which guarentees a
 * unique cart entry. Alternatively, cart entries may be accessed through item
 * ids which do not necessarily reference a unique cart entry.
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
	 * This is an array of StoreCartEntry data objects.
	 *
	 * @var array
	 */
	protected $entries = array();

	/**
	 * The entries in this cart indexed by their id
	 *
	 * This is an array of StoreCartEntry data objects indexed by the entry
	 * ids.
	 *
	 * @var array
	 */
	protected $entries_by_id = array();

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
	 * combined. Validity of an entry is defined in the {@link validateEntry()}
	 * method. The entry is only added if it is valid. Equivalency is
	 * determined by the {@link StoreCartEntry::compare()} method.
	 *
	 * @param StoreCartEntry $entry the StoreCartEntry to add.
	 *
	 * @return StoreCartEntry the added entry. If the entry to be added is not
	 *                         valid, null is returned.
	 *
	 * @see StoreCart::validateEntry() StoreCart::validateCombinedEntry()
	 */
	public function addEntry(StoreCartEntry $entry)
	{
		$added_entry = null;

		$entry->setDatabase($this->app->db);

		if ($this->validateEntry($entry)) {
			$already_in_cart = false;

			// check for existing entry to combine with
			foreach ($this->entries as $key => $existing_entry) {
				if ($existing_entry->compare($entry) == 0) {
					$already_in_cart = true;
					$backup_entry = clone $existing_entry;
					$existing_entry->combine($entry);

					if ($this->validateCombinedEntry($existing_entry)) {
						$added_entry = $existing_entry;
						$this->setChanged();
					} else {
						// rollback to original entry
						$this->entries[$key] = $backup_entry;
						$this->entries_by_id[$backup_entry->id] =
							$backup_entry;
					}

					// we don't need this anymore
					unset($backup_entry);

					break;
				}
			}

			// not combining. add individual entry
			if (!$already_in_cart && $this->validateCombinedEntry($entry)) {
				$this->addNewEntry($entry);
				$added_entry = $entry;
			}
		}

		return $added_entry;
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

		if (array_key_exists($entry_id, $this->entries_by_id))
			$old_entry = $this->removeEntry($this->entries_by_id[$entry_id]);

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
	public function removeEntry(StoreCartEntry $entry)
	{
		$old_entry = null;

		if (in_array($entry, $this->entries)) {
			foreach ($this->entries as $key => $cart_entry) {
				if ($cart_entry === $entry) {
					$old_entry = $this->entries[$key];
					unset($this->entries[$key]);
					unset($this->entries_by_id[$entry->id]);
					$this->module->registerRemovedEntry($old_entry);
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

		foreach ($entries as $entry)
			$this->module->registerRemovedEntry($entry);

		return $entries;
	}

	// }}}
	// {{{ public function getEntryById()

	/**
	 * Gets an entry in this cart by its id
	 *
	 * @param integer $entry_id the database id of the entry in the cart to
	 *                          be returned.
	 *
	 * @return StoreCartEntry the entry with the given id or null if no such
	 *                         entry exists in this cart.
	 */
	public function getEntryById($entry_id)
	{
		$entry = null;

		if (array_key_exists($entry_id, $this->entries_by_id))
			$entry = $this->entries_by_id[$entry_id];

		return $entry;
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
	// {{{ public function getProductCount()

	/**
	 * Gets the number of unique products in this cart
	 *
	 * @return integer the number of unique products in this cart.
	 */
	public function getProductCount()
	{
		$count = 0;

		$product_id = null;
		foreach ($this->entries as $entry) {
			if ($entry->item->product->id !== $product_id)
				$count++;

			$product_id = $entry->item->product->id;
		}

		return $count;
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
		foreach ($this->entries as $key => $existing_entry) {
			if ($existing_entry === $entry) {
				if ($value <= 0) {
					$this->removeEntry($entry);
				} else {
					$backup_entry = clone $entry;
					$entry->setQuantity($value);
					if ($this->validateEntry($entry) &&
						$this->validateCombinedEntry($entry)) {
						echo 'foo';
						$this->setChanged();
						unset($backup_entry);
					} else {
						// rollback to original entry
						$this->entries[$key] = $backup_entry;
						$this->entries_by_id[$backup_entry->id] =
							$backup_entry;
					}
				}
			}
		}
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
	// {{{ protected function addMessage()

	/**
	 * Adds a status messages to this cart
	 *
	 * @param SwatMessage $message Status message.
	 */
	protected function addMessage(SwatMessage $message)
	{
		$this->messages[] = $message;
	}

	// }}}
	// {{{ protected function validateEntry()

	/**
	 * Checks to see if an entry is valid
	 *
	 * Use this method to verify that the item referenced by an entry exists
	 * and that the item is available for purchase. Also use this method to
	 * ensure an entry's quantity is valid.
	 *
	 * While validating an entry, this method should add validation error
	 * messages to this cart. See {@link StoreCart::addMessage()}.
	 *
	 * @param StoreCartEntry $entry the cart entry to validate.
	 *
	 * @return boolean true if the entry is valid and false if it is not valid.
	 */
	protected function validateEntry(StoreCartEntry $entry)
	{
		$valid = true;

		if ($entry->quantity <= 0) {
			$message = sprintf(
				'Quantity of “%s” item #%s must be at least one.',
				$entry->item->product->title,
				$entry->item->sku);

			$this->addMessage(new SwatMessage($message, SwatMessage::ERROR));
			$valid = false;
		}

		return $valid;
	}

	// }}}
	// {{{ protected function validateCombinedEntry()

	/**
	 * Checks to see if an entry is valid after combining the entry with
	 * existing entries in this cart
	 *
	 * Used to verify quantities meet requirments for purchase.
	 *
	 * While validating an entry, this method should add validation error
	 * messages to this cart. See {@link StoreCart::addMessage()}.
	 *
	 * @param StoreCartEntry $entry the cart entry to validate when combined
	 *                               with the other entries in this cart.
	 *
	 * @return boolean true if the entry is valid in combination with the
	 *                  entries in this cart and false if it is not valid.
	 */
	protected function validateCombinedEntry(StoreCartEntry $entry)
	{
		return true;
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
	// {{{ protected function addNewEntry()

	/**
	 * Adds a new entry to this cart
	 *
	 * This performs maintennance tasks required when adding an actual new
	 * entry object to this cart.
	 *
	 * @param StoreCartEntry $entry the entry to add.
	 */
	protected function addNewEntry(StoreCartEntry $entry)
	{
		$this->preSaveEntry($entry);
		$entry->save();

		$this->entries[] = $entry;
		$this->entries_by_id[$entry->id] = $entry;

		$this->module->registerAddedEntry($entry);
		$this->setChanged();
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
